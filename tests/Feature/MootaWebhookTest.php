<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MootaWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'moota-webhook-secret-test';

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\User::create([
            'id' => 1,
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        Gateway::create([
            'type' => 'payment',
            'provider' => 'moota',
            'is_active' => true,
            'credentials' => ['api_token' => 'moota-api-token', 'webhook_secret' => self::SECRET],
        ]);
    }

    /** Kirim webhook dengan header Signature = HMAC-SHA256(raw body, secret). */
    private function postSigned(array $payload, ?string $secret = self::SECRET)
    {
        $headers = $secret === null ? [] : ['Signature' => hash_hmac('sha256', json_encode($payload), $secret)];

        return $this->postJson(route('webhooks.moota'), $payload, $headers);
    }

    private function unpaidInvoice(string $code, string $number, int $amount): Invoice
    {
        $customer = Customer::create([
            'customer_code' => $code,
            'username' => strtolower($code),
            'name' => 'Pelanggan ' . $code,
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        return Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => $number,
            'amount' => $amount,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);
    }

    private function mutation(int $amount, string $description = 'TRANSFER DARI MOOTA CUSTOMER'): array
    {
        return [[
            'mutation_id' => 'mut_' . $amount,
            'amount' => $amount,
            'type' => 'CR',
            'description' => $description,
            'bank_id' => 'b_1',
        ]];
    }

    public function test_moota_webhook_successfully_reconciles_single_matching_amount()
    {
        $invoice = $this->unpaidInvoice('CUST_MOO', 'INV-MOO-001', 150382);

        $this->postSigned($this->mutation(150382))->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertTrue((bool) $invoice->customer->fresh()->is_active);
        $this->assertNotNull(BankAccount::where('bank_name', 'MOOTA')->first());
    }

    public function test_moota_webhook_disambiguates_multiple_matching_amounts_using_description()
    {
        $invoice1 = $this->unpaidInvoice('CUST_MOO_A', 'INV-MOO-A1', 150000);
        $invoice2 = $this->unpaidInvoice('CUST_MOO_B', 'INV-MOO-B2', 150000);

        $this->postSigned($this->mutation(150000, 'TRF DARI BOB MOOTA INV-MOO-B2'))->assertStatus(200);

        $this->assertEquals('paid', $invoice2->fresh()->status);
        $this->assertEquals('unpaid', $invoice1->fresh()->status);
    }

    public function test_moota_webhook_without_signature_is_rejected()
    {
        $invoice = $this->unpaidInvoice('CUST_MOO_X', 'INV-MOO-X1', 175555);

        $this->postSigned($this->mutation(175555), secret: null)->assertForbidden();

        $this->assertEquals('unpaid', $invoice->fresh()->status);
    }

    public function test_moota_webhook_with_wrong_signature_is_rejected()
    {
        $invoice = $this->unpaidInvoice('CUST_MOO_Y', 'INV-MOO-Y1', 175556);

        $this->postSigned($this->mutation(175556), secret: 'tebakan-penyerang')->assertForbidden();

        $this->assertEquals('unpaid', $invoice->fresh()->status);
    }

    public function test_moota_webhook_is_rejected_when_gateway_not_configured()
    {
        Gateway::where('provider', 'moota')->delete();
        $invoice = $this->unpaidInvoice('CUST_MOO_Z', 'INV-MOO-Z1', 175557);

        $this->postSigned($this->mutation(175557))->assertForbidden();

        $this->assertEquals('unpaid', $invoice->fresh()->status);
    }

    public function test_legacy_config_uses_api_token_as_secret()
    {
        Gateway::where('provider', 'moota')->update(['credentials' => json_encode(['api_token' => 'legacy-token'])]);
        $invoice = $this->unpaidInvoice('CUST_MOO_L', 'INV-MOO-L1', 175558);

        $this->postSigned($this->mutation(175558), secret: 'legacy-token')->assertOk();

        $this->assertEquals('paid', $invoice->fresh()->status);
    }
}
