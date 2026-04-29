<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class ResetUsage extends Command
{
    protected $signature = 'app:reset-usage';
    protected $description = 'Reset current month data usage for all customers';

    public function handle()
    {
        $this->info('🔄 Resetting monthly data usage...');
        
        Customer::query()->update([
            'current_month_usage_gb' => 0,
            'last_usage_sync' => now()
        ]);

        $this->info('✅ All customers usage reset to 0.');
    }
}
