<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Olt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Aksi ONU setelah isolir/buka atau ganti paket: 'suspend', 'resume', 'update_speed'.
 *
 * Sistem hanya memakai SNMP (telnet dihapus):
 * - suspend/resume tidak perlu apa-apa di OLT — isolir & buka isolir sudah diatur RADIUS;
 * - update_speed (paket punya Profil T-CONT OLT) dicatat agar profil ONU diganti MANUAL di OLT;
 *   kecepatan internet sendiri tetap diatur RADIUS.
 */
class UpdateOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $oltId, public $onuIndex, public string $action, public ?string $profile = null, public ?string $sn = null)
    {
    }

    public function handle(): void
    {
        if ($this->action !== 'update_speed' || ! $this->profile) {
            return;
        }
        $olt = Olt::find($this->oltId);
        ActivityLog::create(['action' => 'onu_profile_manual', 'subject_type' => Olt::class, 'subject_id' => $this->oltId,
            'description' => 'Ganti profil T-CONT ONU MANUAL di OLT ' . ($olt?->name ?? "#{$this->oltId}")
                . ": posisi {$this->onuIndex}" . ($this->sn ? ", SN {$this->sn}" : '') . " → profil {$this->profile}."]);
    }
}
