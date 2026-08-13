<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Jobs\SendTicketToWhatsAppGroupJob;

class Ticket extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'type',
        'status',
        'priority',
        'subject',
        'description',
        'attachment',
        'resolution_notes',
        'assigned_to',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Check if the ticket has exceeded the SLA (24 hours for open tickets)
     */
    public function isOverdue()
    {
        return in_array($this->status, ['open', 'in_progress']) && $this->created_at->diffInHours(now()) >= 24;
    }

    /**
     * Send push notification via Expo Push API
     */
    public static function sendExpoPushNotification($toToken, $title, $body, $data = [])
    {
        if (!$toToken) return;

        try {
            \Illuminate\Support\Facades\Http::timeout(10)->post('https://exp.host/--/api/v2/push/send', [
                'to' => $toToken,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Expo Push Notification Failed: ' . $e->getMessage());
        }
    }

    // Auto-generate ticket number and handle notifications
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $type = $ticket->type ?? 'gangguan';
                $prefixKey = 'ticket_prefix_' . $type;
                
                $fallbacks = [
                    'gangguan' => 'TT',
                    'aktivasi' => 'AO',
                    'dismantle' => 'DO',
                    'relokasi' => 'RL',
                    'maintenance' => 'MT'
                ];
                
                $prefix = get_setting($prefixKey, $fallbacks[$type] ?? 'TKT');
                $count = static::whereDate('created_at', now()->toDateString())->count();
                $ticket->ticket_number = $prefix . '/' . now()->format('Ymd') . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($ticket) {
            SendTicketToWhatsAppGroupJob::dispatch($ticket, 'created');
        });

        static::updated(function ($ticket) {
            if ($ticket->wasChanged(['status', 'assigned_to', 'resolution_notes'])) {
                SendTicketToWhatsAppGroupJob::dispatch($ticket, 'updated');
            }

            if ($ticket->wasChanged('status')) {
                $oldStatus = $ticket->getOriginal('status');
                $newStatus = $ticket->status;
                
                $ticket->replies()->create([
                    'message' => "⚙️ Status tiket diperbarui dari '" . strtoupper($oldStatus) . "' menjadi '" . strtoupper($newStatus) . "'.",
                    'is_system' => true,
                ]);

                $customer = $ticket->customer;
                if ($customer) {
                    if ($customer->phone) {
                        $status = strtoupper($ticket->status);
                        $cleanMsg = "Halo *{$customer->name}*,\n\nStatus tiket gangguan Anda *#{$ticket->ticket_number}* telah diperbarui menjadi *[{$status}]*.\n\nSilakan cek aplikasi mobile untuk informasi lebih lanjut.";
                        \App\Jobs\SendCustomWhatsappMessageJob::dispatch($customer->phone, $cleanMsg);
                    }
                    if ($customer->expo_push_token) {
                        self::sendExpoPushNotification(
                            $customer->expo_push_token,
                            "Status Tiket Diperbarui",
                            "Status tiket #" . $ticket->ticket_number . " Anda diubah menjadi [" . strtoupper($ticket->status) . "].",
                            ['ticket_id' => $ticket->id]
                        );
                    }
                }
            }

            if ($ticket->wasChanged('assigned_to')) {
                $newAssignee = $ticket->assignee;
                $assigneeName = $newAssignee ? $newAssignee->name : 'Belum Ditugaskan / Dilepas';
                
                $ticket->replies()->create([
                    'message' => "👤 Tiket ditugaskan kepada: " . $assigneeName,
                    'is_system' => true,
                ]);

                $customer = $ticket->customer;
                if ($customer && $customer->expo_push_token) {
                    self::sendExpoPushNotification(
                        $customer->expo_push_token,
                        "Petugas Ditugaskan",
                        "Tiket #" . $ticket->ticket_number . " Anda sekarang ditangani oleh: " . ($newAssignee ? $newAssignee->name : 'staf pendukung') . ".",
                        ['ticket_id' => $ticket->id]
                    );
                }
            }

            if ($ticket->wasChanged('resolution_notes') && $ticket->resolution_notes) {
                $ticket->replies()->create([
                    'message' => "🔧 Catatan Solusi: " . $ticket->resolution_notes,
                    'is_system' => true,
                ]);
            }
        });
    }
}
