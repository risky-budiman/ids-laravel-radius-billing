<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment'
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($reply) {
            $ticket = $reply->ticket;
            if (!$ticket) return;

            $customer = $ticket->customer;
            if (!$customer) return;

            $isStaffReply = $reply->user_id !== null;

            if ($isStaffReply) {
                // Notify individual Customer
                if ($customer->phone) {
                    $cleanMsg = "Halo *{$customer->name}*,\n\nAda balasan baru dari CS/Teknisi IDS untuk tiket *#{$ticket->ticket_number}*:\n\n\"{$reply->message}\"\n\nSilakan cek aplikasi mobile untuk membaca dan membalas pesan.";
                    \App\Jobs\SendCustomWhatsappMessageJob::dispatch($customer->phone, $cleanMsg);
                }
            } else {
                // Notify Staff/Technician Group
                $settingKey = "whatsapp_group_id_{$ticket->type}";
                $groupId = get_setting($settingKey) ?: get_setting('whatsapp_group_id');
                if ($groupId) {
                    $cleanMsg = "💬 *BALASAN TIKET GANGGUAN*\n\n*Pelanggan:* {$customer->name}\n*No Tiket:* #{$ticket->ticket_number}\n*Pesan:* \"{$reply->message}\"\n\nSilakan cek admin panel untuk menindaklanjuti.";
                    \App\Jobs\SendCustomWhatsappMessageJob::dispatch($groupId, $cleanMsg);
                }

                // Also notify assigned technician via database notification if assigned
                if ($ticket->assigned_to && $ticket->assignee) {
                    $ticket->assignee->notify(new \App\Notifications\TicketCreatedNotification($ticket));
                }
            }
        });
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
