@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $pct = fn ($a, $b) => $b > 0 ? round($a / $b * 100) : 0;
    $statusLabel = ['offline' => 'Mati', 'los' => 'LOS', 'dying-gasp' => 'Listrik padam', 'not_found' => 'Tidak ada di OLT'];
    $lastSync = $stats['last_sync_at'] ? \Carbon\Carbon::createFromTimestamp($stats['last_sync_at'])->locale('id') : null;
    $sigTotal = $stats['signal_good'] + $stats['signal_warn'] + $stats['signal_critical'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">NOC Center</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    Kondisi jaringan & ONU pelanggan.
                    <span x-data title="{{ $lastSync?->format('d/m/Y H:i') }}">Data ONU terakhir: <strong>{{ $lastSync ? $lastSync->diffForHumans() : 'belum pernah' }}</strong></span>
                </p>
            </div>
            <div class="flex items-center gap-2" x-data="nocSync()">
                <span x-show="msg" x-cloak class="max-w-xs text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'" x-text="msg"></span>
                <a href="{{ route('noc.index', ['refresh' => 1]) }}" class="btn-secondary" title="Baca ulang CPU, suhu & uptime OLT"><x-icon name="server" class="w-4 h-4" /> Status OLT</a>
                <button type="button" class="btn-primary" :disabled="busy" @click="run()">
                    <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <x-icon name="refresh" class="w-4 h-4" x-show="!busy" />
                    <span x-text="busy ? 'Membaca ONU semua OLT…' : 'Sinkron ONU'"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <a href="{{ route('online-users.index') }}" class="card p-4 transition-colors hover:border-primary-300">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-surface-500">PPPoE online</p><x-icon name="signal" class="w-4 h-4 text-surface-400" /></div>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $fmt($stats['pppoe_online']) }}<span class="text-base font-normal text-surface-400"> / {{ $fmt($stats['customers_active']) }}</span></p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct($stats['pppoe_online'], $stats['customers_active']) }}%"></div></div>
                <p class="mt-1.5 text-xs text-surface-500">{{ $fmt($stats['pppoe_offline']) }} pelanggan aktif offline{{ $stats['isolated_online'] ? ' · ' . $fmt($stats['isolated_online']) . ' terisolir tersambung' : '' }}</p>
            </a>
            <a href="{{ route('noc.signals', ['filter' => 'down']) }}" class="card p-4 transition-colors hover:border-primary-300">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-surface-500">ONU online</p><x-icon name="chip" class="w-4 h-4 text-surface-400" /></div>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $fmt($stats['onu_online']) }}<span class="text-base font-normal text-surface-400"> / {{ $fmt($stats['onu_tracked']) }}</span></p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct($stats['onu_online'], $stats['onu_tracked']) }}%"></div></div>
                <p class="mt-1.5 text-xs {{ $stats['onu_down'] ? 'text-rose-600' : 'text-surface-500' }}">{{ $fmt($stats['onu_down']) }} mati{{ $stats['onu_not_found'] ? ' · ' . $fmt($stats['onu_not_found']) . ' tidak ada di OLT' : '' }}</p>
            </a>
            <a href="{{ route('noc.signals', ['filter' => 'critical']) }}" class="card p-4 transition-colors hover:border-primary-300">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-surface-500">Sinyal optik</p><x-icon name="trending-down" class="w-4 h-4 text-surface-400" /></div>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $stats['signal_critical'] ? 'text-rose-600' : 'text-surface-900 dark:text-white' }}">{{ $fmt($stats['signal_critical']) }}<span class="text-base font-normal text-surface-400"> kritis</span></p>
                <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700">
                    @if($sigTotal)
                        <div class="bg-emerald-500" style="width: {{ $pct($stats['signal_good'], $sigTotal) }}%"></div>
                        <div class="bg-amber-500" style="width: {{ $pct($stats['signal_warn'], $sigTotal) }}%"></div>
                        <div class="bg-rose-500" style="width: {{ $pct($stats['signal_critical'], $sigTotal) }}%"></div>
                    @endif
                </div>
                <p class="mt-1.5 text-xs text-surface-500">{{ $fmt($stats['signal_good']) }} baik · {{ $fmt($stats['signal_warn']) }} waspada</p>
            </a>
            <a href="{{ route('noc.discovery') }}" class="card p-4 transition-colors hover:border-primary-300">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-surface-500">ONU belum terdaftar</p><x-icon name="search" class="w-4 h-4 text-surface-400" /></div>
                @if($unregistered)
                    <p class="mt-1 text-2xl font-semibold tabular-nums {{ count($unregistered['onus']) ? 'text-amber-600' : 'text-surface-900 dark:text-white' }}">{{ count($unregistered['onus']) }}</p>
                    <p class="mt-1.5 text-xs text-surface-500">dipindai {{ \Carbon\Carbon::createFromTimestamp($unregistered['at'])->locale('id')->diffForHumans() }}</p>
                @else
                    <p class="mt-1 text-2xl font-semibold text-surface-300 dark:text-surface-600">—</p>
                    <p class="mt-1.5 text-xs text-primary-600">Pindai sekarang →</p>
                @endif
            </a>
            <a href="{{ route('tickets.index', ['type' => 'gangguan']) }}" class="card col-span-2 p-4 transition-colors hover:border-primary-300 lg:col-span-1">
                <div class="flex items-center justify-between"><p class="text-xs font-medium text-surface-500">Tiket gangguan terbuka</p><x-icon name="ticket" class="w-4 h-4 text-surface-400" /></div>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $openFaults ? 'text-amber-600' : 'text-surface-900 dark:text-white' }}">{{ $fmt($openFaults) }}</p>
                <p class="mt-1.5 text-xs text-surface-500">belum selesai</p>
            </a>
        </div>

        {{-- OLT --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-surface-900 dark:text-white">OLT</h3>
                <a href="{{ route('olts.index') }}" class="text-xs text-primary-600 hover:underline">Kelola OLT →</a>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($olts as $olt)
                    @php
                        $s = $oltStats[$olt->id] ?? null;
                        $up = ($s['status'] ?? null) === 'online';
                        $cpu = (int) ($s['cpu'] ?? 0);
                        $temp = (int) ($s['temp'] ?? 0);
                        $o = $oltOnus[$olt->id] ?? null;
                    @endphp
                    <a href="{{ route('olts.show', $olt) }}" class="card p-4 transition-colors hover:border-primary-300">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-surface-900 dark:text-white">{{ $olt->name }}</p>
                                <p class="font-mono text-xs text-surface-500">{{ $olt->ip_address }} · {{ $olt->typeLabel() }}</p>
                            </div>
                            <span class="{{ $up ? 'badge-success' : ($s ? 'badge-danger' : 'badge-gray') }}">
                                @if($up)<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>@endif
                                {{ $up ? 'Online' : ($s ? 'Tidak merespons' : 'Belum dicek') }}
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-2 text-center">
                            <div><p class="text-[11px] text-surface-500">ONU</p><p class="text-sm font-semibold tabular-nums text-surface-900 dark:text-white">{{ $o ? $o['online'] . '/' . $o['total'] : '—' }}</p></div>
                            <div><p class="text-[11px] text-surface-500">CPU</p><p class="text-sm font-semibold tabular-nums {{ $cpu >= 85 ? 'text-rose-600' : 'text-surface-900 dark:text-white' }}">{{ $up ? $cpu . '%' : '—' }}</p></div>
                            <div><p class="text-[11px] text-surface-500">Suhu</p><p class="text-sm font-semibold tabular-nums {{ $temp >= 65 ? 'text-rose-600' : ($temp >= 55 ? 'text-amber-600' : 'text-surface-900 dark:text-white') }}">{{ $up && $temp ? $temp . '°C' : '—' }}</p></div>
                            <div><p class="text-[11px] text-surface-500">Uptime</p><p class="truncate text-sm font-semibold text-surface-900 dark:text-white">{{ $up ? ($s['uptime'] ?? '—') : '—' }}</p></div>
                        </div>
                        @if($o && $o['total'])
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $pct($o['online'], $o['total']) }}%"></div></div>
                        @endif
                    </a>
                @empty
                    <div class="card p-6 text-sm text-surface-500">Belum ada OLT aktif. <a href="{{ route('olts.index') }}" class="text-primary-600 hover:underline">Tambah OLT</a></div>
                @endforelse
            </div>
        </section>

        {{-- Yang perlu ditindaklanjuti --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="card overflow-hidden">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h3 class="card-title">ONU pelanggan aktif bermasalah</h3>
                        <p class="text-xs text-surface-500">Mati / LOS / tidak ditemukan di OLT pada sinkron terakhir.</p>
                    </div>
                    <a href="{{ route('noc.signals', ['filter' => 'down']) }}" class="text-xs text-primary-600 hover:underline">Semua →</a>
                </div>
                <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                    @forelse($down as $row)
                        <li>
                            <a href="{{ route('noc.history', $row->customer_id) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-surface-900 dark:text-white">{{ $row->customer->name }}</span>
                                    <span class="block font-mono text-[11px] text-surface-500">{{ $row->customer->customer_code }} · {{ $row->onu_index }}</span>
                                </span>
                                <span class="text-right">
                                    <span class="badge-danger">{{ $statusLabel[$row->status] ?? $row->status }}</span>
                                    <span class="block text-[11px] text-surface-500">{{ $row->last_polled_at?->locale('id')->diffForHumans() }}</span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-surface-500"><x-icon name="check-circle" class="mx-auto mb-1 w-6 h-6 text-emerald-500" /> Tidak ada ONU pelanggan aktif yang mati.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card overflow-hidden">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h3 class="card-title">Sinyal kritis terlemah</h3>
                        <p class="text-xs text-surface-500">Rx &lt; {{ \App\Services\Network\NocService::CRITICAL_DBM }} dBm — cek konektor, kabel drop atau splitter.</p>
                    </div>
                    <a href="{{ route('noc.signals', ['filter' => 'critical']) }}" class="text-xs text-primary-600 hover:underline">Semua →</a>
                </div>
                <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                    @forelse($critical as $row)
                        <li>
                            <a href="{{ route('noc.history', $row->customer_id) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-surface-900 dark:text-white">{{ $row->customer->name }}</span>
                                    <span class="block font-mono text-[11px] text-surface-500">{{ $row->customer->customer_code }} · {{ $row->onu_index }}</span>
                                </span>
                                <span class="whitespace-nowrap text-sm font-semibold tabular-nums text-rose-600">{{ number_format($row->rx_power, 2) }} dBm</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-surface-500"><x-icon name="check-circle" class="mx-auto mb-1 w-6 h-6 text-emerald-500" /> Tidak ada sinyal kritis.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <p class="text-xs text-surface-500">
            <strong>Sinkron ONU</strong> membaca semua ONU di semua OLT (± beberapa detik per OLT) lalu memperbarui status & sinyal pelanggan.
            Pemantauan otomatis & peringatan WhatsApp diatur di
            @canroute('integrations.noc-bot')<a href="{{ route('integrations.noc-bot') }}" class="text-primary-600 hover:underline">Integrasi › Bot NOC</a>@else Integrasi › Bot NOC @endcanroute.
        </p>
    </div>

    @push('scripts')
    <script>
        function nocSync() {
            return {
                busy: false, msg: '', ok: true,
                run() {
                    this.busy = true; this.msg = '';
                    fetch(@js(route('noc.sync', [], false)), { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                        .then(async r => { const d = await r.json().catch(() => ({})); this.ok = r.ok && d.success; this.msg = d.message || ('HTTP ' + r.status); if (this.ok) setTimeout(() => location.reload(), 1200); })
                        .catch(() => { this.ok = false; this.msg = 'Gagal menghubungi server.'; })
                        .finally(() => this.busy = false);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
