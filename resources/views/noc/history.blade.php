@php
    $cache = $customer->signalCache;
    $points = $logs->filter(fn ($l) => $l->rx_power !== null)->values();
    $W = 900; $H = 260; $padL = 44; $padR = 12; $padT = 12; $padB = 28;
    $from = now()->subDays($days)->getTimestamp(); $to = now()->getTimestamp();
    $yMin = -35; $yMax = -5;
    if ($points->count()) {
        $yMin = min(-30, floor($points->min('rx_power')) - 1);
        $yMax = max(-12, ceil($points->max('rx_power')) + 1);
    }
    $x = fn ($t) => $padL + ($W - $padL - $padR) * (($t - $from) / max(1, $to - $from));
    $y = fn ($v) => $padT + ($H - $padT - $padB) * (($yMax - $v) / max(1, $yMax - $yMin));
    $poly = $points->map(fn ($p) => round($x($p->created_at->getTimestamp()), 1) . ',' . round($y($p->rx_power), 1))->implode(' ');
    $downs = $logs->filter(fn ($l) => in_array($l->status, \App\Services\Network\NocService::DOWN_STATUSES, true));
    $rxNow = $cache?->rx_power;
    $lvl = $rxNow === null ? 'none' : ($rxNow < -27 ? 'bad' : ($rxNow < -24 ? 'warn' : 'good'));
    $cls = ['good' => 'text-emerald-600 dark:text-emerald-400', 'warn' => 'text-amber-600', 'bad' => 'text-rose-600', 'none' => 'text-surface-400'][$lvl];
    $statusLabel = ['online' => 'Online', 'offline' => 'Mati', 'los' => 'LOS', 'dying-gasp' => 'Listrik padam', 'not_found' => 'Tidak ada di OLT'];
    $ticks = collect(range(0, 4))->map(fn ($i) => $from + ($to - $from) * $i / 4);
    $yTicks = collect(range((int) ceil($yMin / 5) * 5, (int) floor($yMax / 5) * 5, 5));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('noc.signals') }}" class="btn-ghost btn-icon" aria-label="Kembali"><x-icon name="chevron-right" class="w-5 h-5 rotate-180" /></a>
                <div class="min-w-0">
                    <h2 class="truncate font-bold text-2xl text-surface-900 dark:text-white leading-tight">Riwayat Sinyal · {{ $customer->name }}</h2>
                    <p class="text-sm text-surface-500 dark:text-surface-400"><span class="font-mono">{{ $customer->customer_code }}</span> · {{ $customer->olt?->name ?? 'OLT belum diisi' }} · <span class="font-mono">{{ strtoupper((string) $customer->onu_sn) }}</span></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="inline-flex rounded-lg border border-surface-200 p-0.5 dark:border-surface-700">
                    @foreach([1 => '24 jam', 7 => '7 hari', 30 => '30 hari'] as $d => $label)
                        <a href="{{ route('noc.history', [$customer->id, 'days' => $d]) }}" class="rounded-md px-3 py-1 text-xs font-medium {{ $days === $d ? 'bg-primary-600 text-white' : 'text-surface-600 hover:bg-surface-50 dark:text-surface-300 dark:hover:bg-surface-800' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <a href="{{ route('customers.show', $customer->id) }}" class="btn-secondary">Data pelanggan</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Sinyal sekarang</p>
                <p class="mt-1 text-xl font-semibold tabular-nums {{ $cls }}">{{ $rxNow !== null ? number_format($rxNow, 2) . ' dBm' : '—' }}</p>
                <p class="text-xs text-surface-500">{{ $cache?->last_polled_at?->locale('id')->diffForHumans() ?? 'belum terbaca' }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Status ONU</p>
                <p class="mt-1"><span class="{{ ($cache?->status) === 'online' ? 'badge-success' : ($cache ? 'badge-danger' : 'badge-gray') }}">{{ $cache ? ($statusLabel[$cache->status] ?? $cache->status) : 'Belum terbaca' }}</span></p>
                <p class="mt-1 font-mono text-xs text-surface-500">{{ $cache?->onu_index ?? $customer->onu_index }}</p>
            </div>
            <div class="card p-4"><p class="text-xs font-medium text-surface-500">Rata-rata</p><p class="mt-1 text-xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $summary['avg'] !== null ? number_format($summary['avg'], 2) : '—' }}</p><p class="text-xs text-surface-500">dBm, {{ $days === 1 ? '24 jam' : $days . ' hari' }}</p></div>
            <div class="card p-4"><p class="text-xs font-medium text-surface-500">Terlemah / terkuat</p><p class="mt-1 text-xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $summary['min'] !== null ? number_format($summary['min'], 1) . ' / ' . number_format($summary['max'], 1) : '—' }}</p><p class="text-xs text-surface-500">dBm</p></div>
            <div class="card p-4 col-span-2 lg:col-span-1"><p class="text-xs font-medium text-surface-500">ONU putus</p><p class="mt-1 text-xl font-semibold tabular-nums {{ $summary['down_events'] ? 'text-rose-600' : 'text-surface-900 dark:text-white' }}">{{ $summary['down_events'] }}×</p><p class="text-xs text-surface-500">dalam periode ini</p></div>
        </div>

        <section class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="card-title">Grafik daya terima (Rx)</h3>
                <span class="flex items-center gap-3 text-xs text-surface-500">
                    <span class="inline-flex items-center gap-1"><span class="h-0.5 w-4 bg-amber-500"></span>-24</span>
                    <span class="inline-flex items-center gap-1"><span class="h-0.5 w-4 bg-rose-500"></span>-27 dBm</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span>ONU putus</span>
                </span>
            </div>
            <div class="card-body">
                @if($points->count() || $downs->count())
                    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Grafik sinyal Rx {{ $customer->name }}">
                        @foreach($yTicks as $t)
                            <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" class="stroke-surface-200 dark:stroke-surface-700" stroke-width="1" />
                            <text x="{{ $padL - 6 }}" y="{{ $y($t) + 4 }}" text-anchor="end" class="fill-surface-400" font-size="11">{{ $t }}</text>
                        @endforeach
                        @foreach([-24 => '#f59e0b', -27 => '#f43f5e'] as $th => $color)
                            @if($th <= $yMax && $th >= $yMin)
                                <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y($th) }}" y2="{{ $y($th) }}" stroke="{{ $color }}" stroke-width="1" stroke-dasharray="4 4" />
                            @endif
                        @endforeach
                        @foreach($ticks as $t)
                            <text x="{{ $x($t) }}" y="{{ $H - 8 }}" text-anchor="middle" class="fill-surface-400" font-size="11">{{ \Carbon\Carbon::createFromTimestamp($t)->format($days === 1 ? 'H:i' : 'd/m') }}</text>
                        @endforeach
                        @if($points->count() > 1)
                            <polyline points="{{ $poly }}" fill="none" stroke="#6366f1" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                        @endif
                        @foreach($points as $p)
                            <circle cx="{{ $x($p->created_at->getTimestamp()) }}" cy="{{ $y($p->rx_power) }}" r="{{ $points->count() > 60 ? 1.5 : 3 }}" fill="#6366f1"><title>{{ $p->created_at->format('d/m H:i') }} · {{ number_format($p->rx_power, 2) }} dBm</title></circle>
                        @endforeach
                        @foreach($downs as $d)
                            <circle cx="{{ $x($d->created_at->getTimestamp()) }}" cy="{{ $H - $padB }}" r="4" fill="#f43f5e"><title>{{ $d->created_at->format('d/m H:i') }} · {{ $statusLabel[$d->status] ?? $d->status }}</title></circle>
                        @endforeach
                    </svg>
                @else
                    <p class="py-10 text-center text-sm text-surface-500">Belum ada riwayat sinyal pada periode ini. Riwayat terisi dari <strong>Sinkron ONU</strong> dan pemantauan otomatis (Integrasi › Bot NOC).</p>
                @endif
            </div>
        </section>

        @if($logs->count())
            <section class="card overflow-hidden">
                <div class="card-header"><h3 class="card-title">Catatan ({{ $logs->count() }})</h3></div>
                <div class="max-h-80 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-surface-50 text-left text-xs font-medium uppercase tracking-wide text-surface-500 dark:bg-surface-900"><tr><th class="px-5 py-2">Waktu</th><th class="px-5 py-2">Rx</th><th class="px-5 py-2">Status</th></tr></thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @foreach($logs->reverse() as $l)
                                <tr>
                                    <td class="px-5 py-2 text-surface-700 dark:text-surface-300">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-2 font-mono tabular-nums">{{ $l->rx_power !== null ? number_format($l->rx_power, 2) . ' dBm' : '—' }}</td>
                                    <td class="px-5 py-2"><span class="{{ $l->status === 'online' ? 'badge-success' : 'badge-danger' }}">{{ $statusLabel[$l->status] ?? $l->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
