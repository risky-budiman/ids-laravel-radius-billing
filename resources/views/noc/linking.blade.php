@php
    $tabs = ['suggested' => 'Usulan', 'unlinked' => 'Belum tertaut', 'linked' => 'Sudah tertaut'];
    $total = array_sum(array_intersect_key($counts, $tabs));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Penautan ONU ↔ Pelanggan</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Tautkan pelanggan lama ke ONU di OLT agar sinyal, status & peringatan NOC berjalan.</p>
            </div>
            <div class="flex items-center gap-2">
                @if($olts->count() > 1)
                    <form method="GET"><select name="olt" class="input w-auto" onchange="this.form.submit()" aria-label="OLT">
                        @foreach($olts as $o)<option value="{{ $o->id }}" @selected($olt?->id === $o->id)>{{ $o->name }}</option>@endforeach
                    </select></form>
                @endif
                @if($counts['suggested'])
                    <form method="POST" action="{{ route('noc.link-suggested') }}" onsubmit="return confirm('Tautkan {{ $counts['suggested'] }} usulan sekaligus? Periksa daftarnya dulu.')">@csrf
                        <input type="hidden" name="olt_id" value="{{ $olt->id }}">
                        <button class="btn-primary"><x-icon name="check" class="w-4 h-4" /> Tautkan semua usulan ({{ $counts['suggested'] }})</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-4" x-data="onuLinking()">
        @if(session('success'))
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"><x-icon name="check-circle" class="w-5 h-5 shrink-0" /> {{ session('success') }}</div>
        @endif

        @if(! $olt)
            <div class="card p-8 text-center text-sm text-surface-500">Belum ada OLT aktif.</div>
        @elseif(! $scanned)
            <div class="card p-8 text-center">
                <p class="font-medium text-surface-800 dark:text-surface-200">Data ONU {{ $olt->name }} belum dibaca</p>
                <p class="text-sm text-surface-500">Buka <a href="{{ route('noc.index') }}" class="text-primary-600 hover:underline">NOC Center</a> lalu klik <strong>Sinkron ONU</strong>, kemudian kembali ke halaman ini.</p>
            </div>
        @else
            <div class="grid grid-cols-3 gap-4">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('noc.linking', ['olt' => $olt->id, 'tab' => $key]) }}" class="card p-4 transition-colors hover:border-primary-300 {{ $tab === $key ? 'ring-2 ring-primary-500 border-transparent' : '' }}">
                        <p class="text-xs font-medium text-surface-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums {{ $key === 'linked' ? 'text-emerald-600' : ($key === 'suggested' ? 'text-primary-600' : 'text-surface-900 dark:text-white') }}">{{ $counts[$key] }}<span class="text-sm font-normal text-surface-400"> / {{ $total }} ONU</span></p>
                    </a>
                @endforeach
            </div>

            <p x-show="msg" x-cloak class="rounded-lg px-4 py-2 text-sm" :class="ok ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'" x-text="msg"></p>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-50 text-left text-xs font-medium uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                            <tr><th class="px-4 py-3">Posisi</th><th class="px-4 py-3">ONU</th><th class="px-4 py-3">Deskripsi</th><th class="px-4 py-3">Sinyal</th><th class="px-4 py-3">Pelanggan</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @forelse($rows as $i => $r)
                                @php $rx = is_numeric($r['signal']) ? (float) $r['signal'] : null; @endphp
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-surface-600 dark:text-surface-300">{{ $r['index'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-surface-900 dark:text-white">{{ $r['name'] ?: '—' }}</span>
                                        <span class="block font-mono text-[11px] text-surface-500">{{ strtoupper($r['sn']) }}</span>
                                    </td>
                                    <td class="max-w-[14rem] truncate px-4 py-3 text-surface-600 dark:text-surface-300" title="{{ $r['description'] }}">{{ $r['description'] ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 tabular-nums {{ $rx === null ? 'text-surface-400' : ($rx < -27 ? 'text-rose-600' : ($rx < -24 ? 'text-amber-600' : 'text-emerald-600')) }}">{{ $rx !== null ? number_format($rx, 2) . ' dBm' : ($r['status'] === 'online' ? 'online' : 'offline') }}</td>
                                    <td class="px-4 py-3">
                                        @if($r['customer'])
                                            <a href="{{ route('customers.show', $r['customer']->id) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $r['customer']->name }}</a>
                                            <span class="block font-mono text-[11px] text-surface-500">{{ $r['customer']->customer_code }} · {{ $r['customer']->username }}</span>
                                            @if($r['reason'])<span class="block text-[11px] text-primary-600">usulan: {{ $r['reason'] }}</span>@endif
                                        @else
                                            <div class="relative w-64" x-data="{ q: '', results: [], open: false }">
                                                <input type="search" x-model.debounce.300ms="q" @input.debounce.300ms="search(q).then(r => { results = r; open = true; })" @focus="open = results.length > 0"
                                                       class="input py-1.5 text-xs" placeholder="Cari nama / kode / username…" aria-label="Cari pelanggan untuk ONU {{ $r['index'] }}">
                                                <ul x-show="open && results.length" x-cloak @click.outside="open = false" class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-surface-200 bg-white py-1 shadow-lg dark:border-surface-700 dark:bg-surface-900">
                                                    <template x-for="c in results" :key="c.id">
                                                        <li><button type="button" class="block w-full px-3 py-1.5 text-left text-xs hover:bg-surface-50 dark:hover:bg-surface-800"
                                                                    @click="open = false; link({{ $olt->id }}, @js($r['index']), c.id, c.label)">
                                                            <span class="font-medium text-surface-900 dark:text-white" x-text="c.label"></span>
                                                            <span class="block text-surface-500" x-text="c.detail"></span>
                                                        </button></li>
                                                    </template>
                                                </ul>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @if($r['state'] === 'suggested')
                                            <button type="button" class="btn-primary btn-sm" @click="link({{ $olt->id }}, @js($r['index']), {{ $r['customer']->id }}, @js($r['customer']->name))">Tautkan</button>
                                        @elseif($r['state'] === 'linked')
                                            <button type="button" class="btn-ghost btn-sm text-rose-600" @click="unlink(@js(route('noc.unlink', $r['customer']->id, false)), @js($r['customer']->name))">Lepas</button>
                                        @endif
                                        <a href="{{ route('olts.show-port', [$olt, $r['port_id']]) }}" class="btn-ghost btn-sm">Port</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-surface-500">Tidak ada ONU di kelompok ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="space-y-1 text-xs text-surface-500">
            <p><strong>Usulan</strong> dibuat dari: MAC/SN yang sama, <strong>nama ONU = username pelanggan</strong> (tanpa @domain) atau kode pelanggan, atau <strong>deskripsi ONU = nama pelanggan</strong> (web OLT). Hanya kecocokan yang unik yang diusulkan.</p>
            <p>Menautkan menyimpan OLT, posisi & MAC ONU di data pelanggan. Sinyal & status terisi pada <strong>Sinkron ONU</strong> berikutnya. Kecocokan lewat nama ONU juga ditautkan otomatis saat Sinkron ONU.</p>
        </div>
    </div>

    @push('scripts')
    <script>
        function onuLinking() {
            const headers = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content });
            return {
                msg: '', ok: true,
                search(q) {
                    if (!q || q.length < 2) return Promise.resolve([]);
                    return fetch(@js(route('api.customers.lookup', [], false)) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(r => r.json()).catch(() => []);
                },
                done(r) { return r.json().catch(() => ({})).then(d => { this.ok = r.ok && d.success; this.msg = d.message || ('HTTP ' + r.status); if (this.ok) setTimeout(() => location.reload(), 700); }); },
                link(oltId, index, customerId, name) {
                    if (!confirm('Tautkan ONU ' + index + ' ke ' + name + '?')) return;
                    fetch(@js(route('noc.link', [], false)), { method: 'POST', headers: headers(), body: JSON.stringify({ olt_id: oltId, index, customer_id: customerId }) })
                        .then(r => this.done(r)).catch(() => { this.ok = false; this.msg = 'Gagal menghubungi server.'; });
                },
                unlink(url, name) {
                    if (!confirm('Lepas tautan ONU dari ' + name + '?')) return;
                    fetch(url, { method: 'POST', headers: headers() }).then(r => this.done(r)).catch(() => { this.ok = false; this.msg = 'Gagal menghubungi server.'; });
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
