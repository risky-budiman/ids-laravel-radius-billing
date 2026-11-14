<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Backup database harian otomatis (mysqldump -> .sql.gz).
 *
 * Berbeda dengan backup modul (.bak, terenkripsi APP_KEY): file ini SQL standar berisi SEMUA
 * tabel, dibuat bertahap (tidak memuat tabel ke memori), dan bisa dipulihkan dengan alat MySQL
 * biasa walau server & .env hilang:   gunzip < file.sql.gz | mysql nama_database
 *
 * Rotasi & lokasi: config/backup.php (BACKUP_* di .env).
 * Opsional salinan ke disk lain (config backup.remote_disk / BACKUP_REMOTE_DISK, mis. s3 / sftp).
 */
class DatabaseDumpService
{
    public const DIR = 'backups/db';

    public function directory(): string
    {
        return (string) (config('backup.directory') ?: storage_path('app/' . self::DIR));
    }

    /** @return array{file: string, path: string, size: int, remote: ?string} */
    public function run(): array
    {
        $conn = (string) (config('backup.connection') ?: config('database.default'));
        $cfg = config("database.connections.{$conn}");
        if (! in_array($cfg['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Backup otomatis hanya untuk MySQL/MariaDB (koneksi '{$conn}' memakai {$cfg['driver']}).");
        }

        File::ensureDirectoryExists($this->directory());
        $name = 'db_' . ($cfg['database'] ?? 'database') . '_' . now()->format('Y-m-d_His');
        $sqlPath = $this->directory() . DIRECTORY_SEPARATOR . $name . '.sql';
        $gzPath = $sqlPath . '.gz';

        $command = [
            (string) config('backup.mysqldump_path', 'mysqldump'),
            '--host=' . ($cfg['host'] ?? '127.0.0.1'),
            '--port=' . ($cfg['port'] ?? 3306),
            '--user=' . ($cfg['username'] ?? 'root'),
            '--single-transaction',      // snapshot konsisten tanpa mengunci tabel (InnoDB)
            '--quick',                   // baris dialirkan, bukan dimuat ke memori
            '--routines', '--triggers',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            '--result-file=' . $sqlPath,
            (string) $cfg['database'],
        ];

        // Password lewat variabel lingkungan, bukan argumen (tidak terlihat di daftar proses)
        $result = Process::timeout(3600)
            ->env(['MYSQL_PWD' => (string) ($cfg['password'] ?? '')])
            ->run($command);

        if (! $result->successful() || ! File::exists($sqlPath) || File::size($sqlPath) === 0) {
            File::delete($sqlPath);
            throw new RuntimeException('mysqldump gagal: ' . trim($result->errorOutput() ?: $result->output() ?: 'file kosong'));
        }

        $this->gzip($sqlPath, $gzPath);
        File::delete($sqlPath);

        $remote = $this->copyToRemote($gzPath);
        $this->prune();

        return ['file' => basename($gzPath), 'path' => $gzPath, 'size' => File::size($gzPath), 'remote' => $remote];
    }

    /** Kompres bertahap (blok 1 MB) agar dump besar tidak memenuhi memori. */
    private function gzip(string $from, string $to): void
    {
        $in = fopen($from, 'rb');
        $out = gzopen($to, 'wb6');
        if (! $in || ! $out) {
            throw new RuntimeException('Gagal membuka file untuk kompresi.');
        }
        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1024 * 1024));
        }
        fclose($in);
        gzclose($out);
    }

    private function copyToRemote(string $path): ?string
    {
        $disk = (string) config('backup.remote_disk', '');
        if ($disk === '') {
            return null;
        }

        $target = trim((string) config('backup.remote_path', 'radius-billing/db'), '/') . '/' . basename($path);
        $stream = fopen($path, 'rb');
        try {
            Storage::disk($disk)->writeStream($target, $stream);
        } finally {
            is_resource($stream) && fclose($stream);
        }

        return "{$disk}:{$target}";
    }

    /** Hapus file lama: simpan N harian terbaru + file pertama tiap bulan selama M bulan. */
    public function prune(): array
    {
        $keepDaily = max(1, (int) config('backup.keep_daily', 7));
        $keepMonthly = max(0, (int) config('backup.keep_monthly', 6));

        $files = collect($this->list())->sortByDesc('time')->values();
        $keep = $files->take($keepDaily)->pluck('file')->all();

        $files->groupBy(fn ($f) => date('Y-m', $f['time']))
            ->sortKeysDesc()->take($keepMonthly)
            ->each(function ($month) use (&$keep) {
                $keep[] = $month->sortBy('time')->first()['file'];   // file paling awal di bulan itu
            });

        $deleted = [];
        foreach ($files as $f) {
            if (! in_array($f['file'], $keep, true)) {
                File::delete($this->directory() . DIRECTORY_SEPARATOR . $f['file']);
                $deleted[] = $f['file'];
            }
        }

        return $deleted;
    }

    /** @return array<int, array{file: string, size: int, time: int}> */
    public function list(): array
    {
        if (! File::isDirectory($this->directory())) {
            return [];
        }

        return collect(File::files($this->directory()))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'))
            ->map(fn ($f) => ['file' => $f->getFilename(), 'size' => $f->getSize(), 'time' => $f->getMTime()])
            ->sortByDesc('time')->values()->all();
    }

    public function path(string $file): ?string
    {
        // Hanya nama file dari daftar (cegah path traversal)
        $match = collect($this->list())->firstWhere('file', $file);

        return $match ? $this->directory() . DIRECTORY_SEPARATOR . $match['file'] : null;
    }

    /** Catat hasil terakhir untuk ditampilkan di halaman Backup. */
    public function recordStatus(bool $ok, string $message): void
    {
        foreach (['backup_last_status' => $ok ? 'ok' : 'failed', 'backup_last_message' => $message, 'backup_last_at' => now()->toDateTimeString()] as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('app_settings');

        if (! $ok) {
            Log::critical('Backup database otomatis GAGAL: ' . $message);
        }
    }
}
