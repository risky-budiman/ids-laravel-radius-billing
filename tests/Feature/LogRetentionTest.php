<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\LogRetention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Masa simpan log: bawaan tidak menghapus apa pun; keuangan ≥ 5 tahun, keamanan ≥ 1 tahun. */
class LogRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $action, int $daysAgo, ?string $subject = null): int
    {
        return DB::table('activity_logs')->insertGetId(['action' => $action, 'description' => $action, 'subject_type' => $subject,
            'subject_id' => $subject ? 1 : null, 'created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)]);
    }

    public function test_default_keeps_everything(): void
    {
        $this->log('updated', 4000, 'App\\Models\\Customer');
        DB::table('whatsapp_logs')->insert(['target_phone' => '08', 'message' => 'x', 'status' => 'sent', 'created_at' => now()->subYears(3), 'updated_at' => now()]);
        $this->artisan('logs:prune')->expectsOutputToContain('Tidak ada log yang perlu dihapus')->assertSuccessful();
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'updated')->count());
        $this->assertSame(1, DB::table('whatsapp_logs')->count());
    }

    public function test_prune_respects_finance_and_security_minimums(): void
    {
        $ids = [
            'customer_100d' => $this->log('updated', 100, 'App\\Models\\Customer'),        // operasional > 90 hari → hapus
            'olt_50d' => $this->log('olt_updated', 50),                                    // < 90 hari → simpan
            'login_200d' => $this->log('login', 200),                                      // keamanan min 1 tahun → simpan
            'login_400d' => $this->log('login', 400),                                      // > 1 tahun → hapus
            'invoice_400d' => $this->log('updated', 400, 'App\\Models\\Invoice'),          // keuangan → simpan
            'payment_6y' => $this->log('payment_recorded', 6 * 366),                       // keuangan > 5 tahun → hapus
            'bank_4y' => $this->log('bank_statement_imported', 4 * 365),                   // keuangan < 5 tahun → simpan
            'setting_6y' => $this->log('updated', 6 * 366, 'App\\Models\\Setting'),        // pengaturan = keuangan → hapus (> 5 th)
        ];
        DB::table('whatsapp_logs')->insert([
            ['target_phone' => '08', 'message' => 'lama', 'status' => 'sent', 'created_at' => now()->subDays(40), 'updated_at' => now()],
            ['target_phone' => '08', 'message' => 'baru', 'status' => 'sent', 'created_at' => now()->subDays(5), 'updated_at' => now()],
        ]);
        DB::table('radpostauth')->insert([['username' => 'a', 'pass' => '', 'reply' => 'Access-Accept', 'authdate' => now()->subDays(100)],
            ['username' => 'b', 'pass' => '', 'reply' => 'Access-Accept', 'authdate' => now()->subDays(10)]]);
        $u = User::factory()->create();
        DB::table('notifications')->insert([
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'x', 'notifiable_type' => User::class, 'notifiable_id' => $u->id, 'data' => '{}', 'read_at' => now()->subDays(60), 'created_at' => now()->subDays(60), 'updated_at' => now()],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'x', 'notifiable_type' => User::class, 'notifiable_id' => $u->id, 'data' => '{}', 'read_at' => null, 'created_at' => now()->subDays(60), 'updated_at' => now()],
        ]);

        LogRetention::set(['log_retention_general_days' => 90, 'log_retention_finance_years' => 5, 'log_retention_whatsapp_days' => 30,
            'log_retention_radius_days' => 30, 'log_retention_notifications_days' => 30]);
        $before = DB::table('activity_logs')->count();
        $this->artisan('logs:prune --dry-run')->expectsOutputToContain('akan dihapus: 1');
        $this->assertSame($before, DB::table('activity_logs')->count());   // dry-run tidak menghapus

        $this->artisan('logs:prune')->assertSuccessful();
        $left = DB::table('activity_logs')->pluck('id')->all();
        foreach (['olt_50d', 'login_200d', 'invoice_400d', 'bank_4y'] as $k) {
            $this->assertContains($ids[$k], $left, "$k harus tersimpan");
        }
        foreach (['customer_100d', 'login_400d', 'payment_6y', 'setting_6y'] as $k) {
            $this->assertNotContains($ids[$k], $left, "$k harus terhapus");
        }
        $this->assertSame(['baru'], DB::table('whatsapp_logs')->pluck('message')->all());
        $this->assertSame(['b'], DB::table('radpostauth')->pluck('username')->all());
        $this->assertSame(1, DB::table('notifications')->whereNull('read_at')->count());
        $this->assertSame(0, DB::table('notifications')->whereNotNull('read_at')->count());
        $this->assertTrue(ActivityLog::where('action', 'logs_pruned')->exists());
    }

    public function test_settings_page_preview_save_and_access(): void
    {
        $this->log('updated', 200, 'App\\Models\\Customer');
        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $form = ['log_retention_general_days' => 90, 'log_retention_finance_years' => 0, 'log_retention_whatsapp_days' => 0,
            'log_retention_radius_days' => 0, 'log_retention_notifications_days' => 0];

        $this->actingAs($root)->get(route('activity-logs.index'))->assertOk()->assertSee('Masa simpan log')->assertSee('Simpan selamanya');
        $this->post(route('activity-logs.retention'), $form + ['action' => 'preview'])->assertSessionHas('success', fn ($m) => str_contains($m, 'Pratinjau: 1 baris'));
        $this->assertSame(0, LogRetention::get('log_retention_general_days'));   // pratinjau tidak menyimpan

        $this->post(route('activity-logs.retention'), $form + ['action' => 'save'])->assertSessionHas('success', fn ($m) => str_contains($m, '1 baris lama'));
        $this->assertSame(90, LogRetention::get('log_retention_general_days'));
        $this->assertTrue(ActivityLog::where('action', 'logs_retention_updated')->exists());

        // Nilai di luar pilihan ditolak (mis. keuangan 1 tahun)
        $this->post(route('activity-logs.retention'), ['log_retention_finance_years' => 1] + $form)->assertSessionHasErrors('log_retention_finance_years');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('activity-logs.retention'), $form)->assertForbidden();
    }
}
