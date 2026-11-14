@php
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y');
    $n = fn ($v) => rupiah($v, 0, true, false);
    $isRefund = $netTaxPayable < 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Rangkuman Pajak PPN" :subtitle="'Masa ' . $fmtDate($startDate) . ' – ' . $fmtDate($endDate)">
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
        </x-report-header>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-4">
        <div class="no-print grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Pajak Keluaran" :value="rupiah($totalTaxOutput)" icon="arrow-up" tone="info" hint="Dari invoice penjualan" />
            <x-stat-card label="Pajak Masukan" :value="rupiah($totalTaxInput)" icon="arrow-down" tone="warning" hint="Dari pembelian inventaris" />
            <x-stat-card :label="$isRefund ? 'PPN Lebih Bayar' : 'PPN Kurang Bayar'" :value="rupiah(abs($netTaxPayable))" icon="receipt" :tone="$isRefund ? 'success' : 'primary'"
                         :hint="$isRefund ? 'Dapat dikompensasikan ke masa berikutnya' : 'Perlu disetor ke kas negara'" />
        </div>

        <div class="report-doc">
            <div class="overflow-x-auto">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Jenis pajak</th>
                            <th>Akun</th>
                            <th class="amount">Debit</th>
                            <th class="amount">Kredit</th>
                            <th class="amount">Saldo periode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="report-row">
                            <td>Pajak Keluaran (PPN atas penjualan)</td>
                            <td><span class="report-code">2103</span>Hutang PPN</td>
                            <td class="amount text-surface-400">–</td>
                            <td class="amount">{{ $n($totalTaxOutput) }}</td>
                            <td class="amount">{{ $n($totalTaxOutput) }}</td>
                        </tr>
                        <tr class="report-row">
                            <td>Pajak Masukan (PPN atas pembelian)</td>
                            <td><span class="report-code">1106</span>PPN Masukan</td>
                            <td class="amount">{{ $n($totalTaxInput) }}</td>
                            <td class="amount text-surface-400">–</td>
                            <td class="amount">({{ rupiah($totalTaxInput, 0, false, false) }})</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="report-total">
                            <td colspan="4">{{ $isRefund ? 'PPN lebih bayar' : 'PPN kurang bayar (harus disetor)' }}</td>
                            <td class="amount">{{ $n(abs($netTaxPayable)) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="mt-6 text-xs text-surface-500 dark:text-surface-400 leading-relaxed">
                Dihitung dari mutasi akun 2103 Hutang PPN dan 1106 PPN Masukan pada periode di atas.
                PPN kurang bayar adalah pajak keluaran dikurangi pajak masukan, yang wajib disetor ke kas negara.
            </p>
        </div>
    </div>
</x-app-layout>
