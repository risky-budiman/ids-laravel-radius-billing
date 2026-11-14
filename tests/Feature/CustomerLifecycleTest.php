<?php

namespace Tests\Feature;

use App\Jobs\DeprovisionOnuJob;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Web dan API kini memakai jalur yang sama (CustomerLifecycleService / InvoiceService).
 */
class CustomerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();   // radclient & reload freeradius tidak benar-benar dijalankan
        Queue::fake();
        Nas::forceCreate(['nasname' => '10.0.0.1', 'shortname' => 'nas-a', 'secret' => 'sec-a']);
        Nas::forceCreate(['nasname' => '10.0.0.2', 'shortname' => 'nas-b', 'secret' => 'sec-b']);
    }

    private function customer(string $code, array $attrs = []): Customer
    {
        $c = Customer::create(array_merge([
            'customer_code' => $code, 'username' => strtolower($code), 'name' => 'Pelanggan ' . $code,
            'is_active' => true, 'status' => Customer::STATUS_ACTIVE,
        ], $attrs));
        $c->refresh();
        RadCheck::forceCreate(['username' => $c->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'rahasia']);
        RadReply::forceCreate(['username' => $c->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => '=', 'value' => '10M/10M']);
        RadUserGroup::forceCreate(['username' => $c->username, 'groupname' => 'paket-10m', 'priority' => 1]);

        return $c;
    }

    private function onlineSession(Customer $c, string $nasIp, string $sessionId): void
    {
        RadAcct::forceCreate([
            'acctsessionid' => $sessionId, 'acctuniqueid' => md5($sessionId), 'username' => $c->username,
            'nasipaddress' => $nasIp, 'acctstarttime' => now()->subHour(), 'acctstoptime' => null,
        ]);
    }

    private function assertRadiusGone(Customer $c): void
    {
        $this->assertSame(0, RadCheck::where('username', $c->username)->count(), 'radcheck tersisa: pelanggan masih bisa login PPPoE');
        $this->assertSame(0, RadReply::where('username', $c->username)->count());
        $this->assertSame(0, RadUserGroup::where('username', $c->username)->count());
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_web_delete_cleans_radius_disconnects_on_the_sessions_router_and_deprovisions_onu(): void
    {
        $olt = \App\Models\Olt::forceCreate(['name' => 'OLT Uji', 'ip_address' => '10.10.10.1']);
        $c = $this->customer('CUST-DEL-1', ['olt_id' => $olt->id, 'onu_index' => '1/1/1:5', 'onu_sn' => 'ZTEG00000001']);
        $this->onlineSession($c, '10.0.0.2', 'sess-b-1');   // sesi di router KEDUA

        $this->actingAs($this->staff('administrator'))->delete(route('customers.destroy', $c))->assertRedirect();

        $this->assertNull(Customer::find($c->id));
        $this->assertRadiusGone($c);
        Process::assertRan(fn (PendingProcess $p) => is_array($p->command)
            && in_array('10.0.0.2:3799', $p->command, true) && in_array('sec-b', $p->command, true)
            && str_contains($p->input, 'sess-b-1'));
        Queue::assertPushed(DeprovisionOnuJob::class);
    }

    public function test_web_bulk_delete_also_removes_radius_account(): void
    {
        $c = $this->customer('CUST-BULK-1');

        $this->actingAs($this->staff('admin'))
            ->post(route('customers.bulk-action'), ['action' => 'delete', 'ids' => [$c->id]])
            ->assertSessionHas('success');

        $this->assertNull(Customer::find($c->id));
        $this->assertRadiusGone($c);
    }

    public function test_api_bulk_delete_is_admin_only_and_cleans_radius(): void
    {
        $c = $this->customer('CUST-API-DEL');

        Sanctum::actingAs($this->staff('sales'));
        $this->postJson('/api/v1/admin/customers/bulk-action', ['action' => 'delete', 'ids' => [$c->id]])->assertForbidden();
        $this->assertNotNull(Customer::find($c->id));

        Sanctum::actingAs($this->staff('admin'));
        $this->postJson('/api/v1/admin/customers/bulk-action', ['action' => 'delete', 'ids' => [$c->id]])->assertOk();
        $this->assertNull(Customer::find($c->id));
        $this->assertRadiusGone($c);
    }

    public function test_bulk_activate_skips_dismantled_customers_on_web_and_api(): void
    {
        $dismantled = $this->customer('CUST-DSM', ['is_active' => false, 'status' => Customer::STATUS_DISMANTLED]);
        Customer::whereKey($dismantled->id)->update(['status' => Customer::STATUS_DISMANTLED, 'is_active' => false]);

        Sanctum::actingAs($this->staff('admin'));
        $this->postJson('/api/v1/admin/customers/bulk-action', ['action' => 'activate', 'ids' => [$dismantled->id]])
            ->assertOk()->assertJson(['processed' => 0]);

        $this->actingAs($this->staff('admin'))
            ->post(route('customers.bulk-action'), ['action' => 'activate', 'ids' => [$dismantled->id]]);

        $this->assertSame(Customer::STATUS_DISMANTLED, $dismantled->fresh()->status);
        $this->assertFalse((bool) $dismantled->fresh()->is_active);
    }

    public function test_api_payment_uses_the_same_path_as_web_and_records_cash_mutation(): void
    {
        $c = $this->customer('CUST-PAY', ['is_active' => false, 'status' => Customer::STATUS_SUSPENDED]);
        Customer::whereKey($c->id)->update(['status' => Customer::STATUS_SUSPENDED, 'is_active' => false]);
        $invoice = Invoice::create([
            'customer_id' => $c->id, 'invoice_number' => 'INV-API-PAY-1', 'subtotal' => 150000, 'amount' => 150000,
            'status' => 'unpaid', 'due_date' => now()->addDays(7),
        ]);

        Sanctum::actingAs($this->staff('kasir'));
        $this->postJson("/api/v1/admin/invoices/{$invoice->id}/pay", ['payment_method' => 'transfer'])->assertOk();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('transfer', $invoice->payment_method);
        // Sebelumnya jalur API tidak membuat mutasi kas: saldo Kas & Bank tidak bertambah.
        $this->assertTrue(BankTransaction::where('description', 'like', '%INV-API-PAY-1%')->where('amount', 150000)->exists());
        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);
    }
}
