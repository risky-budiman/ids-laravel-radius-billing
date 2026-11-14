<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Webhook pembayaran tidak boleh menandai invoice lunas bila gateway
 * belum dikonfigurasi (tidak ada kredensial untuk memverifikasi pengirim).
 */
class WebhookFailClosedTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'customer_code' => 'CUST-WH-01',
            'username' => 'cust_wh_01',
            'name' => 'Pelanggan Webhook',
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $this->invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-WH-001',
            'amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_midtrans_rejected_without_configured_server_key(): void
    {
        $this->postJson(route('webhooks.midtrans'), [
            'order_id' => 'INV-WH-001-123',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'apa-saja',
        ])->assertForbidden();

        $this->assertEquals('unpaid', $this->invoice->fresh()->status);
    }

    public function test_xendit_rejected_without_configured_callback_token(): void
    {
        $this->postJson(route('webhooks.xendit'), [
            'external_id' => 'INV-WH-001',
            'status' => 'PAID',
            'amount' => 150000,
        ])->assertForbidden();

        $this->assertEquals('unpaid', $this->invoice->fresh()->status);
    }

    public function test_duitku_rejected_without_configured_api_key(): void
    {
        $this->postJson(route('webhooks.duitku'), [
            'merchantCode' => 'D0001',
            'amount' => '150000',
            'merchantOrderId' => 'INV-WH-001',
            'resultCode' => '00',
            'signature' => 'apa-saja',
        ])->assertForbidden();

        $this->assertEquals('unpaid', $this->invoice->fresh()->status);
    }
}
