@php
    $tabs = ['' => 'Semua', 'critical' => 'Kritis', 'warn' => 'Waspada', 'good' => 'Baik', 'down' => 'Mati', 'not_found' => 'Tidak ada di OLT', 'nodata' => 'Belum terbaca'];
    $url = fn (array $over) => route('noc.signals', array_filter(array_merge(['filter' => $filter, 'olt' => $oltId, 'q' => $q ?: null], $over), fn ($v) => $v !== null && $v !== ''));
    $statusLabel = ['online' => 'Online', 'offline' => 'Mati', 'los' => 'LOS', 'dying-gasp' => 'Listrik padam', 'not_found' => 'Tidak ada di OLT'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Sinyal Optik Pelanggan</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    Daya terima (Rx) ONU setiap pelanggan berlangganan. Terburuk di atas.
                    Data terakhir: <strong>{{ $lastSync ? \Carbon\Carbon::createFromTimestamp($lastSync)->locale('id')->diffForHumans() : 'belum pernah' }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-2" x-data="nocSync()">
                <span x-show="msg" x-cloak class="max-w-xs text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'" x-text="msg"></span>
                <button type="button" class="btn-primary" :disabled="busy" @click="run()">
                    <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <x-icon name="refresh" class="w-4 h-4" x-show="!busy" />
                    <span x-text="busy ? 'Membaca ONU semua OLT…' : 'Sinkron ONU'"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-surface-200 p-4 dark:border-surface-800">
                <div class="flex flex-wrap gap-1.5">
                    @foreach($tabs as $key => $label)
                        @php $n = $counts[$key ?: 'all']; $on = ($filter ?? '') === $key; @endphp
                        <a href="{{ $url(['filter' => $key ?: null, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors {{ $on ? 'border-primary-600 bg-primary-600 text-white' : 'border-surface-200 text-surface-600 hover:bg-surface-50 dark:border-surface-700 dark:text-surface-300 dark:hover:bg-surface-800' }}">
                            @if(in_array($key, ['critical', 'down', 'not_found'], true) && $n && ! $on)<span class="h-1.5 w-1.5 rounded-full {{ $key === 'critical' ? 'bg-rose-500' : 'bg-amber-500' }}"></span>@endif
                            {{ $label }} <span class="{{ $on ? 'text-white/80' : 'text-surface-400' }}">{{ number_format($n, 0, ',', '.') }}</span>
                        </a>
                    @endforeach
                </div>
                <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
                    <div class="relative w-full sm:max-w-sm">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                        <input type="search" name="q" value="{{ $q }}" class="input pl-9" placeholder="Nama, kode, username atau SN…" aria-label="Cari pelanggan">
                    </div>
                    @if($olts->count() > 1)
                        <select name="olt" class="input w-auto" onchange="this.form.submit()" aria-label="OLT">
                            <option value="">Semua OLT</option>
                            @foreach($olts as $o)<option value="{{ $o->id }}" @selected($oltId === $o->id)>{{ $o->name }}</option>@endforeach
                        </select>
                    @endif
                    <button class="btn-secondary">Cari</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 text-left text-xs font-medium uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                        <tr>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">OLT / posisi</th>
                            <th class="px-4 py-3">SN / MAC</th>
                            <th class="px-4 py-3">Sinyal Rx</th>
                            <th class="px-4 py-3">Status ONU</th>
                            <th class="px-4 py-3 text-right">Riwayat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @forelse($customers as $c)
                            @php
                                $rx = $c->rx_power !== null ? (float) $c->rx_power : null;
                                $lvl = $rx === null ? 'none' : ($rx < -27 ? 'bad' : ($rx < -24 ? 'warn' : 'good'));
                                $cls = ['good' => 'text-emerald-600 dark:text-emerald-400', 'warn' => 'text-amber-600', 'bad' => 'text-rose-600', 'none' => 'text-surface-400'][$lvl];
                                $bar = ['good' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'bad' => 'bg-rose-500', 'none' => 'bg-surface-300'][$lvl];
                                $st = in_array($c->onu_status, [null, '', 'unknown'], true) ? null : $c->onu_status;   // 'unknown' = sisa poller lama
                            @endphp
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('customers.show', $c->id) }}" class="font-medium text-surface-900 hover:text-primary-700 dark:text-white">{{ $c->name }}</a>
                                    <span class="block font-mono text-[11px] text-surface-500">{{ $c->customer_code }}{{ $c->status === 'suspended' ? ' · terisolir' : '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="text-surface-700 dark:text-surface-300">{{ $c->olt?->name ?? '—' }}</span>
                                    <span class="block font-mono text-surface-500">{{ $c->polled_index ?: $c->onu_index ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-surface-600 dark:text-surface-300">{{ strtoupper($c->onu_sn) }}</td>
                                <td class="px-4 py-3">
                                    @if($rx !== null)
                                        <span class="whitespace-nowrap font-semibold tabular-nums {{ $cls }}">{{ number_format($rx, 2) }} dBm</span>
                                        <div class="mt-1 h-1 w-20 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700"><div class="h-full rounded-full {{ $bar }}" style="width: {{ max(5, min(100, (($rx + 35) / 27) * 100)) }}%"></div></div>
                                    @else
                                        <span class="text-surface-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($st)
                                        <span class="{{ $st === 'online' ? 'badge-success' : 'badge-danger' }}">{{ $statusLabel[$st] ?? $st }}</span>
                                        <span class="block whitespace-nowrap text-[11px] text-surface-500" title="{{ $c->last_polled_at }}">{{ $c->last_polled_at ? \Carbon\Carbon::parse($c->last_polled_at)->locale('id')->diffForHumans() : '' }}</span>
                                    @else
                                        <span class="badge-gray">Belum terbaca</span>
                                        <span class="block text-[11px] text-surface-500">{{ $c->olt_id ? 'tekan Sinkron ONU' : 'OLT belum diisi' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('noc.history', $c->id) }}" class="btn-ghost btn-sm">Grafik</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-surface-500">Tidak ada pelanggan pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->total() > 0)
                <div class="border-t border-surface-200 px-5 py-3 dark:border-surface-800">{{ $customers->links(null, ['label' => 'pelanggan']) }}</div>
            @endif
        </div>
        <p class="text-xs text-surface-500">Rx: hijau ≥ -24 · kuning -24 s/d -27 · merah &lt; -27 dBm. Hanya pelanggan aktif & terisolir yang punya SN ONU. "Tidak ada di OLT" = SN pelanggan tidak ditemukan saat semua port terbaca (ONU diganti/dipindah atau SN salah).</p>
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
