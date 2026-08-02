<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
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

    public function test_xendit_webhook_successfully_updates_invoice_to_paid()
    {
        $callbackToken = 'xendit-callback-token-12345';
        Gateway::create([
            'provider' => 'xendit',
            'type' => 'payment',
            'credentials' => [
                'secret_key' => 'xnd_key',
                'callback_token' => $callbackToken,
            ],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST_XND',
            'username' => 'xendituser',
            'name' => 'Xendit Customer',
            'email' => 'xendit@example.com',
            'phone' => '08123456789',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-XND-001',
            'amount' => 120000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $orderId = $invoice->invoice_number . '-1786523900';

        $response = $this->withHeaders([
            'x-callback-token' => $callbackToken,
        ])->postJson(route('webhooks.xendit'), [
            'id' => 'xendit_inv_id_999',
            'external_id' => $orderId,
            'status' => 'PAID',
            'paid_amount' => 120000,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'OK']);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $customer->refresh();
        $this->assertTrue((bool)$customer->is_active);

        $pgAccount = BankAccount::where('bank_name', 'XENDIT')->first();
        $this->assertNotNull($pgAccount);
    }

    public function test_xendit_webhook_fails_with_invalid_callback_token()
    {
        Gateway::create([
            'provider' => 'xendit',
            'type' => 'payment',
            'credentials' => [
                'secret_key' => 'xnd_key',
                'callback_token' => 'valid-token',
            ],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST_XND_2',
            'username' => 'xendituser2',
            'name' => 'Xendit Customer 2',
            'email' => 'xendit2@example.com',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-XND-002',
            'amount' => 120000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->withHeaders([
            'x-callback-token' => 'invalid-token',
        ])->postJson(route('webhooks.xendit'), [
            'id' => 'xendit_inv_id_999',
            'external_id' => $invoice->invoice_number . '-1786523900',
            'status' => 'PAID',
        ]);

        $response->assertStatus(403);
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }
}
