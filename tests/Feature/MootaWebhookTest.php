<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\BankMutation;
use App\Models\User;
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

    private function unpaidInvoice(string $code, string $number, int $amount, ?string $name = null): Invoice
    {
        $customer = Customer::create([
            'customer_code' => $code,
            'username' => strtolower($code),
            'name' => $name ?? 'Pelanggan ' . $code,
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

    private function mutation(int $amount, string $description = 'TRANSFER DARI MOOTA CUSTOMER', ?string $id = null): array
    {
        return [[
            'mutation_id' => $id ?? 'mut_' . $amount,
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

    public function test_resent_mutation_does_not_pay_another_invoice(): void
    {
        $a = $this->unpaidInvoice('C-RA', 'INV-RA-1', 150000, 'Andi Wijaya');
        $b = $this->unpaidInvoice('C-RB', 'INV-RB-1', 150000, 'Budi Santoso');

        $this->postSigned($this->mutation(150000, 'TRF ANDI WIJAYA', 'mut-1'))->assertOk();
        $this->assertSame('paid', $a->fresh()->status);

        // Moota mengirim ulang mutasi yang sama: dulu B (kini satu-satunya bernominal sama) ikut dilunasi
        $this->postSigned($this->mutation(150000, 'TRF ANDI WIJAYA', 'mut-1'))->assertOk();
        $this->assertSame('unpaid', $b->fresh()->status);
        $this->assertSame(1, BankMutation::count());
    }

    public function test_ambiguous_names_are_not_guessed(): void
    {
        $budi = $this->unpaidInvoice('C-N1', 'INV-N1-1', 150000, 'Budi');
        $budiman = $this->unpaidInvoice('C-N2', 'INV-N2-1', 150000, 'Budiman');

        // "BUDIMAN" dulu cocok dengan "Budi" (substring; invoice pertama menang)
        $this->postSigned($this->mutation(150000, 'TRSF E-BANKING BUDIMAN', 'mut-n'))->assertOk();
        $this->assertSame('unpaid', $budi->fresh()->status);
        $this->assertSame('paid', $budiman->fresh()->status);

        // Keterangan tanpa nama -> menunggu manual & tercatat (dulu hanya di log)
        $c = $this->unpaidInvoice('C-N3', 'INV-N3-1', 150000, 'Citra');
        $this->postSigned($this->mutation(150000, 'SETORAN TUNAI', 'mut-x'))->assertOk();
        $this->assertSame('unpaid', $budi->fresh()->status);
        $this->assertSame('unpaid', $c->fresh()->status);
        $this->assertSame('unmatched', BankMutation::where('mutation_id', 'mut-x')->value('status'));
    }

    public function test_invoice_number_wins_but_underpayment_is_held(): void
    {
        $inv = $this->unpaidInvoice('C-IN', 'INV-2026-0042', 200000);
        $this->unpaidInvoice('C-OT', 'INV-2026-0043', 150000);

        $this->postSigned($this->mutation(150000, 'bayar inv-2026-0042', 'mut-u'))->assertOk();
        $this->assertSame('unpaid', $inv->fresh()->status);
        $this->assertStringContainsString('kurang', BankMutation::where('mutation_id', 'mut-u')->value('note'));

        $this->postSigned($this->mutation(200000, 'bayar inv-2026-0042', 'mut-v'))->assertOk();
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_unmatched_mutation_can_be_matched_manually_once(): void
    {
        $x = $this->unpaidInvoice('C-M1', 'INV-M-1', 150000, 'Dewi');
        $y = $this->unpaidInvoice('C-M2', 'INV-M-2', 150000, 'Eka');
        $this->postSigned($this->mutation(150000, 'TRANSFER', 'mut-m'))->assertOk();
        $mutation = BankMutation::sole();
        $this->assertSame('unmatched', $mutation->status);

        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->get(route('bank-mutations.index'))->assertOk()->assertSee('Belum cocok');
        $this->post(route('bank-mutations.match', $mutation), ['invoice_number' => 'INV-M-2'])->assertSessionHas('success');
        $this->assertSame('paid', $y->fresh()->status);
        $this->assertSame('manual', $mutation->fresh()->status);

        // Mutasi yang sama tidak bisa dipakai dua kali
        $this->post(route('bank-mutations.match', $mutation), ['invoice_number' => 'INV-M-1'])->assertSessionHas('error');
        $this->assertSame('unpaid', $x->fresh()->status);

        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]))
            ->get(route('bank-mutations.index'))->assertForbidden();
    }
}
