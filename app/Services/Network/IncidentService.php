<?php

namespace App\Services\Network;

use App\Jobs\SendWhatsappMessageJob;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\NetworkIncident;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gangguan massal: pelanggan aktif terdampak per OLT / port PON / ODP, satu tiket induk, tiket gangguan
 * pelanggan yang tertaut, dan WhatsApp pemberitahuan & pemulihan lewat antrean (jeda anti-ban).
 */
class IncidentService
{
    public function __construct(private NocService $noc) {}

    /** @return array{label: string, customers: Collection<int, Customer>} */
    public function affected(string $scope, string $id): array
    {
        $base = fn () => Customer::where('status', Customer::STATUS_ACTIVE);

        switch ($scope) {
            case 'olt':
                $olt = Olt::findOrFail($id);

                return ['label' => "OLT {$olt->name}", 'customers' => $base()->where('olt_id', $olt->id)->orderBy('name')->get()];

            case 'odp':
                $odp = Odp::findOrFail($id);

                return ['label' => "ODP {$odp->name}", 'customers' => $base()->where('odp_id', $odp->id)->orderBy('name')->get()];

            case 'port':
                $port = OltPonPort::with('olt')->findOrFail($id);
                $olt = $port->olt;
                // 1) posisi ONU tersimpan (shelf/slot/port:onu atau 1.slot.port.onu)
                $byPosition = $base()->where('olt_id', $olt->id)->whereNotNull('onu_index')->get()
                    ->filter(function ($c) use ($port) {
                        $p = array_values(array_filter(preg_split('/[.\/:]/', (string) $c->onu_index), 'strlen'));

                        return count($p) >= 3 && (int) $p[1] === (int) $port->slot && (int) $p[2] === (int) $port->pon_port;
                    });
                // 2) hasil baca port terakhir (cache) dicocokkan seperti NOC (MAC/SN, nama ONU)
                $rows = Cache::get("olt_port_data_{$port->id}");
                $byScan = is_array($rows)
                    ? collect($this->noc->matchRows($olt, $rows))->pluck('customer')->filter(fn ($c) => $c->status === Customer::STATUS_ACTIVE)
                    : collect();

                return ['label' => "OLT {$olt->name} port {$port->slot}/{$port->pon_port}",
                    'customers' => $byPosition->concat($byScan)->unique('id')->sortBy('name')->values()];
        }

        throw new RuntimeException('Cakupan gangguan tidak dikenal.');
    }

    /**
     * @param  array{scope_type: string, scope_id: string, title?: ?string, description?: ?string, estimate?: ?string,
     *               priority?: ?string, notify?: bool}  $data
     */
    public function open(array $data, ?User $by): NetworkIncident
    {
        ['label' => $label, 'customers' => $customers] = $this->affected($data['scope_type'], (string) $data['scope_id']);
        $existing = NetworkIncident::where('status', 'open')->where('scope_type', $data['scope_type'])->where('scope_id', (string) $data['scope_id'])->first();
        if ($existing) {
            throw new RuntimeException("Gangguan massal untuk {$label} masih terbuka (#{$existing->id}).");
        }
        $title = trim((string) ($data['title'] ?? '')) ?: "Gangguan massal {$label}";

        $incident = DB::transaction(function () use ($data, $label, $customers, $title, $by) {
            $incident = NetworkIncident::create([
                'scope_type' => $data['scope_type'], 'scope_id' => (string) $data['scope_id'], 'scope_label' => $label,
                'title' => $title, 'description' => $data['description'] ?? null, 'estimate' => $data['estimate'] ?? null,
                'status' => 'open', 'started_at' => now(), 'created_by' => $by?->id,
            ]);
            $incident->customers()->attach($customers->pluck('id'));

            $ticket = Ticket::create([
                'type' => 'gangguan', 'status' => 'open', 'priority' => $data['priority'] ?? 'urgent',
                'subject' => "[Gangguan massal] {$title}",
                'description' => trim(($data['description'] ?? '') . "\n\nCakupan: {$label}\nPelanggan aktif terdampak: {$customers->count()}"
                    . (! empty($data['estimate']) ? "\nEstimasi perbaikan: {$data['estimate']}" : '')),
            ]);
            $ticket->forceFill(['incident_id' => $incident->id])->saveQuietly();
            $incident->update(['ticket_id' => $ticket->id]);

            // Tiket gangguan pelanggan terdampak yang sudah terbuka ikut ditautkan
            Ticket::where('type', 'gangguan')->whereNull('incident_id')->whereNotIn('status', Ticket::DONE_STATUSES)
                ->whereIn('customer_id', $customers->pluck('id'))->get()
                ->each(fn ($t) => $this->attachTicket($t, $incident));

            return $incident;
        });

        ActivityLog::log('incident_opened', "Gangguan massal dibuka: {$title} ({$label}, {$customers->count()} pelanggan).", $incident);
        if (! empty($data['notify'])) {
            $this->notify($incident, 'gangguan_masal', $incident->estimate ?: 'secepatnya');
        }

        return $incident->fresh();
    }

    /** Tautkan tiket gangguan pelanggan ke gangguan massal (dengan catatan sistem di tiket). */
    public function attachTicket(Ticket $ticket, NetworkIncident $incident): void
    {
        $ticket->forceFill(['incident_id' => $incident->id])->saveQuietly();
        TicketReply::create(['ticket_id' => $ticket->id, 'user_id' => null, 'is_system' => true,
            'message' => "Tiket ini bagian dari gangguan massal: {$incident->title} (tiket induk " . ($incident->ticket?->ticket_number ?? '-') . '). Akan diselesaikan otomatis saat gangguan ditandai pulih.']);
    }

    /** Gangguan massal terbuka yang mencakup pelanggan ini (untuk menautkan tiket baru). */
    public static function openIncidentFor(int $customerId): ?NetworkIncident
    {
        return NetworkIncident::where('status', 'open')
            ->whereHas('customers', fn ($q) => $q->where('customers.id', $customerId))->latest('id')->first();
    }

    /** Kirim WhatsApp ke pelanggan terdampak yang masih aktif & punya nomor. @return int jumlah antrean */
    public function notify(NetworkIncident $incident, string $templateType, string $date): int
    {
        $template = WhatsappTemplate::where('type', $templateType)->where('is_active', true)->first();
        if (! $template) {
            return 0;
        }
        $delay = 0;
        $n = 0;
        foreach ($incident->customers()->where('status', Customer::STATUS_ACTIVE)->whereNotNull('phone')->where('phone', '!=', '')->get() as $c) {
            $log = WhatsappLog::create(['target_phone' => $c->phone, 'message' => "Template: {$template->name}", 'status' => 'pending']);
            $job = SendWhatsappMessageJob::dispatch($c->id, $template->id, $date, $log->id);
            if ($delay > 0) {
                $job->delay(now()->addSeconds($delay));
            }
            $delay += WhatsAppService::getRandomDelay();
            $n++;
        }
        $incident->increment($templateType === 'gangguan_pulih' ? 'resolved_notified_count' : 'notified_count', $n);

        return $n;
    }

    /** Tandai pulih: selesaikan tiket induk & tiket tertaut, opsional WhatsApp "sudah normal". */
    public function resolve(NetworkIncident $incident, ?string $notes, bool $notify, ?User $by): array
    {
        if (! $incident->isOpen()) {
            throw new RuntimeException('Gangguan ini sudah ditandai pulih.');
        }
        $notes = trim((string) $notes) ?: 'Gangguan massal teratasi.';
        $children = 0;
        DB::transaction(function () use ($incident, $notes, $by, &$children) {
            $incident->update(['status' => 'resolved', 'resolved_at' => now(), 'resolution_notes' => $notes, 'resolved_by' => $by?->id]);

            // Tiket pelanggan: satu per satu tanpa pesan ke grup WA (cukup satu pesan dari tiket induk)
            Ticket::$quietGroupNotify = true;
            try {
                foreach ($incident->tickets()->whereKeyNot($incident->ticket_id)->whereNotIn('status', Ticket::DONE_STATUSES)->get() as $t) {
                    $t->update(['status' => 'resolved', 'resolution_notes' => "Bagian dari gangguan massal: {$notes}"]);
                    $children++;
                }
            } finally {
                Ticket::$quietGroupNotify = false;
            }
            $incident->ticket?->update(['status' => 'resolved', 'resolution_notes' => $notes]);
        });

        ActivityLog::log('incident_resolved', "Gangguan massal pulih: {$incident->title} ({$children} tiket pelanggan ikut diselesaikan).", $incident);
        $sent = $notify ? $this->notify($incident, 'gangguan_pulih', now()->format('Y-m-d H:i')) : 0;

        return ['children' => $children, 'notified' => $sent];
    }
}
