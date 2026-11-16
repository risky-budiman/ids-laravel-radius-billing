@php
    $base = ['server_id' => $selectedServer?->id, 'q' => $search ?: null, 'tag' => $tagFilter ?: null, 'limit' => request('limit')];
    $url = fn (array $over) => route('acs-servers.devices', array_filter(array_merge($base, $over), fn ($v) => $v !== null && $v !== ''));
    $sig = ['good' => ['text-emerald-600 dark:text-emerald-400', 'bg-emerald-500'], 'warn' => ['text-amber-600 dark:text-amber-400', 'bg-amber-500'],
        'bad' => ['text-rose-600 dark:text-rose-400', 'bg-rose-500'], 'none' => ['text-surface-400', 'bg-surface-300']];
    $canReboot = \App\Support\RouteAccess::allows(auth()->user(), 'acs-servers.reboot');
    $canTags = \App\Support\RouteAccess::allows(auth()->user(), 'acs-servers.update-tags');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Modem Pelanggan</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Pantau & kelola modem (ONT/CPE) lewat GenieACS (TR-069).</p>
            </div>
            <div class="flex items-center gap-2">
                @if($servers->count() > 1)
                    <form method="GET" action="{{ route('acs-servers.devices') }}">
                        <select name="server_id" onchange="this.form.submit()" class="input py-2 text-sm" aria-label="Server ACS">
                            @foreach($servers as $s)
                                <option value="{{ $s->id }}" @selected(($selectedServer->id ?? null) === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @elseif($selectedServer)
                    <span class="badge-gray"><x-icon name="server" class="w-3.5 h-3.5" /> {{ $selectedServer->name }}</span>
                @endif
                @canroute('acs-servers.index')
                    <a href="{{ route('acs-servers.index') }}" class="btn-secondary"><x-icon name="settings" class="w-4 h-4" /> Server ACS</a>
                @endcanroute
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="deviceTagManager()">
        @foreach(['success' => 'emerald', 'error' => 'rose'] as $key => $c)
            @if(session($key))
                <div class="flex items-start gap-2.5 rounded-lg border border-{{ $c }}-200 bg-{{ $c }}-50 px-4 py-3 text-sm text-{{ $c }}-800 dark:border-{{ $c }}-500/20 dark:bg-{{ $c }}-500/10 dark:text-{{ $c }}-300" role="{{ $key === 'error' ? 'alert' : 'status' }}">
                    <x-icon name="{{ $key === 'error' ? 'exclamation' : 'check-circle' }}" class="w-5 h-5 shrink-0" /> {{ session($key) }}
                </div>
            @endif
        @endforeach

        {{-- Ringkasan & filter status --}}
        @if($stats)
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    [null, 'Total modem', $stats['total'], 'text-surface-900 dark:text-white', 'cube'],
                    ['online', 'Online', $stats['online'], 'text-emerald-600 dark:text-emerald-400', 'signal'],
                    ['offline', 'Offline', $stats['offline'], $stats['offline'] ? 'text-amber-600 dark:text-amber-400' : 'text-surface-900 dark:text-white', 'x-circle'],
                ] as [$key, $label, $value, $cls, $icon])
                    <a href="{{ $url(['status' => $key, 'page' => null]) }}"
                       class="card p-4 transition-colors hover:border-primary-300 {{ $status === $key ? 'ring-2 ring-primary-500 border-transparent' : '' }}">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-surface-500">{{ $label }}</p>
                            <x-icon :name="$icon" class="w-4 h-4 text-surface-400" />
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums {{ $cls }}">{{ number_format($value, 0, ',', '.') }}</p>
                        @if($key === 'online' && $stats['total'])
                            <div class="mt-2 h-1.5 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ round($stats['online'] / $stats['total'] * 100) }}%"></div>
                            </div>
                        @endif
                    </a>
                @endforeach
                <div class="card p-4">
                    <p class="text-xs font-medium text-surface-500">Halaman ini</p>
                    @php $linked = $paginatedDevices->getCollection()->filter(fn ($r) => $r['customer'])->count(); @endphp
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $linked }}<span class="text-base text-surface-400"> / {{ $paginatedDevices->count() }}</span></p>
                    <p class="text-xs text-surface-500">tertaut ke pelanggan</p>
                </div>
            </div>
        @endif

        @if($error)
            <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                <x-icon name="exclamation" class="w-5 h-5 shrink-0" /> {{ $error }}
            </div>
        @endif

        <div class="card overflow-hidden">
            {{-- Pencarian --}}
            <form action="{{ route('acs-servers.devices') }}" method="GET" class="flex flex-col gap-3 border-b border-surface-200 dark:border-surface-800 p-4 md:flex-row md:items-center">
                @foreach(['server_id' => $selectedServer?->id, 'tag' => $tagFilter, 'status' => $status] as $k => $val)
                    @if($val)<input type="hidden" name="{{ $k }}" value="{{ $val }}">@endif
                @endforeach
                <div class="relative w-full md:max-w-md">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                    <input type="search" name="q" value="{{ $search }}" class="input pl-9" placeholder="Cari username PPPoE, SN, tag atau IP…" aria-label="Cari modem" autocomplete="off">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($status)
                        <a href="{{ $url(['status' => null, 'page' => null]) }}" class="badge-primary hover:underline">{{ $status === 'online' ? 'Online' : 'Offline' }} ✕</a>
                    @endif
                    @if($tagFilter)
                        <a href="{{ $url(['tag' => null, 'page' => null]) }}" class="badge-primary hover:underline">Tag: {{ $tagFilter }} ✕</a>
                    @endif
                </div>
                <div class="flex items-center gap-2 md:ml-auto">
                    <select name="limit" onchange="this.form.submit()" class="input py-2 text-sm w-auto" aria-label="Baris per halaman">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) request('limit', 25) === $n)>{{ $n }} / halaman</option>
                        @endforeach
                    </select>
                    <button class="btn-primary"><x-icon name="search" class="w-4 h-4" /> Cari</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-900/60 text-left text-xs font-medium uppercase tracking-wide text-surface-500">
                        <tr>
                            <th class="px-4 py-3">Modem</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">IP WAN</th>
                            <th class="px-4 py-3">Sinyal Rx</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Tag</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @forelse($paginatedDevices as $d)
                            @php
                                $detailUrl = route('acs-servers.device-details', ['deviceId' => $d['id'], 'server_id' => $selectedServer->id]);
                                $level = \App\Support\AcsDevice::signalLevel($d['rx']);
                            @endphp
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ $detailUrl }}" class="group flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-100 text-surface-500 dark:bg-surface-800">
                                            <x-icon name="chip" class="w-5 h-5" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block whitespace-nowrap font-medium text-surface-900 group-hover:text-primary-700 dark:text-white">{{ $d['model'] ?: 'Modem' }}
                                                @if($d['manufacturer'])<span class="font-normal text-surface-400"> · {{ $d['manufacturer'] }}</span>@endif
                                            </span>
                                            <span class="block font-mono text-[11px] text-surface-500">{{ $d['serial'] }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    @if($d['customer'])
                                        <a href="{{ route('customers.show', $d['customer']->id) }}" class="block hover:underline">
                                            <span class="font-medium text-primary-700 dark:text-primary-300">{{ $d['customer']->name }}</span>
                                            <span class="block font-mono text-[11px] text-surface-500">{{ $d['customer']->customer_code }}</span>
                                        </a>
                                    @elseif($d['pppoe'])
                                        <span class="font-mono text-xs text-surface-700 dark:text-surface-300">{{ $d['pppoe'] }}</span>
                                        <span class="block text-[11px] text-surface-400">belum tertaut</span>
                                    @else
                                        <span class="text-surface-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-surface-600 dark:text-surface-300">{{ $d['ip'] ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($d['rx'] !== null)
                                        <span class="whitespace-nowrap font-semibold tabular-nums {{ $sig[$level][0] }}">{{ number_format($d['rx'], 2) }} dBm</span>
                                        <div class="mt-1 h-1 w-20 rounded-full bg-surface-200 dark:bg-surface-700 overflow-hidden">
                                            <div class="h-full rounded-full {{ $sig[$level][1] }}" style="width: {{ max(5, min(100, (($d['rx'] + 35) / 27) * 100)) }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-surface-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $d['online'] ? 'badge-success' : 'badge-gray' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $d['online'] ? 'bg-emerald-500' : 'bg-surface-400' }}"></span>
                                        {{ $d['online'] ? 'Online' : 'Offline' }}
                                    </span>
                                    <span class="mt-0.5 block whitespace-nowrap text-[11px] text-surface-500" title="{{ $d['last_inform']?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}">
                                        {{ $d['last_inform'] ? $d['last_inform']->locale('id')->diffForHumans() : 'belum pernah' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex max-w-[15rem] flex-wrap items-center gap-1">
                                        @foreach(array_slice($d['tags'], 0, 2) as $tag)
                                            <a href="{{ $url(['tag' => $tag, 'page' => null]) }}" class="max-w-[8rem] truncate rounded-md bg-primary-50 px-1.5 py-0.5 text-[11px] font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300" title="Filter tag {{ $tag }}">{{ $tag }}</a>
                                        @endforeach
                                        @if(count($d['tags']) > 2)
                                            <span class="text-[11px] text-surface-500" title="{{ implode(', ', array_slice($d['tags'], 2)) }}">+{{ count($d['tags']) - 2 }}</span>
                                        @endif
                                        @if($canTags)
                                            <button type="button" class="rounded p-0.5 text-surface-400 hover:text-primary-600" title="Ubah tag" aria-label="Ubah tag"
                                                    @click='openTagModal(@json($d["id"]), @json($d["tags"]), @json($d["serial"]))'>
                                                <x-icon name="plus" class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        @if($canReboot)
                                            <form method="POST" action="{{ route('acs-servers.reboot', ['deviceId' => $d['id'], 'server_id' => $selectedServer->id]) }}"
                                                  onsubmit="return confirm('Reboot modem {{ $d['serial'] }}? Internet pelanggan terputus ±1–2 menit.')">@csrf
                                                <button class="btn-ghost btn-icon h-8 w-8" title="Reboot modem" aria-label="Reboot modem"><x-icon name="refresh" class="w-4 h-4" /></button>
                                            </form>
                                        @endif
                                        <a href="{{ $detailUrl }}" class="btn-secondary btn-sm">Kelola</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <x-icon name="chip" class="mx-auto mb-3 w-10 h-10 text-surface-300" />
                                    <p class="font-medium text-surface-700 dark:text-surface-300">Tidak ada modem</p>
                                    <p class="text-sm text-surface-500">
                                        @if(! $selectedServer)
                                            Belum ada server GenieACS aktif.
                                        @elseif($search || $tagFilter || $status)
                                            Tidak ada yang cocok dengan pencarian/filter. <a href="{{ route('acs-servers.devices', ['server_id' => $selectedServer->id]) }}" class="text-primary-600 hover:underline">Hapus filter</a>
                                        @else
                                            Server belum melaporkan perangkat.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginatedDevices->total() > 0)
                <div class="border-t border-surface-200 dark:border-surface-800 px-5 py-3">
                    {{ $paginatedDevices->links(null, ['label' => 'modem']) }}
                </div>
            @endif
        </div>

        <p class="text-xs text-surface-500">Online = modem mengirim laporan (inform) dalam {{ \App\Support\AcsDevice::ONLINE_MINUTES }} menit terakhir. Sinyal: hijau ≥ -24, kuning -24 s/d -27, merah &lt; -27 dBm.</p>

        {{-- Ubah tag --}}
        <template x-teleport="body">
            <div x-show="showTagModal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-surface-900/40 p-4 sm:p-10" @keydown.escape.window="showTagModal = false" @click.self="showTagModal = false">
                <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-surface-900">
                    <div class="flex items-start justify-between gap-3 border-b border-surface-200 p-5 dark:border-surface-800">
                        <div>
                            <h3 class="text-lg font-semibold text-surface-900 dark:text-white">Ubah tag modem</h3>
                            <p class="font-mono text-xs text-surface-500" x-text="activeSerial || activeDeviceId"></p>
                        </div>
                        <button type="button" class="btn-ghost btn-icon" @click="showTagModal = false" aria-label="Tutup"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="flex min-h-[44px] flex-wrap items-center gap-1.5 rounded-lg border border-surface-200 p-2 dark:border-surface-700">
                            <template x-for="(tag, index) in activeTags" :key="tag">
                                <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                    <span x-text="tag"></span>
                                    <button type="button" @click="removeTag(index)" class="text-primary-400 hover:text-rose-600" aria-label="Hapus tag">✕</button>
                                </span>
                            </template>
                            <span x-show="activeTags.length === 0" class="text-xs text-surface-400">Belum ada tag.</span>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" x-model="newTagInput" @keydown.enter.prevent="addInputTag()" placeholder="Tag baru, mis. odp-12 atau nama pelanggan" class="input">
                            <button type="button" @click="addInputTag()" class="btn-secondary">Tambah</button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="preset in ['VIP', 'Prioritas', 'Isolir', 'Gangguan']" :key="preset">
                                <button type="button" @click="addPresetTag(preset)" class="rounded-md border border-dashed border-surface-300 px-2 py-0.5 text-xs text-surface-600 hover:border-primary-400 hover:text-primary-700 dark:border-surface-600 dark:text-surface-300">+ <span x-text="preset"></span></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-surface-200 p-4 dark:border-surface-800">
                        <button type="button" @click="showTagModal = false" class="btn-secondary">Batal</button>
                        <button type="button" @click="saveTags()" :disabled="isSaving" class="btn-primary"><span x-text="isSaving ? 'Menyimpan…' : 'Simpan tag'"></span></button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        function deviceTagManager() {
            return {
                showTagModal: false, activeDeviceId: '', activeSerial: '', activeTags: [], originalTags: [], newTagInput: '', isSaving: false,
                openTagModal(deviceId, tags, serial) {
                    this.activeDeviceId = deviceId; this.activeSerial = serial || deviceId;
                    this.activeTags = Array.isArray(tags) ? [...tags] : []; this.originalTags = [...this.activeTags];
                    this.newTagInput = ''; this.showTagModal = true;
                },
                addInputTag() { const t = this.newTagInput.trim(); if (t && !this.activeTags.includes(t)) this.activeTags.push(t); this.newTagInput = ''; },
                addPresetTag(t) { if (!this.activeTags.includes(t)) this.activeTags.push(t); },
                removeTag(i) { this.activeTags.splice(i, 1); },
                saveTags() {
                    this.isSaving = true;
                    const url = @js(route('acs-servers.update-tags', ':id', false)).replace(':id', encodeURIComponent(this.activeDeviceId));
                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ server_id: @js($selectedServer->id ?? ''), tags: this.activeTags, current_tags: this.originalTags }),
                    })
                        .then(r => r.json())
                        .then(d => { if (d.success) { window.location.reload(); } else { alert('Gagal menyimpan tag: ' + (d.message || 'kesalahan tidak diketahui')); } })
                        .catch(e => alert('Gagal menghubungi server: ' + e.message))
                        .finally(() => this.isSaving = false);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
