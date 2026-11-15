<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceWhatsappJob;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pengingat tagihan WhatsApp H-3 / H-1 / hari H.
 */
class InvoiceReminderTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(9, 0));
        WhatsappTemplate::updateOrCreate(['type' => 'invoice_reminder'], [
            'name' => 'Pengingat', 'is_active' => true,
            'message' => 'Halo {name}, tagihan {invoice_number} Rp {amount} jatuh tempo {due_date}. {payment_link}',
        ]);
    }

    private function invoiceDue(string $due, array $customer = [], string $number = null, string $status = 'unpaid'): Invoice
    {
        $n = ++$this->seq;
        $c = Customer::create(array_merge([
            'customer_code' => "C-REM-$n", 'username' => "rem$n", 'name' => "Pelanggan $n", 'phone' => '0812000000' . $n,
            'is_active' => true, 'status' => Customer::STATUS_ACTIVE,
        ], $customer));
        Customer::whereKey($c->id)->update(['status' => $customer['status'] ?? Customer::STATUS_ACTIVE, 'is_active' => true]);

        return Invoice::create(['customer_id' => $c->id, 'invoice_number' => $number ?? "INV-REM-$n", 'amount' => 150000, 'subtotal' => 150000,
            'status' => $status, 'due_date' => $due]);
    }

    public function test_reminders_go_out_on_h3_h1_and_due_day_once(): void
    {
        Queue::fake();
        $h3 = $this->invoiceDue('2026-11-13');
        $h1 = $this->invoiceDue('2026-11-11');
        $h0 = $this->invoiceDue('2026-11-10');
        $this->invoiceDue('2026-11-12');                                        // H-2: tidak dijadwalkan
        $this->invoiceDue('2026-11-13', [], null, 'paid');                      // sudah lunas
        $this->invoiceDue('2026-11-13', [], 'INV-BOOST-77');                    // booster
        $this->invoiceDue('2026-11-13', ['phone' => null]);                     // tanpa HP
        $this->invoiceDue('2026-11-13', ['status' => Customer::STATUS_SUSPENDED]); // sudah diisolir

        $this->artisan('app:send-invoice-reminders')->assertSuccessful();

        Queue::assertPushed(SendInvoiceWhatsappJob::class, 3);
        foreach ([$h3, $h1, $h0] as $inv) {
            Queue::assertPushed(SendInvoiceWhatsappJob::class, fn ($j) => $j->invoiceId === $inv->id && $j->templateType === 'invoice_reminder');
        }

        // Dijalankan ulang di hari yang sama -> tidak terkirim dua kali
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        Queue::assertPushed(SendInvoiceWhatsappJob::class, 3);
    }

    public function test_days_are_configurable_and_disabled_template_sends_nothing(): void
    {
        Queue::fake();
        Setting::updateOrCreate(['key' => 'invoice_reminder_days'], ['value' => '2, -1']);
        Cache::forget('app_settings');
        $h2 = $this->invoiceDue('2026-11-12');
        $late = $this->invoiceDue('2026-11-09');
        $this->invoiceDue('2026-11-13');

        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        Queue::assertPushed(SendInvoiceWhatsappJob::class, 2);

        WhatsappTemplate::where('type', 'invoice_reminder')->update(['is_active' => false]);
        $this->invoiceDue('2026-11-12');
        $this->artisan('app:send-invoice-reminders')->assertSuccessful();
        Queue::assertPushed(SendInvoiceWhatsappJob::class, 2);
    }

    public function test_job_uses_reminder_template_and_skips_if_paid_meanwhile(): void
    {
        $inv = $this->invoiceDue('2026-11-13');
        $sent = [];
        $this->mock(WhatsAppService::class, function ($m) use (&$sent) {
            $m->shouldReceive('sendMessage')->andReturnUsing(function ($phone, $msg) use (&$sent) {
                $sent[] = $msg;

                return true;
            });
        });

        (new SendInvoiceWhatsappJob($inv->id, null, 'invoice_reminder'))->handle(app(WhatsAppService::class));
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('jatuh tempo 13-11-2026', $sent[0]);
        $this->assertStringContainsString('/pay/' . $inv->invoice_number, $sent[0]);

        $inv->update(['status' => 'paid']);
        (new SendInvoiceWhatsappJob($inv->id, null, 'invoice_reminder'))->handle(app(WhatsAppService::class));
        $this->assertCount(1, $sent, 'sudah lunas saat antre: pengingat tidak dikirim');
    }

    public function test_reminder_days_can_be_set_from_the_template_page(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $tpl = WhatsappTemplate::where('type', 'invoice_reminder')->first();

        $this->actingAs($admin)->get(route('whatsapp-templates.edit', $tpl))->assertOk()->assertSee('Kirim pengingat pada');
        $this->actingAs($admin)->put(route('whatsapp-templates.update', $tpl), [
            'message' => $tpl->message, 'is_active' => '1', 'reminder_days' => '5, 2,0',
        ])->assertRedirect();

        Cache::forget('app_settings');
        $this->assertSame([5, 2, 0], \App\Console\Commands\SendInvoiceReminders::reminderDays());

        $this->actingAs($admin)->put(route('whatsapp-templates.update', $tpl), [
            'message' => $tpl->message, 'is_active' => '1', 'reminder_days' => 'tiga hari',
        ])->assertSessionHasErrors('reminder_days');
    }

    public function test_reminder_is_scheduled_daily(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($e) => str_contains($e->command ?? '', 'app:send-invoice-reminders') && $e->expression === '0 9 * * *'));
    }
}
