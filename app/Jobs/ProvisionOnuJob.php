<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Services\Network\OltGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        if (!$customer || !$customer->olt || !$customer->onu_sn) {
            Log::warning("Provisioning Job: Missing data for customer ID {$this->customerId}");
            return;
        }

        // OLT mode SNMP saja: tidak ada telnet — staf mendaftarkan ONU manual di OLT
        if (! $customer->olt->usesTelnet()) {
            ActivityLog::create(['action' => 'onu_provision_skipped', 'subject_type' => Customer::class, 'subject_id' => $customer->id,
                'description' => "Registrasi otomatis ONU {$customer->onu_sn} ({$customer->name}) dilewati: {$customer->olt->name} memakai mode SNMP saja. Daftarkan ONU manual di OLT."]);

            return;
        }

        Log::info("Starting background provisioning for customer: {$customer->name}");

        $gateway = app()->makeWith(OltGateway::class, ['olt' => $customer->olt]);
        
        // Port PON dari onu_index: ".1.1.7", "1.1.7.5", "1/1/7" atau "1/1/7:5" (dulu hanya format titik)
        $pos = (string) $customer->onu_index;
        $parts = preg_split('/[.\/:]/', trim($pos, '.'), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) < 3 || array_filter(array_slice($parts, 0, 3), fn ($p) => ! ctype_digit($p))) {
            Log::error("Invalid ONU index format for provisioning: {$pos}");
            return;
        }

        [$shelf, $slot, $port] = array_map('intval', array_slice($parts, 0, 3));

        // VLAN dari isian aktivasi (dulu selalu 100); profil T-CONT dari paket (dulu selalu UP-100M)
        $vlan = (int) ($customer->vlan_id ?: 100);

        $result = $gateway->provisionOnu(
            $shelf,
            $slot,
            $port,
            $customer->onu_sn,
            $customer->onu_type ?: 'ZTE-ONU',
            $vlan,
            $customer->customer_code ?: $customer->name,
            $customer->package?->olt_tcont_profile
        );

        // Hasil registrasi tercatat di Log Aktivitas (dulu kegagalan hanya ada di log server)
        if ($result) {
            $customer->update(['onu_index' => $result]);
            Log::info("Provisioning successful for {$customer->name}: New Index is {$result}");
            \App\Models\ActivityLog::create(['action' => 'onu_provisioned', 'subject_type' => \App\Models\Customer::class, 'subject_id' => $customer->id,
                'description' => "ONU {$customer->onu_sn} pelanggan {$customer->name} terdaftar di {$customer->olt->name} posisi {$result}."]);
        } else {
            $reason = $gateway->lastWriteError() ?? 'tidak diketahui';
            Log::error("Provisioning failed for {$customer->name}: {$reason}");
            \App\Models\ActivityLog::create(['action' => 'onu_provision_failed', 'subject_type' => \App\Models\Customer::class, 'subject_id' => $customer->id,
                'description' => "Registrasi ONU {$customer->onu_sn} pelanggan {$customer->name} di {$customer->olt->name} GAGAL. Alasan: {$reason}"]);
        }
    }
}
