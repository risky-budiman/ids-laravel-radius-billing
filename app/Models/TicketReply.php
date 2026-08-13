<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment',
        'is_system'
    ];

    protected $casts = [
        'is_system' => 'boolean'
    ];

    protected $touches = ['ticket'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($reply) {
            $ticket = $reply->ticket;
            if (!$ticket) return;

            if ($reply->is_system) {
                return;
            }

            $customer = $ticket->customer;
            if (!$customer) return;

            $isStaffReply = $reply->user_id !== null;

            if ($isStaffReply) {
                // Notify individual Customer
                if ($customer->phone) {
                    $cleanMsg = "Halo *{$customer->name}*,\n\nAda balasan baru dari CS/Teknisi IDS untuk tiket *#{$ticket->ticket_number}*:\n\n\"{$reply->message}\"\n\nSilakan cek aplikasi mobile untuk membaca dan membalas pesan.";
                    \App\Jobs\SendCustomWhatsappMessageJob::dispatch($customer->phone, $cleanMsg);
                }
                if ($customer->expo_push_token) {
                    \App\Models\Ticket::sendExpoPushNotification(
                        $customer->expo_push_token,
                        "Balasan Tiket #" . $ticket->ticket_number,
                        $reply->message,
                        ['ticket_id' => $ticket->id]
                    );
                }
            } else {
                // Notify Staff/Technician Group
                $settingKey = "whatsapp_group_id_{$ticket->type}";
                $groupId = get_setting($settingKey) ?: get_setting('whatsapp_group_id');
                if ($groupId) {
                    $cleanMsg = "💬 *BALASAN TIKET GANGGUAN*\n\n*Pelanggan:* {$customer->name}\n*No Tiket:* #{$ticket->ticket_number}\n*Pesan:* \"{$reply->message}\"\n\nSilakan cek admin panel untuk menindaklanjuti.";
                    \App\Jobs\SendCustomWhatsappMessageJob::dispatch($groupId, $cleanMsg);
                }

                // Also notify assigned technician via database notification if assigned, otherwise notify all staff
                if ($ticket->assigned_to && $ticket->assignee) {
                    try {
                        $ticket->assignee->notify(new \App\Notifications\TicketCreatedNotification($ticket));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to notify assignee for ticket #' . $ticket->id . ': ' . $e->getMessage());
                    }
                } else {
                    try {
                        $staff = \App\Models\User::whereIn('role', ['administrator', 'admin', 'teknisi'])->get();
                        foreach ($staff as $admin) {
                            $admin->notify(new \App\Notifications\TicketCreatedNotification($ticket));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send TicketCreatedNotification (reply) to staff for ticket #' . $ticket->id . ': ' . $e->getMessage());
                    }
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
