@php
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y');
    $amt = fn ($v) => rupiah($v, 0);
    $neg = fn ($v) => '(' . rupiah($v, 0, false) . ')';
    $cls = fn ($v) => $v < 0 ? 'amount-neg' : '';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Laporan Arus Kas" :subtitle="'Metode langsung · ' . $fmtDate($startDate) . ' – ' . $fmtDate($endDate)">
            <x-slot name="filters">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="start_date" class="label !mb-1 text-xs">Dari</label>
                        <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="input !h-9">
                    </div>
                    <div>
                        <label for="end_date" class="label !mb-1 text-xs">Sampai</label>
                        <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="input !h-9">
                    </div>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
            <x-slot name="actions">
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false" class="btn-secondary">
                        Ekspor Excel <x-icon name="chevron-down" class="w-4 h-4" />
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-20 mt-1 w-56 card shadow-elevated p-1">
                        <a href="{{ route('accounting.reports.cash-flow.export', request()->all()) }}" class="block px-3 py-2 rounded-md text-sm text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-800">Periode yang ditampilkan</a>
                        <a href="{{ route('accounting.reports.cash-flow.export', array_merge(request()->all(), ['all_time' => 1])) }}" class="block px-3 py-2 rounded-md text-sm text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-800">Seluruh data</a>
                    </div>
                </div>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="report-doc">
            <table class="report-table">
                <thead><tr><th>Keterangan</th><th class="amount">Jumlah (Rp)</th></tr></thead>
                <tbody>
                    <tr class="report-section"><td colspan="2">Arus kas dari aktivitas operasional</td></tr>
                    <tr class="report-row"><td>Penerimaan kas dari pelanggan</td><td class="amount">{{ $amt($operatingIn) }}</td></tr>
                    <tr class="report-row"><td>Pembayaran kas kepada pemasok &amp; karyawan</td><td class="amount">{{ $neg($operatingOut) }}</td></tr>
                    <tr class="report-subtotal"><td>Kas neto dari aktivitas operasional</td><td class="amount {{ $cls($netOperating) }}">{{ $amt($netOperating) }}</td></tr>

                    <tr class="report-section"><td colspan="2">Arus kas dari aktivitas investasi</td></tr>
                    <tr class="report-row"><td>Pembelian inventaris &amp; aset tetap</td><td class="amount">{{ $neg($investingOut) }}</td></tr>
                    <tr class="report-subtotal"><td>Kas neto dari aktivitas investasi</td><td class="amount {{ $cls($netInvesting) }}">{{ $amt($netInvesting) }}</td></tr>

                    <tr class="report-section"><td colspan="2">Arus kas dari aktivitas pendanaan</td></tr>
                    <tr class="report-row"><td>Penerimaan modal / pinjaman</td><td class="amount">{{ $amt($financingIn) }}</td></tr>
                    <tr class="report-subtotal"><td>Kas neto dari aktivitas pendanaan</td><td class="amount {{ $cls($netFinancing) }}">{{ $amt($netFinancing) }}</td></tr>

                    <tr class="report-section"><td colspan="2">Rekonsiliasi kas</td></tr>
                    <tr class="report-row"><td>Kenaikan (penurunan) kas neto</td><td class="amount {{ $cls($netIncrease) }}">{{ $amt($netIncrease) }}</td></tr>
                    <tr class="report-row"><td>Saldo kas awal periode</td><td class="amount">{{ $amt($openingCash) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="report-total"><td>Saldo kas akhir periode</td><td class="amount {{ $cls($closingCash) }}">{{ $amt($closingCash) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
