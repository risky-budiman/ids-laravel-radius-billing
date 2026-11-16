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
 * Aksi ONU di OLT: 'suspend', 'resume', 'update_speed' (profil T-CONT).
 *
 * - $profile untuk update_speed = NAMA profil T-CONT di OLT (Paket > Profil T-CONT OLT). Dulu dikirim
 *   angka bandwidth sehingga perintah selalu gagal diam-diam.
 * - Bila $sn diberikan, aksi hanya dijalankan bila SN di posisi itu cocok (lihat DeprovisionOnuJob).
 * - Indeks format "1.1.7.5" maupun "1/1/7:5" diterima.
 */
class UpdateOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $oltId, public $onuIndex, public string $action, public ?string $profile = null, public ?string $sn = null)
    {
    }

    public function handle(): void
    {
        $olt = Olt::find($this->oltId);
        $pos = ZteOids::parseOnuPosition($this->onuIndex);
        if (! $olt || ! $pos) {
            Log::warning("ONU {$this->action} dilewati: OLT #{$this->oltId} / indeks '{$this->onuIndex}' tidak valid.");

            return;
        }
        if ($this->action === 'update_speed' && ! $this->profile) {
            return;   // paket tanpa profil T-CONT: kecepatan cukup diatur RADIUS
        }
        // OLT mode SNMP saja: isolir/buka cukup lewat RADIUS; ganti profil T-CONT dicatat agar dikerjakan manual
        if (! $olt->usesTelnet()) {
            if ($this->action === 'update_speed') {
                $this->audit('onu_action_skipped', "Ganti profil ONU {$this->onuIndex} ke {$this->profile} dilewati: {$olt->name} memakai mode SNMP saja. Ubah manual di OLT bila perlu.", $olt);
            }

            return;
        }

        $gateway = $this->gateway($olt);
        if ($this->sn) {
            $check = $gateway->verifyOnuSerial($pos['shelf'], $pos['slot'], $pos['port'], $pos['onu_id'], $this->sn);
            if (! $check['ok']) {
                $this->audit('onu_action_aborted', "ONU {$this->action} di {$olt->name} DIBATALKAN: {$check['reason']}", $olt);

                return;
            }
        }

        [$s, $sl, $p, $o] = [$pos['shelf'], $pos['slot'], $pos['port'], $pos['onu_id']];
        $ok = match ($this->action) {
            'suspend' => $gateway->suspendOnu($s, $sl, $p, $o),
            'resume' => $gateway->resumeOnu($s, $sl, $p, $o),
            'update_speed' => $gateway->updateOnuProfile($s, $sl, $p, $o, $this->profile),
            default => false,
        };
        if (! $ok) {
            $this->audit('onu_action_failed', "ONU {$this->action} GAGAL di {$olt->name} posisi {$s}/{$sl}/{$p}:{$o}" . ($this->profile ? " (profil {$this->profile})" : '') . '. Alasan: ' . ($gateway->lastWriteError() ?? 'tidak diketahui') . '.', $olt);
        }
    }

    protected function gateway(Olt $olt): OltGateway
    {
        return app()->makeWith(OltGateway::class, ['olt' => $olt]);
    }

    private function audit(string $action, string $message, Olt $olt): void
    {
        Log::warning($message);
        ActivityLog::create(['action' => $action, 'description' => $message, 'subject_type' => Olt::class, 'subject_id' => $olt->id]);
    }
}
