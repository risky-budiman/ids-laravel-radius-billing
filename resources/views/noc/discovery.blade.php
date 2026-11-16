@php
    $onus = $data['onus'] ?? [];
    $canAllow = \App\Support\RouteAccess::allows(auth()->user(), 'olts.onus.allow');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">ONU Belum Terdaftar</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    ONU yang sudah tersambung ke OLT tetapi belum didaftarkan / diizinkan.
                    @if($data) Dipindai <strong>{{ \Carbon\Carbon::createFromTimestamp($data['at'])->locale('id')->diffForHumans() }}</strong>. @endif
                </p>
            </div>
            <div class="flex items-center gap-2" x-data="discoveryScan()">
                <span x-show="msg" x-cloak class="text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'" x-text="msg"></span>
                <button type="button" class="btn-primary" :disabled="busy" @click="run()">
                    <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <x-icon name="search" class="w-4 h-4" x-show="!busy" />
                    <span x-text="busy ? 'Memindai semua OLT…' : 'Pindai sekarang'"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4" x-data="allowOnu()">
        @if(! $data)
            <div class="card p-10 text-center">
                <x-icon name="search" class="mx-auto mb-3 w-10 h-10 text-surface-300" />
                <p class="font-medium text-surface-800 dark:text-surface-200">Belum dipindai</p>
                <p class="text-sm text-surface-500">Klik <strong>Pindai sekarang</strong> untuk mencari ONU baru di semua OLT.</p>
            </div>
        @else
            {{-- Status per OLT --}}
            <div class="flex flex-wrap gap-2">
                @foreach($data['olts'] as $o)
                    <span class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs {{ $o['state'] === 'ok' ? 'border-surface-200 dark:border-surface-700' : 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10' }}">
                        <span class="h-2 w-2 rounded-full {{ $o['state'] === 'ok' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <strong class="text-surface-800 dark:text-surface-200">{{ $o['name'] }}</strong>
                        <span class="text-surface-500">{{ $o['state'] === 'ok' ? $o['count'] . ' ONU' : $o['message'] }}</span>
                    </span>
                @endforeach
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-50 text-left text-xs font-medium uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                            <tr><th class="px-4 py-3">SN / MAC</th><th class="px-4 py-3">OLT / port</th><th class="px-4 py-3">Keterangan</th><th class="px-4 py-3">Pelanggan</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @forelse($onus as $i => $onu)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs font-medium text-surface-900 dark:text-white">{{ strtoupper($onu['sn']) }}</td>
                                    <td class="px-4 py-3 text-xs"><span class="text-surface-700 dark:text-surface-300">{{ $onu['olt_name'] }}</span><span class="block font-mono text-surface-500">PON {{ $onu['port'] ?: '—' }}</span></td>
                                    <td class="px-4 py-3"><span class="{{ $onu['source'] === 'denied' ? 'badge-danger' : 'badge-warning' }}">{{ $onu['note'] }}</span></td>
                                    <td class="px-4 py-3">
                                        @if($onu['customer'])
                                            <a href="{{ route('customers.show', $onu['customer']['id']) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $onu['customer']['name'] }}</a>
                                            <span class="block font-mono text-[11px] text-surface-500">{{ $onu['customer']['code'] }}{{ $onu['work_item'] ? ' · ada di Pekerjaan OLT' : '' }}</span>
                                        @else
                                            <span class="text-xs text-surface-400">Belum tertaut (SN tidak cocok dengan pelanggan)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if($canAllow && $onu['port_id'] && $onu['source'] !== 'unconfigured')
                                            <button type="button" class="btn-primary btn-sm" :disabled="busy === {{ $i }} || done[{{ $i }}]"
                                                    @click="allow({{ $i }}, @js(route('olts.onus.allow', [$onu['olt_id'], $onu['port_id']], false)), @js($onu['sn']))">
                                                <span x-text="done[{{ $i }}] ? 'Diizinkan' : (busy === {{ $i }} ? 'Mengirim…' : 'Allow')"></span>
                                            </button>
                                        @endif
                                        @if($onu['port_id'])
                                            <a href="{{ route('olts.show-port', [$onu['olt_id'], $onu['port_id']]) }}" class="btn-ghost btn-sm">Port</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-surface-500"><x-icon name="check-circle" class="mx-auto mb-1 w-7 h-7 text-emerald-500" /> Tidak ada ONU yang menunggu didaftarkan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p x-show="msg" x-cloak class="text-sm" :class="ok ? 'text-emerald-600' : 'text-rose-600'" x-text="msg"></p>
        @endif
        <p class="text-xs text-surface-500">HSGQ: daftar diambil dari web OLT (ONU belum diizinkan & daftar tolak) — isi <em>Akses web OLT</em> di Ubah OLT. ZTE: dari tabel ONU belum terkonfigurasi (SNMP). Allow menyimpan konfigurasi OLT otomatis; nama & deskripsi ONU diisi dari data pelanggan bila SN cocok.</p>
    </div>

    @push('scripts')
    <script>
        const csrf = () => document.querySelector('meta[name=csrf-token]').content;
        function discoveryScan() {
            return {
                busy: false, msg: '', ok: true,
                run() {
                    this.busy = true; this.msg = '';
                    fetch(@js(route('noc.discovery.scan', [], false)), { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() } })
                        .then(async r => { const d = await r.json().catch(() => ({})); this.ok = r.ok && d.success; this.msg = this.ok ? d.count + ' ONU ditemukan.' : (d.message || 'HTTP ' + r.status); if (this.ok) setTimeout(() => location.reload(), 800); })
                        .catch(() => { this.ok = false; this.msg = 'Gagal menghubungi server.'; })
                        .finally(() => this.busy = false);
                },
            };
        }
        function allowOnu() {
            return {
                busy: null, done: {}, msg: '', ok: true,
                allow(i, url, mac) {
                    if (!confirm('Allow ONU ' + mac.toUpperCase() + '?')) return;
                    this.busy = i; this.msg = '';
                    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mac }) })
                        .then(async r => { const d = await r.json().catch(() => ({})); this.ok = r.ok && d.success; this.msg = d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'HTTP ' + r.status); if (this.ok) this.done[i] = true; })
                        .catch(() => { this.ok = false; this.msg = 'Gagal menghubungi server.'; })
                        .finally(() => this.busy = null);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
