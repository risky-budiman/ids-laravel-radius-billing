<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Olt;
use App\Models\Customer;
use App\Models\CustomerSignalCache;
use App\Models\CustomerSignalLog;
use App\Services\Network\OltGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Network\NocAlertService;

class NocPollCommand extends Command
{
    protected $signature = 'noc:monitor';
    protected $description = 'Poll OLTs for ONU status and signal power via SNMP';

    /** Ambang sinyal kritis (dBm) dan jeda minimal peringatan ulang per pelanggan. */
    public const CRITICAL_DBM = -27.0;
    public const REALERT_HOURS = 6;
    private const DOWN_STATUSES = ['los', 'dying-gasp', 'offline'];

    public function handle()
    {
        $this->info('Starting NOC SNMP Polling...');
        Log::info("NOC Poll: Starting execution...");

        $olts = Olt::where('is_active', true)->get();
        if ($olts->isEmpty()) {
            Log::warning("NOC Poll: No active OLTs found to poll.");
            return;
        }

        $alerts = app(NocAlertService::class);

        foreach ($olts as $olt) {
            $this->info("Polling OLT: {$olt->name} ({$olt->ip_address})...");
            $los = [];
            $critical = [];

            try {
                $gateway = $this->gateway($olt);
                $bulkData = $gateway->bulkWalkAllOnus();

                // Tanpa satu pun SN & OLT tidak menjawab = OLT mati (bukan "semua ONU kosong")
                if (empty($bulkData['sns']) && ($gateway->getOltStatus()['status'] ?? 'offline') !== 'online') {
                    throw new \RuntimeException('OLT tidak merespons SNMP');
                }
                $this->oltRecovered($olt, $alerts);

                $customers = Customer::where('olt_id', $olt->id)->get();
                foreach ($customers as $customer) {
                    $index = $customer->onu_index;
                    $sn = $customer->onu_sn;
                    if (!$index && !$sn) {
                        continue;
                    }

                    $match = $gateway->matchCustomerInBulkData($bulkData, $index, $sn);
                    $dbm = $match['rx_power'];
                    $status = $match['status'];

                    if (isset($match['real_index']) && $match['real_index'] && $match['real_index'] != $index) {
                        $customer->update(['onu_index' => $match['real_index']]);
                        $this->info("Updated index for {$customer->name} to {$match['real_index']}");
                    }
                    // Cache memakai indeks terbaru (dulu indeks lama tetap tersimpan setelah indeks diperbarui)
                    $index = ! empty($match['real_index']) ? $match['real_index'] : $index;

                    $previous = CustomerSignalCache::where('customer_id', $customer->id)->first();
                    $canAlert = ! $previous?->last_alerted_at || $previous->last_alerted_at->lt(now()->subHours(self::REALERT_HOURS));
                    $alerted = false;

                    // Hanya pelanggan aktif: ONU pelanggan terisolir/dicabut yang mati bukan gangguan
                    if ($customer->status === Customer::STATUS_ACTIVE && $canAlert) {
                        if (in_array($status, self::DOWN_STATUSES, true) && ($previous?->status === 'online')) {
                            $los[] = "{$customer->customer_code} {$customer->name} ({$status})";
                            $alerted = true;
                        } elseif ($dbm !== null && $dbm < self::CRITICAL_DBM) {
                            $critical[] = "{$customer->customer_code} {$customer->name}: {$dbm} dBm";
                            $alerted = true;
                        }
                    }

                    CustomerSignalCache::updateOrCreate(
                        ['customer_id' => $customer->id],
                        array_filter([
                            'onu_index' => $index,
                            'rx_power' => $dbm,
                            'status' => $status,
                            'last_polled_at' => now(),
                            'last_alerted_at' => $alerted ? now() : null,
                        ], fn ($v, $k) => $k !== 'last_alerted_at' || $v !== null, ARRAY_FILTER_USE_BOTH)
                    );

                    if ($dbm !== null) {
                        $this->logHistory($customer, $dbm, $status);
                    }
                }

                // Satu ringkasan per OLT (bukan satu pesan per pelanggan)
                if ($los || $critical) {
                    $lines = ["OLT {$olt->name}"];
                    if ($los) {
                        $lines[] = "\nONU putus (" . count($los) . "):\n- " . implode("\n- ", array_slice($los, 0, 30));
                    }
                    if ($critical) {
                        $lines[] = "\nSinyal kritis < " . self::CRITICAL_DBM . " dBm (" . count($critical) . "):\n- " . implode("\n- ", array_slice($critical, 0, 30));
                    }
                    $alerts->sendAlert(implode("\n", $lines));
                }

                $this->info("Successfully polled {$olt->name}");
            } catch (\Throwable $e) {
                $this->error("Failed to poll OLT {$olt->name}: " . $e->getMessage());
                Log::error("NOC Poll Error for OLT {$olt->id}: " . $e->getMessage());
                $this->oltDown($olt, $alerts, $e->getMessage());
            }
        }

        $this->info('NOC Polling Completed.');
    }

    /** Peringatan OLT mati dikirim sekali sampai OLT pulih. */
    private function oltDown(Olt $olt, NocAlertService $alerts, string $reason): void
    {
        if (Cache::add("noc_olt_down_{$olt->id}", now()->toDateTimeString(), now()->addDays(30))) {
            $alerts->sendAlert("OLT {$olt->name} ({$olt->ip_address}) TIDAK MERESPONS.\n{$reason}");
        }
    }

    private function oltRecovered(Olt $olt, NocAlertService $alerts): void
    {
        $since = Cache::pull("noc_olt_down_{$olt->id}");
        if ($since) {
            $alerts->sendAlert("OLT {$olt->name} ({$olt->ip_address}) kembali online (mati sejak {$since}).");
        }
    }

    /** Dipisah agar bisa diganti di tes. */
    protected function gateway(Olt $olt): OltGateway
    {
        return app()->makeWith(OltGateway::class, ['olt' => $olt]);
    }

    private function logHistory($customer, $dbm, $status)
    {
        $lastLog = CustomerSignalLog::where('customer_id', $customer->id)
            ->latest()
            ->first();

        // Log if:
        // 1. No history yet
        // 2. Status changed
        // 3. Signal changed significantly (0.5 dBm)
        // 4. Last log is more than 10 minutes old
        $shouldLog = !$lastLog || 
                     $lastLog->status != $status || 
                     abs($lastLog->rx_power - $dbm) >= 0.5 ||
                     $lastLog->created_at->diffInMinutes(now()) >= 10;

        if ($shouldLog) {
            CustomerSignalLog::create([
                'customer_id' => $customer->id,
                'rx_power' => $dbm,
                'status' => $status,
                'created_at' => now(),
            ]);
        }
    }
}
