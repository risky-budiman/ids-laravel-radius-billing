<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Peta pelanggan: lapisan status benar dan data pribadi (password, NIK, HP, alamat) tidak dikirim ke browser. */
class CustomerMapTest extends TestCase
{
    use RefreshDatabase;

    private Package $pkg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pkg = Package::create(['name' => 'Paket 20M', 'type' => 'pppoe', 'price' => 1, 'upload_speed' => '1M', 'download_speed' => '1M', 'is_active' => true]);
    }

    private function customer(string $name, string $status, array $extra = []): Customer
    {
        static $n = 0;
        $n++;
        $c = Customer::create(array_merge(['customer_code' => sprintf('0010010%05d', $n), 'username' => "u{$n}@net", 'name' => $name, 'package_id' => $this->pkg->id,
            'phone' => '081299990' . $n, 'password' => "rahasiaPPP{$n}", 'ktp' => '320100000000000' . $n, 'address' => "Jl. Rahasia {$n}",
            'latitude' => -6.2 + $n / 1000, 'longitude' => 106.8], $extra));
        $c->forceFill(['status' => $status, 'is_active' => $status === 'active'])->saveQuietly();

        return $c->fresh();
    }

    public function test_layers_and_no_private_data(): void
    {
        $normal = $this->customer('Normal Saja', 'active');
        $overdue = $this->customer('Telat Bayar', 'active');
        Invoice::create(['customer_id' => $overdue->id, 'invoice_number' => 'INV-M-1', 'amount' => 150000, 'subtotal' => 150000, 'status' => 'unpaid', 'due_date' => now()->subDays(3)]);
        Invoice::create(['customer_id' => $normal->id, 'invoice_number' => 'INV-M-2', 'amount' => 150000, 'subtotal' => 150000, 'status' => 'unpaid', 'due_date' => now()->addDays(5)]);   // belum jatuh tempo
        $ticket = $this->customer('Ada Tiket', 'active');
        Ticket::create(['customer_id' => $ticket->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'medium', 'subject' => 'x', 'description' => 'y']);
        $los = $this->customer('ONU Mati', 'active');
        CustomerSignalCache::create(['customer_id' => $los->id, 'onu_index' => '1.1.1.1', 'status' => 'los', 'last_polled_at' => now()]);
        $weak = $this->customer('Sinyal Lemah', 'active');
        CustomerSignalCache::create(['customer_id' => $weak->id, 'onu_index' => '1.1.1.2', 'status' => 'online', 'rx_power' => -29.5, 'last_polled_at' => now()]);
        $this->customer('Diisolir', 'suspended');
        $this->customer('Baru Daftar', 'waiting_activation');
        $this->customer('Sudah Cabut', 'dismantled');
        $this->customer('<img src=x onerror=alert(1)>', 'active');
        $this->customer('Tanpa Koordinat', 'active', ['latitude' => null, 'longitude' => null]);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $res = $this->actingAs($admin)->get(route('customers.map'))->assertOk();
        $points = collect($res->viewData('points'))->keyBy('name');

        $this->assertSame('normal', $points['Normal Saja']['layer']);
        $this->assertSame('overdue', $points['Telat Bayar']['layer']);
        $this->assertSame('trouble', $points['Ada Tiket']['layer']);
        $this->assertSame('trouble', $points['ONU Mati']['layer']);
        $this->assertSame('ONU los', $points['ONU Mati']['problem']);
        $this->assertSame('trouble', $points['Sinyal Lemah']['layer']);
        $this->assertSame('suspended', $points['Diisolir']['layer']);
        $this->assertSame('pending', $points['Baru Daftar']['layer']);
        $this->assertSame('inactive', $points['Sudah Cabut']['layer']);
        $this->assertFalse($points->has('Tanpa Koordinat'));
        $this->assertSame(3, $res->viewData('counts')['trouble']);

        // Data pribadi tidak lagi tertulis di sumber halaman
        $html = $res->getContent();
        foreach (['rahasiaPPP', '3201000000000001', '0812999901', 'Jl. Rahasia'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
        $this->assertStringNotContainsString('<img src=x', $html);   // nama ber-HTML di-escape di JSON
        $this->assertStringNotContainsString('Mapped Customers', $html);
    }

    public function test_sales_sees_map_without_infrastructure(): void
    {
        \App\Models\Odp::create(['id' => 'ODP-01', 'name' => 'ODP Rahasia', 'latitude' => -6.2, 'longitude' => 106.8]);
        $this->customer('Pelanggan A', 'active');
        $sales = User::factory()->create(['role' => 'sales', 'is_active' => true]);

        $res = $this->actingAs($sales)->get(route('customers.map'))->assertOk();
        $this->assertSame(['Pelanggan A'], collect($res->viewData('points'))->pluck('name')->all());
        $this->assertSame([], $res->viewData('infrastructure'));
        $res->assertDontSee('ODP Rahasia');
    }
}
