<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Package;
use App\Models\User;
use App\Services\CustomerActivationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Integritas stok: status tersedia yang seragam, SN wajib & tervalidasi, arsip produk, stock opname.
 */
class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private InventoryCategory $cat;
    private InventoryItem $onu;
    private InventoryItem $cable;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        $this->admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->cat = InventoryCategory::create(['name' => 'Perangkat']);
        $this->onu = InventoryItem::create(['category_id' => $this->cat->id, 'name' => 'ONU', 'unit' => 'pcs', 'track_serial' => true, 'min_stock' => 0]);
        $this->cable = InventoryItem::create(['category_id' => $this->cat->id, 'name' => 'Kabel', 'unit' => 'meter', 'track_serial' => false, 'min_stock' => 0]);
    }

    private function svc(): InventoryService
    {
        return app(InventoryService::class);
    }

    public function test_api_stock_out_marks_serials_installed_and_requires_them(): void
    {
        $this->svc()->stockIn($this->onu, ['quantity' => 2, 'serials' => ['SN-A', 'SN-B']], $this->admin);
        Sanctum::actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));

        // Barang ber-SN tanpa SN ditolak (dulu tercatat keluar tanpa mengubah unit apa pun)
        $this->postJson("/api/v1/admin/inventory/{$this->onu->id}/movement", ['type' => 'out', 'quantity' => 1])->assertStatus(422);

        // Dulu: status 'deployed' (tidak ada di database) -> error
        $this->postJson("/api/v1/admin/inventory/{$this->onu->id}/movement", ['type' => 'out', 'quantity' => 1, 'serial_number' => 'sn-a'])
            ->assertOk()->assertJsonPath('current_stock', 1);
        $this->assertSame('installed', InventoryStock::where('serial_number', 'SN-A')->value('status'));

        // SN yang sudah keluar / milik produk lain / tidak ada
        $this->postJson("/api/v1/admin/inventory/{$this->onu->id}/movement", ['type' => 'out', 'quantity' => 1, 'serials' => ['SN-A']])
            ->assertStatus(422)->assertJsonFragment(['message' => 'SN tidak tersedia di gudang: SN-A (Terpasang).']);
        $this->postJson("/api/v1/admin/inventory/{$this->onu->id}/movement", ['type' => 'out', 'quantity' => 1, 'serials' => ['XX']])->assertStatus(422);

        // Barang tanpa SN: desimal boleh, tidak boleh melebihi saldo
        $this->postJson("/api/v1/admin/inventory/{$this->cable->id}/movement", ['type' => 'in', 'quantity' => 100.5])->assertOk();
        $this->postJson("/api/v1/admin/inventory/{$this->cable->id}/movement", ['type' => 'out', 'quantity' => 200])->assertStatus(422);
        $this->postJson("/api/v1/admin/inventory/{$this->cable->id}/movement", ['type' => 'out', 'quantity' => 35.5])
            ->assertOk()->assertJsonPath('current_stock', 65);
    }

    public function test_dismantled_units_count_as_stock_everywhere(): void
    {
        InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'OLD-1', 'status' => 'dismantled', 'condition' => 'used']);
        InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'BROKEN-1', 'status' => 'dismantled', 'condition' => 'broken']);
        InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'NEW-1', 'status' => 'ready']);

        $this->assertSame(2, $this->onu->stock_count, 'hasil tarik dihitung, yang rusak tidak');
        $opts = app(CustomerActivationService::class)->activationOptions()['serial_items']->first()->stocks->pluck('serial_number')->all();
        $this->assertSame(['NEW-1', 'OLD-1'], $opts);

        $this->actingAs($this->admin)->get(route('inventory.stock-out'))->assertOk()->assertSee('OLD-1')->assertDontSee('BROKEN-1');
        $old = InventoryStock::where('serial_number', 'OLD-1')->first();
        $this->post(route('inventory.stock-out.store'), [
            'inventory_item_id' => $this->onu->id, 'quantity' => 1, 'type' => 'maintenance', 'stock_ids' => [$old->id],
        ])->assertRedirect(route('inventory.index'));
        $this->assertSame('installed', $old->fresh()->status);
    }

    public function test_web_stock_out_rejects_serials_of_other_items_or_installed_units(): void
    {
        $other = InventoryItem::create(['category_id' => $this->cat->id, 'name' => 'Router', 'unit' => 'pcs', 'track_serial' => true]);
        $foreign = InventoryStock::create(['inventory_item_id' => $other->id, 'serial_number' => 'R-1', 'status' => 'ready']);
        $installed = InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'I-1', 'status' => 'installed']);
        $this->actingAs($this->admin);

        foreach ([$foreign, $installed] as $s) {
            $this->post(route('inventory.stock-out.store'), [
                'inventory_item_id' => $this->onu->id, 'quantity' => 1, 'type' => 'other', 'stock_ids' => [$s->id],
            ])->assertSessionHasErrors('quantity');
        }
        $this->assertSame('ready', $foreign->fresh()->status);
        $this->assertSame(0, InventoryMovement::where('type', 'out')->count());

        // SN ganda saat stok masuk: pesan, bukan error 500
        $this->post(route('inventory.stock-in.store'), [
            'inventory_item_id' => $this->onu->id, 'quantity' => 1, 'unit_price' => 0, 'serials' => ['I-1'],
        ])->assertSessionHasErrors('serials');
    }

    public function test_deleting_a_product_archives_it_and_keeps_history(): void
    {
        $pkg = Package::create(['name' => 'P', 'type' => 'pppoe', 'price' => 100000, 'upload_speed' => '5M', 'download_speed' => '5M']);
        $customer = Customer::create(['customer_code' => 'C-INV', 'username' => 'c-inv', 'name' => 'Pel', 'package_id' => $pkg->id, 'status' => 'active', 'is_active' => true]);
        $unit = InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'AT-CUST', 'status' => 'installed', 'customer_id' => $customer->id]);
        InventoryMovement::create(['inventory_item_id' => $this->onu->id, 'type' => 'out', 'quantity' => 1, 'customer_id' => $customer->id, 'user_id' => $this->admin->id]);
        $this->actingAs($this->admin);

        $this->delete(route('inventory.destroy', $this->onu))->assertSessionHas('success');
        $this->assertSoftDeleted($this->onu);
        $this->assertNotNull($unit->fresh(), 'SN di pelanggan tetap ada');
        $this->assertSame('ONU', $unit->fresh()->item->name, 'relasi tetap terbaca');
        $this->assertSame(1, InventoryMovement::where('inventory_item_id', $this->onu->id)->count());
        $this->get(route('inventory.show', $this->onu))->assertOk()->assertSee('diarsipkan');

        // Kategori yang masih dipakai produk arsip tidak boleh dihapus (cascade)
        $this->delete(route('inventory.category.destroy', $this->cat))->assertSessionHas('error');

        // Produk dengan stok di gudang tidak bisa diarsipkan
        $this->svc()->stockIn($this->cable, ['quantity' => 10], $this->admin);
        $this->delete(route('inventory.destroy', $this->cable))->assertSessionHas('error');
        $this->assertNotSoftDeleted($this->cable);
    }

    public function test_opname_aligns_ledger_with_serials_and_physical_count(): void
    {
        // Seperti data lapangan: 12 unit tercatat masuk, hanya 2 punya SN
        InventoryMovement::create(['inventory_item_id' => $this->onu->id, 'type' => 'in', 'quantity' => 12, 'user_id' => $this->admin->id]);
        $a = InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'A', 'status' => 'ready']);
        InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'B', 'status' => 'ready']);
        $this->assertEquals(10, $this->onu->serialDiscrepancy());
        $this->actingAs($this->admin);
        $this->get(route('inventory.show', $this->onu))->assertOk()->assertSee('belum didaftarkan SN-nya');

        // Unit B tidak ditemukan; 3 SN baru ditemukan
        $this->post(route('inventory.opname.store', $this->onu), ['present' => [$a->id], 'found_serials' => "C\nD\nE"])
            ->assertRedirect(route('inventory.show', $this->onu));
        $this->assertSame('lost', InventoryStock::where('serial_number', 'B')->value('status'));
        $this->assertSame(4, $this->onu->stock_count);
        $this->assertEquals(0, $this->onu->serialDiscrepancy());
        $this->assertEquals(-8, InventoryMovement::where('type', 'adjustment')->value('quantity'));

        // Tanpa SN: jumlah fisik
        $this->svc()->stockIn($this->cable, ['quantity' => 100], $this->admin);
        $this->post(route('inventory.opname.store', $this->cable), ['physical_qty' => 92.5])->assertSessionHas('success');
        $this->assertEquals(92.5, $this->cable->stock_count);

        // Teknisi tidak boleh opname
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]))
            ->get(route('inventory.opname', $this->cable))->assertForbidden();
    }

    public function test_concurrent_activation_cannot_reuse_a_modem(): void
    {
        $modem = InventoryStock::create(['inventory_item_id' => $this->onu->id, 'serial_number' => 'M-1', 'status' => 'ready']);
        $this->assertTrue($modem->isAvailable());
        $modem->update(['status' => 'installed']);
        $this->assertFalse($modem->fresh()->isAvailable());
        $this->assertSame(0, InventoryStock::available()->count());
    }
}
