<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pelanggan baru dengan ONU: catat bahwa ONU harus didaftarkan MANUAL di OLT.
 *
 * Sistem hanya memakai SNMP (telnet dihapus) dan registrasi ONU belum didukung lewat SNMP. Catatan memuat
 * OLT, port, SN, VLAN & profil T-CONT paket agar teknisi bisa langsung mendaftarkannya.
 */
class ProvisionOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }

    public function handle()
    {
        $customer = Customer::with('olt', 'package')->find($this->customerId);
        if (! $customer || ! $customer->olt || ! $customer->onu_sn) {
            Log::warning("Provisioning Job: Missing data for customer ID {$this->customerId}");

            return;
        }

        $profile = $customer->package?->olt_tcont_profile;
        \App\Models\OltWorkItem::record($customer->olt_id, 'provision', [
            'customer_id' => $customer->id, 'onu_sn' => strtoupper((string) $customer->onu_sn), 'onu_index' => $customer->onu_index,
            'vlan' => (int) ($customer->vlan_id ?: 100) ?: null, 'profile' => $profile,
        ]);
        ActivityLog::create(['action' => 'onu_provision_manual', 'subject_type' => Customer::class, 'subject_id' => $customer->id,
            'description' => "Daftarkan ONU MANUAL di OLT {$customer->olt->name}: SN {$customer->onu_sn}, pelanggan {$customer->name}"
                . " ({$customer->customer_code}), port {$customer->onu_index}, VLAN " . ($customer->vlan_id ?: 100)
                . ($profile ? ", profil T-CONT {$profile}" : '') . '.']);
    }
}
