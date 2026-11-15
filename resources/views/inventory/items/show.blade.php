@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $stock = $item->stock_count;
    $balance = $item->movementBalance();
    $gap = $item->serialDiscrepancy();
    $canManage = auth()->user()->hasRole(['administrator', 'admin']);
    $statusTone = ['ready' => 'badge-success', 'returned' => 'badge-info', 'dismantled' => 'badge-info',
                   'installed' => 'badge-primary', 'damaged' => 'badge-danger', 'lost' => 'badge-danger'];
    $typeLabel = ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ $item->name }}</h2>
                <p class="page-subtitle">{{ $item->category?->name ?? 'Tanpa kategori' }} · SKU <span class="font-mono">{{ $item->sku ?? '—' }}</span>
                    · {{ $item->track_serial ? 'dilacak per SN' : 'dihitung per jumlah' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inventory.index') }}" class="btn-ghost btn-sm">Kembali</a>
                @if($canManage && ! $item->trashed())
                    <a href="{{ route('inventory.opname', $item) }}" class="btn-secondary btn-sm">Stock opname</a>
                @endif
                @unless($item->trashed())
                    <a href="{{ route('inventory.stock-in', ['item_id' => $item->id]) }}" class="btn-primary btn-sm">Tambah stok</a>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl space-y-6">
        @foreach(['success' => 'emerald', 'error' => 'rose'] as $key => $tone)
            @if(session($key))
                <div class="rounded-xl border border-{{ $tone }}-200 bg-{{ $tone }}-50 px-4 py-3 text-sm text-{{ $tone }}-800 dark:border-{{ $tone }}-500/30 dark:bg-{{ $tone }}-500/10 dark:text-{{ $tone }}-300">{{ session($key) }}</div>
            @endif
        @endforeach

        @if($item->trashed())
            <div class="rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 text-sm text-surface-700 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-300">
                Produk ini diarsipkan pada {{ $item->deleted_at->locale('id')->translatedFormat('d M Y') }}. Riwayatnya tetap ditampilkan.
            </div>
        @endif

        @if($gap != 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                @if($gap > 0)
                    Catatan mutasi menunjukkan <strong>{{ $fmt($balance) }} {{ $item->unit }}</strong> di gudang, tetapi hanya <strong>{{ $stock }}</strong> unit yang punya SN.
                    {{ $fmt($gap) }} unit belum didaftarkan SN-nya (mis. dari Purchase Order) atau sudah tidak ada.
                @else
                    Ada <strong>{{ $stock }}</strong> unit ber-SN di gudang, tetapi catatan mutasi hanya {{ $fmt($balance) }} {{ $item->unit }} — {{ $fmt(-$gap) }} unit tidak punya catatan masuk.
                @endif
                @if($canManage)
                    <a href="{{ route('inventory.opname', $item) }}" class="font-semibold underline">Lakukan stock opname</a> untuk menyamakannya.
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Stok tersedia" :value="$fmt($stock) . ' ' . $item->unit" icon="cube"
                         :tone="$stock <= $item->min_stock ? 'warning' : 'success'" :hint="'Minimum ' . $fmt($item->min_stock) . ' ' . $item->unit" />
            @if($item->track_serial)
                <x-stat-card label="Terpasang di pelanggan" :value="(string) $item->stocks->where('status', 'installed')->count()" icon="users" tone="info"
                             :hint="$item->stocks->whereIn('status', ['lost', 'damaged'])->count() . ' rusak / hilang'" />
            @else
                <x-stat-card label="Total keluar" :value="$fmt($item->movements()->where('type', 'out')->sum('quantity')) . ' ' . $item->unit" icon="arrow-up" tone="info" />
            @endif
            <x-stat-card label="Saldo catatan mutasi" :value="$fmt($balance) . ' ' . $item->unit" icon="document" :tone="$gap != 0 ? 'warning' : 'gray'"
                         :hint="$gap != 0 ? 'Tidak cocok dengan SN' : 'Cocok dengan stok'" />
        </div>

        @if($item->description)
            <p class="text-sm text-surface-600 dark:text-surface-400">{{ $item->description }}</p>
        @endif

        @if($item->track_serial)
            <div class="card">
                <div class="card-header"><h3 class="card-title">Unit ber-SN</h3></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Nomor seri / MAC</th><th>Kondisi</th><th>Status</th><th>Pelanggan</th></tr></thead>
                        <tbody>
                            @forelse($item->stocks->sortBy(fn ($s) => [in_array($s->status, \App\Models\InventoryStock::AVAILABLE_STATUSES) ? 0 : 1, $s->serial_number]) as $s)
                                <tr>
                                    <td class="font-mono text-xs">{{ $s->serial_number ?? '—' }}@if($s->mac_address)<div class="text-surface-400">{{ $s->mac_address }}</div>@endif</td>
                                    <td>{{ \App\Models\InventoryStock::CONDITION_LABELS[$s->condition] ?? $s->condition }}</td>
                                    <td><span class="{{ $statusTone[$s->status] ?? 'badge-gray' }}">{{ \App\Models\InventoryStock::STATUS_LABELS[$s->status] ?? $s->status }}</span></td>
                                    <td>
                                        @if($s->customer)
                                            <a href="{{ route('customers.show', $s->customer) }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $s->customer->name }}</a>
                                        @else — @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-sm text-surface-500">Belum ada unit ber-SN.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h3 class="card-title">Riwayat mutasi</h3></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th class="text-right">Jumlah</th><th>Referensi / catatan</th><th>Oleh</th></tr></thead>
                    <tbody>
                        @forelse($movements as $m)
                            @php $signed = $m->type === 'out' ? -$m->quantity : (float) $m->quantity; @endphp
                            <tr>
                                <td class="whitespace-nowrap text-xs">{{ $m->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                <td><span class="{{ ['in' => 'badge-success', 'out' => 'badge-warning', 'adjustment' => 'badge-gray'][$m->type] ?? 'badge-gray' }}">{{ $typeLabel[$m->type] ?? $m->type }}</span></td>
                                <td class="text-right font-mono whitespace-nowrap">{{ $signed > 0 ? '+' : '−' }}{{ $fmt(abs($signed)) }} {{ $item->unit }}</td>
                                <td class="text-xs">
                                    <div>{{ $m->reference ?? '—' }}@if($m->customer) · {{ $m->customer->name }}@endif</div>
                                    @if($m->notes)<div class="text-surface-500">{{ $m->notes }}</div>@endif
                                </td>
                                <td class="text-xs">{{ $m->user?->name ?? 'Sistem' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-surface-500">Belum ada mutasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())<div class="card-body">{{ $movements->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
