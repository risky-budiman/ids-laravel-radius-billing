@php
    $asOf = \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    $totalPasiva = $totalLiabilities + $totalEquity;
    $diff = $totalAssets - $totalPasiva;
    $balanced = abs($diff) < 0.01;
    $amt = fn ($v) => rupiah($v, 2);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Laporan Neraca" :subtitle="'Posisi keuangan per ' . $asOf" :closed="$isClosed">
            <x-slot name="filters">
                <form action="{{ route('accounting.reports.balance-sheet') }}" method="GET" class="flex items-end gap-2">
                    <div>
                        <label for="date" class="label !mb-1 text-xs">Per tanggal</label>
                        <input id="date" type="date" name="date" value="{{ $date }}" class="input !h-9">
                    </div>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="report-doc">
            <div class="grid grid-cols-1 lg:grid-cols-2 print:grid-cols-1 gap-x-10">
                {{-- AKTIVA --}}
                <table class="report-table">
                    <thead><tr><th>Aktiva</th><th class="amount">Saldo (Rp)</th></tr></thead>
                    <tbody>
                        <tr class="report-section"><td colspan="2">Aset</td></tr>
                        @forelse($assets as $account)
                            <tr class="report-row">
                                <td><span class="report-code">{{ $account->code }}</span>{{ $account->name }}</td>
                                <td class="amount {{ $account->current_balance < 0 ? 'amount-neg' : '' }}">{{ $amt($account->current_balance) }}</td>
                            </tr>
                        @empty
                            <tr class="report-row"><td colspan="2" class="text-surface-400 italic">Tidak ada saldo aset</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="report-total"><td>Total Aktiva</td><td class="amount">{{ $amt($totalAssets) }}</td></tr>
                    </tfoot>
                </table>

                {{-- PASIVA --}}
                <table class="report-table mt-8 lg:mt-0 print:mt-8">
                    <thead><tr><th>Pasiva</th><th class="amount">Saldo (Rp)</th></tr></thead>
                    <tbody>
                        <tr class="report-section"><td colspan="2">Kewajiban</td></tr>
                        @forelse($liabilities as $account)
                            <tr class="report-row">
                                <td><span class="report-code">{{ $account->code }}</span>{{ $account->name }}</td>
                                <td class="amount {{ $account->current_balance < 0 ? 'amount-neg' : '' }}">{{ $amt($account->current_balance) }}</td>
                            </tr>
                        @empty
                            <tr class="report-row"><td colspan="2" class="text-surface-400 italic">Tidak ada saldo kewajiban</td></tr>
                        @endforelse
                        <tr class="report-subtotal"><td>Total Kewajiban</td><td class="amount">{{ $amt($totalLiabilities) }}</td></tr>

                        <tr class="report-section"><td colspan="2">Ekuitas</td></tr>
                        @foreach($equity as $account)
                            <tr class="report-row">
                                <td><span class="report-code">{{ $account->code }}</span>{{ $account->name }}</td>
                                <td class="amount {{ $account->current_balance < 0 ? 'amount-neg' : '' }}">{{ $amt($account->current_balance) }}</td>
                            </tr>
                        @endforeach
                        <tr class="report-row">
                            <td><span class="report-code">—</span>Laba (rugi) tahun berjalan</td>
                            <td class="amount {{ $currentEarnings < 0 ? 'amount-neg' : '' }}">{{ $amt($currentEarnings) }}</td>
                        </tr>
                        <tr class="report-subtotal"><td>Total Ekuitas</td><td class="amount">{{ $amt($totalEquity) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="report-total"><td>Total Pasiva</td><td class="amount">{{ $amt($totalPasiva) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            {{-- Pemeriksaan keseimbangan --}}
            <div class="mt-8 flex items-start gap-2.5 rounded-lg px-4 py-3 text-sm {{ $balanced ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300' }}">
                <x-icon :name="$balanced ? 'check-circle' : 'exclamation'" class="w-5 h-5 shrink-0" />
                @if($balanced)
                    <p>Neraca seimbang: total aktiva sama dengan total pasiva.</p>
                @else
                    <p><span class="font-semibold">Neraca tidak seimbang.</span> Selisih {{ rupiah(abs($diff), 2) }} — periksa jurnal yang belum lengkap atau akun yang belum dipetakan.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
