<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CustomerSearchAndBillingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);
        
        $this->package = Package::create([
            'name' => '10Mbps',
            'price' => 100000,
            'speed_limit_down' => 10240,
            'speed_limit_up' => 5120,
            'is_active' => true
        ]);

        // Create default system user for accounting/treasury record keeping (id = 1)
        User::create([
            'id' => 1,
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    /** @test */
    public function test_customer_list_search_functionality()
    {
        $this->actingAs($this->user);

        // Create customers
        $c1 = Customer::create([
            'customer_code' => 'CUST-BGR-001',
            'username' => 'alice_cooper',
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
            'phone' => '08111111111',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $c2 = Customer::create([
            'customer_code' => 'CUST-BGR-002',
            'username' => 'bob_marley',
            'name' => 'Bob Marley',
            'email' => 'bob@example.com',
            'phone' => '08222222222',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // 1. Search for Alice
        $response = $this->get(route('customers.index', ['search' => 'Alice']));
        $response->assertStatus(200);
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Marley');

        // 2. Search for Bob's username
        $response = $this->get(route('customers.index', ['search' => 'bob_marley']));
        $response->assertStatus(200);
        $response->assertSee('Bob Marley');
        $response->assertDontSee('Alice Cooper');

        // 3. Search for a specific code
        $response = $this->get(route('customers.index', ['search' => 'CUST-BGR-001']));
        $response->assertStatus(200);
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Marley');

        // 4. Search for phone number
        $response = $this->get(route('customers.index', ['search' => '08222222222']));
        $response->assertStatus(200);
        $response->assertSee('Bob Marley');
        $response->assertDontSee('Alice Cooper');
    }

    /** @test */
    public function test_automated_billing_only_generates_invoices_for_active_and_suspended_customers()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00'));

        $statuses = [
            Customer::STATUS_NEW => false,
            Customer::STATUS_WAITING_ACTIVATION => false,
            Customer::STATUS_ACTIVE => true,
            Customer::STATUS_SUSPENDED => true,
            Customer::STATUS_WAITING_DISMANTLE => false,
            Customer::STATUS_DISMANTLED => false,
            Customer::STATUS_CANCELED => false,
        ];

        $customers = [];
        foreach ($statuses as $status => $shouldBill) {
            $customer = Customer::create([
                'customer_code' => 'CODE-' . strtoupper($status),
                'username' => 'user_' . $status,
                'name' => 'User ' . ucfirst($status),
                'package_id' => $this->package->id,
                'billing_type' => 'postpaid',
                'billing_method' => 'cycle',
                'billing_due_day' => 20,
                'activated_at' => Carbon::parse('2026-07-01 10:00:00'),
                'billing_next_date' => Carbon::parse('2026-08-01'),
                'billing_due_date' => Carbon::parse('2026-08-20'),
                'is_active' => ($status === Customer::STATUS_ACTIVE),
            ]);
            $customer->status = $status;
            $customer->saveQuietly();
            
            $customers[$status] = $customer;
        }

        // Run the process-billing artisan command
        Artisan::call('app:process-billing');

        foreach ($statuses as $status => $shouldBill) {
            $customer = $customers[$status];
            $invoice = Invoice::where('customer_id', $customer->id)->first();

            if ($shouldBill) {
                $this->assertNotNull($invoice, "Invoice should be generated for status: {$status}");
                $this->assertEquals(100000, $invoice->amount);
            } else {
                $this->assertNull($invoice, "Invoice should NOT be generated for status: {$status}");
            }
        }

        Carbon::setTestNow(); // Reset time mock
    }

    /** @test */
    public function test_customer_list_is_sorted_by_latest_first()
    {
        $this->actingAs($this->user);

        $customer1 = new Customer([
            'customer_code' => 'CUST-SORT-001',
            'username' => 'user_sort_1',
            'name' => 'First User',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);
        $customer1->created_at = now()->subDays(5);
        $customer1->save();

        $customer2 = new Customer([
            'customer_code' => 'CUST-SORT-002',
            'username' => 'user_sort_2',
            'name' => 'Second User',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);
        $customer2->created_at = now();
        $customer2->save();

        $response = $this->get(route('customers.index'));
        $response->assertStatus(200);

        // Check if customer2 (Second User) appears before customer1 (First User)
        $response->assertSeeInOrder(['Second User', 'First User']);
    }

    /** @test */
    public function test_customer_bulk_actions_functionality()
    {
        $this->actingAs($this->user);

        $c1 = Customer::create([
            'customer_code' => 'CUST-BULK-001',
            'username' => 'bulk_user_1',
            'name' => 'Bulk User 1',
            'is_active' => false,
            'status' => Customer::STATUS_WAITING_ACTIVATION,
        ]);

        $c2 = Customer::create([
            'customer_code' => 'CUST-BULK-002',
            'username' => 'bulk_user_2',
            'name' => 'Bulk User 2',
            'is_active' => false,
            'status' => Customer::STATUS_WAITING_ACTIVATION,
        ]);

        // 1. Bulk Activate
        $response = $this->post(route('customers.bulk-action'), [
            'action' => 'activate',
            'ids' => [$c1->id, $c2->id]
        ]);
        $response->assertSessionHas('success', '2 pelanggan berhasil diaktifkan secara massal.');
        
        $this->assertEquals(Customer::STATUS_ACTIVE, $c1->fresh()->status);
        $this->assertEquals(Customer::STATUS_ACTIVE, $c2->fresh()->status);
        $this->assertTrue((bool)$c1->fresh()->is_active);
        $this->assertTrue((bool)$c2->fresh()->is_active);

        // 2. Bulk Suspend
        $response = $this->post(route('customers.bulk-action'), [
            'action' => 'suspend',
            'ids' => [$c1->id, $c2->id]
        ]);
        $response->assertSessionHas('success', '2 pelanggan berhasil di-suspend secara massal.');
        
        $this->assertEquals(Customer::STATUS_SUSPENDED, $c1->fresh()->status);
        $this->assertEquals(Customer::STATUS_SUSPENDED, $c2->fresh()->status);
        $this->assertFalse((bool)$c1->fresh()->is_active);
        $this->assertFalse((bool)$c2->fresh()->is_active);

        // 3. Bulk Dismantle
        $response = $this->post(route('customers.bulk-action'), [
            'action' => 'dismantle',
            'ids' => [$c1->id]
        ]);
        $response->assertSessionHas('success', '1 pelanggan berhasil diajukan dismantle secara massal.');
        $this->assertEquals(Customer::STATUS_WAITING_DISMANTLE, $c1->fresh()->status);

        // 4. Bulk Delete
        $response = $this->post(route('customers.bulk-action'), [
            'action' => 'delete',
            'ids' => [$c1->id, $c2->id]
        ]);
        $response->assertSessionHas('success', '2 pelanggan berhasil dihapus secara massal.');
        $this->assertNull(Customer::find($c1->id));
        $this->assertNull(Customer::find($c2->id));
    }
}
