<?php

namespace Tests\Feature;

use App\Jobs\ProcessCustomerSuspension;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Reports\ReceivablesService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Masa tenggang isolir: pelanggan diisolir N hari setelah jatuh tempo invoice (bawaan 0 = sehari setelahnya).
 */
class IsolationGraceDaysTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->customer = Customer::create(['customer_code' => 'C-GR', 'username' => 'c-gr', 'name' => 'Pel', 'phone' => null,
            'billing_type' => 'postpaid', 'billing_method' => 'cycle']);
        $this->customer->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        Invoice::create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-GR-1', 'amount' => 150000,
            'status' => 'unpaid', 'due_date' => now()->subDays(3)->toDateString()]);
    }

    private function grace(int $days): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]))
            ->post(route('settings.isolation.grace'), ['isolir_grace_days' => $days])->assertSessionHas('success');
    }

    public function test_without_grace_overdue_customer_is_suspended(): void
    {
        $this->artisan('app:process-billing');
        Queue::assertPushed(ProcessCustomerSuspension::class, fn ($job) => true);
    }

    public function test_grace_period_delays_suspension_and_preview(): void
    {
        $this->grace(5);
        $this->assertSame(5, Invoice::graceDays());

        $this->artisan('app:process-billing');
        Queue::assertNotPushed(ProcessCustomerSuspension::class);
        $this->assertCount(0, app(ReceivablesService::class)->toBeSuspended(CarbonImmutable::today()));

        // Hari ke-6 setelah jatuh tempo: diisolir
        $this->travel(3)->days();
        $this->assertCount(1, app(ReceivablesService::class)->toBeSuspended(CarbonImmutable::today()));
        $this->artisan('app:process-billing');
        Queue::assertPushed(ProcessCustomerSuspension::class);

        $this->get(route('settings.isolation'))->assertOk()->assertSee('Masa tenggang sebelum isolir');
    }
}
