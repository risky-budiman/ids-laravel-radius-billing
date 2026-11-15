<?php

namespace App\Console\Commands;

use App\Jobs\SendCustomWhatsappMessageJob;
use App\Models\Ticket;
use App\Services\Reports\TicketRecapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Peringatan tiket ke grup WhatsApp teknisi (grup per tipe: whatsapp_group_id_<tipe>, cadangan
 * whatsapp_group_id), masing-masing SEKALI per tiket:
 *  - Belum diambil : status open, tanpa petugas, lebih dari `ticket_unclaimed_alert_hours` (default 2) jam.
 *  - Lewat SLA     : belum selesai melewati batas — dibuat + 24 jam (sama dengan rekap kinerja),
 *                    atau tanggal jadwal (due_date) bila tiket terjadwal, mis. aktivasi.
 */
class TicketSlaAlerts extends Command
{
    protected $signature = 'app:ticket-sla-alerts {--dry-run : Tampilkan saja, tanpa mengirim}';
    protected $description = 'Kirim peringatan tiket belum diambil / lewat SLA ke grup WhatsApp teknisi';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        $unclaimedHours = max(1, (int) get_setting('ticket_unclaimed_alert_hours', 2));
        $unclaimed = Ticket::with('customer:id,name,customer_code,phone,address')
            ->where('status', 'open')->whereNull('assigned_to')->whereNull('unclaimed_alerted_at')
            ->where('created_at', '<=', $now->copy()->subHours($unclaimedHours))
            ->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '<=', $now->toDateString()))
            ->get();

        foreach ($unclaimed as $t) {
            $hours = (int) floor($t->created_at->diffInHours($now));
            $sent += $this->sendAlert($t, 'unclaimed_alerted_at',
                "⏰ *TIKET BELUM DIAMBIL* ({$hours} jam)\n\n" . $this->body($t) . "\n\nMohon segera diklaim oleh teknisi yang bertugas.");
        }

        $slaHours = TicketRecapService::SLA_HOURS;
        $late = Ticket::with(['customer:id,name,customer_code,phone,address', 'assignee:id,name'])
            ->whereIn('status', TicketRecapService::OPEN_STATUSES)->whereNull('sla_alerted_at')
            ->where(function ($q) use ($now, $slaHours) {
                // Terjadwal: lewat tanggal jadwalnya. Tidak terjadwal: lewat 24 jam sejak dibuat.
                $q->where(fn ($s) => $s->whereNotNull('due_date')->whereDate('due_date', '<', $now->toDateString()))
                  ->orWhere(fn ($s) => $s->whereNull('due_date')->where('created_at', '<=', $now->copy()->subHours($slaHours)));
            })
            ->get();

        foreach ($late as $t) {
            $age = $t->created_at->diffInHours($now);
            $ageText = $age >= 48 ? floor($age / 24) . ' hari' : floor($age) . ' jam';
            $who = $t->assignee?->name ? "Petugas: *{$t->assignee->name}*" : 'Petugas: *belum ada*';
            $sent += $this->sendAlert($t, 'sla_alerted_at',
                "🚨 *TIKET LEWAT SLA* (terbuka {$ageText})\n\n" . $this->body($t) . "\n{$who}\nStatus: " . strtoupper($t->status));
        }

        $this->info("Peringatan tiket dikirim: {$sent}");

        return self::SUCCESS;
    }

    private function body(Ticket $t): string
    {
        $c = $t->customer;

        return "*No. Tiket:* {$t->ticket_number}\n*Tipe:* " . ucfirst((string) $t->type) . ' · *Prioritas:* ' . strtoupper((string) $t->priority) . "\n"
            . '*Pelanggan:* ' . ($c ? "{$c->name} ({$c->customer_code})" : '-') . "\n"
            . ($c?->phone ? "*HP:* {$c->phone}\n" : '')
            . ($c?->address ? "*Alamat:* {$c->address}\n" : '')
            . "*Subjek:* {$t->subject}";
    }

    /** @return int 1 bila peringatan dijadwalkan */
    private function sendAlert(Ticket $t, string $column, string $message): int
    {
        $group = get_setting("whatsapp_group_id_{$t->type}") ?: get_setting('whatsapp_group_id');
        if (! $group) {
            Log::warning("Peringatan tiket {$t->ticket_number}: grup WhatsApp untuk tipe '{$t->type}' belum diatur.");

            return 0;
        }
        if ($this->option('dry-run')) {
            $this->line("[dry-run] {$column} {$t->ticket_number}");

            return 0;
        }

        // Tandai dulu (tanpa memicu event model/notifikasi status) agar run ganda tidak mengirim dua kali
        $claimed = DB::table('tickets')->where('id', $t->id)->whereNull($column)->update([$column => now()]);
        if (! $claimed) {
            return 0;
        }

        SendCustomWhatsappMessageJob::dispatch($group, $message);

        return 1;
    }
}
