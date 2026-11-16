<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Olt;
use App\Services\Network\OltGateway;
use App\Services\Network\ZteOids;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Hapus ONU dari OLT (dismantle, hapus pelanggan, hapus manual).
 *
 * ONU baru dihapus bila SN di posisi itu (dibaca lewat SNMP) sama dengan SN yang diharapkan. Dulu yang
 * dihapus adalah apa pun yang ada di indeks tersimpan — bila indeks sudah dipakai ONU lain, layanan
 * pelanggan lain ikut terhapus. Hasil (berhasil / dibatalkan) dicatat di Log Aktivitas.
 */
class DeprovisionOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $oltId, public $onuIndex, public $sn, public ?int $requestedBy = null)
    {
    }

    public function handle(): void
    {
        $olt = Olt::find($this->oltId);
        if (! $olt) {
            $this->audit('onu_delete_failed', "Hapus ONU {$this->sn}: OLT #{$this->oltId} tidak ditemukan.");

            return;
        }
        if (str_contains(strtoupper((string) $olt->olt_type), 'HSGQ')) {
            $this->audit('onu_delete_failed', "Hapus ONU {$this->sn} di {$olt->name}: OLT HSGQ belum didukung — hapus manual di OLT.");

            return;
        }

        $pos = ZteOids::parseOnuPosition($this->onuIndex);
        if (! $pos) {
            $this->audit('onu_delete_failed', "Hapus ONU {$this->sn} di {$olt->name}: format indeks '{$this->onuIndex}' tidak dikenali.");

            return;
        }

        $gateway = $this->gateway($olt);
        $check = $gateway->verifyOnuSerial($pos['shelf'], $pos['slot'], $pos['port'], $pos['onu_id'], $this->sn);
        if (! $check['ok']) {
            $this->audit('onu_delete_aborted', "Hapus ONU di {$olt->name} DIBATALKAN: {$check['reason']}");

            return;
        }

        $ok = $gateway->deprovisionOnu($pos['shelf'], $pos['slot'], $pos['port'], $pos['onu_id']);
        $this->audit($ok ? 'onu_deleted' : 'onu_delete_failed',
            ($ok ? 'ONU dihapus' : 'Hapus ONU GAGAL') . " di {$olt->name}: SN {$this->sn}, posisi {$pos['shelf']}/{$pos['slot']}/{$pos['port']}:{$pos['onu_id']}."
            . ($ok ? '' : ' Alasan: ' . ($gateway->lastWriteError() ?? 'tidak diketahui') . '.'));
    }

    /** Dipisah agar bisa diganti di tes. */
    protected function gateway(Olt $olt): OltGateway
    {
        return app()->makeWith(OltGateway::class, ['olt' => $olt]);
    }

    private function audit(string $action, string $message): void
    {
        $action === 'onu_deleted' ? Log::info($message) : Log::warning($message);
        ActivityLog::create(['user_id' => $this->requestedBy, 'action' => $action, 'description' => $message,
            'subject_type' => Olt::class, 'subject_id' => $this->oltId]);
    }
}
