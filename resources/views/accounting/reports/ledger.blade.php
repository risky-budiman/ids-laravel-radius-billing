@php
    $fmtDate = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d F Y');
    $n = fn ($v) => rupiah($v, 0, true, false);
    $selected = $accountId ? $accounts->firstWhere('id', $accountId) : null;
    $subtitle = ($allTime ? 'Seluruh periode' : $fmtDate($startDate) . ' – ' . $fmtDate($endDate))
        . ($selected ? ' · Akun ' . $selected->code . ' ' . $selected->name : ' · Semua akun');
    $typeLabel = ['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Ekuitas', 'income' => 'Pendapatan', 'expense' => 'Beban'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Buku Besar" :subtitle="$subtitle">
            <x-slot name="filters">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="account_id" class="label !mb-1 text-xs">Akun</label>
                        <select id="account_id" name="account_id" class="input !h-9 min-w-[14rem]">
                            <option value="">Semua akun</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected($accountId == $acc->id)>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="{{ $allTime ? 'opacity-50' : '' }}">
                        <label for="start_date" class="label !mb-1 text-xs">Dari</label>
                        <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="input !h-9" @readonly($allTime)>
                    </div>
                    <div class="{{ $allTime ? 'opacity-50' : '' }}">
                        <label for="end_date" class="label !mb-1 text-xs">Sampai</label>
                        <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="input !h-9" @readonly($allTime)>
                    </div>
                    <label class="flex items-center gap-2 h-9 px-1 cursor-pointer select-none">
                        <input type="checkbox" name="all_time" value="1" @checked($allTime) onchange="this.form.submit()" class="w-4 h-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-surface-600 dark:text-surface-300">Seluruh periode</span>
                    </label>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('accounting.reports.ledger.export', request()->all()) }}" class="btn-secondary">Ekspor Excel</a>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
        @if($itemsByAccount->isEmpty())
            <div class="card px-6 py-14 text-center">
                <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Tidak ada mutasi</p>
                <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Ubah akun atau rentang tanggal untuk melihat transaksi.</p>
            </div>
        @else
            {{-- Ringkasan semua akun --}}
            @if(!$accountId)
                <div class="report-doc">
                    <h3 class="text-sm font-semibold text-surface-900 dark:text-white mb-3">Ringkasan mutasi per akun</h3>
                    <div class="overflow-x-auto">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="amount">Saldo awal</th>
                                    <th class="amount">Debit</th>
                                    <th class="amount">Kredit</th>
                                    <th class="amount">Saldo akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($itemsByAccount as $data)
                                    <tr class="report-row">
                                        <td><span class="report-code">{{ $data['account']->code }}</span>{{ $data['account']->name }}</td>
                                        <td class="amount">{{ $n($data['opening_balance']) }}</td>
                                        <td class="amount">{{ $n($data['debit']) }}</td>
                                        <td class="amount">{{ $n($data['credit']) }}</td>
                                        <td class="amount font-medium text-surface-900 dark:text-white">{{ $n($data['ending_balance']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="report-total">
                                    <td>Total</td>
                                    <td class="amount">{{ $n($summary['total_opening']) }}</td>
                                    <td class="amount">{{ $n($summary['total_debit']) }}</td>
                                    <td class="amount">{{ $n($summary['total_credit']) }}</td>
                                    <td class="amount">{{ $n($summary['total_ending']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Rincian per akun --}}
            @foreach($itemsByAccount as $data)
                @php $curBal = $data['opening_balance']; $isDebitNormal = in_array($data['account']->type, ['asset', 'expense']); @endphp
                <div class="report-doc" style="break-inside: avoid-page;">
                    <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-surface-900 dark:text-white"><span class="report-code !text-sm">{{ $data['account']->code }}</span>{{ $data['account']->name }}</h3>
                            <p class="text-xs text-surface-500 dark:text-surface-400">{{ $typeLabel[$data['account']->type] ?? ucfirst($data['account']->type) }} · saldo normal {{ $isDebitNormal ? 'debit' : 'kredit' }}</p>
                        </div>
                        <p class="text-sm text-surface-500 dark:text-surface-400">Saldo awal <span class="ml-1 font-medium tabular-nums text-surface-900 dark:text-white">{{ rupiah($data['opening_balance']) }}</span></p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th class="w-24">Tanggal</th>
                                    <th>Referensi &amp; keterangan</th>
                                    <th class="amount">Debit</th>
                                    <th class="amount">Kredit</th>
                                    <th class="amount">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['items'] as $item)
                                    @php $curBal += $isDebitNormal ? ($item->debit - $item->credit) : ($item->credit - $item->debit); @endphp
                                    <tr class="report-row">
                                        <td class="!pl-0 whitespace-nowrap text-surface-500">{{ \Carbon\Carbon::parse($item->journal->date)->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="font-medium text-surface-800 dark:text-surface-200">{{ $item->journal->reference }}</span>
                                            <span class="block text-xs text-surface-500 dark:text-surface-400">{{ $item->journal->description }}</span>
                                        </td>
                                        <td class="amount">{{ $item->debit > 0 ? $n($item->debit) : '–' }}</td>
                                        <td class="amount">{{ $item->credit > 0 ? $n($item->credit) : '–' }}</td>
                                        <td class="amount font-medium text-surface-900 dark:text-white {{ $curBal < 0 ? 'amount-neg' : '' }}">{{ $n($curBal) }}</td>
                                    </tr>
                                @empty
                                    <tr class="report-row"><td colspan="5" class="!pl-0 text-surface-400 italic">Tidak ada mutasi pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="report-subtotal">
                                    <td colspan="2">Total mutasi &amp; saldo akhir</td>
                                    <td class="amount">{{ $n($data['debit']) }}</td>
                                    <td class="amount">{{ $n($data['credit']) }}</td>
                                    <td class="amount">{{ $n($data['ending_balance']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-app-layout>
