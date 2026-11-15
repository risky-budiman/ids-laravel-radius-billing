<?php

namespace App\Console\Commands;

use App\Support\ChangelogBuilder;
use Illuminate\Console\Command;

/**
 * Changelog per versi.
 *
 *  - Default (server, halaman Changelog, updater): sinkron tabel `changelogs` dari CHANGELOG.md.
 *    File tidak diubah, jadi `git pull` berikutnya tidak bentrok.
 *  - --write (hook post-commit di mesin developer): susun ulang CHANGELOG.md dari riwayat git —
 *    tiap versi hanya berisi commit miliknya sendiri, dikelompokkan per jenis — lalu sinkron DB.
 */
class GenerateChangelog extends Command
{
    protected $signature = 'app:generate-changelog
        {--write : Susun ulang CHANGELOG.md dari riwayat git (dipakai hook post-commit)}
        {--dry-run : Tampilkan hasil tanpa menulis apa pun}';

    protected $description = 'Susun CHANGELOG.md dari riwayat git (--write) dan sinkronkan ke database';

    public function handle(ChangelogBuilder $builder): int
    {
        $path = base_path('CHANGELOG.md');

        if ($this->option('write') || $this->option('dry-run')) {
            $releases = $builder->fromGit(base_path());
            if ($releases === null) {
                $this->warn('Riwayat git tidak tersedia; CHANGELOG.md tidak diubah.');
            } else {
                $markdown = $builder->toMarkdown($releases);
                if ($this->option('dry-run')) {
                    $this->line(implode("\n", array_slice(explode("\n", $markdown), 0, 60)));
                    $this->info(count($releases) . ' versi.');

                    return self::SUCCESS;
                }
                file_put_contents($path, $markdown);
                $this->info('CHANGELOG.md disusun ulang: ' . count($releases) . ' versi.');
            }
        }

        if (! is_file($path)) {
            $this->warn('CHANGELOG.md tidak ditemukan.');

            return self::SUCCESS;
        }

        $n = $builder->syncDatabase($builder->parseMarkdown(file_get_contents($path)));
        $this->info("Database changelog disinkronkan ({$n} versi).");

        return self::SUCCESS;
    }
}
