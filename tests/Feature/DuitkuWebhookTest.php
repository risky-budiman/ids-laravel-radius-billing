<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuitkuWebhookTest extends TestCase
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

    public function test_duitku_webhook_successfully_updates_invoice_to_paid()
    {
        $apiKey = 'duitku-api-key-12345';
        $merchantCode = 'D1234';
        
        Gateway::create([
            'provider' => 'duitku',
            'type' => 'payment',
            'credentials' => [
                'merchant_code' => $merchantCode,
                'api_key' => $apiKey,
            ],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST_DKU',
            'username' => 'duitkuuser',
            'name' => 'Duitku Customer',
            'email' => 'duitku@example.com',
            'phone' => '08123456789',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DKU-001',
            'amount' => 135000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $merchantOrderId = $invoice->invoice_number . '-1786523900';
        $amount = '135000';
        
        // Generate valid SHA256 Duitku signature
        $signature = hash('sha256', $merchantCode . $amount . $merchantOrderId . $apiKey);

        $response = $this->postJson(route('webhooks.duitku'), [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'resultCode' => '00',
            'signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'OK']);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $customer->refresh();
        $this->assertTrue((bool)$customer->is_active);

        $pgAccount = BankAccount::where('bank_name', 'DUITKU')->first();
        $this->assertNotNull($pgAccount);
    }

    public function test_duitku_webhook_fails_with_invalid_signature()
    {
        $apiKey = 'duitku-api-key-12345';
        $merchantCode = 'D1234';
        
        Gateway::create([
            'provider' => 'duitku',
            'type' => 'payment',
            'credentials' => [
                'merchant_code' => $merchantCode,
                'api_key' => $apiKey,
            ],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST_DKU_2',
            'username' => 'duitkuuser2',
            'name' => 'Duitku Customer 2',
            'email' => 'duitku2@example.com',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DKU-002',
            'amount' => 135000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->postJson(route('webhooks.duitku'), [
            'merchantCode' => $merchantCode,
            'amount' => '135000',
            'merchantOrderId' => $invoice->invoice_number . '-1786523900',
            'resultCode' => '00',
            'signature' => 'invalid-signature-value',
        ]);

        $response->assertStatus(403);
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }
}
