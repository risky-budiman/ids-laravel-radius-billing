<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
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

    public function test_midtrans_webhook_successfully_updates_invoice_to_paid()
    {
        // 1. Create a Midtrans Gateway settings
        $serverKey = 'SB-Mid-server-1234567890';
        Gateway::create([
            'provider' => 'midtrans',
            'type' => 'payment',
            'credentials' => [
                'server_key' => $serverKey,
                'environment' => 'sandbox',
            ],
            'is_active' => true,
        ]);

        // 2. Create customer and invoice with hyphenated invoice number
        $customer = Customer::create([
            'customer_code' => 'CUST001',
            'username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-08-001',
            'amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $orderId = $invoice->invoice_number . '-1786523900';
        $statusCode = '200';
        $grossAmount = '150000.00';
        $transactionStatus = 'settlement';
        
        // Generate valid signature
        $signatureKey = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        // 3. Fire the webhook request
        $response = $this->postJson(route('webhooks.midtrans'), [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $transactionStatus,
            'signature_key' => $signatureKey,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'OK']);

        // 4. Assert invoice is updated
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        // 5. Assert customer is reactivated
        $customer->refresh();
        $this->assertTrue((bool)$customer->is_active);
        $this->assertEquals(Customer::STATUS_ACTIVE, $customer->status);

        // 6. Assert BankTransaction is recorded
        $pgAccount = BankAccount::where('bank_name', 'MIDTRANS')->first();
        $this->assertNotNull($pgAccount);
        
        $transaction = BankTransaction::where('bank_account_id', $pgAccount->id)
            ->where('amount', 150000)
            ->first();
        $this->assertNotNull($transaction);
    }

    public function test_midtrans_webhook_fails_with_invalid_signature()
    {
        $serverKey = 'SB-Mid-server-1234567890';
        Gateway::create([
            'provider' => 'midtrans',
            'type' => 'payment',
            'credentials' => [
                'server_key' => $serverKey,
                'environment' => 'sandbox',
            ],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST002',
            'username' => 'johndoe2',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-08-002',
            'amount' => 200000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $orderId = $invoice->invoice_number . '-1786523900';
        $statusCode = '200';
        $grossAmount = '200000.00';
        $transactionStatus = 'settlement';
        
        // Invalid signature key
        $signatureKey = 'invalid-signature-key-here';

        $response = $this->postJson(route('webhooks.midtrans'), [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $transactionStatus,
            'signature_key' => $signatureKey,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Invalid signature']);

        // Assert invoice is NOT paid
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_midtrans_webhook_handles_test_notification_simulation()
    {
        $response = $this->postJson(route('webhooks.midtrans'), [
            'order_id' => 'payment_notif_test_G652527386_d24b656a-d938-4334-a819-2237d5d6a4a9',
            'status_code' => '200',
            'gross_amount' => '105000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'dummy_signature',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notification processed successfully']);
    }

    public function test_midtrans_webhook_handles_empty_order_id()
    {
        $response = $this->postJson(route('webhooks.midtrans'), [
            'status_code' => '200',
            'status_message' => 'Success account association',
            'payment_type' => 'gopay',
            'account_id' => '97e6822c-a0bb-4b68-b7eb-116d41a54b42',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notification processed successfully']);
    }
}
