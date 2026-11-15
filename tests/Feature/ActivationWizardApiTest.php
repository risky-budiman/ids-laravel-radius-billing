<?php

namespace Tests\Feature;

use App\Jobs\DeprovisionOnuJob;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadReply;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Wizard aktivasi & dismantle lewat API (aplikasi mobile) — sama dengan wizard web.
 */
class ActivationWizardApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teknisi;
    private InventoryItem $modemItem;
    private InventoryItem $cable;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        $this->teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $cat = InventoryCategory::create(['name' => 'Perangkat']);
        $this->modemItem = InventoryItem::create(['category_id' => $cat->id, 'name' => 'ONU ZTE F660', 'unit' => 'pcs', 'track_serial' => true]);
        $this->cable = InventoryItem::create(['category_id' => $cat->id, 'name' => 'Kabel Drop Core', 'unit' => 'meter', 'track_serial' => false]);
    }

    private function newCustomer(string $code, float $fee = 250000): Customer
    {
        $pkg = Package::create(['name' => "Paket $code", 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M']);

        // Observer: pelanggan baru -> menunggu aktivasi + tiket aktivasi
        return Customer::create(['customer_code' => $code, 'username' => strtolower($code), 'name' => "Pelanggan $code",
            'package_id' => $pkg->id, 'installation_fee' => $fee, 'is_active' => false, 'status' => Customer::STATUS_NEW,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle'])->fresh();
    }

    private function modem(string $sn, string $status = 'ready', ?int $customerId = null): InventoryStock
    {
        return InventoryStock::create(['inventory_item_id' => $this->modemItem->id, 'serial_number' => $sn, 'status' => $status, 'customer_id' => $customerId]);
    }

    public function test_technician_activates_from_mobile_with_modem_material_and_cash_fee(): void
    {
        $c = $this->newCustomer('C-ACT-1');
        $this->assertSame(Customer::STATUS_WAITING_ACTIVATION, $c->status);
        $modem = $this->modem('ZTEG11111111');
        Sanctum::actingAs($this->teknisi);

        $form = $this->getJson("/api/v1/admin/customers/{$c->id}/activation")->assertOk();
        $form->assertJsonPath('can_activate', true)->assertJsonPath('modems.0.serial_number', 'ZTEG11111111')
            ->assertJsonPath('consumables.0.name', 'Kabel Drop Core');

        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", [
            'modem_stock_id' => $modem->id, 'consumables' => [['item_id' => $this->cable->id, 'quantity' => 35]],
            'payment_method' => 'cash', 'odp_port' => 4, 'latitude' => 3.5952, 'longitude' => 98.6722,
        ])->assertOk()->assertJsonPath('customer.status', 'active')->assertJsonPath('installation_invoice', null);

        $c->refresh();
        $this->assertSame(Customer::STATUS_ACTIVE, $c->status);
        $this->assertTrue((bool) $c->is_active);
        $this->assertNotNull($c->activated_at);
        $this->assertSame('ZTEG11111111', $c->onu_sn, 'SN diambil dari modem gudang');
        $this->assertNotNull($c->billing_next_date);
        $this->assertSame('installed', $modem->fresh()->status);
        $this->assertSame($c->id, $modem->fresh()->customer_id);
        $this->assertSame(2, InventoryMovement::where('customer_id', $c->id)->where('type', 'out')->count());
        $this->assertTrue(BankTransaction::where('description', 'like', '%[Instalasi]%C-ACT-1%')->where('amount', 250000)->exists());
        $this->assertSame('resolved', Ticket::where('customer_id', $c->id)->where('type', 'aktivasi')->sole()->status);
    }

    public function test_activation_without_modem_or_photos_and_with_large_phone_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        Sanctum::actingAs($this->teknisi);

        // Tanpa modem gudang & tanpa foto: tetap bisa
        $a = $this->newCustomer('C-NOPHOTO', 0);
        $this->postJson("/api/v1/admin/customers/{$a->id}/activate", ['onu_sn' => 'ZTEGC8A1B2C3'])->assertOk();
        $this->assertSame('ZTEGC8A1B2C3', $a->fresh()->onu_sn);
        $this->assertNull($a->fresh()->cpe_photo);

        // Foto kamera ponsel ~5 MB dulu ditolak (batas 2 MB)
        $b = $this->newCustomer('C-BIGPHOTO', 0);
        $this->post("/api/v1/admin/customers/{$b->id}/activate", [
            'cpe_photo' => \Illuminate\Http\UploadedFile::fake()->image('label.jpg', 4000, 3000)->size(5000),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertNotNull($b->fresh()->cpe_photo);
    }

    public function test_activation_guards_status_and_modem_availability(): void
    {
        $other = $this->newCustomer('C-ACT-OTHER');
        $taken = $this->modem('ZTEG22222222', 'installed', $other->id);
        $c = $this->newCustomer('C-ACT-2');
        Sanctum::actingAs($this->teknisi);

        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['modem_stock_id' => $taken->id])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'tidak tersedia'));
        $this->assertSame($other->id, $taken->fresh()->customer_id);

        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", [])->assertOk();
        // Aktivasi ulang pelanggan aktif ditolak (dulu bisa: stok terpakai dua kali)
        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", [])->assertStatus(422);
    }

    public function test_gateway_installation_fee_creates_invoice_with_grace_period(): void
    {
        $c = $this->newCustomer('C-ACT-PG', 300000);
        Sanctum::actingAs($this->teknisi);

        $res = $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'pg'])->assertOk();

        $inv = Invoice::where('customer_id', $c->id)->sole();
        $this->assertStringStartsWith('INV-INST', $inv->invoice_number);
        $this->assertEquals(300000, (float) $inv->amount);
        $res->assertJsonPath('installation_invoice.invoice_number', $inv->invoice_number);
        $this->assertNotNull($c->fresh()->activation_grace_expires_at);
        $this->assertNull($c->fresh()->installation_paid_at);
    }

    public function test_dismantle_from_mobile_returns_only_this_customers_equipment(): void
    {
        $c = $this->newCustomer('C-DSM-1');
        Customer::whereKey($c->id)->update(['olt_id' => null]);
        Sanctum::actingAs($this->teknisi);
        $modem = $this->modem('ZTEG33333333');
        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['modem_stock_id' => $modem->id])->assertOk();
        RadCheck::forceCreate(['username' => $c->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'x']);
        RadReply::forceCreate(['username' => $c->username, 'attribute' => 'Framed-IP-Address', 'op' => ':=', 'value' => '100.64.0.9']);
        $foreign = $this->modem('ZTEG44444444', 'installed', $this->newCustomer('C-DSM-OTHER')->id);

        $this->postJson("/api/v1/admin/customers/{$c->id}/request-dismantle")->assertOk();
        $this->assertSame(1, Ticket::where('customer_id', $c->id)->where('type', 'dismantle')->count(), 'satu tiket, bukan dua');

        $this->getJson("/api/v1/admin/customers/{$c->id}/dismantle")->assertOk()
            ->assertJsonPath('installed_equipment.0.serial_number', 'ZTEG33333333')->assertJsonCount(1, 'installed_equipment');

        $this->postJson("/api/v1/admin/customers/{$c->id}/dismantle", ['stock_ids' => [$modem->id, $foreign->id]])->assertStatus(422);
        $this->assertSame('installed', $foreign->fresh()->status);

        $this->postJson("/api/v1/admin/customers/{$c->id}/dismantle", ['stock_ids' => [$modem->id], 'notes' => 'Modem lengkap dengan adaptor'])
            ->assertOk()->assertJsonPath('customer.status', 'dismantled');

        $c->refresh();
        $this->assertSame(Customer::STATUS_DISMANTLED, $c->status);
        $this->assertNotNull($c->terminated_at);
        $this->assertSame('dismantled', $modem->fresh()->status);
        $this->assertNull($modem->fresh()->customer_id);
        $this->assertTrue(InventoryMovement::where('customer_id', $c->id)->where('type', 'in')->exists());
        $this->assertStringContainsString('Modem lengkap', Ticket::where('customer_id', $c->id)->where('type', 'dismantle')->sole()->resolution_notes);
        $this->assertSame(0, RadCheck::where('username', $c->username)->count());
        $this->assertSame(0, RadReply::where('username', $c->username)->count(), 'radreply ikut dibersihkan');
    }

    public function test_dismantle_deprovisions_onu_when_olt_is_set(): void
    {
        $c = $this->newCustomer('C-DSM-OLT');
        $olt = \App\Models\Olt::forceCreate(['name' => 'OLT Uji', 'ip_address' => '10.10.10.1']);
        Customer::whereKey($c->id)->update(['status' => Customer::STATUS_WAITING_DISMANTLE, 'is_active' => true,
            'olt_id' => $olt->id, 'onu_index' => '1/1/1:3', 'onu_sn' => 'ZTEG55555555']);
        Sanctum::actingAs($this->teknisi);

        $this->postJson("/api/v1/admin/customers/{$c->id}/dismantle", [])->assertOk();

        Queue::assertPushed(DeprovisionOnuJob::class);
    }

    public function test_sales_cannot_use_the_wizard(): void
    {
        $c = $this->newCustomer('C-SALES');
        Sanctum::actingAs(User::factory()->create(['role' => 'sales', 'is_active' => true]));

        $this->getJson("/api/v1/admin/customers/{$c->id}/activation")->assertForbidden();
        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", [])->assertForbidden();
    }

    public function test_web_wizard_uses_the_same_rules(): void
    {
        $c = $this->newCustomer('C-WEB');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('customers.activate.store', $c), ['payment_method' => 'cash'])
            ->assertRedirect(route('customers.index'));
        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);

        $this->actingAs($admin)->post(route('customers.activate.store', $c), [])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('customers.request-dismantle', $c));
        $this->assertSame(1, Ticket::where('customer_id', $c->id)->where('type', 'dismantle')->count());
    }
}
