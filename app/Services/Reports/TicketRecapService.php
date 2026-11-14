<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Rekap pekerjaan tim dari tiket, per bulan atau per tahun (audit kinerja).
 *
 * Definisi:
 *  - Masuk       : tiket dibuat dalam periode.
 *  - Selesai     : tiket berstatus resolved/closed dengan resolved_at dalam periode
 *                  (bisa berasal dari tiket yang masuk periode sebelumnya).
 *  - Dibatalkan  : tiket masuk dalam periode yang kini berstatus canceled.
 *  - Masih terbuka: tiket masuk dalam periode yang kini masih open/in_progress.
 *  - Durasi      : resolved_at - created_at; SLA terpenuhi bila <= SLA_HOURS.
 */
class TicketRecapService
{
    public const SLA_HOURS = 24;
    public const TYPES = ['aktivasi' => 'Aktivasi', 'gangguan' => 'Gangguan', 'dismantle' => 'Dismantle'];
    public const OPEN_STATUSES = ['open', 'in_progress'];

    public function period(int $year, ?int $month): array
    {
        $start = $month
            ? CarbonImmutable::create($year, $month, 1)->startOfMonth()
            : CarbonImmutable::create($year, 1, 1)->startOfYear();
        $end = $month ? $start->endOfMonth() : $start->endOfYear();

        return [$start, $end];
    }

    public function build(int $year, ?int $month = null): array
    {
        [$start, $end] = $this->period($year, $month);

        $created = Ticket::with(['assignee:id,name,role', 'customer:id,name,customer_code'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $resolved = Ticket::with(['assignee:id,name,role', 'customer:id,name,customer_code'])
            ->whereIn('status', Ticket::DONE_STATUSES)
            ->whereBetween('resolved_at', [$start, $end])
            ->orderBy('resolved_at')
            ->get();

        $data = [
            'year' => $year,
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'slaHours' => self::SLA_HOURS,
            'summary' => $this->metrics($created, $resolved),
            'byType' => $this->byType($created, $resolved),
            'byStaff' => $this->byStaff($created, $resolved),
        ];

        if ($month) {
            $data['tickets'] = $this->detail($created, $resolved);
        } else {
            $data['byMonth'] = $this->byMonth($created, $resolved);
            $data['staffByMonth'] = $this->staffByMonth($resolved, $data['byStaff']);
        }

        return $data;
    }

    /** Jumlah & kualitas penyelesaian untuk satu kelompok tiket. */
    private function metrics(Collection $created, Collection $resolved): array
    {
        $minutes = $resolved->map(fn (Ticket $t) => $this->durationMinutes($t))->filter(fn ($m) => $m !== null);
        $withinSla = $minutes->filter(fn ($m) => $m <= self::SLA_HOURS * 60)->count();

        return [
            'masuk' => $created->count(),
            'selesai' => $resolved->count(),
            'dibatalkan' => $created->where('status', 'canceled')->count(),
            'terbuka' => $created->whereIn('status', self::OPEN_STATUSES)->count(),
            'avg_minutes' => $minutes->isEmpty() ? null : (int) round($minutes->avg()),
            'sla_ok' => $withinSla,
            'sla_late' => $minutes->count() - $withinSla,
            'sla_pct' => $minutes->isEmpty() ? null : round($withinSla / $minutes->count() * 100, 1),
        ];
    }

    private function byType(Collection $created, Collection $resolved): array
    {
        $rows = [];
        foreach (self::TYPES as $type => $label) {
            $rows[$type] = ['label' => $label] + $this->metrics(
                $created->where('type', $type),
                $resolved->where('type', $type),
            );
        }

        return $rows;
    }

    /**
     * Satu baris per petugas: semua teknisi aktif (walau nol pekerjaan, agar terlihat saat audit)
     * ditambah siapa pun yang memegang tiket dalam periode, serta baris "Belum ditugaskan".
     */
    private function byStaff(Collection $created, Collection $resolved): array
    {
        $staff = User::where('role', User::ROLE_TEKNISI)->where('is_active', true)->get(['id', 'name', 'role'])
            ->keyBy('id');
        foreach ($created->concat($resolved) as $t) {
            if ($t->assignee && ! $staff->has($t->assignee->id)) {
                $staff->put($t->assignee->id, $t->assignee);
            }
        }

        $rows = [];
        foreach ($staff->sortBy('name') as $id => $user) {
            $rows[] = $this->staffRow($id, $user->name, $user->role, $created, $resolved);
        }

        $unassigned = $this->staffRow(null, 'Belum ditugaskan', null, $created, $resolved);
        if ($unassigned['masuk'] || $unassigned['selesai']) {
            $rows[] = $unassigned;
        }

        // Paling produktif di atas.
        usort($rows, fn ($a, $b) => [$a['id'] === null, -$a['selesai'], $a['name']] <=> [$b['id'] === null, -$b['selesai'], $b['name']]);

        return $rows;
    }

    private function staffRow(?int $id, string $name, ?string $role, Collection $created, Collection $resolved): array
    {
        $mine = fn (Ticket $t) => ($t->assigned_to === null ? null : (int) $t->assigned_to) === $id;
        $c = $created->filter($mine);
        $r = $resolved->filter($mine);

        $perType = [];
        foreach (array_keys(self::TYPES) as $type) {
            $perType[$type] = $r->where('type', $type)->count();
        }

        return ['id' => $id, 'name' => $name, 'role' => $role, 'selesai_per_tipe' => $perType] + $this->metrics($c, $r);
    }

    private function byMonth(Collection $created, Collection $resolved): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $inMonth = fn ($date) => fn (Ticket $t) => $t->{$date} && (int) $t->{$date}->month === $m;
            $rows[$m] = $this->metrics($created->filter($inMonth('created_at')), $resolved->filter($inMonth('resolved_at')));
            foreach (array_keys(self::TYPES) as $type) {
                $rows[$m]['masuk_' . $type] = $created->filter($inMonth('created_at'))->where('type', $type)->count();
            }
        }

        return $rows;
    }

    /** Matriks petugas x bulan: jumlah tiket yang diselesaikan. */
    private function staffByMonth(Collection $resolved, array $byStaff): array
    {
        $matrix = [];
        foreach ($byStaff as $row) {
            $counts = array_fill(1, 12, 0);
            foreach ($resolved as $t) {
                $assignee = $t->assigned_to === null ? null : (int) $t->assigned_to;
                if ($assignee === $row['id']) {
                    $counts[(int) $t->resolved_at->month]++;
                }
            }
            $matrix[] = ['name' => $row['name'], 'months' => $counts, 'total' => array_sum($counts)];
        }

        return $matrix;
    }

    /** Daftar tiket yang masuk ATAU selesai dalam periode (untuk audit bulanan). */
    private function detail(Collection $created, Collection $resolved): Collection
    {
        return $created->concat($resolved)->unique('id')->sortBy('created_at')->values()
            ->map(function (Ticket $t) {
                $minutes = in_array($t->status, Ticket::DONE_STATUSES, true) ? $this->durationMinutes($t) : null;

                return [
                    'id' => $t->id,
                    'number' => $t->ticket_number,
                    'type' => $t->type,
                    'priority' => $t->priority,
                    'status' => $t->status,
                    'subject' => $t->subject,
                    'customer' => $t->customer?->name,
                    'customer_code' => $t->customer?->customer_code,
                    'staff' => $t->assignee?->name,
                    'created_at' => $t->created_at,
                    'resolved_at' => in_array($t->status, Ticket::DONE_STATUSES, true) ? $t->resolved_at : null,
                    'minutes' => $minutes,
                    'sla_ok' => $minutes === null ? null : $minutes <= self::SLA_HOURS * 60,
                ];
            });
    }

    private function durationMinutes(Ticket $t): ?int
    {
        if (! $t->resolved_at || ! $t->created_at) {
            return null;
        }

        return (int) max(0, round($t->created_at->diffInMinutes($t->resolved_at, false)));
    }

    /** "45 mnt", "5 j 20 mnt", "2 h 4 j". */
    public static function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }
        if ($minutes < 60) {
            return $minutes . ' mnt';
        }
        if ($minutes < 1440) {
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;

            return $h . ' j' . ($m ? ' ' . $m . ' mnt' : '');
        }
        $d = intdiv($minutes, 1440);
        $h = intdiv($minutes % 1440, 60);

        return $d . ' h' . ($h ? ' ' . $h . ' j' : '');
    }
}
