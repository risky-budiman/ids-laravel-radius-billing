<?php

namespace Tests\Feature;

use App\Jobs\ProcessCustomerSuspension;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Services\AutoSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Sakelar isolir otomatis: menahan isolir tunggakan, renewal & batas bayar instalasi, termasuk job yang sudah
 * antre; nonaktif otomatis saat update di server yang sudah berisi pelanggan.
 */
class AutoSuspensionSwitchTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create(['customer_code' => 'C-AS', 'username' => 'c-as', 'name' => 'Penunggak', 'phone' => null,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle']);
        $this->customer->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        $this->invoice = Invoice::create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-AS-1', 'amount' => 150000,
            'status' => 'unpaid', 'due_date' => now()->subDays(3)->toDateString()]);
    }

    public function test_fresh_install_is_enabled_and_switch_stops_billing_suspensions(): void
    {
        $this->assertTrue(AutoSuspension::enabled());   // tanpa baris pengaturan = aktif

        Queue::fake();
        AutoSuspension::setEnabled(false);
        $this->artisan('app:process-billing')->expectsOutputToContain('Isolir otomatis NONAKTIF')->assertSuccessful();
        Queue::assertNotPushed(ProcessCustomerSuspension::class);

        // dry-run tetap menampilkan siapa yang akan diisolir
        $this->artisan('app:process-billing --dry-run')->expectsOutputToContain('Would dispatch suspension for c-as');

        AutoSuspension::setEnabled(true);
        $this->artisan('app:process-billing');
        Queue::assertPushed(ProcessCustomerSuspension::class);
    }

    public function test_queued_job_respects_switch_and_skips_customers_who_paid(): void
    {
        $run = fn () => app()->call([new ProcessCustomerSuspension($this->customer->fresh(), 'uji'), 'handle']);

        AutoSuspension::setEnabled(false);
        $run();
        $this->assertSame(Customer::STATUS_ACTIVE, $this->customer->fresh()->status);

        AutoSuspension::setEnabled(true);
        $this->invoice->update(['status' => 'paid', 'paid_at' => now()]);   // lunas selagi job antre
        $run();
        $this->assertSame(Customer::STATUS_ACTIVE, $this->customer->fresh()->status);

        $this->invoice->update(['status' => 'unpaid', 'paid_at' => null]);
        $run();
        $this->assertSame(Customer::STATUS_SUSPENDED, $this->customer->fresh()->status);
    }

    public function test_installation_deadline_respects_switch(): void
    {
        $this->invoice->update(['status' => 'paid']);
        $this->customer->forceFill(['activation_grace_expires_at' => now()->subHour(), 'installation_paid_at' => null])->saveQuietly();

        AutoSuspension::setEnabled(false);
        $this->artisan('customer:check-grace-period')->expectsOutputToContain('Isolir otomatis NONAKTIF');
        $this->assertSame(Customer::STATUS_ACTIVE, $this->customer->fresh()->status);
    }

    public function test_upgrade_migration_turns_it_off_only_on_servers_with_customers(): void
    {
        $migration = require database_path('migrations/2026_11_17_000002_add_auto_suspension_switch.php');

        Setting::where('key', AutoSuspension::KEY)->delete();
        $migration->up();   // ada pelanggan → nonaktif
        $this->assertFalse(AutoSuspension::enabled());

        Setting::where('key', AutoSuspension::KEY)->update(['value' => '1']);
        $migration->up();   // pilihan yang sudah ada tidak ditimpa
        $this->assertTrue(AutoSuspension::enabled());

        Setting::where('key', AutoSuspension::KEY)->delete();
        Invoice::query()->delete();
        Customer::query()->forceDelete();
        $migration->up();   // instalasi baru → tetap aktif
        $this->assertTrue(AutoSuspension::enabled());
    }

    public function test_settings_page_toggle_preview_and_access(): void
    {
        AutoSuspension::setEnabled(false);
        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($root)->get(route('settings.isolation'))->assertOk()
            ->assertSee('Isolir otomatis')->assertSee('Nonaktif')->assertSee('Penunggak')->assertSee('Aktifkan isolir otomatis');

        $this->post(route('settings.isolation.auto'), ['enabled' => 1])->assertSessionHas('success', fn ($m) => str_contains($m, '1 pelanggan memenuhi syarat'));
        $this->assertTrue(AutoSuspension::enabled());
        $this->assertTrue(ActivityLog::where('action', 'isolir_auto_enabled')->exists());

        $this->post(route('settings.isolation.auto'), ['enabled' => 0])->assertSessionHas('success');
        $this->assertFalse(AutoSuspension::enabled());

        // Piutang memberi tahu bahwa isolir otomatis nonaktif
        $this->get(route('receivables.index'))->assertOk()->assertSee('Isolir otomatis sedang nonaktif');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('settings.isolation.auto'), ['enabled' => 1])->assertForbidden();
        $this->assertFalse(AutoSuspension::enabled());
    }
}
