<?php

namespace App\Services;

use App\Models\PartnerCommission;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class PartnerService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Calculate and record commission for an invoice payment
     */
    public function processPaymentCommission(Invoice $invoice)
    {
        // Check if partner module is enabled
        if (get_setting('enable_partner_module') != '1') {
            return null;
        }

        $customer = $invoice->customer;
        if (!$customer || !$customer->partner_id) {
            return null;
        }

        $partner = $customer->partner;
        if (!$partner) {
            return null;
        }

        // Determine Rate and Type (Hierarchy: Customer -> Partner -> Global)
        $rate = $customer->commission_rate;
        $type = $customer->commission_type;

        if (is_null($rate)) {
            $rate = $partner->commission_rate;
            $type = $partner->commission_type;
        }

        if (is_null($rate)) {
            $rate = get_setting('default_commission_rate', 0);
            $type = get_setting('default_commission_type', 'percentage');
        }

        // Calculate Amount
        $amount = 0;
        $baseAmount = $invoice->amount; // Use total amount paid

        if ($type === 'percentage') {
            $amount = ($rate / 100) * $baseAmount;
        } else {
            $amount = $rate;
        }

        if ($amount <= 0) {
            return null;
        }

        try {
            // Create Commission Record
            $commission = PartnerCommission::create([
                'partner_id' => $partner->id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'base_amount' => $baseAmount,
                'rate' => $rate,
                'type' => $type,
                'status' => 'earned',
            ]);

            // Journal it
            $this->accountingService->recordPartnerCommission($commission);

            return $commission;
        } catch (\Exception $e) {
            Log::error("Failed to process partner commission: " . $e->getMessage());
            return null;
        }
    }
}
