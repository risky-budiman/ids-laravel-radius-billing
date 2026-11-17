<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Services\Network\NocAlertService;
use App\Services\Network\NocService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pemantauan NOC terjadwal: baca ONU semua OLT aktif (per port PON, HSGQ & ZTE), perbarui sinyal & status
 * pelanggan, lalu kirim SATU ringkasan peringatan per OLT (ONU putus / sinyal kritis) dan peringatan OLT mati
 * atau pulih. Router/NAS juga dicek (ping / port MikroTik) dengan peringatan mati & pulih.
 * Jadwal diatur di Integrasi › Bot NOC (bawaan nonaktif).
 */
class NocPollCommand extends Command
{
    protected $signature = 'noc:monitor';
    protected $description = 'Pantau OLT & router: status & sinyal ONU pelanggan, peringatan ONU putus/sinyal kritis, OLT & router mati';

    public const CRITICAL_DBM = NocService::CRITICAL_DBM;
    public const REALERT_HOURS = 6;

    public function handle(NocService $noc, NocAlertService $alerts, \App\Services\Network\NasMonitor $nasMonitor): int
    {
        // Router/NAS (MikroTik): mati = semua pelanggan di router itu putus
        foreach (\App\Models\Radius\Nas::all() as $nas) {
            if (! \App\Services\Network\NasMonitor::monitorable($nas)) {
                continue;
            }
            try {
                $r = $nasMonitor->check($nas, $alerts);
                $this->info("Router {$nas->shortname} ({$nas->nasname}): " . ($r['up'] ? "online via {$r['via']}" : 'TIDAK MERESPONS') . ", {$r['sessions']} sesi aktif");
            } catch (\Throwable $e) {
                Log::error("NOC monitor NAS {$nas->id}: " . $e->getMessage());
            }
        }

        $olts = Olt::where('is_active', true)->get();
        if ($olts->isEmpty()) {
            $this->info('Tidak ada OLT aktif.');
            Cache::put('noc_last_sync_at', now()->getTimestamp(), now()->addDays(7));

            return self::SUCCESS;
        }

        foreach ($olts as $olt) {
            $this->info("Memantau {$olt->name} ({$olt->ip_address})…");
            try {
                $scan = $noc->scanOlt($olt);
                $up = $scan['reachable'] && ($scan['onus'] || ($noc->gateway($olt)->getOltStatus()['status'] ?? 'offline') === 'online');
                if (! $up) {
                    throw new \RuntimeException('OLT tidak merespons SNMP');
                }
                $this->oltRecovered($olt, $alerts);

                $r = $noc->syncCustomers($olt, $scan, true, self::REALERT_HOURS);
                if ($r['los'] || $r['critical']) {
                    $lines = ["OLT {$olt->name}"];
                    if ($r['los']) {
                        $lines[] = "\nONU putus (" . count($r['los']) . "):\n- " . implode("\n- ", array_slice($r['los'], 0, 30));
                    }
                    if ($r['critical']) {
                        $lines[] = "\nSinyal kritis < " . self::CRITICAL_DBM . ' dBm (' . count($r['critical']) . "):\n- " . implode("\n- ", array_slice($r['critical'], 0, 30));
                    }
                    $alerts->sendAlert(implode("\n", $lines));
                }
                $this->info("  {$r['matched']} pelanggan diperbarui" . ($scan['failed_ports'] ? ', port gagal: ' . implode(', ', $scan['failed_ports']) : ''));
            } catch (\Throwable $e) {
                $this->error("  Gagal: {$e->getMessage()}");
                Log::error("NOC monitor OLT {$olt->id}: " . $e->getMessage());
                $this->oltDown($olt, $alerts, $e->getMessage());
            }
        }
        Cache::put('noc_last_sync_at', now()->getTimestamp(), now()->addDays(7));

        return self::SUCCESS;
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
}
