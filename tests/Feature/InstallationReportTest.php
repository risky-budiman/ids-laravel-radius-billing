<?php

namespace Tests\Feature;

use App\Jobs\SendCustomWhatsappMessageJob;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Services\InstallationService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * BA instalasi (tanda tangan digital), invoice instalasi wajib & batas bayar — semuanya opsional.
 */
class InstallationReportTest extends TestCase
{
    use RefreshDatabase;

    private User $teknisi;
    private InventoryItem $modemItem;

    /** PNG 1x1 valid */
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        Storage::fake('public');
        $this->teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true, 'name' => 'Teknisi Satu']);
        $cat = InventoryCategory::create(['name' => 'Perangkat']);
        $this->modemItem = InventoryItem::create(['category_id' => $cat->id, 'name' => 'ONU ZTE F660', 'unit' => 'pcs', 'track_serial' => true]);
    }

    private function settings(array $values): void
    {
        app(InstallationService::class)->saveSettings($values);
    }

    private function newCustomer(string $code, float $fee = 250000): Customer
    {
        $pkg = Package::create(['name' => "Paket $code", 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M']);

        return Customer::create(['customer_code' => $code, 'username' => strtolower($code), 'name' => "Pelanggan $code", 'phone' => '081234567890',
            'package_id' => $pkg->id, 'installation_fee' => $fee, 'is_active' => false, 'status' => Customer::STATUS_NEW,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle'])->fresh();
    }

    public function test_defaults_keep_legacy_behaviour_without_ba_or_invoice(): void
    {
        $c = $this->newCustomer('C-INS-0');
        Sanctum::actingAs($this->teknisi);

        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'cash'])
            ->assertOk()->assertJsonPath('installation_invoice', null)->assertJsonPath('installation_report', null);

        $this->assertSame(0, InstallationReport::count());
        $this->assertSame(0, Invoice::where('customer_id', $c->id)->count());
        $this->assertTrue(BankTransaction::where('description', 'like', '%[Instalasi]%C-INS-0%')->exists());
    }

    public function test_cash_on_site_issues_paid_invoice_and_ba_snapshot(): void
    {
        $this->settings(['activation_ba_enabled' => '1', 'installation_invoice_enabled' => '1']);
        $c = $this->newCustomer('C-INS-1');
        $modem = InventoryStock::create(['inventory_item_id' => $this->modemItem->id, 'serial_number' => 'ZTEG22223333', 'status' => 'ready']);
        Sanctum::actingAs($this->teknisi);

        $res = $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'cash', 'modem_stock_id' => $modem->id])
            ->assertOk()->assertJsonPath('installation_invoice.status', 'paid')
            ->assertJsonPath('installation_report.fully_signed', false);

        $inv = Invoice::where('customer_id', $c->id)->sole();
        $this->assertStringStartsWith('INV-INST', $inv->invoice_number);
        $this->assertSame('paid', $inv->status);
        $c->refresh();
        $this->assertNotNull($c->installation_paid_at);
        $this->assertNull($c->activation_grace_expires_at);
        // Tidak dobel: tidak ada setoran "[Instalasi]" gaya lama di samping pembayaran invoice
        $this->assertFalse(BankTransaction::where('description', 'like', '%[Instalasi]%C-INS-1%')->exists());

        $report = InstallationReport::sole();
        $this->assertMatchesRegularExpression('#^BA/\d{4}/\d{2}/0001$#', $report->number);
        $this->assertSame($res->json('installation_report.number'), $report->number);
        $this->assertSame('ZTEG22223333', $report->snapshot['devices'][0]['serial_number']);
        $this->assertSame('Teknisi Satu', $report->snapshot['technician']);
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, fn ($job) => str_contains($job->message, "/ba/") && str_contains($job->message, "BA/"));
    }

    public function test_unpaid_installation_gets_deadline_reminder_then_isolation_and_payment_restores(): void
    {
        $this->settings(['installation_invoice_enabled' => '1', 'installation_grace_hours' => '24', 'installation_reminder_hours' => '3']);
        $c = $this->newCustomer('C-INS-2');
        Sanctum::actingAs($this->teknisi);

        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'pg'])
            ->assertOk()->assertJsonPath('installation_invoice.status', 'unpaid');
        $c->refresh();
        $this->assertSame(Customer::STATUS_ACTIVE, $c->status, 'internet tetap aktif selama batas bayar');
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $c->activation_grace_expires_at->timestamp, 5);

        // Belum waktunya pengingat
        $this->artisan('customer:check-grace-period')->assertSuccessful();
        $this->assertNull($c->fresh()->installation_reminder_sent_at);

        // 2 jam sebelum batas -> pengingat sekali saja
        $this->travel(22)->hours();
        Queue::fake();
        $this->artisan('customer:check-grace-period')->assertSuccessful();
        $this->artisan('customer:check-grace-period')->assertSuccessful();
        Queue::assertPushed(SendCustomWhatsappMessageJob::class, 1);
        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);

        // Lewat batas -> diisolir
        $this->travel(3)->hours();
        $this->artisan('customer:check-grace-period')->assertSuccessful();
        $this->assertSame(Customer::STATUS_SUSPENDED, $c->fresh()->status);

        // Dibayar -> aktif kembali, batas dihapus
        $inv = Invoice::where('customer_id', $c->id)->where('invoice_number', 'like', 'INV-INST%')->sole();
        app(InvoiceService::class)->markAsPaid($inv);
        $c->refresh();
        $this->assertSame(Customer::STATUS_ACTIVE, $c->status);
        $this->assertNotNull($c->installation_paid_at);
        $this->assertNull($c->activation_grace_expires_at);
    }

    public function test_zero_grace_hours_never_isolates(): void
    {
        $this->settings(['installation_invoice_enabled' => '1', 'installation_grace_hours' => '0']);
        $c = $this->newCustomer('C-INS-3');
        Sanctum::actingAs($this->teknisi);
        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'pg'])->assertOk();

        $this->assertNull($c->fresh()->activation_grace_expires_at);
        $this->travel(10)->days();
        $this->artisan('customer:check-grace-period')->assertSuccessful();
        $this->assertSame(Customer::STATUS_ACTIVE, $c->fresh()->status);
    }

    public function test_free_installation_issues_no_invoice(): void
    {
        $this->settings(['installation_invoice_enabled' => '1']);
        $c = $this->newCustomer('C-INS-4', 0);
        Sanctum::actingAs($this->teknisi);
        $this->postJson("/api/v1/admin/customers/{$c->id}/activate", ['payment_method' => 'pg'])
            ->assertOk()->assertJsonPath('installation_invoice', null);
        $this->assertNull($c->fresh()->activation_grace_expires_at);
    }

    public function test_signing_via_api_and_public_link(): void
    {
        $this->settings(['activation_ba_enabled' => '1']);
        $c = $this->newCustomer('C-INS-5', 0);
        Sanctum::actingAs($this->teknisi);
        $id = $this->postJson("/api/v1/admin/customers/{$c->id}/activate", [])->assertOk()->json('installation_report.id');
        $report = InstallationReport::findOrFail($id);

        $this->getJson("/api/v1/admin/customers/{$c->id}/installation-report")->assertOk()
            ->assertJsonPath('installation_report.number', $report->number)
            ->assertJsonPath('installation_report.snapshot.customer.customer_code', 'C-INS-5');

        // Teknisi lewat API
        $this->postJson("/api/v1/admin/installation-reports/{$id}/sign", ['role' => 'technician', 'signature' => self::PNG])
            ->assertOk()->assertJsonPath('installation_report.fully_signed', false);
        $this->postJson("/api/v1/admin/installation-reports/{$id}/sign", ['role' => 'technician', 'signature' => self::PNG])
            ->assertStatus(422);
        $this->postJson("/api/v1/admin/installation-reports/{$id}/sign", ['role' => 'customer', 'signature' => 'data:image/png;base64,bukan-gambar'])
            ->assertStatus(422);

        // Pelanggan lewat tautan publik (tanpa login)
        $this->app['auth']->forgetGuards();
        $url = "/ba/{$report->sign_token}";
        $this->get($url)->assertOk()->assertSee($report->number)->assertSee('Tanda tangani')->assertHeader('X-Robots-Tag', 'noindex');
        $this->post("$url/sign", ['signature' => self::PNG, 'signer_name' => 'Budi'])->assertSessionHasErrors('agree');
        $this->post("$url/sign", ['signature' => self::PNG, 'signer_name' => 'Budi', 'agree' => '1'])->assertRedirect($url);

        $report->refresh();
        $this->assertTrue($report->isFullySigned());
        $this->assertSame('Budi', $report->customer_signer_name);
        $this->assertSame('tautan', $report->sign_meta['customer']['via'] ?? null);
        Storage::disk('public')->assertExists($report->customer_signature);
        $this->get($url)->assertOk()->assertDontSee('Tanda tangani')->assertSee('sudah Anda tanda tangani');

        $this->get('/ba/' . str_repeat('x', 48))->assertNotFound();
    }

    public function test_staff_pages_and_settings(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin);

        $this->get(route('settings.installation'))->assertOk()->assertSee('Pengaturan Instalasi');
        $this->post(route('settings.installation.update'), [
            'activation_ba_enabled' => '1', 'installation_invoice_enabled' => '1',
            'installation_grace_hours' => '48', 'installation_reminder_hours' => '6', 'installation_ba_terms' => '',
        ])->assertRedirect(route('settings.installation'));
        $svc = app(InstallationService::class);
        $this->assertTrue($svc->baEnabled());
        $this->assertTrue($svc->invoiceEnabled());
        $this->assertSame(48, $svc->graceHours());
        $this->assertSame(InstallationService::DEFAULT_TERMS, $svc->setting('installation_ba_terms'));

        // Aktivasi web -> diarahkan ke halaman BA
        $c = $this->newCustomer('C-INS-6');
        $this->post(route('customers.activate.store', $c), ['payment_method' => 'transfer'])
            ->assertRedirect(route('installation-reports.show', InstallationReport::sole()));
        $report = InstallationReport::sole();
        $this->get(route('installation-reports.show', $report))->assertOk()->assertSee($report->number)->assertSee('Simpan tanda tangan pelanggan');
        $this->post(route('installation-reports.sign', $report), ['role' => 'customer', 'signature' => self::PNG, 'signer_name' => 'Ani'])
            ->assertSessionHas('success');
        $this->assertSame('Ani', $report->fresh()->customer_signer_name);

        // Kasir/sales tidak bisa membuka pengaturan
        $this->actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        $this->get(route('settings.installation'))->assertForbidden();
    }
}
