<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MootaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\User::create([
            'id' => 1,
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    public function test_moota_webhook_successfully_reconciles_single_matching_amount()
    {
        $customer = Customer::create([
            'customer_code' => 'CUST_MOO',
            'username' => 'mootauser',
            'name' => 'Moota Customer',
            'email' => 'moota@example.com',
            'phone' => '08123456789',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-MOO-001',
            'amount' => 150382, // Unique amount (e.g. 150,000 + 382 unique code)
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->postJson(route('webhooks.moota'), [
            [
                'mutation_id' => 'mut_991',
                'amount' => 150382,
                'type' => 'CR',
                'description' => 'TRANSFER DARI MOOTA CUSTOMER',
                'bank_id' => 'b_1',
            ]
        ]);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $customer->refresh();
        $this->assertTrue((bool)$customer->is_active);

        $pgAccount = BankAccount::where('bank_name', 'MOOTA')->first();
        $this->assertNotNull($pgAccount);
    }

    public function test_moota_webhook_disambiguates_multiple_matching_amounts_using_description()
    {
        $customer1 = Customer::create([
            'customer_code' => 'CUST_MOO_A',
            'username' => 'mootauserA',
            'name' => 'Alice Moota',
            'email' => 'alice@example.com',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $customer2 = Customer::create([
            'customer_code' => 'CUST_MOO_B',
            'username' => 'mootauserB',
            'name' => 'Bob Moota',
            'email' => 'bob@example.com',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        // Two invoices with the exact same amount
        $invoice1 = Invoice::create([
            'customer_id' => $customer1->id,
            'invoice_number' => 'INV-MOO-A1',
            'amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $invoice2 = Invoice::create([
            'customer_id' => $customer2->id,
            'invoice_number' => 'INV-MOO-B2',
            'amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        // Send mutation for Bob Moota specifically
        $response = $this->postJson(route('webhooks.moota'), [
            [
                'mutation_id' => 'mut_992',
                'amount' => 150000,
                'type' => 'CR',
                'description' => 'TRF DARI BOB MOOTA INV-MOO-B2',
                'bank_id' => 'b_1',
            ]
        ]);

        $response->assertStatus(200);

        // Bob's invoice should be paid
        $invoice2->refresh();
        $this->assertEquals('paid', $invoice2->status);

        // Alice's invoice should still be unpaid
        $invoice1->refresh();
        $this->assertEquals('unpaid', $invoice1->status);
    }
}
