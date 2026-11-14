@php
    use App\Services\Reports\TicketRecapService as Recap;
    $months = \App\Support\Period::MONTHS;
    $dur = fn ($m) => Recap::formatDuration($m);
    $pct = fn ($p) => $p === null ? '—' : rtrim(rtrim(number_format($p, 1, ',', '.'), '0'), ',') . '%';
    $pctClass = fn ($p) => $p === null ? 'text-surface-400' : ($p >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($p >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'));
    $num = fn ($n) => $n ? number_format($n, 0, ',', '.') : '<span class="text-surface-300 dark:text-surface-600">0</span>';
    $isYear = $month === null;
    $statusBadge = [
        'open' => 'badge-info', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success',
        'closed' => 'badge-gray', 'canceled' => 'badge-danger',
    ];
    $statusLabel = ['open' => 'Open', 'in_progress' => 'Dikerjakan', 'resolved' => 'Resolved', 'closed' => 'Closed', 'canceled' => 'Dibatalkan'];
    $s = $summary;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Rekap Pekerjaan Tim" :subtitle="'Audit tiket · ' . $periodLabel . ' · SLA ≤ ' . $slaHours . ' jam'">
            <x-slot name="filters">
                <form method="GET" class="flex flex-wrap items-end gap-2" x-data="{ period: '{{ $isYear ? 'year' : 'month' }}' }">
                    <div>
                        <label for="period" class="label !mb-1 text-xs">Periode</label>
                        <select id="period" name="period" x-model="period" class="input !h-9 !w-auto">
                            <option value="month">Bulanan</option>
                            <option value="year">Tahunan</option>
                        </select>
                    </div>
                    <div x-show="period === 'month'">
                        <label for="month" class="label !mb-1 text-xs">Bulan</label>
                        <select id="month" name="month" class="input !h-9 !w-auto" :disabled="period !== 'month'">
                            @foreach($months as $m => $name)
                                <option value="{{ $m }}" @selected(($month ?? now()->month) == $m)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="year" class="label !mb-1 text-xs">Tahun</label>
                        <select id="year" name="year" class="input !h-9 !w-auto">
                            @foreach($years as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('tickets.recap.export', request()->query()) }}" class="btn-secondary">
                    <x-icon name="arrow-down" class="w-4 h-4" /> Ekspor Excel
                </a>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="space-y-6">
        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <x-stat-card label="Tiket masuk" :value="number_format($s['masuk'], 0, ',', '.')" icon="inbox" tone="info" />
            <x-stat-card label="Diselesaikan" :value="number_format($s['selesai'], 0, ',', '.')" icon="check-circle" tone="success"
                         hint="Termasuk tiket dari periode sebelumnya" />
            <x-stat-card label="Masih terbuka" :value="number_format($s['terbuka'], 0, ',', '.')" icon="clock" tone="warning"
                         hint="Dari tiket yang masuk periode ini" />
            <x-stat-card label="Rata-rata durasi" :value="$dur($s['avg_minutes'])" icon="refresh" tone="gray" hint="Dibuat → selesai" />
            <x-stat-card label="Sesuai SLA" :value="$pct($s['sla_pct'])" icon="shield-check" :tone="$s['sla_pct'] === null ? 'gray' : ($s['sla_pct'] >= 90 ? 'success' : ($s['sla_pct'] >= 70 ? 'warning' : 'danger'))"
                         :hint="$s['sla_ok'] . ' sesuai · ' . $s['sla_late'] . ' lewat ' . $slaHours . ' jam'" />
        </div>

        {{-- Kinerja per petugas --}}
        <section class="card overflow-hidden">
            <div class="card-header">
                <h3 class="card-title">Kinerja per petugas</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">Diurutkan dari penyelesaian terbanyak. Teknisi aktif tanpa tiket tetap ditampilkan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Petugas</th>
                            <th class="text-right">Ditugaskan</th>
                            <th class="text-right">Selesai</th>
                            @foreach(Recap::TYPES as $label)
                                <th class="text-right font-normal normal-case">{{ $label }}</th>
                            @endforeach
                            <th class="text-right">Batal</th>
                            <th class="text-right">Terbuka</th>
                            <th class="text-right">Rata-rata</th>
                            <th class="text-right">% SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byStaff as $r)
                            <tr>
                                <td class="font-medium text-surface-900 dark:text-white whitespace-nowrap">
                                    @if($r['id'] === null)
                                        <span class="italic text-surface-500">{{ $r['name'] }}</span>
                                    @else
                                        {{ $r['name'] }}
                                        @if($r['role'] && $r['role'] !== 'teknisi')
                                            <span class="badge-gray ml-1">{{ ucfirst($r['role']) }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="amount">{!! $num($r['masuk']) !!}</td>
                                <td class="amount font-semibold text-surface-900 dark:text-white">{!! $num($r['selesai']) !!}</td>
                                @foreach(array_keys(Recap::TYPES) as $t)
                                    <td class="amount text-surface-500">{!! $num($r['selesai_per_tipe'][$t]) !!}</td>
                                @endforeach
                                <td class="amount">{!! $num($r['dibatalkan']) !!}</td>
                                <td class="amount {{ $r['terbuka'] ? 'text-amber-600 dark:text-amber-400' : '' }}">{!! $num($r['terbuka']) !!}</td>
                                <td class="amount">{{ $dur($r['avg_minutes']) }}</td>
                                <td class="amount font-medium {{ $pctClass($r['sla_pct']) }}">{{ $pct($r['sla_pct']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-surface-500 py-8">Belum ada petugas atau tiket pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold text-surface-900 dark:text-white border-t-2 border-surface-300 dark:border-surface-700">
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 amount">{{ number_format($s['masuk'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 amount">{{ number_format($s['selesai'], 0, ',', '.') }}</td>
                            @foreach(array_keys(Recap::TYPES) as $t)
                                <td class="px-4 py-3 amount">{{ number_format($byType[$t]['selesai'], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-4 py-3 amount">{{ number_format($s['dibatalkan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 amount">{{ number_format($s['terbuka'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 amount">{{ $dur($s['avg_minutes']) }}</td>
                            <td class="px-4 py-3 amount {{ $pctClass($s['sla_pct']) }}">{{ $pct($s['sla_pct']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- Per tipe --}}
        <section class="card overflow-hidden">
            <div class="card-header"><h3 class="card-title">Per tipe tiket</h3></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th class="text-right">Masuk</th>
                            <th class="text-right">Selesai</th>
                            <th class="text-right">Batal</th>
                            <th class="text-right">Terbuka</th>
                            <th class="text-right">Rata-rata durasi</th>
                            <th class="text-right">Sesuai / lewat SLA</th>
                            <th class="text-right">% SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byType as $r)
                            <tr>
                                <td class="font-medium text-surface-900 dark:text-white">{{ $r['label'] }}</td>
                                <td class="amount">{!! $num($r['masuk']) !!}</td>
                                <td class="amount font-semibold text-surface-900 dark:text-white">{!! $num($r['selesai']) !!}</td>
                                <td class="amount">{!! $num($r['dibatalkan']) !!}</td>
                                <td class="amount">{!! $num($r['terbuka']) !!}</td>
                                <td class="amount">{{ $dur($r['avg_minutes']) }}</td>
                                <td class="amount">{{ $r['sla_ok'] }} / <span class="{{ $r['sla_late'] ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ $r['sla_late'] }}</span></td>
                                <td class="amount font-medium {{ $pctClass($r['sla_pct']) }}">{{ $pct($r['sla_pct']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if($isYear)
            {{-- Per bulan --}}
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h3 class="card-title">Per bulan</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400 no-print">Klik nama bulan untuk rincian tiketnya.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">Masuk</th>
                                <th class="text-right font-normal normal-case">Aktivasi</th>
                                <th class="text-right font-normal normal-case">Gangguan</th>
                                <th class="text-right font-normal normal-case">Dismantle</th>
                                <th class="text-right">Selesai</th>
                                <th class="text-right">Batal</th>
                                <th class="text-right">Terbuka</th>
                                <th class="text-right">Rata-rata</th>
                                <th class="text-right">% SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byMonth as $m => $r)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('tickets.recap', ['period' => 'month', 'year' => $year, 'month' => $m]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $months[$m] }}</a>
                                    </td>
                                    <td class="amount font-semibold text-surface-900 dark:text-white">{!! $num($r['masuk']) !!}</td>
                                    <td class="amount text-surface-500">{!! $num($r['masuk_aktivasi']) !!}</td>
                                    <td class="amount text-surface-500">{!! $num($r['masuk_gangguan']) !!}</td>
                                    <td class="amount text-surface-500">{!! $num($r['masuk_dismantle']) !!}</td>
                                    <td class="amount font-semibold text-surface-900 dark:text-white">{!! $num($r['selesai']) !!}</td>
                                    <td class="amount">{!! $num($r['dibatalkan']) !!}</td>
                                    <td class="amount">{!! $num($r['terbuka']) !!}</td>
                                    <td class="amount">{{ $dur($r['avg_minutes']) }}</td>
                                    <td class="amount font-medium {{ $pctClass($r['sla_pct']) }}">{{ $pct($r['sla_pct']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold text-surface-900 dark:text-white border-t-2 border-surface-300 dark:border-surface-700">
                                <td class="px-4 py-3">Total {{ $year }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($s['masuk'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($byType['aktivasi']['masuk'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($byType['gangguan']['masuk'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($byType['dismantle']['masuk'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($s['selesai'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($s['dibatalkan'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ number_format($s['terbuka'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 amount">{{ $dur($s['avg_minutes']) }}</td>
                                <td class="px-4 py-3 amount {{ $pctClass($s['sla_pct']) }}">{{ $pct($s['sla_pct']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            {{-- Petugas x bulan --}}
            <section class="card overflow-hidden">
                <div class="card-header"><h3 class="card-title">Tiket diselesaikan per petugas per bulan</h3></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Petugas</th>
                                @foreach($months as $name)
                                    <th class="text-right !px-2">{{ mb_substr($name, 0, 3) }}</th>
                                @endforeach
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staffByMonth as $r)
                                <tr>
                                    <td class="font-medium text-surface-900 dark:text-white whitespace-nowrap">{{ $r['name'] }}</td>
                                    @foreach($r['months'] as $n)
                                        <td class="amount !px-2">{!! $num($n) !!}</td>
                                    @endforeach
                                    <td class="amount font-semibold text-surface-900 dark:text-white">{{ number_format($r['total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            {{-- Rincian tiket --}}
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h3 class="card-title">Rincian tiket ({{ number_format($tickets->count(), 0, ',', '.') }})</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Tiket yang masuk atau diselesaikan pada {{ $periodLabel }}.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. tiket</th>
                                <th>Dibuat</th>
                                <th>Tipe</th>
                                <th>Pelanggan / subjek</th>
                                <th>Petugas</th>
                                <th>Status</th>
                                <th>Selesai</th>
                                <th class="text-right">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $t)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('tickets.show', $t['id']) }}" class="font-mono text-xs text-primary-600 hover:underline dark:text-primary-400">{{ $t['number'] }}</a>
                                    </td>
                                    <td class="whitespace-nowrap tabular-nums text-xs">{{ $t['created_at']->format('d/m/Y H:i') }}</td>
                                    <td>{{ Recap::TYPES[$t['type']] ?? $t['type'] }}</td>
                                    <td class="min-w-[14rem]">
                                        <div class="text-surface-900 dark:text-white">{{ $t['customer'] ?? '—' }}</div>
                                        <div class="text-xs text-surface-500 line-clamp-1">{{ $t['subject'] }}</div>
                                    </td>
                                    <td class="whitespace-nowrap">{!! $t['staff'] ? e($t['staff']) : '<span class="italic text-surface-400">Belum ditugaskan</span>' !!}</td>
                                    <td><span class="{{ $statusBadge[$t['status']] ?? 'badge-gray' }}">{{ $statusLabel[$t['status']] ?? $t['status'] }}</span></td>
                                    <td class="whitespace-nowrap tabular-nums text-xs">{{ $t['resolved_at']?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="amount whitespace-nowrap">
                                        @if($t['minutes'] !== null)
                                            <span class="{{ $t['sla_ok'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" title="{{ $t['sla_ok'] ? 'Sesuai SLA' : 'Lewat SLA' }}">{{ $dur($t['minutes']) }}</span>
                                        @else
                                            <span class="text-surface-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-surface-500 py-8">Tidak ada tiket pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <p class="text-xs text-surface-500 dark:text-surface-400">
            <strong>Ditugaskan/masuk</strong> = tiket dibuat dalam periode. <strong>Selesai</strong> = tiket berstatus Resolved/Closed yang diselesaikan dalam periode.
            <strong>Durasi</strong> dihitung dari tiket dibuat sampai diselesaikan; <strong>SLA</strong> terpenuhi bila ≤ {{ $slaHours }} jam.
        </p>
    </div>
</x-app-layout>
