<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create Admin User
        $this->admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true
        ]);

        // Create Necessary CoA for Accounting Service (Correct Types: asset, liability, equity, income, expense)
        ChartOfAccount::create(['code' => '1104', 'name' => 'Persediaan Barang', 'type' => 'asset']);
        ChartOfAccount::create(['code' => '1101', 'name' => 'Kas Utama', 'type' => 'asset']);
        ChartOfAccount::create(['code' => '4101', 'name' => 'Pendapatan Internet', 'type' => 'income']);
        ChartOfAccount::create(['code' => '1103', 'name' => 'Piutang', 'type' => 'asset']);

        $this->category = InventoryCategory::create(['name' => 'Test Category']);
    }

    public function test_can_stock_in_non_serial_items_without_serials()
    {
        $this->withoutExceptionHandling();

        $item = InventoryItem::create([
            'category_id' => $this->category->id,
            'name' => 'Cable Dropcore',
            'unit' => 'meters',
            'track_serial' => false,
            'min_stock' => 10
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 100,
            'unit_price' => 1000,
            'reference' => 'Test Buy'
        ]);

        $response->assertRedirect(route('inventory.index'));
        
        // Verify stock count using the model's attribute logic
        $this->assertEquals(100, $item->fresh()->stock_count);
    }

    public function test_cannot_stock_in_serial_items_with_missing_serials()
    {
        $item = InventoryItem::create([
            'category_id' => $this->category->id,
            'name' => 'Modem ZTE',
            'unit' => 'pcs',
            'track_serial' => true,
            'min_stock' => 5
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 200000,
            'serials' => ['SN1', 'SN2'] // Only 2 serials for quantity 5
        ]);

        $response->assertSessionHasErrors('serials');
        $this->assertEquals(0, $item->fresh()->stock_count);
    }

    public function test_can_stock_in_serial_items_with_complete_serials()
    {
        $this->withoutExceptionHandling();

        $item = InventoryItem::create([
            'category_id' => $this->category->id,
            'name' => 'Modem Huawei',
            'unit' => 'pcs',
            'track_serial' => true,
            'min_stock' => 5
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 3,
            'unit_price' => 250000,
            'serials' => ['HWA001', 'HWA002', 'HWA003']
        ]);

        $response->assertRedirect(route('inventory.index'));
        $this->assertEquals(3, $item->fresh()->stock_count);
    }
}
