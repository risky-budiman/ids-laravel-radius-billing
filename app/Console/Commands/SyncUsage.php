<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadReply;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUsage extends Command
{
    protected $signature = 'app:sync-usage';
    protected $description = 'Sync data usage from radacct and enforce FUP speed limits';

    public function handle()
    {
        // 1. Cek apakah modul FUP aktif
        if (get_setting('enable_fup_module') != '1') {
            $this->info('FUP Module is disabled globally.');
            return;
        }

        $this->info('🚀 Starting Usage Sync & FUP Enforcement...');
        
        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $endOfMonth = now()->endOfMonth()->toDateTimeString();

        // 2. Ambil semua pelanggan aktif yang punya paket dengan FUP
        $customers = Customer::where('is_active', true)
            ->whereHas('package', function($q) {
                $q->where('enable_fup', true);
            })
            ->with('package')
            ->get();

        foreach ($customers as $customer) {
            // Hitung total pemakaian bulan ini (Input + Output Octets)
            // Octets ke GB: Octets / 1024 / 1024 / 1024
            $totalOctets = RadAcct::where('username', $customer->username)
                ->where('acctstarttime', '>=', $startOfMonth)
                ->sum(\DB::raw('acctinputoctets + acctoutputoctets'));

            $usageGb = round($totalOctets / 1073741824, 2);
            
            $customer->update([
                'current_month_usage_gb' => $usageGb,
                'last_usage_sync' => now()
            ]);

            $this->processFup($customer, $usageGb);
        }

        $this->info('✅ Usage Sync Completed.');
    }

    protected function processFup($customer, $usageGb)
    {
        $package = $customer->package;
        $limitGb = $package->fup_limit_gb;
        $fupSpeed = $package->fup_speed_limit;

        // Calculate additional FUP quota from paid boosters this month
        $boosterQuotaGb = \App\Models\CustomerBooster::where('customer_id', $customer->id)
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->join('boosters', 'customer_boosters.booster_id', '=', 'boosters.id')
            ->sum('boosters.quota_gb') ?: 0;

        $effectiveLimitGb = $limitGb + $boosterQuotaGb;

        // Jika pemakaian melebihi limit efektif (limit utama + booster)
        if ($usageGb >= $effectiveLimitGb) {
            $this->warn("⚠️ Customer {$customer->username} reached FUP limit ({$usageGb}GB / {$effectiveLimitGb}GB)");
            
            // Cek apakah kecepatan di radreply sudah di-FUP atau belum
            $currentReply = RadReply::where('username', $customer->username)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->first();

            if ($currentReply && $currentReply->value !== $fupSpeed) {
                // Update kecepatan ke profil FUP
                $currentReply->update(['value' => $fupSpeed]);
                
                // Putus koneksi agar router meminta profil baru (CoA / Disconnect)
                $this->disconnectUser($customer->username);
                
                Log::warning("FUP Applied to {$customer->username}: Speed reduced to {$fupSpeed}");
            }
        } else {
            // Jika pemakaian masih di bawah limit, pastikan kecepatan kembali normal
            $normalSpeed = $package->mikrotik_rate_limit;
            
            $currentReply = RadReply::where('username', $customer->username)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->first();

            if ($currentReply && $currentReply->value !== $normalSpeed) {
                $currentReply->update(['value' => $normalSpeed]);
                $this->disconnectUser($customer->username);
                
                Log::info("FUP Lifted for {$customer->username}: Speed restored to {$normalSpeed}");
            }
        }
    }

    protected function disconnectUser($username)
    {
        // Putuskan sesi aktif agar profil kecepatan baru langsung berlaku.
        // NAS dicari dari sesi aktif pelanggan (radacct.nasipaddress = nas.nasname),
        // sehingga benar untuk setup dengan lebih dari satu router.
        try {
            $coa = app(\App\Services\RadiusCoAService::class);
            $sessions = RadAcct::online()->where('username', $username)->get();

            foreach ($sessions as $session) {
                $nas = \App\Models\Radius\Nas::where('nasname', $session->nasipaddress)->first();
                if ($nas) {
                    $coa->disconnect($nas->nasname, (string) $nas->secret, (string) $username, $session->acctsessionid);
                } else {
                    Log::warning("FUP disconnect: NAS {$session->nasipaddress} untuk {$username} tidak terdaftar.");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to send disconnect request for {$username}: " . $e->getMessage());
        }
    }
}
