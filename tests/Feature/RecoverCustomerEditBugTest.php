<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** customers:recover-edit-bug — pemulihan dari Log Aktivitas, dry-run default, tindakan sah tidak ditimpa. */
class RecoverCustomerEditBugTest extends TestCase
{
    use RefreshDatabase;

    private Package $pkg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pkg = Package::create(['name' => 'P10', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
    }

    private function customer(string $code, array $state): Customer
    {
        $c = Customer::create(['customer_code' => $code, 'username' => "u{$code}@net", 'name' => "Pelanggan {$code}",
            'package_id' => $this->pkg->id, 'phone' => '08']);
        $c->forceFill($state)->saveQuietly();
        ActivityLog::query()->delete();

        return $c->fresh();
    }

    private function log(Customer $c, array $old, array $new, string $at): ActivityLog
    {
        $l = ActivityLog::create(['action' => 'updated', 'description' => 'Mengubah pelanggan', 'subject_type' => Customer::class,
            'subject_id' => $c->id, 'properties' => ['old' => $old, 'new' => $new], 'user_id' => null]);
        $l->forceFill(['created_at' => $at])->saveQuietly();

        return $l;
    }

    public function test_dry_run_then_apply_restores_status_and_coordinates(): void
    {
        // Menunggu aktivasi → terisolir + koordinat terhapus oleh form edit
        $a = $this->customer('001001000001', ['status' => 'suspended', 'is_active' => false, 'suspended_at' => '2026-05-04 13:01:46', 'latitude' => null, 'longitude' => null]);
        $this->log($a, ['status' => 'waiting_activation', 'suspended_at' => null, 'latitude' => '-6.21394059', 'longitude' => '106.84959412'],
            ['status' => 'suspended', 'suspended_at' => '2026-05-04 13:01:46', 'latitude' => null, 'longitude' => null], '2026-05-04 13:01:46');

        $this->artisan('customers:recover-edit-bug')->expectsOutputToContain('akan dipulihkan')->assertSuccessful();
        $this->assertSame('suspended', $a->fresh()->status, 'dry-run tidak mengubah apa pun');

        $this->artisan('customers:recover-edit-bug', ['--apply' => true])->expectsOutputToContain('DIPULIHKAN')->assertSuccessful();
        $a->refresh();
        $this->assertSame(['waiting_activation', null], [$a->status, $a->suspended_at]);
        $this->assertEquals(-6.21394059, (float) $a->latitude);
        $this->assertTrue(ActivityLog::where('action', 'customer_recovered')->where('subject_id', $a->id)->exists());

        // Dijalankan ulang: tidak ada lagi yang dipulihkan
        $this->artisan('customers:recover-edit-bug', ['--apply' => true])->expectsOutputToContain('Status: 0 · Koordinat: 0')->assertSuccessful();
    }

    public function test_dismantled_customer_loses_recreated_radius_login_and_invoices_are_reported(): void
    {
        $d = $this->customer('001001000002', ['status' => 'active', 'is_active' => true, 'terminated_at' => null]);
        $this->log($d, ['status' => 'dismantled', 'is_active' => false, 'terminated_at' => '2026-03-01 10:00:00'],
            ['status' => 'active', 'is_active' => true, 'terminated_at' => null], '2026-04-10 09:00:00');
        RadCheck::create(['username' => $d->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'x']);
        $inv = Invoice::create(['customer_id' => $d->id, 'invoice_number' => 'INV-REC-1', 'amount' => 150000, 'total_amount' => 150000,
            'status' => 'unpaid', 'due_date' => '2026-05-20', 'issue_date' => '2026-05-01']);
        $inv->forceFill(['created_at' => '2026-05-01 00:00:00'])->saveQuietly();

        $this->artisan('customers:recover-edit-bug', ['--apply' => true])->expectsOutputToContain('INV-REC-1')->assertSuccessful();
        $d->refresh();
        $this->assertSame(['dismantled', false], [$d->status, (bool) $d->is_active]);
        $this->assertSame('2026-03-01 10:00:00', $d->terminated_at?->format('Y-m-d H:i:s'), 'tanggal berhenti asli dipertahankan');
        $this->assertFalse(RadCheck::where('username', $d->username)->exists());
        $this->assertSame('unpaid', $inv->fresh()->status, 'invoice hanya dilaporkan');
    }

    public function test_legitimate_later_changes_and_official_activation_are_left_alone(): void
    {
        // Setelah bug, pelanggan diaktifkan resmi (status & koordinat diisi lagi) → dilewati
        $b = $this->customer('001001000003', ['status' => 'active', 'is_active' => true, 'latitude' => '3.6888', 'longitude' => '98.2617']);
        $c = $this->customer('001001000004', ['status' => 'active', 'is_active' => true]);   // dibuat dulu: helper mengosongkan log
        $this->log($b, ['status' => 'waiting_activation', 'latitude' => '-6.2', 'longitude' => '106.8'], ['status' => 'suspended', 'latitude' => null, 'longitude' => null], '2026-05-04 13:01:00');
        $this->log($b, ['status' => 'suspended', 'latitude' => null], ['status' => 'active', 'latitude' => '3.6888', 'activated_at' => '2026-05-04 00:00:00'], '2026-05-04 13:02:00');

        // Aktivasi resmi (activated_at terisi) bukan bug
        $this->log($c, ['status' => 'waiting_activation', 'activated_at' => null], ['status' => 'active', 'activated_at' => '2026-05-05 00:00:00'], '2026-05-05 10:00:00');

        $this->artisan('customers:recover-edit-bug', ['--apply' => true])
            ->expectsOutputToContain('LEWATI status')->expectsOutputToContain('Status: 0 · Koordinat: 0')->assertSuccessful();
        $this->assertSame('active', $b->fresh()->status);
        $this->assertSame('active', $c->fresh()->status);
    }

    public function test_before_and_customer_filters(): void
    {
        $a = $this->customer('001001000005', ['latitude' => null, 'longitude' => null]);
        $this->log($a, ['latitude' => '-6.1', 'longitude' => '106.1'], ['latitude' => null, 'longitude' => null], '2026-11-20 10:00:00');

        $this->artisan('customers:recover-edit-bug', ['--apply' => true, '--before' => '2026-11-15 14:40'])->assertSuccessful();
        $this->assertNull($a->fresh()->latitude, 'kejadian setelah --before diabaikan');

        $this->artisan('customers:recover-edit-bug', ['--customer' => '999'])->assertFailed();
        $this->artisan('customers:recover-edit-bug', ['--apply' => true, '--customer' => '001001000005'])->assertSuccessful();
        $this->assertEquals(-6.1, (float) $a->fresh()->latitude);
    }
}
