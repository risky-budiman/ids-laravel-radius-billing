<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Penghapusan piutang macet: jurnal penagihan dibalik, keluar dari piutang, pulih otomatis bila dibayar.
 */
class InvoiceWriteOffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        foreach ([['1104', 'Piutang Pelanggan', 'asset'], ['2102', 'Pendapatan Ditangguhkan', 'liability'], ['4101', 'Pendapatan Internet', 'income'],
                  ['2103', 'Hutang PPN', 'liability'], ['1101', 'Kas Tunai', 'asset']] as [$code, $name, $type]) {
            ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }
        $customer = Customer::create(['customer_code' => 'C-WO', 'username' => 'c-wo', 'name' => 'Pelanggan Macet']);
        $customer->update(['status' => Customer::STATUS_DISMANTLED, 'is_active' => false]);
        $this->invoice = Invoice::create(['customer_id' => $customer->id, 'invoice_number' => 'INV-WO-1', 'amount' => 150000,
            'subtotal' => 150000, 'status' => 'unpaid', 'due_date' => now()->subMonths(4)]);
        $this->actingAs($this->admin);
        (new AccountingService())->recordInvoiceGenerated($this->invoice);
    }

    private function balance(string $code): float
    {
        $id = ChartOfAccount::where('code', $code)->value('id');

        return (float) \App\Models\JournalItem::where('account_id', $id)->sum('debit') - (float) \App\Models\JournalItem::where('account_id', $id)->sum('credit');
    }

    public function test_write_off_reverses_billing_journal_and_leaves_receivables(): void
    {
        $this->assertEquals(150000, $this->balance('1104'));

        $this->post(route('invoices.write-off', $this->invoice), ['reason' => 'Pelanggan pindah, tidak bisa dihubungi'])->assertSessionHas('success');

        $inv = $this->invoice->fresh();
        $this->assertSame('written_off', $inv->status);
        $this->assertSame($this->admin->id, $inv->written_off_by);
        $this->assertEquals(0, $this->balance('1104'), 'piutang di buku hilang');
        $this->assertEquals(0, $this->balance('2102'));
        $this->assertTrue(Journal::where('reference', 'WO-INV-WO-1')->exists());
        $this->assertSame(0, Invoice::where('status', 'unpaid')->count(), 'keluar dari daftar piutang');

        $this->get(route('invoices.index', ['status' => 'written_off']))->assertOk()->assertSee('Dihapus buku');

        // Alasan wajib; tidak bisa dua kali
        $this->post(route('invoices.write-off', $this->invoice), ['reason' => 'lagi'])->assertSessionHas('error');
    }

    public function test_restore_and_late_payment_reinstate_receivable(): void
    {
        $this->post(route('invoices.write-off', $this->invoice), ['reason' => 'Macet']);

        // Pembatalan manual
        $this->delete(route('invoices.write-off.destroy', $this->invoice))->assertSessionHas('success');
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
        $this->assertEquals(150000, $this->balance('1104'));

        // Dihapus lagi, lalu pelanggan membayar: write-off dibatalkan otomatis & pembayaran tercatat
        $this->post(route('invoices.write-off', $this->invoice), ['reason' => 'Macet']);
        $bank = BankAccount::create(['bank_name' => 'KAS', 'account_name' => 'Kas', 'type' => 'cash', 'is_active' => true]);
        app(InvoiceService::class)->markAsPaid($this->invoice->fresh(), $bank->id);

        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertNull($this->invoice->fresh()->written_off_at);
        $this->assertEquals(0, $this->balance('1104'), 'piutang lunas, tidak negatif');
    }

    public function test_cashier_cannot_write_off(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]))
            ->post(route('invoices.write-off', $this->invoice), ['reason' => 'x'])->assertForbidden();
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
    }
}
