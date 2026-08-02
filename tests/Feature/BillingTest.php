<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Jobs\GenerateCustomerInvoice;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->package = Package::create([
            'name' => 'Premium Package',
            'price' => 100000.00,
            'speed_limit_down' => 10,
            'speed_limit_up' => 10,
        ]);
    }

    /**
     * Test syncBillingDates for postpaid cycle across months of different lengths.
     */
    public function test_postpaid_cycle_dates_for_various_months()
    {
        $testCases = [
            '2026-01-31' => ['expected_next' => '2026-02-01', 'expected_due' => '2026-02-20'],
            '2026-02-28' => ['expected_next' => '2026-03-01', 'expected_due' => '2026-03-20'],
            '2028-02-29' => ['expected_next' => '2028-03-01', 'expected_due' => '2028-03-20'], // leap year
            '2026-04-30' => ['expected_next' => '2026-05-01', 'expected_due' => '2026-05-20'],
            '2026-08-31' => ['expected_next' => '2026-09-01', 'expected_due' => '2026-09-20'],
        ];

        foreach ($testCases as $currentDate => $expected) {
            Carbon::setTestNow(Carbon::parse($currentDate));

            $customer = Customer::factory()->make([
                'billing_type' => 'postpaid',
                'billing_method' => 'cycle',
                'billing_due_day' => 20,
            ]);

            $customer->syncBillingDates();

            $this->assertEquals($expected['expected_next'], $customer->billing_next_date->toDateString());
            $this->assertEquals($expected['expected_due'], $customer->billing_due_date->toDateString());
        }

        Carbon::setTestNow(); // Reset time mock
    }

    /**
     * Test that syncBillingDates correctly catches up sequentially and does not skip months
     * when the system scheduler runs late.
     */
    public function test_postpaid_cycle_does_not_skip_months_when_scheduler_is_delayed()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 00:00:00'));

        $customer = Customer::factory()->make([
            'billing_type' => 'postpaid',
            'billing_method' => 'cycle',
            'billing_due_day' => 20,
            'billing_next_date' => Carbon::parse('2026-06-01'), // Should have billed on June 1st but delayed
        ]);

        // When syncing dates, it should advance from billing_next_date (2026-06-01) to 2026-07-01
        $customer->syncBillingDates();

        $this->assertEquals('2026-07-01', $customer->billing_next_date->toDateString());
        $this->assertEquals('2026-07-20', $customer->billing_due_date->toDateString());
    }

    /**
     * Test prorata calculation logic for different activation dates.
     */
    public function test_postpaid_cycle_prorata_for_various_activation_dates()
    {
        $activationTests = [
            // Activated in July (31 days)
            [
                'activated_at' => '2026-07-15 10:00:00',
                'billing_next' => '2026-08-01',
                'expected_subtotal' => (17 / 31) * 100000.00,
                'expected_notes' => 'Tagihan pemakaian proporsional (Prorata) periode 15/07 s/d 31/07'
            ],
            // Activated on 1st day of July
            [
                'activated_at' => '2026-07-01 09:00:00',
                'billing_next' => '2026-08-01',
                'expected_subtotal' => 100000.00,
                'expected_notes' => 'Tagihan pemakaian proporsional (Prorata) periode 01/07 s/d 31/07'
            ],
            // Activated on the last day of July (tests the startOfDay/endOfDay bug fix!)
            [
                'activated_at' => '2026-07-31 18:30:00',
                'billing_next' => '2026-08-01',
                'expected_subtotal' => (1 / 31) * 100000.00,
                'expected_notes' => 'Tagihan pemakaian proporsional (Prorata) periode 31/07 s/d 31/07'
            ],
            // Activated in non-leap Feb (28 days)
            [
                'activated_at' => '2026-02-15 08:00:00',
                'billing_next' => '2026-03-01',
                'expected_subtotal' => (14 / 28) * 100000.00,
                'expected_notes' => 'Tagihan pemakaian proporsional (Prorata) periode 15/02 s/d 28/02'
            ],
            // Activated in leap Feb (29 days)
            [
                'activated_at' => '2028-02-15 12:00:00',
                'billing_next' => '2028-03-01',
                'expected_subtotal' => (15 / 29) * 100000.00,
                'expected_notes' => 'Tagihan pemakaian proporsional (Prorata) periode 15/02 s/d 29/02'
            ]
        ];

        foreach ($activationTests as $test) {
            // 1. Mock the time to activation date
            Carbon::setTestNow(Carbon::parse($test['activated_at']));

            // Disable tax for simplicity in matching exact subtotals
            $customer = Customer::factory()->create([
                'package_id' => $this->package->id,
                'billing_type' => 'postpaid',
                'billing_method' => 'cycle',
                'billing_due_day' => 20,
                'activated_at' => Carbon::parse($test['activated_at']),
                'is_active' => true,
                'status' => Customer::STATUS_ACTIVE,
                'use_tax' => false,
            ]);

            // Sync dates on activation (sets billing_next_date and billing_due_date)
            $customer->syncBillingDates();

            // 2. Mock the time to the scheduled billing next date
            Carbon::setTestNow(Carbon::parse($test['billing_next']));

            // Dispatch and execute job synchronously
            GenerateCustomerInvoice::dispatchSync($customer);

            // Fetch the generated invoice
            $invoice = Invoice::where('customer_id', $customer->id)->first();

            $this->assertNotNull($invoice);
            $this->assertEqualsWithDelta($test['expected_subtotal'], $invoice->subtotal, 0.01);
            $this->assertEquals($test['expected_notes'], $invoice->notes);

            // Clean up for next iteration
            $invoice->delete();
            $customer->delete();
        }

        Carbon::setTestNow(); // Reset time mock
    }
}
