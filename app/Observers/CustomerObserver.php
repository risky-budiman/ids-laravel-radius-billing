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
                'description' => 'Aktivasi pelanggan baru dengan username: ' . $customer->username,
            ]);
        }
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
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
