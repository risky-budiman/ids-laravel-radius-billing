<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\RadiusCoAService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckActivationGracePeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:check-grace-period';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suspend customers who failed to pay installation fee within 1 hour grace period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking activation grace periods...');

        // Find customers who expired their grace period and haven't paid installation
        $expiredCustomers = Customer::where('status', Customer::STATUS_ACTIVE)
            ->whereNotNull('activation_grace_expires_at')
            ->where('activation_grace_expires_at', '<=', now())
            ->whereNull('installation_paid_at')
            ->get();

        if ($expiredCustomers->isEmpty()) {
            $this->info('No expired grace periods found.');
            return;
        }

        $coa = new RadiusCoAService();

        foreach ($expiredCustomers as $customer) {
            $this->warn("Suspending customer: {$customer->name} ({$customer->username}) due to expired grace period.");
            
            // 1. Update Status to Suspended
            $customer->update([
                'status' => Customer::STATUS_SUSPENDED,
                'is_active' => false,
            ]);

            // 2. Disconnect from NAS (Force logout)
            try {
                // Find active sessions
                $activeSessions = \App\Models\Radius\RadAcct::online()
                    ->where('username', $customer->username)
                    ->get();

                foreach ($activeSessions as $session) {
                    $nas = \App\Models\Radius\Nas::where('nasname', $session->nasipaddress)->first();
                    if ($nas) {
                        $coa->disconnect($nas->nasname, $nas->secret, $customer->username);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to disconnect customer {$customer->username} after grace period: " . $e->getMessage());
            }

            $this->info("Customer {$customer->name} has been suspended.");
        }

        $this->info('Grace period check completed.');
    }
}
