<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTemplateRenderTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(array $attrs = []): Invoice
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-TPL-01',
            'username' => 'cust_tpl_01',
            'name' => 'Sinta Lestari',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return Invoice::create(array_merge([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TPL-001',
            'subtotal' => 200000,
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'discount_amount' => 20000,
            'tax_amount' => 0,
            'amount' => 180000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ], $attrs));
    }

    public function test_starter_template_renders_without_period_dates_and_shows_discount(): void
    {
        $invoice = $this->invoice(); // period_start / period_end kosong
        $tpl = InvoiceTemplate::create(['name' => 'Starter', 'format' => 'A4', 'is_default' => true, 'html_content' => InvoiceTemplate::starterHtml()]);

        $html = $tpl->render($invoice);

        $this->assertStringContainsString('Sinta Lestari', $html);
        $this->assertStringContainsString('Diskon', $html);              // baris diskon tampil
        $this->assertStringContainsString('Rp 180.000', $html);
        $this->assertStringContainsString('Belum dibayar', $html);
        $this->assertStringContainsString('/pay/INV-TPL-001', $html);
        $this->assertStringNotContainsString('{', $html);                 // semua placeholder terganti
    }

    public function test_tax_row_hidden_when_no_tax(): void
    {
        $html = strtr('<table>{baris_pajak}</table>', InvoiceTemplate::replacementsFor($this->invoice()));

        $this->assertSame('<table></table>', $html);
    }

    public function test_print_page_renders_with_custom_template(): void
    {
        $invoice = $this->invoice();
        InvoiceTemplate::create(['name' => 'Starter', 'format' => 'A4', 'is_default' => true, 'html_content' => InvoiceTemplate::starterHtml()]);
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('id="customA4Area"', false)
            ->assertSee('Sinta Lestari');
    }

    public function test_editor_preview_uses_real_renderer(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('invoice-templates.preview'), ['html_content' => '{nama_pelanggan}|{baris_diskon}|{rp_total}'])
            ->assertOk()
            ->assertSee('Ahmad Budiman')
            ->assertSee('Rp 360.750');
    }
}
