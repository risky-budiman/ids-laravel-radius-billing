@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Stock opname · {{ $item->name }}</h2>
            <p class="page-subtitle">Cocokkan dengan barang fisik di gudang. Catatan mutasi disamakan otomatis lewat satu penyesuaian.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('inventory.opname.store', $item) }}" class="max-w-4xl space-y-6">
        @csrf
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-stat-card label="{{ $item->track_serial ? 'Unit ber-SN di gudang' : 'Stok menurut sistem' }}"
                         :value="($item->track_serial ? $available->count() : $fmt($balance)) . ' ' . $item->unit" icon="cube" tone="info" />
            <x-stat-card label="Saldo catatan mutasi" :value="$fmt($balance) . ' ' . $item->unit" icon="document"
                         :tone="$item->track_serial && $balance != $available->count() ? 'warning' : 'gray'"
                         :hint="$item->track_serial && $balance != $available->count() ? 'Selisih ' . $fmt($balance - $available->count()) . ' — akan disamakan' : null" />
        </div>

        @if($item->track_serial)
            <div class="card" x-data="{ all: true }">
                <div class="card-header flex items-center justify-between gap-3">
                    <div>
                        <h3 class="card-title">Unit di gudang</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400">Hilangkan centang pada unit yang tidak ditemukan — statusnya menjadi <strong>Hilang</strong>.</p>
                    </div>
                    <button type="button" class="btn-ghost btn-sm" @click="all = !all; $root.querySelectorAll('input[name=\'present[]\']').forEach(c => c.checked = all)">Centang / hapus semua</button>
                </div>
                <div class="card-body">
                    @if($available->isEmpty())
                        <p class="text-sm text-surface-500">Tidak ada unit ber-SN di gudang.</p>
                    @else
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($available as $s)
                                <label class="flex items-center gap-2 rounded-lg border border-surface-200 px-3 py-2 text-sm dark:border-surface-700">
                                    <input type="checkbox" name="present[]" value="{{ $s->id }}" @checked(! old() || in_array($s->id, old('present', []))) class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                                    <span class="font-mono text-xs">{{ $s->serial_number ?? '(tanpa SN)' }}</span>
                                    <span class="ml-auto text-xs text-surface-400">{{ \App\Models\InventoryStock::STATUS_LABELS[$s->status] ?? $s->status }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">SN ditemukan tetapi belum tercatat</h3></div>
                <div class="card-body">
                    <textarea name="found_serials" rows="4" class="input !h-auto font-mono text-xs" placeholder="Satu SN per baris (bisa ditempel dari pemindai)">{{ old('found_serials') }}</textarea>
                    <p class="help-text">Didaftarkan sebagai unit siap pakai (kondisi bekas). Pakai juga untuk mendaftarkan SN barang yang diterima dari Purchase Order.</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <label for="physical_qty" class="label">Jumlah fisik di gudang ({{ $item->unit }})</label>
                    <input id="physical_qty" name="physical_qty" type="number" step="0.01" min="0" class="input sm:max-w-xs" value="{{ old('physical_qty', $balance) }}" required>
                    <p class="help-text">Selisih dengan stok sistem ({{ $fmt($balance) }} {{ $item->unit }}) dicatat sebagai penyesuaian.</p>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <label for="notes" class="label">Catatan <span class="text-surface-400 font-normal">(opsional)</span></label>
                <input id="notes" name="notes" class="input" maxlength="500" value="{{ old('notes') }}" placeholder="mis. Opname akhir bulan November">
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('inventory.show', $item) }}" class="btn-ghost">Batal</a>
            <button class="btn-primary" onclick="return confirm('Simpan hasil stock opname?')">Simpan stock opname</button>
        </div>
    </form>
</x-app-layout>
