<?php

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        // For new customers that are not active, create activation ticket
        if (!$customer->is_active || $customer->status === Customer::STATUS_NEW) {
            $customer->updateQuietly(['status' => Customer::STATUS_WAITING_ACTIVATION]);
            
            \App\Models\Ticket::create([
                'customer_id' => $customer->id,
                'type' => 'aktivasi',
                'status' => 'open',
                'priority' => 'high',
                'subject' => 'Aktivasi Baru: ' . $customer->name,
                'description' => 'Aktivasi pelanggan baru dengan username: ' . $customer->username . 
                               ($customer->scheduled_activation_at ? ' | Jadwal: ' . $customer->scheduled_activation_at->format('d M Y') : ''),
                'due_date' => $customer->scheduled_activation_at,
            ]);
        }
    }

    /**
     * Handle the Customer "updated" event.
     */
    /**
     * Catat tanggal layanan berakhir (untuk churn): diisi saat dicabut/batal, dikosongkan
     * bila pelanggan kembali berlangganan.
     */
    public function saving(Customer $customer): void
    {
        if (! $customer->isDirty('status')) {
            return;
        }
        if (in_array($customer->status, [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED], true)) {
            $customer->terminated_at ??= now();
        } elseif (in_array($customer->status, [Customer::STATUS_ACTIVE, Customer::STATUS_SUSPENDED], true)) {
            $customer->terminated_at = null;
        }

        // Sejak kapan terisolir (dasar pengajuan dismantle otomatis)
        if ($customer->status === Customer::STATUS_SUSPENDED) {
            $customer->suspended_at ??= now();
        } else {
            $customer->suspended_at = null;
        }
    }

    public function updated(Customer $customer): void
    {
        $this->syncIsolation($customer);

        // Trigger dismantle ticket if status changes to waiting_dismantle
        if ($customer->isDirty('status') && $customer->status === Customer::STATUS_WAITING_DISMANTLE) {
            \App\Models\Ticket::create([
                'customer_id' => $customer->id,
                'type' => 'dismantle',
                'status' => 'open',
                'priority' => 'medium',
                'subject' => 'Dismantle Perangkat: ' . $customer->name,
                'description' => 'Penarikan perangkat untuk pelanggan: ' . $customer->name . ' (' . $customer->username . ')',
            ]);
        }
    }

    /**
     * Isolir RADIUS mengikuti status pelanggan, dari jalur mana pun status diubah
     * (billing otomatis, masa tenggang instalasi, manual, API, pembayaran).
     *  - menjadi suspended            -> pindah ke grup ISOLIR + IP isolir
     *  - suspended -> active          -> pulihkan grup paket & IP asli, putus sesi agar IP normal
     *  - suspended -> dismantled/batal-> lepas IP isolir (akun RADIUS dihapus alur dismantle)
     *  - suspended -> waiting_dismantle tetap terisolir
     */
    private function syncIsolation(Customer $customer): void
    {
        if (! $customer->wasChanged('status')) {
            return;
        }

        $isolation = app(\App\Services\IsolationService::class);
        $from = $customer->getOriginal('status');
        $to = $customer->status;

        try {
            if ($to === Customer::STATUS_SUSPENDED) {
                $isolation->isolate($customer);
            } elseif ($from === Customer::STATUS_SUSPENDED && $to === Customer::STATUS_ACTIVE) {
                if ($isolation->isIsolated($customer)) {
                    $isolation->release($customer);
                    app(\App\Services\CustomerLifecycleService::class)->disconnectSessions((string) $customer->username);
                }
            } elseif (in_array($to, [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED], true)) {
                $isolation->release($customer, restoreRadius: false);
            }
        } catch (\Throwable $e) {
            // Status pelanggan tetap tersimpan; kegagalan RADIUS dicatat untuk ditindaklanjuti.
            \Illuminate\Support\Facades\Log::error("Sinkron isolir {$customer->username} ({$from} -> {$to}) gagal: " . $e->getMessage());
        }
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "restored" event.
     */
    public function restored(Customer $customer): void
    {
        //
    }

    /**
     * Handle the Customer "force deleted" event.
     */
    public function forceDeleted(Customer $customer): void
    {
        //
    }
}
