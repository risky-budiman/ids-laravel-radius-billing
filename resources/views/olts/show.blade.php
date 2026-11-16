@php
    $isAdministrator = auth()->user()->isAdministrator();
    $online = ($status['status'] ?? null) === 'online';
    $state = ! $olt->is_active ? 'inactive' : ($status === null ? 'unknown' : ($online ? 'online' : 'offline'));
    $badge = ['online' => ['badge-success', 'Online'], 'offline' => ['badge-danger', 'Tidak merespons'],
        'unknown' => ['badge-gray', 'Belum dicek'], 'inactive' => ['badge-gray', 'Nonaktif']][$state];
    $cpu = (int) ($status['cpu'] ?? 0);
    $temp = (int) ($status['temp'] ?? 0);
    $ports = $olt->ponPorts->map(fn ($p) => [
        'id' => $p->id, 'label' => "PON {$p->slot}/{$p->pon_port}",
        'url' => route('olts.show-port', [$olt, $p]),
        'data' => route('olts.get-port-data', [$olt, $p], false),
    ])->values();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('olts.index') }}" class="btn-ghost btn-icon" aria-label="Kembali ke daftar OLT">
                    <x-icon name="chevron-right" class="w-5 h-5 rotate-180" />
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight truncate">{{ $olt->name }}</h2>
                        <span class="{{ $badge[0] }}">
                            @if($state === 'online')<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>@endif
                            {{ $badge[1] }}
                        </span>
                    </div>
                    <p class="text-sm text-surface-500 dark:text-surface-400 truncate">
                        <span class="font-mono">{{ $olt->ip_address }}</span> · {{ $olt->typeLabel() }} · {{ $olt->ponTechnology() }}
                        @if($olt->description) · {{ $olt->description }} @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2" x-data="{ testing: false, result: null, ok: false }">
                <span x-show="result" x-cloak class="text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'" x-text="result"></span>
                <button type="button" class="btn-secondary" :disabled="testing"
                        @click="testing = true; result = null; fetch(@js(route('olts.test-connection', $olt, false)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(d => { ok = !!d.success; result = d.success ? 'Terhubung' : (d.message || 'Tidak merespons'); }).catch(() => { ok = false; result = 'Gagal'; }).finally(() => testing = false)">
                    <x-icon name="bolt" class="w-4 h-4" /> <span x-text="testing ? 'Menguji…' : 'Tes koneksi'"></span>
                </button>
                <a href="{{ route('olts.show', [$olt, 'refresh' => 1]) }}" class="btn-secondary"><x-icon name="refresh" class="w-4 h-4" /> Perbarui</a>
                @if($isAdministrator)
                    <a href="{{ route('olts.edit', $olt) }}" class="btn-primary"><x-icon name="settings" class="w-4 h-4" /> Ubah</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="oltPorts(@js($ports))">
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

        {{-- Kesehatan perangkat & ONU --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Uptime</p>
                <p class="mt-1 text-lg font-semibold text-surface-900 dark:text-white truncate">{{ $online ? ($status['uptime'] ?? '—') : '—' }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">CPU</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-surface-900 dark:text-white">{{ $online ? $cpu . '%' : '—' }}</p>
                @if($online)
                    <div class="mt-1.5 h-1.5 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                        <div class="h-full rounded-full {{ $cpu >= 85 ? 'bg-rose-500' : ($cpu >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, max(2, $cpu)) }}%"></div>
                    </div>
                @endif
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Suhu</p>
                <p class="mt-1 text-lg font-semibold tabular-nums {{ $temp >= 65 ? 'text-rose-600' : ($temp >= 55 ? 'text-amber-600' : 'text-surface-900 dark:text-white') }}">{{ $online && $temp ? $temp . '°C' : '—' }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">ONU online</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-surface-900 dark:text-white">
                    <span class="text-emerald-600 dark:text-emerald-400" x-text="total('online')">—</span><span class="text-surface-400"> / </span><span x-text="total('total')">—</span>
                </p>
                <p class="text-xs text-surface-500" x-show="total('offline') > 0" x-cloak><span class="text-amber-600" x-text="total('offline')"></span> offline</p>
            </div>
            <div class="card p-4 col-span-2 lg:col-span-1">
                <p class="text-xs font-medium text-surface-500">Pelanggan terhubung</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-surface-900 dark:text-white">{{ number_format($olt->customers_count, 0, ',', '.') }}</p>
                <p class="text-xs text-surface-500" x-show="total('critical') > 0" x-cloak><span class="text-rose-600" x-text="total('critical')"></span> sinyal kritis</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Port PON --}}
            <section class="card xl:col-span-2">
                <div class="card-header flex items-center justify-between gap-3">
                    <div>
                        <h3 class="card-title">Port PON</h3>
                        <p class="text-xs text-surface-500">Klik port untuk melihat & mengelola ONU-nya.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-sm" @click="scanAll(true)" :disabled="scanning" x-show="ports.length">
                        <x-icon name="refresh" class="w-3.5 h-3.5" /> <span x-text="scanning ? 'Memindai…' : 'Pindai semua port'"></span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="ports.length">
                        <template x-for="p in ports" :key="p.id">
                            <a :href="p.url" class="group rounded-xl border border-surface-200 dark:border-surface-700 p-4 transition-colors hover:border-primary-400 hover:bg-primary-50/40 dark:hover:bg-primary-500/5">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 font-semibold text-surface-900 dark:text-white">
                                        <span class="w-2 h-2 rounded-full" :class="!p.s ? 'bg-surface-300' : (p.s.total ? 'bg-emerald-500' : 'bg-surface-400')"></span>
                                        <span x-text="p.label"></span>
                                    </span>
                                    <span class="text-xs text-surface-500 group-hover:text-primary-600">Kelola →</span>
                                </div>
                                <template x-if="p.loading">
                                    <p class="mt-3 text-sm text-surface-500">Membaca ONU…</p>
                                </template>
                                <template x-if="!p.loading && p.error">
                                    <p class="mt-3 text-sm text-rose-600" x-text="p.error"></p>
                                </template>
                                <template x-if="!p.loading && !p.error && p.s">
                                    <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                                        <div><p class="text-[11px] text-surface-500">ONU</p><p class="text-base font-semibold tabular-nums text-surface-900 dark:text-white" x-text="p.s.total"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Online</p><p class="text-base font-semibold tabular-nums text-emerald-600 dark:text-emerald-400" x-text="p.s.online"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Kritis</p><p class="text-base font-semibold tabular-nums" :class="p.s.critical ? 'text-rose-600' : 'text-surface-900 dark:text-white'" x-text="p.s.critical"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Rata Rx</p><p class="text-base font-semibold tabular-nums" :class="signalClass(p.s.avg)" x-text="p.s.avg === null ? '—' : p.s.avg.toFixed(1)"></p></div>
                                    </div>
                                </template>
                                <template x-if="!p.loading && !p.error && p.s && p.s.total">
                                    <div class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700">
                                        <div class="bg-emerald-500" :style="'width:' + (p.s.good / p.s.total * 100) + '%'"></div>
                                        <div class="bg-amber-500" :style="'width:' + (p.s.warn / p.s.total * 100) + '%'"></div>
                                        <div class="bg-rose-500" :style="'width:' + (p.s.critical / p.s.total * 100) + '%'"></div>
                                    </div>
                                </template>
                            </a>
                        </template>
                    </div>

                    @if($olt->ponPorts->isEmpty())
                        <div class="py-8 text-center">
                            <p class="text-sm text-surface-500">Port PON belum terdeteksi. Pastikan OLT menjawab SNMP lalu deteksi ulang.</p>
                            @if($isAdministrator)
                                <div class="mt-4 flex flex-col items-center gap-3">
                                    <form method="POST" action="{{ route('olts.auto-discover-ports', $olt) }}">@csrf
                                        <button class="btn-primary"><x-icon name="search" class="w-4 h-4" /> Deteksi port otomatis</button>
                                    </form>
                                    <details class="text-sm text-surface-600 dark:text-surface-300">
                                        <summary class="cursor-pointer text-xs text-surface-500">Atau buat port manual</summary>
                                        <form method="POST" action="{{ route('olts.generate-ports', $olt) }}" class="mt-3 flex items-end gap-2">@csrf
                                            <label class="text-xs">Slot<input type="number" name="slot" value="1" min="1" class="input mt-1 w-20" required></label>
                                            <label class="text-xs">Jumlah port
                                                <select name="port_count" class="input mt-1 w-28">@foreach([4, 8, 16] as $n)<option value="{{ $n }}">{{ $n }} port</option>@endforeach</select>
                                            </label>
                                            <button class="btn-secondary">Buat</button>
                                        </form>
                                    </details>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-4 border-t border-surface-200 dark:border-surface-800 px-5 py-3 text-xs text-surface-500" x-show="ports.length">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Rx ≥ -24</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span>-24 s/d -27</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span>&lt; -27 dBm</span>
                    @if($isAdministrator && $olt->ponPorts->isNotEmpty())
                        <form method="POST" action="{{ route('olts.auto-discover-ports', $olt) }}" class="ml-auto"
                              onsubmit="return confirm('Deteksi ulang port PON lewat SNMP?')">@csrf
                            <button class="text-xs text-surface-500 hover:text-primary-600 underline-offset-2 hover:underline">Deteksi ulang port</button>
                        </form>
                    @endif
                </div>
            </section>

            {{-- Info perangkat --}}
            <section class="card">
                <div class="card-header"><h3 class="card-title">Info perangkat</h3></div>
                <dl class="divide-y divide-surface-100 dark:divide-surface-800 text-sm">
                    <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Tipe</dt><dd class="text-right font-medium">{{ $olt->typeLabel() }}</dd></div>
                    @if(! empty($board['product_name']))
                        <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Model</dt><dd class="text-right">{{ $board['product_name'] }}</dd></div>
                    @endif
                    @if(! empty($board['fw_ver']))
                        <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Firmware</dt><dd class="text-right font-mono text-xs break-all">{{ $board['fw_ver'] }}</dd></div>
                    @endif
                    @if(! empty($board['sn']))
                        <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Serial number</dt><dd class="text-right font-mono text-xs">{{ $board['sn'] }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Teknologi</dt><dd class="text-right">{{ $olt->ponTechnology() }} · {{ $olt->ponPorts->count() }} port PON</dd></div>
                    <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">IP manajemen</dt><dd class="text-right font-mono">{{ $olt->ip_address }}</dd></div>
                    <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">SNMP</dt><dd class="text-right">{{ $olt->snmpVersionLabel() }} · port {{ $olt->snmp_port }}</dd></div>
                    @if(str_contains(strtoupper((string) $olt->olt_type), 'HSGQ'))
                        <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Akses web OLT</dt>
                            <dd class="text-right">
                                @if(filled($olt->web_username))<span class="badge-success">Terhubung</span>@else<span class="badge-gray">Belum diisi</span>@endif
                            </dd>
                        </div>
                    @endif
                    @if($olt->latitude && $olt->longitude)
                        <div class="flex justify-between gap-4 px-5 py-2.5"><dt class="text-surface-500">Lokasi</dt>
                            <dd class="text-right"><a href="https://www.google.com/maps?q={{ $olt->latitude }},{{ $olt->longitude }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-primary-600 hover:underline"><x-icon name="map" class="w-3.5 h-3.5" /> Buka peta</a></dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
        function oltPorts(ports) {
            return {
                ports: ports.map(p => ({ ...p, s: null, loading: false, error: '' })),
                scanning: false,
                init() { this.scanAll(false); },
                async scanAll(fresh) {
                    this.scanning = true;
                    for (const p of this.ports) {   // berurutan agar OLT tidak dibanjiri permintaan SNMP
                        p.loading = true; p.error = '';
                        try {
                            const r = await fetch(p.data + (fresh ? '?fresh=1' : ''), { headers: { 'Accept': 'application/json' } });
                            const d = await r.json();
                            if (d.status === 'error') throw new Error(d.message);
                            p.s = this.summarize(d.onus || []);
                        } catch (e) { p.error = 'Tidak terbaca'; }
                        p.loading = false;
                    }
                    this.scanning = false;
                },
                summarize(onus) {
                    const rx = onus.map(o => parseFloat(o.signal)).filter(n => !isNaN(n));
                    const online = onus.filter(o => ['online', 'working', 'ready'].includes(String(o.status || '').toLowerCase())).length;
                    return {
                        total: onus.length, online, offline: onus.length - online,
                        good: rx.filter(n => n >= -24).length, warn: rx.filter(n => n < -24 && n >= -27).length,
                        critical: rx.filter(n => n < -27).length,
                        avg: rx.length ? rx.reduce((a, b) => a + b, 0) / rx.length : null,
                    };
                },
                total(key) {
                    const done = this.ports.filter(p => p.s);
                    if (!done.length) return '—';
                    return done.reduce((a, p) => a + p.s[key], 0);
                },
                signalClass(s) { return s === null ? 'text-surface-400' : (s < -27 ? 'text-rose-600' : (s < -24 ? 'text-amber-600' : 'text-emerald-600 dark:text-emerald-400')); },
            };
        }
    </script>
    @endpush
</x-app-layout>
