@php
    $scopeType = old('scope_type', $prefill['scope_type'] ?? 'port');
    $scopeId = old('scope_id', $prefill['scope_id'] ?? '');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('noc.incidents.index') }}" class="text-sm text-surface-500 hover:text-primary-600">← Gangguan Massal</a>
            <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Laporkan Gangguan Massal</h2>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('noc.incidents.store') }}" class="grid gap-4 lg:grid-cols-[1fr_22rem]"
          x-data="incidentForm(@js($scopeType), @js((string) $scopeId))" x-init="load()">
        @csrf
        <div class="card">
            <div class="card-body space-y-4">
                @if(session('error'))
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
                @endif
                <div>
                    <label class="label">Cakupan gangguan</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\NetworkIncident::SCOPES as $k => $v)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                                   :class="type === '{{ $k }}' ? 'border-primary-500 bg-primary-50 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200' : 'border-surface-200 dark:border-surface-700'">
                                <input type="radio" name="scope_type" value="{{ $k }}" x-model="type" @change="id = ''; load()" class="sr-only"> {{ $v }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="type === 'olt'">
                    <label class="label" for="scope_olt">OLT</label>
                    <select id="scope_olt" class="input" :disabled="type !== 'olt'" :name="type === 'olt' ? 'scope_id' : ''" x-model="id" @change="load()">
                        <option value="">— pilih OLT —</option>
                        @foreach($olts as $o)<option value="{{ $o->id }}">{{ $o->name }} ({{ $o->ip_address }})</option>@endforeach
                    </select>
                </div>
                <div x-show="type === 'port'">
                    <label class="label" for="scope_port">Port PON</label>
                    <select id="scope_port" class="input" :disabled="type !== 'port'" :name="type === 'port' ? 'scope_id' : ''" x-model="id" @change="load()">
                        <option value="">— pilih port —</option>
                        @foreach($olts as $o)
                            <optgroup label="{{ $o->name }}">
                                @foreach($o->ponPorts as $p)<option value="{{ $p->id }}">{{ $o->name }} · port {{ $p->slot }}/{{ $p->pon_port }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'odp'">
                    <label class="label" for="scope_odp">ODP</label>
                    <select id="scope_odp" class="input" :disabled="type !== 'odp'" :name="type === 'odp' ? 'scope_id' : ''" x-model="id" @change="load()">
                        <option value="">— pilih ODP —</option>
                        @foreach($odps as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="title">Judul</label>
                    <input id="title" name="title" class="input" maxlength="150" value="{{ old('title') }}" :placeholder="label ? 'Gangguan massal ' + label : 'Mis. Kabel feeder putus di Jl. Merdeka'">
                </div>
                <div>
                    <label class="label" for="description">Keterangan untuk tim</label>
                    <textarea id="description" name="description" rows="3" class="input" maxlength="2000" placeholder="Penyebab dugaan, lokasi, tim yang berangkat…">{{ old('description') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="estimate">Estimasi perbaikan</label>
                        <input id="estimate" name="estimate" class="input" maxlength="100" value="{{ old('estimate') }}" placeholder="mis. 17-11-2026 16:00 atau sore ini">
                        <p class="help-text">Tampil di WhatsApp sebagai "Estimasi perbaikan". Kosong = "secepatnya".</p>
                    </div>
                    <div>
                        <label class="label" for="priority">Prioritas tiket induk</label>
                        <select id="priority" name="priority" class="input">
                            @foreach(['urgent' => 'Mendesak', 'high' => 'Tinggi', 'medium' => 'Sedang'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('priority', 'urgent') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="flex items-start gap-3 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                    <input type="checkbox" name="notify" value="1" class="mt-0.5 rounded" @checked(old('notify'))>
                    <span class="text-sm"><span class="font-medium text-surface-900 dark:text-white">Kirim WhatsApp pemberitahuan ke pelanggan terdampak</span>
                        <span class="block text-surface-500">Template <em>Gangguan Massal</em> (Template Pesan), ke <strong x-text="withPhone"></strong> pelanggan aktif bernomor HP, bertahap lewat antrean. Bisa juga dikirim nanti dari halaman gangguan.</span></span>
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-surface-200 px-6 py-4 dark:border-surface-800">
                <a href="{{ route('noc.incidents.index') }}" class="btn-ghost">Batal</a>
                <button class="btn-primary" :disabled="!id || loading">Buat gangguan massal</button>
            </div>
        </div>

        <aside class="card h-fit">
            <div class="card-body space-y-3 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Pelanggan aktif terdampak</p>
                <p x-show="!id" class="text-surface-500">Pilih cakupan untuk melihat pelanggan terdampak.</p>
                <p x-show="loading" class="text-surface-500">Menghitung…</p>
                <template x-if="id && !loading && count !== null">
                    <div class="space-y-2">
                        <p class="text-surface-700 dark:text-surface-300"><span class="text-3xl font-semibold tabular-nums text-surface-900 dark:text-white" x-text="count"></span> pelanggan · <span x-text="label"></span></p>
                        <p class="text-xs text-surface-500"><span x-text="withPhone"></span> punya No. HP</p>
                        <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                            <template x-for="c in sample" :key="c.code"><li class="py-1.5"><span class="text-surface-900 dark:text-white" x-text="c.name"></span> <span class="font-mono text-[11px] text-surface-500" x-text="c.code"></span></li></template>
                        </ul>
                        <p x-show="count > sample.length" class="text-xs text-surface-500">dan <span x-text="count - sample.length"></span> lainnya</p>
                        <p x-show="count === 0" class="rounded bg-amber-50 px-2 py-1 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">Tidak ada pelanggan aktif yang tercatat di cakupan ini. Untuk port PON, jalankan Sinkron ONU / Penautan ONU agar pelanggan tertaut ke ONU.</p>
                    </div>
                </template>
            </div>
        </aside>
    </form>

    @push('scripts')
    <script>
        function incidentForm(type, id) {
            return {
                type, id, loading: false, count: null, withPhone: 0, label: '', sample: [],
                load() {
                    if (!this.id) { this.count = null; this.label = ''; return; }
                    this.loading = true;
                    fetch(@js(route('noc.incidents.preview', [], false)) + '?scope_type=' + encodeURIComponent(this.type) + '&scope_id=' + encodeURIComponent(this.id), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json()).then(d => { this.count = d.success ? d.count : 0; this.withPhone = d.with_phone || 0; this.label = d.label || ''; this.sample = d.sample || []; })
                        .catch(() => { this.count = null; })
                        .finally(() => { this.loading = false; });
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
