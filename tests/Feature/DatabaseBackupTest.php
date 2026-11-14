<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseDumpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Backup database otomatis: mysqldump dipalsukan (menulis file SQL), sisanya nyata.
 */
class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private string $fakeBinary;

    protected function setUp(): void
    {
        parent::setUp();
        // Folder sementara: test tidak boleh menyentuh backup sungguhan di storage/
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dbbackup-test-' . uniqid();
        config(['backup.directory' => $this->dir]);
        // Program dump palsu (eksekusinya dipalsukan Process::fake) agar test jalan di mesin tanpa MySQL
        File::ensureDirectoryExists($this->dir);
        $this->fakeBinary = $this->dir . DIRECTORY_SEPARATOR . 'fake-mysqldump';
        file_put_contents($this->fakeBinary, '');
        config(['backup.mysqldump_path' => $this->fakeBinary]);

        // Koneksi default seolah-olah MySQL (data tes tetap di sqlite)
        config(['database.connections.mysql_fake' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'database' => 'billing', 'username' => 'u', 'password' => 'rahasia',
        ]]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function fakeMysqldump(bool $ok = true): void
    {
        Process::fake(function (PendingProcess $p) use ($ok) {
            if (! $ok) {
                return Process::result('', 'mysqldump: Got error: 1045 Access denied', 2);
            }
            $file = substr(collect($p->command)->first(fn ($a) => str_starts_with($a, '--result-file=')), 14);
            file_put_contents($file, "-- MySQL dump\nCREATE TABLE `customers` (id int);\nINSERT INTO `customers` VALUES (1);\n");

            return Process::result();
        });
    }

    private function runBackup(): int
    {
        config(['backup.connection' => 'mysql_fake']);

        return $this->artisan('app:backup-database')->run();
    }

    public function test_backup_writes_gzipped_sql_without_password_on_command_line(): void
    {
        $this->fakeMysqldump();

        $this->assertSame(0, $this->runBackup());

        $files = app(DatabaseDumpService::class)->list();
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/^db_billing_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/', $files[0]['file']);
        $this->assertStringContainsString('CREATE TABLE `customers`', gzdecode(file_get_contents($this->dir . '/' . $files[0]['file'])));
        Process::assertRan(fn (PendingProcess $p) => in_array('--single-transaction', $p->command, true)
            && ! collect($p->command)->contains(fn ($a) => str_contains($a, 'rahasia'))
            && ($p->environment['MYSQL_PWD'] ?? null) === 'rahasia');

        Cache::forget('app_settings');
        $this->assertSame('ok', get_setting('backup_last_status'));
    }

    public function test_failure_is_recorded_and_shown(): void
    {
        $this->fakeMysqldump(false);

        $this->assertSame(1, $this->runBackup());

        Cache::forget('app_settings');
        $this->assertSame('failed', get_setting('backup_last_status'));
        $this->assertStringContainsString('Access denied', get_setting('backup_last_message'));

        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]))
            ->get(route('backup.index'))->assertOk()->assertSee('Backup terakhir GAGAL');
    }

    public function test_copy_to_remote_disk_when_configured(): void
    {
        Storage::fake('offsite');
        config(['backup.remote_disk' => 'offsite', 'backup.remote_path' => 'isp/db']);
        $this->fakeMysqldump();

        $this->runBackup();

        $file = app(DatabaseDumpService::class)->list()[0]['file'];
        Storage::disk('offsite')->assertExists("isp/db/{$file}");
    }

    public function test_rotation_keeps_recent_daily_and_one_per_month(): void
    {
        config(['backup.keep_daily' => 3, 'backup.keep_monthly' => 2]);
        File::ensureDirectoryExists($this->dir);
        $make = function (string $date) {
            $path = $this->dir . "/db_billing_{$date}_020000.sql.gz";
            file_put_contents($path, 'x');
            touch($path, strtotime("$date 02:00:00"));
        };
        foreach (['2026-09-01', '2026-09-15', '2026-10-01', '2026-10-20', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13'] as $d) {
            $make($d);
        }

        app(DatabaseDumpService::class)->prune();

        $left = collect(app(DatabaseDumpService::class)->list())->pluck('file')->map(fn ($f) => substr($f, 11, 10))->sort()->values()->all();
        // 3 harian terbaru + file pertama Nov & Okt (2 bulan); September dibuang
        $this->assertSame(['2026-10-01', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13'], $left);
    }

    public function test_download_only_serves_listed_files(): void
    {
        $this->fakeMysqldump();
        $this->runBackup();
        $file = app(DatabaseDumpService::class)->list()[0]['file'];
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->get(route('backup.database.download', $file))->assertOk()->assertDownload($file);
        $this->actingAs($admin)->get('/admin/backup/database/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get(route('backup.database.download', 'db_lain.sql.gz'))->assertRedirect(route('backup.index'));
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->get(route('backup.database.download', $file))->assertForbidden();
    }

    public function test_configured_dump_path_must_exist_and_error_explains_the_fix(): void
    {
        config(['backup.mysqldump_path' => $this->dir . '/tidak-ada/mysqldump']);
        try {
            app(DatabaseDumpService::class)->resolveDumpBinary();
            $this->fail('harus gagal');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('BACKUP_MYSQLDUMP_PATH', $e->getMessage());
        }

        config(['backup.mysqldump_path' => $this->fakeBinary]);
        $this->assertSame($this->fakeBinary, app(DatabaseDumpService::class)->resolveDumpBinary());
    }

    public function test_backup_is_scheduled_daily(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($e) => str_contains($e->command ?? '', 'app:backup-database') && $e->expression === '30 2 * * *'));
    }
}
