@php
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y');
    $amt = fn ($v) => rupiah($v, 2);
    $margin = $totalIncome != 0 ? ($netProfit / $totalIncome) * 100 : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Laporan Laba Rugi" :subtitle="'Periode ' . $fmtDate($startDate) . ' – ' . $fmtDate($endDate)" :closed="$isClosed">
            <x-slot name="filters">
                <form action="{{ route('accounting.reports.profit-loss') }}" method="GET" class="flex flex-wrap items-end gap-2">
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
        </x-report-header>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        {{-- Ringkasan (layar saja) --}}
        <div class="no-print grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Total Pendapatan" :value="rupiah($totalIncome)" icon="arrow-down" tone="success" />
            <x-stat-card label="Total Beban" :value="rupiah($totalExpense)" icon="arrow-up" tone="danger" />
            <x-stat-card label="Laba Bersih" :value="rupiah($netProfit)" icon="trending-up" :tone="$netProfit >= 0 ? 'primary' : 'danger'"
                         :hint="$margin !== null ? 'Margin ' . number_format($margin, 1, ',', '.') . '%' : null" />
        </div>

        <div class="report-doc">
            <table class="report-table">
                <thead><tr><th>Keterangan</th><th class="amount">Jumlah (Rp)</th></tr></thead>
                <tbody>
                    <tr class="report-section"><td colspan="2">Pendapatan</td></tr>
                    @forelse($incomeAccounts as $account)
                        <tr class="report-row">
                            <td><span class="report-code">{{ $account->code }}</span>{{ $account->name }}</td>
                            <td class="amount">{{ $amt($account->period_balance) }}</td>
                        </tr>
                    @empty
                        <tr class="report-row"><td colspan="2" class="text-surface-400 italic">Tidak ada pendapatan pada periode ini</td></tr>
                    @endforelse
                    <tr class="report-subtotal"><td>Total Pendapatan</td><td class="amount">{{ $amt($totalIncome) }}</td></tr>

                    <tr class="report-section"><td colspan="2">Beban</td></tr>
                    @forelse($expenseAccounts as $account)
                        <tr class="report-row">
                            <td><span class="report-code">{{ $account->code }}</span>{{ $account->name }}</td>
                            <td class="amount">{{ $amt($account->period_balance) }}</td>
                        </tr>
                    @empty
                        <tr class="report-row"><td colspan="2" class="text-surface-400 italic">Tidak ada beban pada periode ini</td></tr>
                    @endforelse
                    <tr class="report-subtotal"><td>Total Beban</td><td class="amount">({{ rupiah($totalExpense, 2, false) }})</td></tr>
                </tbody>
                <tfoot>
                    <tr class="report-total">
                        <td>{{ $netProfit >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</td>
                        <td class="amount {{ $netProfit < 0 ? 'amount-neg' : '' }}">{{ $amt($netProfit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
