<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Portal invoice publik: link resmi tetap berfungsi, ID tidak bisa ditebak.
 */
class InvoicePortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'customer_code' => 'CUST-PRT-01',
            'username' => 'cust_prt_01',
            'name' => 'Budi Rahasia',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PRT-7F3A9C',
            'amount' => 150000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_whatsapp_link_by_invoice_number_still_works(): void
    {
        $this->get('/pay/' . $this->invoice->invoice_number)
            ->assertOk()
            ->assertSee('Budi Rahasia');
    }

    public function test_guessing_numeric_id_is_not_allowed(): void
    {
        $this->get('/pay/' . $this->invoice->id)->assertNotFound();
        $this->get('/portal/invoice/' . $this->invoice->id)->assertNotFound();
    }

    public function test_signed_link_by_id_from_customer_portal_still_works(): void
    {
        $this->get(URL::signedRoute('portal.invoice', $this->invoice->id))
            ->assertOk()
            ->assertSee('Budi Rahasia');
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $url = URL::signedRoute('portal.invoice', $this->invoice->id) . 'x';

        $this->get($url)->assertNotFound();
    }

    public function test_gateway_pay_link_requires_signature(): void
    {
        $this->get('/portal/invoice/' . $this->invoice->id . '/pay?gateway=midtrans')->assertForbidden();
    }
}
