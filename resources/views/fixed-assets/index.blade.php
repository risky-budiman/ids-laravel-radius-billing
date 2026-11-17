@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $due = collect($plan)->where('status', 'due');
    $statusBadge = ['due' => 'badge-warning', 'posted' => 'badge-success', 'closed' => 'badge-gray', 'done' => 'badge-gray', 'not_yet' => 'badge-gray', 'inactive' => 'badge-gray', 'config' => 'badge-danger'];
    $canManage = \App\Support\RouteAccess::allows(auth()->user(), 'fixed-assets.depreciation.run');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Aset Tetap</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Nilai perolehan, nilai buku dan penyusutan bulanan (garis lurus).</p>
            </div>
            <a href="{{ route('fixed-assets.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Daftarkan aset</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
        @endif

        {{-- Penyusutan bulanan --}}
        <div class="card" id="penyusutan">
            <div class="flex flex-col gap-3 border-b border-surface-200 px-5 py-4 dark:border-surface-800 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-surface-900 dark:text-white">Penyusutan bulanan
                        <span class="{{ $autoOn ? 'badge-success' : 'badge-gray' }}">Otomatis {{ $autoOn ? 'aktif' : 'nonaktif' }}</span></h3>
                    <p class="text-xs text-surface-500">{{ $autoOn ? 'Dijurnal otomatis setiap hari terakhir bulan pukul 23:00.' : 'Jurnal penyusutan hanya dibuat bila dijalankan dari sini. Aktifkan agar berjalan setiap akhir bulan.' }}
                        Dr beban penyusutan / Cr akumulasi penyusutan, satu jurnal per aset per bulan; bulan yang sudah tutup buku dilewati.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <label for="month" class="sr-only">Bulan</label>
                        <input id="month" type="month" name="month" value="{{ $month->format('Y-m') }}" class="input h-9 w-auto py-1" onchange="this.form.submit()">
                    </form>
                    @if($canManage)
                        <form method="POST" action="{{ route('fixed-assets.depreciation.auto') }}"
                              onsubmit="return confirm({{ $autoOn ? "'Nonaktifkan penyusutan otomatis?'" : "'Aktifkan penyusutan otomatis? Setiap hari terakhir bulan, semua aset aktif dijurnal penyusutannya.'" }})">
                            @csrf<input type="hidden" name="enabled" value="{{ $autoOn ? 0 : 1 }}">
                            <button class="btn-secondary h-9">{{ $autoOn ? 'Nonaktifkan otomatis' : 'Aktifkan otomatis' }}</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Aset</th><th>Akun beban → akumulasi</th><th class="text-right">Per bulan</th><th class="text-right">Sisa disusutkan</th><th>{{ $month->locale('id')->translatedFormat('F Y') }}</th></tr></thead>
                    <tbody>
                        @forelse($plan as $row)
                            @php $a = $row['asset']; @endphp
                            <tr>
                                <td><span class="font-medium text-surface-900 dark:text-white">{{ $a->name }}</span><span class="block font-mono text-[11px] text-surface-500">{{ $a->asset_code }}</span></td>
                                <td class="text-xs">{{ $a->depreciationAccount?->code }} {{ $a->depreciationAccount?->name }} → {{ $a->accumulatedAccount?->code }} {{ $a->accumulatedAccount?->name }}</td>
                                <td class="text-right tabular-nums">{{ $rp(\App\Services\Finance\DepreciationService::monthly($a)) }}</td>
                                <td class="text-right tabular-nums">{{ $rp(\App\Services\Finance\DepreciationService::remaining($a)) }}</td>
                                <td><span class="{{ $statusBadge[$row['status']] }}">{{ $row['note'] }}</span>@if($row['status'] === 'due') <span class="ml-1 text-xs tabular-nums text-surface-600 dark:text-surface-300">{{ $rp($row['amount']) }}</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-surface-500">Belum ada aset tetap.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($canManage && $due->isNotEmpty())
                <form method="POST" action="{{ route('fixed-assets.depreciation.run') }}" class="flex flex-col gap-2 border-t border-surface-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-surface-800"
                      onsubmit="return confirm('Jurnal penyusutan {{ $month->locale('id')->translatedFormat('F Y') }} untuk {{ $due->count() }} aset, total {{ $rp($due->sum('amount')) }}?')">
                    @csrf<input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <p class="text-sm text-surface-600 dark:text-surface-300">{{ $due->count() }} aset siap dijurnal untuk {{ $month->locale('id')->translatedFormat('F Y') }}, total <strong class="tabular-nums">{{ $rp($due->sum('amount')) }}</strong>. Bulan yang terlewat bisa dijurnal dengan memilih bulannya.</p>
                    <button class="btn-primary">Jurnal penyusutan {{ $month->locale('id')->translatedFormat('F Y') }}</button>
                </form>
            @endif
        </div>

        {{-- Daftar aset --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Kode</th><th>Nama</th><th class="text-right">Nilai perolehan</th><th class="text-right">Nilai buku</th><th>Umur manfaat</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($assets as $asset)
                            <tr>
                                <td class="font-mono font-semibold text-primary-700 dark:text-primary-300">{{ $asset->asset_code }}</td>
                                <td class="font-medium text-surface-900 dark:text-white">{{ $asset->name }}<span class="block text-xs font-normal text-surface-500">dibeli {{ $asset->purchase_date?->translatedFormat('d M Y') }}</span></td>
                                <td class="text-right tabular-nums">{{ $rp($asset->purchase_price) }}</td>
                                <td class="text-right tabular-nums"><span class="font-semibold text-surface-900 dark:text-white">{{ $rp($asset->net_book_value) }}</span>
                                    <span class="block text-xs text-surface-500">disusutkan {{ $rp($asset->accumulated_depreciation) }}</span></td>
                                <td>{{ $asset->useful_life_months }} bulan</td>
                                <td>@if($asset->status === 'active')<span class="badge-success">Aktif</span>@else<span class="badge-gray">Dilepas</span>@endif</td>
                                <td class="whitespace-nowrap text-right">
                                    @canroute('fixed-assets.edit')<a href="{{ route('fixed-assets.edit', $asset) }}" class="btn-ghost btn-sm">Ubah</a>@endcanroute
                                    @canroute('fixed-assets.destroy')
                                        <form action="{{ route('fixed-assets.destroy', $asset) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus aset ini?');">
                                            @csrf @method('DELETE')<button type="submit" class="btn-ghost btn-sm text-rose-600">Hapus</button>
                                        </form>
                                    @endcanroute
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-12 text-center text-surface-500">Belum ada aset tetap.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assets->hasPages())<div class="border-t border-surface-200 px-6 py-4 dark:border-surface-700">{{ $assets->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
