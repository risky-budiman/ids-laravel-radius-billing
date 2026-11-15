<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\JournalItem;
use App\Models\User;
use App\Services\CustomerActivationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Jurnal persediaan: pembelian (kas/hutang), pemakaian (harga rata-rata), tarik perangkat, penyesuaian.
 */
class InventoryAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private InventoryItem $cable;
    private InventoryItem $onu;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        foreach ([['1101', 'Kas Tunai', 'asset'], ['1102', 'Bank', 'asset'], ['1105', 'Persediaan Barang', 'asset'], ['1106', 'PPN Masukan', 'asset'],
                  ['1202', 'Peralatan Jaringan', 'asset'], ['2101', 'Hutang Vendor', 'liability'], ['5105', 'Beban Operasional Lapangan', 'expense'],
                  ['5106', 'Beban Maintenance Jaringan', 'expense'], ['4199', 'Pendapatan Lain-lain', 'income']] as [$code, $name, $type]) {
            ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }
        $this->admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $cat = InventoryCategory::create(['name' => 'Material']);
        $this->cable = InventoryItem::create(['category_id' => $cat->id, 'name' => 'Kabel', 'unit' => 'meter', 'track_serial' => false]);
        $this->onu = InventoryItem::create(['category_id' => $cat->id, 'name' => 'ONU', 'unit' => 'pcs', 'track_serial' => true]);
    }

    private function bal(string $code): float
    {
        $id = ChartOfAccount::where('code', $code)->value('id');

        return round((float) JournalItem::where('account_id', $id)->sum('debit') - (float) JournalItem::where('account_id', $id)->sum('credit'), 2);
    }

    private function svc(): InventoryService
    {
        return app(InventoryService::class);
    }

    public function test_cash_purchase_reduces_bank_balance_and_credit_purchase_creates_payable(): void
    {
        $bank = BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor', 'type' => 'cash', 'is_active' => true, 'balance' => 1000000]);
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), [
            'inventory_item_id' => $this->cable->id, 'quantity' => 100, 'unit_price' => 2000,
            'payment_source' => 'bank', 'bank_account_id' => $bank->id,
        ])->assertRedirect(route('inventory.index'));

        $this->assertEquals(800000, (float) $bank->fresh()->balance, 'dulu saldo Kas & Bank tidak berkurang');
        $this->assertEquals(200000, $this->bal('1105'));
        $this->assertEquals(-200000, $this->bal('1102'));

        $this->svc()->stockIn($this->cable, ['quantity' => 100, 'unit_price' => 3000, 'payment_source' => 'payable'], $this->admin);
        $this->assertEquals(-300000, $this->bal('2101'), 'hutang supplier');
        $this->assertEquals(500000, $this->bal('1105'));
        $this->assertEquals(2500, $this->svc()->averageCost($this->cable));

        // Perangkat lama di pelanggan / stok awal tidak dijurnal sebagai pembelian
        $this->svc()->stockIn($this->cable, ['quantity' => 10, 'unit_price' => 5000, 'payment_source' => 'none'], $this->admin);
        $this->assertEquals(500000, $this->bal('1105'));
    }

    public function test_usage_and_adjustments_move_value_out_of_inventory(): void
    {
        $this->svc()->stockIn($this->cable, ['quantity' => 100, 'unit_price' => 2000], $this->admin);   // 200.000

        // Material pemasangan: Dr 5105 / Cr 1105 @ 2.000
        $this->svc()->stockOut($this->cable, ['quantity' => 30, 'debit_account' => '5105'], $this->admin);
        $this->assertEquals(60000, $this->bal('5105'));
        $this->assertEquals(140000, $this->bal('1105'));

        // Opname: fisik 60 m (selisih -10) -> beban 5106
        $this->svc()->opname($this->cable, $this->admin, physicalQty: 60);
        $this->assertEquals(20000, $this->bal('5106'));
        $this->assertEquals(120000, $this->bal('1105'));
    }

    public function test_installed_modem_is_network_equipment_and_returns_on_dismantle(): void
    {
        $this->svc()->stockIn($this->onu, ['quantity' => 2, 'serials' => ['M1', 'M2'], 'unit_price' => 300000], $this->admin);
        $customer = Customer::create(['customer_code' => 'C-ACC', 'username' => 'c-acc', 'name' => 'Pel', 'installation_fee' => 0]);
        $m1 = InventoryStock::where('serial_number', 'M1')->first();

        app(CustomerActivationService::class)->activate($customer->fresh(), ['modem_stock_id' => $m1->id], $this->admin);
        $this->assertEquals(300000, $this->bal('1202'));
        $this->assertEquals(300000, $this->bal('1105'));

        // Ditarik rusak: kembali ke persediaan lalu dibebankan
        app(CustomerActivationService::class)->dismantle($customer->fresh(), [$m1->id], $this->admin, null, [$m1->id]);
        $this->assertEquals(0, $this->bal('1202'));
        $this->assertEquals(300000, $this->bal('1105'));
        $this->assertEquals(300000, $this->bal('5106'));
    }
}
