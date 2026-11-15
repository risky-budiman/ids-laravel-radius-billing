<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Ticket;

/**
 * Aturan tiket yang dipakai bersama oleh web admin dan API admin.
 *
 * Notifikasi staf, WhatsApp grup, broadcast realtime dan pesan ke pelanggan sudah
 * dikirim oleh event model Ticket (created/updated) — controller tidak perlu mengirim lagi.
 */
class TicketService
{
    /** Sesuai enum kolom tickets.type. */
    public const TYPES = ['aktivasi', 'gangguan', 'dismantle'];
    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed', 'canceled'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    /** Tiket yang hanya boleh diselesaikan lewat wizard (billing & inventory ikut diproses). */
    public const WIZARD_TYPES = ['aktivasi', 'dismantle'];

    /**
     * Pesan penolakan bila perubahan melanggar alur kerja, atau null bila boleh.
     */
    public function guard(Ticket $ticket, array $data): ?string
    {
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? $ticket->type;

        if (in_array($status, ['closed', 'resolved'], true)
            && (in_array($ticket->type, self::WIZARD_TYPES, true) || in_array($type, self::WIZARD_TYPES, true))
            && $status !== $ticket->status) {
            $wizard = ($ticket->type === 'dismantle' || $type === 'dismantle') ? 'Dismantle' : 'Aktivasi';

            return "Tiket $wizard hanya dapat diselesaikan melalui Wizard $wizard Pelanggan agar integritas data Billing & Inventory terjaga.";
        }

        return null;
    }

    /**
     * Simpan perubahan tiket lalu sinkronkan status pelanggan bila tiket dibatalkan.
     *
     * @throws \DomainException bila melanggar alur kerja (lihat guard()).
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        if ($error = $this->guard($ticket, $data)) {
            throw new \DomainException($error);
        }

        $wasStatus = $ticket->status;
        $ticket->update($data);

        if ($ticket->status === 'canceled' && $wasStatus !== 'canceled') {
            $this->syncCustomerOnCancel($ticket);
        }

        return $ticket;
    }

    private function syncCustomerOnCancel(Ticket $ticket): void
    {
        $customer = $ticket->customer;
        if (! $customer) {
            return;
        }

        // Hanya ubah pelanggan yang memang sedang menunggu proses tiket ini.
        if ($ticket->type === 'aktivasi'
            && in_array($customer->status, [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION], true)) {
            $customer->update(['status' => Customer::STATUS_CANCELED]);
        } elseif ($ticket->type === 'dismantle' && $customer->status === Customer::STATUS_WAITING_DISMANTLE) {
            // Dismantle batal: kembali berlangganan. Pelanggan yang diajukan karena menunggak dan kini
            // tidak punya tunggakan lewat jatuh tempo (sudah melunasi) langsung aktif lagi; bila masih
            // menunggak, kembali terisolir (pembayaran nanti membukanya seperti biasa).
            $stillOverdue = $customer->invoices()->overdueRecurring()->exists();
            $customer->update($customer->is_active || ! $stillOverdue
                ? ['status' => Customer::STATUS_ACTIVE, 'is_active' => true]
                : ['status' => Customer::STATUS_SUSPENDED]);
        }
    }
}
