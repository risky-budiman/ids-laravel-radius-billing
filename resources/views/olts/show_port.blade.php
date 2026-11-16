@php
    $isAdministrator = auth()->user()->isAdministrator();
    $portLabel = "PON {$port->slot}/{$port->pon_port}";
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('olts.show', $olt) }}" class="btn-ghost btn-icon" aria-label="Kembali ke OLT">
                    <x-icon name="chevron-right" class="w-5 h-5 rotate-180" />
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">{{ $portLabel }}</h2>
                    <p class="text-sm text-surface-500 dark:text-surface-400">{{ $olt->name }} · {{ $olt->typeLabel() }} · {{ $olt->ponTechnology() }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($siblings->count() > 1)
                    <select class="input h-9 py-0 text-sm w-auto" onchange="if (this.value) window.location = this.value" aria-label="Pindah port">
                        @foreach($siblings as $sp)
                            <option value="{{ route('olts.show-port', [$olt, $sp]) }}" @selected($sp->id === $port->id)>PON {{ $sp->slot }}/{{ $sp->pon_port }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="button" class="btn-primary" onclick="window.dispatchEvent(new CustomEvent('sync-onu-live'))">
                    <x-icon name="refresh" class="w-4 h-4" /> Sinkron langsung
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="onuPortManager()">
        {{-- Ringkasan port --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Total ONU</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white" x-text="loading && !onus.length ? '…' : onus.length"></p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Online</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400" x-text="count('online')"></p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Offline</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums" :class="count('offline') ? 'text-amber-600 dark:text-amber-400' : 'text-surface-900 dark:text-white'" x-text="count('offline')"></p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium text-surface-500">Sinyal kritis</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums" :class="count('critical') ? 'text-rose-600 dark:text-rose-400' : 'text-surface-900 dark:text-white'" x-text="count('critical')"></p>
            </div>
            <div class="card p-4 col-span-2 lg:col-span-1">
                <p class="text-xs font-medium text-surface-500">Rata-rata Rx</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums" :class="signalClass(avgSignal())" x-text="avgSignal() === null ? '—' : avgSignal().toFixed(2) + ' dBm'"></p>
            </div>
        </div>

        {{-- Info kemampuan & error --}}
        <template x-if="caps && !caps.reboot && !caps.web_configured">
            <div class="flex items-start gap-2.5 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                <x-icon name="exclamation" class="w-5 h-5 shrink-0" />
                <p>Allow, reboot, ubah & hapus ONU serta deskripsi di OLT HSGQ tidak tersedia lewat SNMP — butuh <strong>akses web OLT</strong>.
                    @if($isAdministrator)<a href="{{ route('olts.edit', $olt) }}" class="font-semibold underline">Isi login web OLT</a>.@else Minta administrator mengisi login web OLT.@endif</p>
            </div>
        </template>
        <p x-show="descError" x-cloak x-text="descError" class="rounded-lg bg-amber-50 dark:bg-amber-500/10 px-4 py-2 text-xs text-amber-800 dark:text-amber-300"></p>
        <p x-show="error" x-cloak x-text="error" class="rounded-lg bg-rose-50 dark:bg-rose-500/10 px-4 py-2 text-sm text-rose-700 dark:text-rose-300"></p>

        {{-- Daftar ONU --}}
        <div class="card overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 border-b border-surface-200 dark:border-surface-800 p-4">
                <div class="relative w-full lg:max-w-xs">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                    <input type="search" x-model.debounce.150ms="q" class="input pl-9" placeholder="Cari MAC/SN, nama, deskripsi, pelanggan…" aria-label="Cari ONU">
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="f in filters" :key="f.key">
                        <button type="button" class="btn-sm rounded-full px-3 border text-xs font-medium transition-colors"
                                :class="filter === f.key ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-200 dark:border-surface-700 text-surface-600 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-800'"
                                @click="filter = f.key" x-text="f.label + ' (' + count(f.key) + ')'"></button>
                    </template>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 lg:ml-auto text-xs text-surface-500 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>≥ -24</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span>-24 s/d -27</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span>&lt; -27 dBm</span>
                </div>
                <button type="button" class="btn-primary btn-sm whitespace-nowrap" x-show="caps?.allow" x-cloak @click="openAllow()">
                    <x-icon name="plus" class="w-4 h-4" /> Allow ONU
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-900/60 text-left text-xs font-medium uppercase tracking-wide text-surface-500">
                        <tr>
                            <th class="px-4 py-3 cursor-pointer select-none" @click="sortBy('onu_id')">Posisi <span x-text="sortMark('onu_id')"></span></th>
                            <th class="px-4 py-3">ONU</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3 cursor-pointer select-none" @click="sortBy('signal')">Sinyal Rx <span x-text="sortMark('signal')"></span></th>
                            <th class="px-4 py-3 cursor-pointer select-none" @click="sortBy('distance')">Jarak <span x-text="sortMark('distance')"></span></th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        <template x-for="onu in visible()" :key="onu.index + onu.sn">
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40 cursor-pointer" @click="openDetail(onu)">
                                <td class="px-4 py-3 font-mono text-xs text-surface-600 dark:text-surface-300" x-text="position(onu)"></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-surface-900 dark:text-white" x-text="onu.name || '—'"></p>
                                    <p class="font-mono text-[11px] text-surface-500" x-text="(onu.sn || '').toUpperCase()"></p>
                                </td>
                                <td class="px-4 py-3 text-surface-600 dark:text-surface-300 max-w-[14rem] truncate" x-text="onu.description || '—'" :title="onu.description"></td>
                                <td class="px-4 py-3" @click.stop>
                                    <template x-if="onu.customer">
                                        <a :href="onu.customer.url" class="block hover:underline">
                                            <span class="font-medium text-primary-700 dark:text-primary-300" x-text="onu.customer.name"></span>
                                            <span class="block font-mono text-[11px] text-surface-500" x-text="onu.customer.code"></span>
                                        </a>
                                    </template>
                                    <span x-show="!onu.customer" class="badge-gray">Belum ditautkan</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold tabular-nums" :class="signalClass(num(onu.signal))" x-text="num(onu.signal) === null ? '—' : num(onu.signal).toFixed(2) + ' dBm'"></span>
                                    </div>
                                    <div class="mt-1 h-1 w-20 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden" x-show="num(onu.signal) !== null">
                                        <div class="h-full rounded-full" :class="signalBar(num(onu.signal))" :style="'width:' + signalPct(num(onu.signal)) + '%'"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-surface-600 dark:text-surface-300" x-text="onu.distance ? (onu.distance >= 1000 ? (onu.distance / 1000).toFixed(2) + ' km' : onu.distance + ' m') : '—'"></td>
                                <td class="px-4 py-3">
                                    <span :class="isOnline(onu) ? 'badge-success' : 'badge-danger'" x-text="isOnline(onu) ? 'Online' : 'Offline'"></span>
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="btn-ghost btn-sm" @click="openDetail(onu)">Detail</button>
                                        <button type="button" class="btn-ghost btn-icon h-8 w-8" :disabled="!caps?.reboot || busy" :title="caps?.reboot ? 'Reboot ONU' : caps?.reboot_hint" @click="reboot(onu)">
                                            <x-icon name="refresh" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="loading && !onus.length" class="p-10 text-center text-sm text-surface-500">
                    <div class="mx-auto mb-2 h-6 w-6 animate-spin rounded-full border-2 border-primary-600 border-t-transparent"></div>
                    Membaca daftar ONU dari OLT…
                </div>
                <p x-show="!loading && !visible().length" x-cloak class="p-10 text-center text-sm text-surface-500"
                   x-text="onus.length ? 'Tidak ada ONU yang cocok dengan filter.' : 'Belum ada ONU terdaftar di port ini.'"></p>
            </div>
        </div>

        {{-- Panel detail ONU --}}
        <template x-teleport="body">
            <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex justify-end bg-surface-900/40" @keydown.escape.window="selected = null" @click.self="selected = null">
                <aside class="h-full w-full max-w-md overflow-y-auto bg-white dark:bg-surface-900 shadow-xl" x-show="selected"
                       x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                    <template x-if="selected">
                        <div>
                            <div class="flex items-start justify-between gap-3 border-b border-surface-200 dark:border-surface-800 p-5">
                                <div>
                                    <p class="text-xs text-surface-500" x-text="'{{ $olt->name }} · ' + position(selected)"></p>
                                    <h3 class="text-lg font-semibold text-surface-900 dark:text-white" x-text="selected.name || 'ONU'"></h3>
                                    <span class="mt-1" :class="isOnline(selected) ? 'badge-success' : 'badge-danger'" x-text="isOnline(selected) ? 'Online' : 'Offline'"></span>
                                </div>
                                <button type="button" class="btn-ghost btn-icon" @click="selected = null" aria-label="Tutup"><x-icon name="x" class="w-4 h-4" /></button>
                            </div>
                            <dl class="divide-y divide-surface-100 dark:divide-surface-800 text-sm">
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">MAC / SN</dt><dd class="font-mono" x-text="(selected.sn || '').toUpperCase()"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">Deskripsi</dt><dd class="text-right" x-text="selected.description || '—'"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">Sinyal Rx</dt><dd class="font-semibold" :class="signalClass(num(selected.signal))" x-text="num(selected.signal) === null ? '—' : num(selected.signal).toFixed(2) + ' dBm'"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">Jarak</dt><dd x-text="selected.distance ? selected.distance + ' m' : '—'"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">Tipe</dt><dd x-text="selected.type || '—'"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3" x-show="selected.reg_time"><dt class="text-surface-500">Terdaftar</dt><dd x-text="selected.reg_time"></dd></div>
                                <div class="flex justify-between gap-4 px-5 py-3" x-show="selected.last_down_reason"><dt class="text-surface-500">Putus terakhir</dt>
                                    <dd class="text-right"><span x-text="selected.last_down_reason"></span><span class="block text-xs text-surface-500" x-text="selected.last_down_time"></span></dd></div>
                                <template x-if="selected.tx_power !== undefined || selected.temperature !== undefined">
                                    <div class="grid grid-cols-4 gap-2 px-5 py-3 text-center">
                                        <div><p class="text-[11px] text-surface-500">Tx</p><p class="text-sm font-semibold tabular-nums" x-text="selected.tx_power != null ? selected.tx_power.toFixed(2) + ' dBm' : '—'"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Suhu</p><p class="text-sm font-semibold tabular-nums" x-text="selected.temperature != null ? selected.temperature + '°C' : '—'"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Tegangan</p><p class="text-sm font-semibold tabular-nums" x-text="selected.voltage != null ? selected.voltage.toFixed(2) + ' V' : '—'"></p></div>
                                        <div><p class="text-[11px] text-surface-500">Bias</p><p class="text-sm font-semibold tabular-nums" x-text="selected.bias != null ? selected.bias + ' mA' : '—'"></p></div>
                                    </div>
                                </template>
                                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-surface-500">Pelanggan</dt>
                                    <dd class="text-right">
                                        <template x-if="selected.customer"><a :href="selected.customer.url" class="font-medium text-primary-700 dark:text-primary-300 hover:underline" x-text="selected.customer.code + ' · ' + selected.customer.name"></a></template>
                                        <span x-show="!selected.customer" class="text-surface-500">Belum ditautkan</span>
                                    </dd>
                                </div>
                            </dl>
                            <div class="space-y-2 p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Aksi</p>
                                <button type="button" class="btn-secondary w-full justify-start" @click="copy(selected.sn)"><x-icon name="document" class="w-4 h-4" /> Salin MAC/SN</button>
                                <template x-if="selected.customer">
                                    <a :href="selected.customer.url" class="btn-secondary w-full justify-start"><x-icon name="user" class="w-4 h-4" /> Buka data pelanggan</a>
                                </template>
                                <button type="button" class="btn-secondary w-full justify-start" :disabled="!caps?.reboot || busy" @click="reboot(selected)">
                                    <x-icon name="refresh" class="w-4 h-4" /> <span x-text="busy ? 'Mengirim…' : 'Reboot ONU'"></span>
                                </button>
                                <p x-show="caps && !caps.reboot" class="text-xs text-surface-500" x-text="caps?.reboot_hint"></p>
                                <template x-if="caps?.edit">
                                    <form class="space-y-2 rounded-lg border border-surface-200 dark:border-surface-700 p-3" @submit.prevent="saveInfo()">
                                        <p class="text-xs font-medium text-surface-600 dark:text-surface-300">Nama & deskripsi di OLT</p>
                                        <input type="text" x-model="infoForm.name" maxlength="16" class="input" placeholder="Nama (maks 16, tanpa spasi)" aria-label="Nama ONU">
                                        <input type="text" x-model="infoForm.description" maxlength="64" class="input" placeholder="Deskripsi (maks 64)" aria-label="Deskripsi ONU">
                                        <button class="btn-secondary w-full" :disabled="busy"><x-icon name="check" class="w-4 h-4" /> <span x-text="busy ? 'Menyimpan…' : 'Simpan ke OLT'"></span></button>
                                    </form>
                                </template>
                                <template x-if="!caps?.edit">
                                    <div>
                                        <button type="button" class="btn-secondary w-full justify-start" disabled><x-icon name="settings" class="w-4 h-4" /> Ubah nama / deskripsi</button>
                                        <p class="mt-1 text-xs text-surface-500" x-text="caps?.edit_hint"></p>
                                    </div>
                                </template>
                                <button type="button" x-show="caps?.delete" class="btn-ghost w-full justify-start text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10" :disabled="busy" @click="removeOnu(selected)">
                                    <x-icon name="x" class="w-4 h-4" /> Hapus ONU dari OLT
                                </button>
                            </div>
                        </div>
                    </template>
                </aside>
            </div>
        </template>

        {{-- Allow ONU (HSGQ) --}}
        <template x-teleport="body">
            <div x-show="allowOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-surface-900/40 p-4 sm:p-10" @keydown.escape.window="allowOpen = false" @click.self="allowOpen = false">
                <div class="w-full max-w-xl rounded-xl bg-white dark:bg-surface-900 shadow-xl">
                    <div class="flex items-start justify-between gap-3 border-b border-surface-200 dark:border-surface-800 p-5">
                        <div>
                            <h3 class="text-lg font-semibold text-surface-900 dark:text-white">Allow ONU · PON {{ $port->slot }}/{{ $port->pon_port }}</h3>
                            <p class="text-xs text-surface-500">ONU baru harus diizinkan dulu di OLT sebelum bisa online.</p>
                        </div>
                        <button type="button" class="btn-ghost btn-icon" @click="allowOpen = false" aria-label="Tutup"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                    <div class="space-y-5 p-5">
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-surface-700 dark:text-surface-300">Menunggu izin di port ini</p>
                                <button type="button" class="text-xs text-primary-600 hover:underline" @click="loadPending()" :disabled="pendingLoading">Muat ulang</button>
                            </div>
                            <p x-show="pendingLoading" class="mt-2 text-sm text-surface-500">Membaca dari OLT…</p>
                            <p x-show="pendingError" x-text="pendingError" class="mt-2 text-sm text-rose-600"></p>
                            <p x-show="!pendingLoading && !pendingError && !pending.length" class="mt-2 text-sm text-surface-500">Tidak ada ONU yang menunggu. Masukkan MAC ONU di bawah.</p>
                            <ul class="mt-2 divide-y divide-surface-100 dark:divide-surface-800 rounded-lg border border-surface-200 dark:border-surface-700" x-show="pending.length">
                                <template x-for="p in pending" :key="p.mac">
                                    <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                        <div>
                                            <p class="font-mono" x-text="p.mac"></p>
                                            <p class="text-xs text-surface-500" x-text="(p.source === 'denied' ? 'Di daftar tolak' : 'Belum diizinkan') + (p.description ? ' · ' + p.description : '')"></p>
                                        </div>
                                        <button type="button" class="btn-secondary btn-sm" @click="allowForm.mac = p.mac">Pilih</button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <form class="space-y-3" @submit.prevent="submitAllow()">
                            <div>
                                <label class="block text-sm font-medium text-surface-700 dark:text-surface-300" for="allow-mac">MAC ONU</label>
                                <input id="allow-mac" type="text" x-model="allowForm.mac" class="input mt-1 font-mono" placeholder="E8:68:19:02:FF:70" required>
                                <p class="mt-1 text-xs text-surface-500">Tertera di label ONU. Bila MAC milik pelanggan, nama & deskripsi otomatis diisi kode & nama pelanggan.</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300" for="allow-name">Nama <span class="text-xs font-normal text-surface-500">(opsional)</span></label>
                                    <input id="allow-name" type="text" x-model="allowForm.name" maxlength="16" class="input mt-1" placeholder="Maks 16, tanpa spasi">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-surface-700 dark:text-surface-300" for="allow-desc">Deskripsi <span class="text-xs font-normal text-surface-500">(opsional)</span></label>
                                    <input id="allow-desc" type="text" x-model="allowForm.description" maxlength="64" class="input mt-1">
                                </div>
                            </div>
                            <p x-show="allowMsg" x-text="allowMsg" class="text-sm" :class="allowOk ? 'text-emerald-600' : 'text-rose-600'"></p>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="btn-secondary" @click="allowOpen = false">Tutup</button>
                                <button class="btn-primary" :disabled="busy"><x-icon name="check" class="w-4 h-4" /> <span x-text="busy ? 'Mengirim…' : 'Allow ONU'"></span></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function onuPortManager() {
            return {
                onus: [], caps: null, loading: false, busy: false, error: '', descError: '', selected: null,
                infoForm: { name: '', description: '' },
                allowOpen: false, pending: [], pendingLoading: false, pendingError: '', allowForm: { mac: '', name: '', description: '' }, allowMsg: '', allowOk: false,
                q: '', filter: 'all', sort: 'onu_id', dir: 1,
                filters: [
                    { key: 'all', label: 'Semua' }, { key: 'online', label: 'Online' }, { key: 'offline', label: 'Offline' },
                    { key: 'critical', label: 'Kritis' }, { key: 'unlinked', label: 'Tanpa pelanggan' },
                ],
                init() {
                    window.addEventListener('sync-onu-live', () => this.load(true));
                    this.load(false);
                },
                load(fresh) {
                    this.loading = true; this.error = '';
                    fetch(@js(route('olts.get-port-data', [$olt, $port], false)) + (fresh ? '?fresh=1' : ''), { headers: { 'Accept': 'application/json' } })
                        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                        .then(d => {
                            if (d.status === 'error') throw new Error(d.message || 'OLT tidak merespons');
                            this.onus = d.onus || []; this.caps = d.capabilities || null; this.descError = d.description_error || '';
                        })
                        .catch(e => { this.error = 'Gagal membaca ONU: ' + e.message + (this.onus.length ? ' — menampilkan data terakhir.' : ''); })
                        .finally(() => this.loading = false);
                },
                num(v) { const n = parseFloat(v); return isNaN(n) ? null : n; },
                isOnline(o) { return ['online', 'working', 'ready'].includes(String(o.status || '').toLowerCase()); },
                matches(o, key) {
                    const s = this.num(o.signal);
                    return key === 'all' || (key === 'online' && this.isOnline(o)) || (key === 'offline' && !this.isOnline(o))
                        || (key === 'critical' && s !== null && s < -27) || (key === 'unlinked' && !o.customer);
                },
                count(key) { return this.onus.filter(o => this.matches(o, key)).length; },
                avgSignal() {
                    const v = this.onus.map(o => this.num(o.signal)).filter(n => n !== null);
                    return v.length ? v.reduce((a, b) => a + b, 0) / v.length : null;
                },
                visible() {
                    const q = this.q.toLowerCase().trim(), qn = q.replace(/[^a-z0-9]/g, '');
                    let rows = this.onus.filter(o => this.matches(o, this.filter));
                    if (q) rows = rows.filter(o => [o.sn, o.name, o.description, o.customer?.name, o.customer?.code, this.position(o)]
                        .some(v => String(v || '').toLowerCase().includes(q)) || (qn && String(o.sn || '').toLowerCase().replace(/[^a-z0-9]/g, '').includes(qn)));
                    const val = o => this.sort === 'signal' ? (this.num(o.signal) ?? -999) : (this.sort === 'distance' ? (o.distance || 0) : (o.onu_id || 0));
                    return rows.sort((a, b) => (val(a) - val(b)) * this.dir);
                },
                sortBy(key) { this.dir = this.sort === key ? -this.dir : 1; this.sort = key; },
                sortMark(key) { return this.sort === key ? (this.dir > 0 ? '▲' : '▼') : ''; },
                position(o) { const p = String(o.index || '').split(/[.\/:]/).filter(Boolean); return p.length >= 4 ? p[0] + '/' + p[1] + '/' + p[2] + ':' + p[3] : (o.index || ''); },
                signalClass(s) { return s === null ? 'text-surface-400' : (s < -27 ? 'text-rose-600 dark:text-rose-400' : (s < -24 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')); },
                signalBar(s) { return s < -27 ? 'bg-rose-500' : (s < -24 ? 'bg-amber-500' : 'bg-emerald-500'); },
                signalPct(s) { return Math.max(5, Math.min(100, ((s + 35) / 27) * 100)); },
                openDetail(o) { this.selected = o; this.infoForm = { name: o.name || '', description: o.description || '' }; },
                post(url, body) {
                    return fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(body),
                    }).then(async r => {
                        const d = await r.json().catch(() => ({}));
                        const errors = d.errors ? Object.values(d.errors).flat().join(' ') : '';
                        return { ok: r.ok && d.success !== false, message: errors || d.message || ('HTTP ' + r.status) };
                    });
                },
                toast(m) { window.showGlobalToast ? window.showGlobalToast(m) : alert(m); },
                saveInfo() {
                    const o = this.selected;
                    if (/\s/.test(this.infoForm.name)) { this.toast('Nama ONU tidak boleh berisi spasi.'); return; }
                    this.busy = true;
                    this.post(@js(route('olts.onus.info', [$olt, $port], false)), { index: this.position(o), sn: o.sn, ...this.infoForm })
                        .then(r => { this.toast(r.message); if (r.ok) { o.name = this.infoForm.name; o.description = this.infoForm.description; } })
                        .catch(() => this.toast('Gagal menghubungi server'))
                        .finally(() => this.busy = false);
                },
                removeOnu(o) {
                    const mac = String(o.sn || '').toUpperCase();
                    const typed = prompt('Hapus ONU ' + mac + (o.customer ? ' (pelanggan ' + o.customer.name + ')' : '') + ' dari OLT?\nONU langsung terputus dan harus di-allow ulang.\n\nKetik HAPUS untuk melanjutkan:');
                    if (typed === null) return;
                    if (typed.trim().toUpperCase() !== 'HAPUS') { this.toast('Dibatalkan: ketik HAPUS untuk menghapus.'); return; }
                    this.busy = true;
                    this.post(@js(route('olts.onus.delete', [$olt, $port], false)), { index: this.position(o), sn: o.sn })
                        .then(r => { this.toast(r.message); if (r.ok) { this.selected = null; this.load(true); } })
                        .catch(() => this.toast('Gagal menghubungi server'))
                        .finally(() => this.busy = false);
                },
                openAllow() { this.allowOpen = true; this.allowMsg = ''; this.allowForm = { mac: '', name: '', description: '' }; this.loadPending(); },
                loadPending() {
                    this.pendingLoading = true; this.pendingError = ''; this.pending = [];
                    fetch(@js(route('olts.onus.pending', [$olt, $port], false)), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => { if (d.success) this.pending = d.onus || []; else this.pendingError = d.message || 'Gagal membaca dari OLT'; })
                        .catch(() => this.pendingError = 'Gagal menghubungi server')
                        .finally(() => this.pendingLoading = false);
                },
                submitAllow() {
                    if (!confirm('Allow ONU ' + this.allowForm.mac.toUpperCase() + ' di PON {{ $port->slot }}/{{ $port->pon_port }}?')) return;
                    this.busy = true; this.allowMsg = '';
                    this.post(@js(route('olts.onus.allow', [$olt, $port], false)), this.allowForm)
                        .then(r => { this.allowOk = r.ok; this.allowMsg = r.message; if (r.ok) { this.loadPending(); setTimeout(() => this.load(true), 60000); } })
                        .catch(() => { this.allowOk = false; this.allowMsg = 'Gagal menghubungi server'; })
                        .finally(() => this.busy = false);
                },
                copy(text) {
                    navigator.clipboard?.writeText(String(text || '').toUpperCase());
                    if (window.showGlobalToast) window.showGlobalToast('MAC/SN disalin');
                },
                reboot(o) {
                    if (!this.caps?.reboot || !confirm('Reboot ONU ' + (o.name || o.sn) + '? Internet pelanggan terputus ±1–2 menit.')) return;
                    this.busy = true;
                    this.post(@js(route('olts.onus.reboot', [$olt, $port], false)), { index: this.position(o), sn: o.sn })
                        .then(r => this.toast(r.message))
                        .catch(() => this.toast('Gagal menghubungi server'))
                        .finally(() => this.busy = false);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
