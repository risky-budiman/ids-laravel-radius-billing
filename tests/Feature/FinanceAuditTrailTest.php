<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Gateway;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perubahan keuangan & konfigurasi penting harus bisa ditelusuri (siapa, kapan, dari berapa ke berapa),
 * tanpa menyimpan rahasia di log.
 */
class FinanceAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function logsFor(string $class, $id = null)
    {
        return ActivityLog::where('subject_type', $class)->when($id, fn ($q) => $q->where('subject_id', $id))->orderBy('id')->get();
    }

    public function test_edit_and_delete_of_bank_transaction_is_traceable_to_the_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true, 'name' => 'Bu Admin']);
        $this->actingAs($admin);
        $kas = BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $tx = new BankTransaction(['bank_account_id' => $kas->id, 'type' => 'deposit', 'amount' => 95659,
            'description' => 'Setoran tunai', 'transaction_date' => '2026-07-01 10:00', 'created_by' => $admin->id]);
        $tx->skipAutoJournal = true;
        $tx->save();

        $this->put(route('bank-transactions.update', $tx), [
            'description' => 'Setoran tunai (koreksi)', 'amount' => 90000, 'transaction_date' => '2026-07-01',
        ])->assertRedirect();
        $tx->fresh()->delete();

        $logs = $this->logsFor(BankTransaction::class, $tx->id);
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame($admin->id, $logs[1]->user_id);
        $this->assertEquals(95659, $logs[1]->properties['old']['amount']);
        $this->assertEquals(90000, $logs[1]->properties['new']['amount']);
        $this->assertStringContainsString('Kas Kantor Utama', $logs[2]->description);
        $this->assertEquals(90000, $logs[2]->properties['old']['amount'], 'data transaksi yang dihapus tetap tersimpan di log');
    }

    public function test_package_price_change_is_logged(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
        $p = Package::create(['name' => 'Paket-10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M']);
        $p->update(['price' => 175000]);

        $log = $this->logsFor(Package::class, $p->id)->last();
        $this->assertEquals(150000, $log->properties['old']['price']);
        $this->assertEquals(175000, $log->properties['new']['price']);
    }

    public function test_secrets_are_not_stored_in_the_log(): void
    {
        $g = Gateway::create(['type' => 'payment', 'provider' => 'midtrans', 'is_active' => true, 'credentials' => ['server_key' => 'SB-rahasia-lama']]);
        $g->update(['credentials' => ['server_key' => 'SB-rahasia-baru']]);
        Setting::create(['key' => 'wa_api_token', 'value' => 'tok-rahasia']);
        Setting::create(['key' => 'company_name', 'value' => 'PT Uji']);

        $all = json_encode(ActivityLog::all()->pluck('properties'));
        $this->assertStringNotContainsString('rahasia', $all);
        $this->assertStringContainsString('[disembunyikan]', json_encode($this->logsFor(Gateway::class, $g->id)->last()->properties));
        $this->assertCount(1, $this->logsFor(Setting::class)->where('description', 'Menambah pengaturan: company_name'));
    }

    public function test_routine_backup_status_settings_are_not_logged(): void
    {
        Setting::updateOrCreate(['key' => 'backup_last_status'], ['value' => 'ok']);
        Setting::updateOrCreate(['key' => 'backup_last_at'], ['value' => now()->toDateTimeString()]);

        $this->assertSame(0, $this->logsFor(Setting::class)->count());
    }
}
