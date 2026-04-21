<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = \App\Models\Customer::first();
        $user = \App\Models\User::first();

        $tickets = [
            [
                'customer_id' => $customer?->id,
                'type' => 'aktivasi',
                'status' => 'open',
                'priority' => 'high',
                'subject' => 'Pemasangan Baru - Jhon Doe',
                'description' => 'Permintaan aktivasi layanan baru di Cluster Magnolia No. 12. Koordinat: -6.2146, 106.8451.',
                'assigned_to' => $user?->id,
            ],
            [
                'customer_id' => $customer?->id,
                'type' => 'gangguan',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'subject' => 'LOS Merah - Red Filter',
                'description' => 'Pelanggan melaporkan lampu indikator LOS menyala merah sejak jam 10 pagi. Sudah coba restart namun tidak ada hasil.',
                'assigned_to' => $user?->id,
            ],
            [
                'customer_id' => $customer?->id,
                'type' => 'dismantle',
                'status' => 'open',
                'priority' => 'medium',
                'subject' => 'Penarikan Perangkat - Jane Smith',
                'description' => 'Pelanggan berhenti berlangganan karena pindah rumah. Perlu dilakukan penarikan ONT dan STB.',
                'assigned_to' => $user?->id,
            ],
        ];

        foreach ($tickets as $ticketData) {
            \App\Models\Ticket::create($ticketData);
        }
    }
}
