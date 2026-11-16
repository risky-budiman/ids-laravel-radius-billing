<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Olt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pelanggan dicabut / dihapus: catat bahwa ONU-nya harus dihapus MANUAL di OLT.
 *
 * Sistem hanya memakai SNMP (telnet dihapus), dan penghapusan ONU belum didukung lewat SNMP. Catatan di
 * Log Aktivitas memuat OLT, posisi & SN agar teknisi menghapus ONU yang benar.
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
        $message = 'Hapus ONU MANUAL di OLT ' . ($olt?->name ?? "#{$this->oltId}")
            . ": SN {$this->sn}, posisi {$this->onuIndex} (pelanggan dicabut/dihapus; sistem hanya SNMP).";
        Log::info($message);
        ActivityLog::create(['user_id' => $this->requestedBy, 'action' => 'onu_delete_manual', 'description' => $message,
            'subject_type' => Olt::class, 'subject_id' => $this->oltId]);
    }
}
