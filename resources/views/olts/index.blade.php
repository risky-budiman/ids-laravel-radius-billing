@php
    $isAdministrator = auth()->user()->isAdministrator();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Manajemen OLT</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400 mt-0.5">Perangkat OLT, status & ONU pelanggan — dipantau lewat SNMP</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('olts.index', ['refresh' => 1]) }}" class="btn-secondary" title="Periksa status semua OLT sekarang lewat SNMP">
                    <x-icon name="refresh" class="w-4 h-4" />
                    Perbarui status
                </a>
                @if($isAdministrator)
                    <a href="{{ route('olts.create') }}" class="btn-primary">
                        <x-icon name="plus" class="w-4 h-4" />
                        Tambah OLT
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ q: '' }">
        @if(session('success'))
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">
                <x-icon name="check-circle" class="w-5 h-5 shrink-0" /> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                <x-icon name="exclamation" class="w-5 h-5 shrink-0" /> {{ session('error') }}
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total OLT" :value="$summary['total']" icon="server" tone="primary"
                :hint="$summary['active'] . ' aktif'" />
            <x-stat-card label="OLT online" :value="$summary['checked'] ? $summary['online'] . ' / ' . $summary['active'] : '—'" icon="signal"
                :tone="! $summary['checked'] ? 'gray' : ($summary['online'] < $summary['active'] ? 'warning' : 'success')"
                :hint="$checkedAt ? 'Dicek ' . $checkedAt->diffForHumans() : 'Belum dicek — klik Perbarui status'" />
            <x-stat-card label="Pelanggan terhubung" :value="number_format($summary['customers'], 0, ',', '.')" icon="users" tone="info"
                hint="Pelanggan yang terhubung ke OLT" />
            <x-stat-card label="Sinyal kritis" :value="$summary['critical']" icon="exclamation"
                :tone="$summary['critical'] > 0 ? 'danger' : 'gray'" hint="ONU dengan Rx di bawah -27 dBm"
                :href="route('noc.signals', ['filter' => 'critical'])" />
        </div>

        @if($olts->isNotEmpty())
            {{-- Pencarian --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative w-full sm:max-w-xs">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                    <input type="search" x-model.debounce.150ms="q" placeholder="Cari nama, IP atau lokasi…" class="input pl-9" aria-label="Cari OLT">
                </div>
                <p class="text-xs text-surface-500 dark:text-surface-400">
                    {{ $olts->count() }} OLT
                    @if($checkedAt) · status dicek {{ $checkedAt->diffForHumans() }} @endif
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-5">
            @foreach($olts as $olt)
                @php
                    $st = $statuses[$olt->id] ?? null;
                    $online = ($st['status'] ?? null) === 'online';
                    $state = ! $olt->is_active ? 'inactive' : ($st === null ? 'unknown' : ($online ? 'online' : 'offline'));
                    $badge = [
                        'online' => ['badge-success', 'Online'],
                        'offline' => ['badge-danger', 'Tidak merespons'],
                        'unknown' => ['badge-gray', 'Belum dicek'],
                        'inactive' => ['badge-gray', 'Nonaktif'],
                    ][$state];
                    $tile = [
                        'online' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'offline' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                        'unknown' => 'bg-surface-100 text-surface-500 dark:bg-surface-800 dark:text-surface-400',
                        'inactive' => 'bg-surface-100 text-surface-400 dark:bg-surface-800 dark:text-surface-500',
                    ][$state];
                    $cpu = (int) ($st['cpu'] ?? 0);
                    $temp = (int) ($st['temp'] ?? 0);
                    $stats = $onuStats[$olt->id] ?? null;
                    $search = strtolower($olt->name . ' ' . $olt->ip_address . ' ' . $olt->description . ' ' . $olt->typeLabel());
                @endphp
                <article class="card flex flex-col overflow-hidden {{ $olt->is_active ? '' : 'opacity-70' }}"
                         x-show="!q || @js($search).includes(q.toLowerCase())"
                         x-data="{ testing: false, result: null, ok: false,
                            test() {
                                this.testing = true; this.result = null;
                                fetch(@js(route('olts.test-connection', $olt)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } })
                                    .then(r => r.json())
                                    .then(d => { this.ok = !!d.success; this.result = d.success ? ('Terhubung — ' + (d.results?.snmp?.device_name || 'OK')) : (d.message || 'Tidak merespons SNMP'); })
                                    .catch(() => { this.ok = false; this.result = 'Gagal menghubungi server'; })
                                    .finally(() => this.testing = false);
                            } }">
                    <div class="p-5 space-y-4 flex-1">
                        {{-- Identitas --}}
                        <div class="flex items-start gap-3">
                            <span class="flex items-center justify-center w-11 h-11 rounded-xl shrink-0 {{ $tile }}">
                                <x-icon name="server" class="w-5 h-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('olts.show', $olt) }}" class="block font-semibold text-surface-900 dark:text-white truncate hover:text-primary-600 dark:hover:text-primary-400">{{ $olt->name }}</a>
                                <p class="font-mono text-xs text-surface-500 dark:text-surface-400">{{ $olt->ip_address }}</p>
                                @if($olt->description)
                                    <p class="mt-0.5 flex items-center gap-1 text-xs text-surface-500 dark:text-surface-400 truncate">
                                        <x-icon name="map" class="w-3.5 h-3.5 shrink-0" /> {{ $olt->description }}
                                    </p>
                                @endif
                            </div>
                            <span class="{{ $badge[0] }} shrink-0">
                                @if($state === 'online')<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>@endif
                                {{ $badge[1] }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-1.5">
                            <span class="badge-gray">{{ $olt->typeLabel() }}</span>
                            <span class="badge-info">{{ $olt->ponTechnology() }}</span>
                            <span class="badge-primary">SNMP {{ $olt->snmpVersionLabel() }}</span>
                            <span class="badge-gray">{{ $olt->pon_ports_count }} port PON</span>
                        </div>

                        {{-- Kesehatan perangkat --}}
                        @if($state === 'online')
                            <div class="grid grid-cols-3 gap-3 rounded-lg bg-surface-50 dark:bg-surface-800/50 p-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-surface-500">CPU</p>
                                    <p class="text-sm font-semibold tabular-nums text-surface-900 dark:text-white">{{ $cpu }}%</p>
                                    <div class="mt-1 h-1.5 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                                        <div class="h-full rounded-full {{ $cpu >= 85 ? 'bg-rose-500' : ($cpu >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, max(2, $cpu)) }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-surface-500">Suhu</p>
                                    <p class="text-sm font-semibold tabular-nums {{ $temp >= 65 ? 'text-rose-600' : ($temp >= 55 ? 'text-amber-600' : 'text-surface-900 dark:text-white') }}">{{ $temp ? $temp . '°C' : '—' }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] uppercase tracking-wide text-surface-500">Uptime</p>
                                    <p class="text-sm font-semibold text-surface-900 dark:text-white truncate">{{ $st['uptime'] ?? '—' }}</p>
                                </div>
                            </div>
                        @elseif($state === 'offline')
                            <div class="flex items-start gap-2 rounded-lg bg-rose-50 dark:bg-rose-500/10 p-3 text-xs text-rose-700 dark:text-rose-300">
                                <x-icon name="x-circle" class="w-4 h-4 shrink-0" />
                                OLT tidak menjawab SNMP saat terakhir dicek. Periksa koneksi, IP dan community/user SNMP.
                            </div>
                        @elseif($state === 'unknown')
                            <div class="flex items-start gap-2 rounded-lg bg-surface-50 dark:bg-surface-800/50 p-3 text-xs text-surface-500 dark:text-surface-400">
                                <x-icon name="clock" class="w-4 h-4 shrink-0" />
                                Status belum dicek. Klik <strong class="mx-1">Perbarui status</strong> atau <strong class="mx-1">Tes koneksi</strong>.
                            </div>
                        @endif

                        {{-- ONU / pelanggan --}}
                        <dl class="grid grid-cols-4 divide-x divide-surface-200 dark:divide-surface-700 rounded-lg border border-surface-200 dark:border-surface-700 text-center">
                            <div class="py-2">
                                <dt class="text-[11px] text-surface-500">Pelanggan</dt>
                                <dd class="text-sm font-semibold tabular-nums text-surface-900 dark:text-white">{{ $olt->customers_count }}</dd>
                            </div>
                            <div class="py-2">
                                <dt class="text-[11px] text-surface-500">Online</dt>
                                <dd class="text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $stats ? (int) $stats->online : '—' }}</dd>
                            </div>
                            <div class="py-2">
                                <dt class="text-[11px] text-surface-500">Offline</dt>
                                <dd class="text-sm font-semibold tabular-nums {{ $stats && $stats->offline > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-surface-900 dark:text-white' }}">{{ $stats ? (int) $stats->offline : '—' }}</dd>
                            </div>
                            <div class="py-2">
                                <dt class="text-[11px] text-surface-500">Kritis</dt>
                                <dd class="text-sm font-semibold tabular-nums {{ $stats && $stats->critical > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-surface-900 dark:text-white' }}">{{ $stats ? (int) $stats->critical : '—' }}</dd>
                            </div>
                        </dl>

                        <p x-show="result" x-cloak class="flex items-center gap-1.5 text-xs" :class="ok ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'">
                            <span x-text="ok ? '✓' : '✕'"></span><span x-text="result"></span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2 border-t border-surface-200 dark:border-surface-800 bg-surface-50/60 dark:bg-surface-900/40 px-5 py-3">
                        <a href="{{ route('olts.show', $olt) }}" class="btn-primary btn-sm">
                            Kelola <x-icon name="chevron-right" class="w-3.5 h-3.5" />
                        </a>
                        <button type="button" class="btn-secondary btn-sm" @click="test()" :disabled="testing">
                            <x-icon name="bolt" class="w-3.5 h-3.5" />
                            <span x-text="testing ? 'Menguji…' : 'Tes koneksi'"></span>
                        </button>
                        @if($isAdministrator)
                            <a href="{{ route('olts.edit', $olt) }}" class="btn-ghost btn-sm ml-auto" title="Ubah OLT">
                                <x-icon name="settings" class="w-3.5 h-3.5" /> Ubah
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if($olts->isNotEmpty())
            <p x-show="q" x-cloak class="text-center text-sm text-surface-500" x-text="'Menampilkan hasil untuk “' + q + '”'"></p>
        @endif

        @if($olts->isEmpty())
            <div class="card p-12 text-center">
                <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 mb-4">
                    <x-icon name="server" class="w-7 h-7" />
                </span>
                <h3 class="text-lg font-semibold text-surface-900 dark:text-white">Belum ada perangkat OLT</h3>
                <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">Tambahkan OLT pertama untuk memantau port PON, ONU dan sinyal pelanggan lewat SNMP.</p>
                @if($isAdministrator)
                    <a href="{{ route('olts.create') }}" class="btn-primary mt-6 inline-flex"><x-icon name="plus" class="w-4 h-4" /> Tambah OLT pertama</a>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
