<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerIsolation;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadGroupReply;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use App\Models\User;
use App\Services\CustomerLifecycleService;
use App\Services\InvoiceService;
use App\Services\IsolationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Isolir lewat RADIUS: grup ISOLIR + IP isolir sesuai pengaturan, dipulihkan saat lunas.
 */
class IsolationTest extends TestCase
{
    use RefreshDatabase;

    private Package $package;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Nas::forceCreate(['nasname' => '10.0.0.1', 'shortname' => 'nas-a', 'secret' => 'sec-a']);
        $this->package = Package::create(['name' => 'Paket-20M', 'type' => 'pppoe', 'price' => 200000, 'upload_speed' => '20M', 'download_speed' => '20M']);
    }

    private function configure(array $values): array
    {
        return app(IsolationService::class)->saveSettings(array_merge([
            'isolir_enabled' => '1', 'isolir_mode' => 'range', 'isolir_group' => 'ISOLIR',
            'isolir_ip_start' => '10.255.0.254', 'isolir_ip_end' => '10.255.1.10',
            'isolir_pool_name' => '', 'isolir_rate_limit' => '256k/256k', 'isolir_address_list' => 'isolir',
            'isolir_mikrotik_group' => '', 'isolir_page_host' => '',
        ], $values));
    }

    private function activeCustomer(?string $staticIp = null): Customer
    {
        $n = ++$this->seq;
        $c = Customer::create([
            'customer_code' => "C-ISO-$n", 'username' => "iso$n", 'name' => "Pelanggan $n",
            'package_id' => $this->package->id, 'is_active' => true, 'status' => Customer::STATUS_ACTIVE,
        ]);
        Customer::whereKey($c->id)->update(['is_active' => true, 'status' => Customer::STATUS_ACTIVE]);
        RadUserGroup::insert(['username' => "iso$n", 'groupname' => 'Paket-20M', 'priority' => 1]);
        if ($staticIp) {
            RadReply::create(['username' => "iso$n", 'attribute' => 'Framed-IP-Address', 'op' => ':=', 'value' => $staticIp]);
        }
        RadAcct::forceCreate(['acctsessionid' => "s$n", 'acctuniqueid' => md5("s$n"), 'username' => "iso$n",
            'nasipaddress' => '10.0.0.1', 'acctstarttime' => now()->subHour(), 'acctstoptime' => null]);

        return $c->fresh();
    }

    private function radGroups(Customer $c): array
    {
        return RadUserGroup::where('username', $c->username)->pluck('groupname')->all();
    }

    private function framedIp(Customer $c): ?string
    {
        return RadReply::where('username', $c->username)->where('attribute', 'Framed-IP-Address')->value('value');
    }

    public function test_disabled_isolation_keeps_previous_behaviour(): void
    {
        $c = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($c);

        $this->assertSame(['Paket-20M'], $this->radGroups($c));
        $this->assertSame(0, CustomerIsolation::count());
    }

    public function test_range_mode_assigns_isolation_ip_and_restores_on_payment(): void
    {
        $this->configure([]);
        $static = $this->activeCustomer('100.64.10.5');
        $other = $this->activeCustomer();

        app(CustomerLifecycleService::class)->suspend($static);
        app(CustomerLifecycleService::class)->suspend($other);

        // Grup paket diganti ISOLIR; IP statis disingkirkan; IP isolir dialokasikan berurutan (lewati .255 & .0)
        $this->assertSame(['ISOLIR'], $this->radGroups($static));
        $this->assertSame('10.255.0.254', $this->framedIp($static));
        $this->assertSame('10.255.1.1', $this->framedIp($other));
        $this->assertEqualsCanonicalizing(
            ['Mikrotik-Rate-Limit' => '256k/256k', 'Mikrotik-Address-List' => 'isolir'],
            RadGroupReply::where('groupname', 'ISOLIR')->pluck('value', 'attribute')->all()
        );
        Process::assertRan(fn (PendingProcess $p) => is_array($p->command) && in_array('10.0.0.1:3799', $p->command, true));

        // Lunas -> paket & IP statis kembali, IP isolir dilepas, sesi diputus agar IP normal dipakai
        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $invoice = Invoice::create(['customer_id' => $static->id, 'invoice_number' => 'INV-ISO-1', 'amount' => 200000, 'subtotal' => 200000,
            'status' => 'unpaid', 'due_date' => now()->subDays(3)]);
        app(InvoiceService::class)->markAsPaid($invoice);

        $this->assertSame(Customer::STATUS_ACTIVE, $static->fresh()->status);
        $this->assertSame(['Paket-20M'], $this->radGroups($static));
        $this->assertSame('100.64.10.5', $this->framedIp($static));
        $this->assertNull(CustomerIsolation::where('customer_id', $static->id)->first());

        // IP yang dilepas dipakai lagi untuk pelanggan berikutnya
        $next = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($next);
        $this->assertSame('10.255.0.254', $this->framedIp($next));
    }

    public function test_pool_mode_sends_framed_pool_and_no_per_user_ip(): void
    {
        $this->configure(['isolir_mode' => 'pool', 'isolir_pool_name' => 'pool-isolir', 'isolir_rate_limit' => '']);
        $c = $this->activeCustomer('100.64.10.9');

        app(CustomerLifecycleService::class)->suspend($c);

        $this->assertSame(['ISOLIR'], $this->radGroups($c));
        $this->assertNull($this->framedIp($c), 'IP statis harus disingkirkan agar pool isolir berlaku');
        $this->assertSame('pool-isolir', RadGroupReply::where('groupname', 'ISOLIR')->where('attribute', 'Framed-Pool')->value('value'));
        $this->assertSame(':=', RadGroupReply::where('groupname', 'ISOLIR')->where('attribute', 'Framed-Pool')->value('op'));
    }

    public function test_full_range_still_moves_customer_to_isolation_group(): void
    {
        $this->configure(['isolir_ip_start' => '10.255.0.10', 'isolir_ip_end' => '10.255.0.10']);
        $a = $this->activeCustomer();
        $b = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($a);
        app(CustomerLifecycleService::class)->suspend($b);

        $this->assertSame('10.255.0.10', $this->framedIp($a));
        $this->assertNull($this->framedIp($b));
        $this->assertSame(['ISOLIR'], $this->radGroups($b));
        $this->assertSame(['total' => 1, 'used' => 1, 'free' => 0], app(IsolationService::class)->capacity());
    }

    public function test_settings_page_applies_to_existing_suspended_customers_and_disable_releases_them(): void
    {
        $c = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($c);    // isolir belum aktif: hanya status
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->get(route('settings.isolation'))->assertOk()->assertSee('Pengaturan Isolir');
        $this->actingAs($admin)->post(route('settings.isolation.update'), [
            'isolir_enabled' => '1', 'isolir_mode' => 'range', 'isolir_group' => 'ISOLIR',
            'isolir_ip_start' => '10.255.5.2', 'isolir_ip_end' => '10.255.5.100',
        ])->assertRedirect(route('settings.isolation'));
        $this->assertSame('10.255.5.2', $this->framedIp($c));

        // Rentang diganti -> IP lama di luar rentang dialokasikan ulang
        $this->actingAs($admin)->post(route('settings.isolation.update'), [
            'isolir_enabled' => '1', 'isolir_mode' => 'range', 'isolir_group' => 'ISOLIR',
            'isolir_ip_start' => '10.99.0.2', 'isolir_ip_end' => '10.99.0.20',
        ]);
        $this->assertSame('10.99.0.2', $this->framedIp($c));

        $this->actingAs($admin)->post(route('settings.isolation.update'), [
            'isolir_enabled' => '0', 'isolir_mode' => 'range', 'isolir_group' => 'ISOLIR',
            'isolir_ip_start' => '10.99.0.2', 'isolir_ip_end' => '10.99.0.20',
        ]);
        $this->assertSame(['Paket-20M'], $this->radGroups($c));
        $this->assertNull($this->framedIp($c));

        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->get(route('settings.isolation'))->assertForbidden();
    }

    public function test_editing_an_isolated_customer_does_not_lift_isolation(): void
    {
        $this->configure([]);
        $c = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($c);
        $other = Package::create(['name' => 'Paket-50M', 'type' => 'pppoe', 'price' => 300000, 'upload_speed' => '50M', 'download_speed' => '50M']);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        $this->putJson("/api/v1/admin/customers/{$c->id}", ['name' => 'Ganti Nama', 'package_id' => $other->id, 'billing_type' => 'postpaid', 'billing_method' => 'cycle', 'customer_type' => 'personal'])->assertOk();

        $this->assertSame(['ISOLIR'], $this->radGroups($c), 'edit pelanggan diam-diam membuka isolir');

        // Saat lunas, paket BARU yang dipulihkan
        app(IsolationService::class)->release($c->fresh());
        $this->assertSame(['Paket-50M'], $this->radGroups($c));
    }

    public function test_isolation_page_recognises_customer_by_ip_and_ignores_spoofed_header(): void
    {
        $this->configure([]);
        $c = $this->activeCustomer();
        app(CustomerLifecycleService::class)->suspend($c);
        Invoice::create(['customer_id' => $c->id, 'invoice_number' => 'INV-ISO-PAGE', 'amount' => 200000, 'subtotal' => 200000,
            'status' => 'unpaid', 'due_date' => now()->subDay()]);
        $ip = $this->framedIp($c);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])->get('/isolir')
            ->assertOk()->assertSee($c->name)->assertSee('INV-ISO-PAGE')->assertHeader('Cache-Control');

        // Lewat proxy lokal: entri terakhir X-Forwarded-For dipakai
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->withHeader('X-Forwarded-For', "1.2.3.4, $ip")
            ->get('/isolir')->assertSee($c->name);

        // Dari luar, header palsu diabaikan
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->withHeader('X-Forwarded-For', $ip)
            ->get('/isolir')->assertOk()->assertDontSee($c->name)->assertSee('Akses internet dibatasi');
    }
}
