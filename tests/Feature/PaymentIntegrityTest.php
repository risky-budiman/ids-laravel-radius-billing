<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Pembayaran tidak boleh tercatat ganda, dan webhook gateway harus membayar penuh.
 */
class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'balance' => 0, 'is_active' => true]);
        $this->customer = Customer::create(['customer_code' => 'C-PAY', 'username' => 'cpay', 'name' => 'Pelanggan Bayar',
            'is_active' => true, 'status' => Customer::STATUS_ACTIVE]);
    }

    private function invoice(string $number, float $amount = 150000): Invoice
    {
        return Invoice::create(['customer_id' => $this->customer->id, 'invoice_number' => $number, 'amount' => $amount,
            'subtotal' => $amount, 'status' => 'unpaid', 'due_date' => now()->addDays(5)]);
    }

    private function cashMutations(string $number): int
    {
        return BankTransaction::where('description', 'like', "%{$number}%")->count();
    }

    public function test_paying_the_same_invoice_twice_from_stale_instances_records_one_mutation(): void
    {
        $this->invoice('INV-DUP-1');
        // Dua proses (mis. webhook dikirim ulang + kasir) memuat invoice sebelum salah satunya menyimpan
        $a = Invoice::where('invoice_number', 'INV-DUP-1')->first();
        $b = Invoice::where('invoice_number', 'INV-DUP-1')->first();

        app(InvoiceService::class)->markAsPaid($a);
        app(InvoiceService::class)->markAsPaid($b);

        $this->assertSame(1, $this->cashMutations('INV-DUP-1'), 'pembayaran tercatat ganda di Kas & Bank');
        $this->assertEquals(150000, (float) BankAccount::sum('balance'));
    }

    public function test_xendit_webhook_with_less_than_the_invoice_amount_is_not_marked_paid(): void
    {
        Gateway::create(['provider' => 'xendit', 'type' => 'payment', 'is_active' => true, 'credentials' => ['callback_token' => 'tok-123']]);
        $invoice = $this->invoice('INV-AMT-1', 200000);

        $this->withHeader('x-callback-token', 'tok-123')->postJson('/webhooks/xendit', [
            'external_id' => 'INV-AMT-1-1700000000', 'status' => 'PAID', 'paid_amount' => 150000, 'amount' => 150000,
        ])->assertOk();

        $this->assertSame('unpaid', $invoice->fresh()->status, 'dibayar kurang tetapi dianggap lunas');
        $this->assertStringContainsString('perlu dicek', (string) $invoice->fresh()->notes);

        // Nominal penuh -> lunas
        $this->withHeader('x-callback-token', 'tok-123')->postJson('/webhooks/xendit', [
            'external_id' => 'INV-AMT-1-1700000001', 'status' => 'PAID', 'paid_amount' => 200000,
        ])->assertOk();
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_midtrans_webhook_checks_gross_amount(): void
    {
        Gateway::create(['provider' => 'midtrans', 'type' => 'payment', 'is_active' => true, 'credentials' => ['server_key' => 'srv-key']]);
        $invoice = $this->invoice('INV-AMT-2', 200000);
        $payload = fn (string $gross) => [
            'order_id' => 'INV-AMT-2-1700000000', 'status_code' => '200', 'gross_amount' => $gross,
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', 'INV-AMT-2-1700000000' . '200' . $gross . 'srv-key'),
        ];

        $this->postJson('/webhooks/midtrans', $payload('100000.00'))->assertOk();
        $this->assertSame('unpaid', $invoice->fresh()->status);

        $this->postJson('/webhooks/midtrans', $payload('200000.00'))->assertOk();
        $this->assertSame('paid', $invoice->fresh()->status);
    }
}
