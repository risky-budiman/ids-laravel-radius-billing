@php
    $rp = fn ($v) => rupiah($v, 0);
    $rpCell = fn ($v) => $v ? rupiah($v, 0, true, false) : '';
    $isYear = $month === null;
    $multi = $selected === 'all';
    $query = fn (array $extra) => array_merge(request()->query(), ['account' => $selected], $extra);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Rekening Koran" :subtitle="$accountLabel . ' · ' . $periodLabel">
            <x-slot name="filters">
                <form method="GET" class="flex flex-wrap items-end gap-2" x-data="{ period: '{{ $isYear ? 'year' : 'month' }}' }">
                    <div>
                        <label for="account" class="label !mb-1 text-xs">Rekening</label>
                        <select id="account" name="account" class="input !h-9 !w-auto max-w-[16rem]">
                            @if(auth()->user()->isAdministrator() && $available->count() > 1)
                                <option value="all" @selected($multi)>Semua rekening (gabungan)</option>
                            @endif
                            @foreach($available as $a)
                                <option value="{{ $a->id }}" @selected($selected === $a->id)>{{ $a->bank_name }} · {{ $a->account_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="period" class="label !mb-1 text-xs">Periode</label>
                        <select id="period" name="period" x-model="period" class="input !h-9 !w-auto">
                            <option value="month">Bulanan</option>
                            <option value="year">Tahunan</option>
                        </select>
                    </div>
                    <div x-show="period === 'month'">
                        <label for="month" class="label !mb-1 text-xs">Bulan</label>
                        <select id="month" name="month" class="input !h-9 !w-auto" :disabled="period !== 'month'">
                            @foreach($months as $m => $name)
                                <option value="{{ $m }}" @selected(($month ?? now()->month) == $m)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="year" class="label !mb-1 text-xs">Tahun</label>
                        <select id="year" name="year" class="input !h-9 !w-auto">
                            @foreach($years as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('bank-statement.export', $query([])) }}" class="btn-secondary"
                   title="{{ $isYear ? 'Sheet ringkasan + satu sheet mutasi per bulan' : 'Mutasi bulan ini' }}">
                    <x-icon name="arrow-down" class="w-4 h-4" /> Ekspor Excel
                </a>
            </x-slot>
        </x-report-header>
    </x-slot>

    @php $head = $isYear ? $summary : $statement; @endphp

    <div class="space-y-6">
        {{-- Ringkasan saldo --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Saldo awal" :value="$rp($head['opening'])" icon="credit-card" tone="gray"
                         :hint="'Per ' . $head['start']->locale('id')->translatedFormat('d F Y')" />
            <x-stat-card label="Total masuk (CR)" :value="$rp($head['total_in'])" icon="arrow-down" tone="success"
                         :hint="$isYear ? null : $statement['count_in'] . ' transaksi'" />
            <x-stat-card label="Total keluar (DB)" :value="$rp($head['total_out'])" icon="arrow-up" tone="danger"
                         :hint="$isYear ? null : $statement['count_out'] . ' transaksi'" />
            <x-stat-card label="Saldo akhir" :value="$rp($head['closing'])" icon="cash" tone="primary"
                         :hint="'Per ' . $head['end']->locale('id')->translatedFormat('d F Y')" />
        </div>

        @if($isYear)
            {{-- Rekap tahunan per bulan --}}
            <div class="report-doc">
                <div class="print-only mb-4">
                    <p class="text-base font-semibold">Rekening Koran Tahunan {{ $year }}</p>
                    <p class="text-sm">{{ $accountLabel }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="amount">Saldo awal</th>
                                <th class="amount">Masuk (CR)</th>
                                <th class="amount">Keluar (DB)</th>
                                <th class="amount">Saldo akhir</th>
                                <th class="amount">Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['months'] as $m => $r)
                                <tr class="report-row {{ $r['future'] ? 'opacity-50' : '' }}">
                                    <td>
                                        @if($r['future'])
                                            {{ $months[$m] }}
                                        @else
                                            <a href="{{ route('bank-statement.index', $query(['period' => 'month', 'month' => $m, 'year' => $year])) }}"
                                               class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $months[$m] }}</a>
                                        @endif
                                    </td>
                                    <td class="amount">{{ $r['future'] ? '' : rupiah($r['opening'], 0, true, false) }}</td>
                                    <td class="amount text-emerald-700 dark:text-emerald-400">{{ $r['future'] ? '' : $rpCell($r['total_in']) }}</td>
                                    <td class="amount text-rose-700 dark:text-rose-400">{{ $r['future'] ? '' : $rpCell($r['total_out']) }}</td>
                                    <td class="amount font-medium text-surface-900 dark:text-white">{{ $r['future'] ? '' : rupiah($r['closing'], 0, true, false) }}</td>
                                    <td class="amount text-surface-500">{{ $r['future'] ? '' : number_format($r['count'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="report-total">
                                <td>Total {{ $year }}</td>
                                <td class="amount">{{ rupiah($summary['opening'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($summary['total_in'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($summary['total_out'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($summary['closing'], 0, true, false) }}</td>
                                <td class="amount">{{ number_format($summary['count'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="mt-4 text-xs text-surface-500 dark:text-surface-400 no-print">Klik nama bulan untuk melihat rincian mutasinya. Ekspor Excel tahunan berisi ringkasan ini dan satu sheet mutasi untuk setiap bulan.</p>
            </div>
        @else
            {{-- Rekening koran bulanan --}}
            <div class="report-doc">
                <div class="print-only mb-4">
                    <p class="text-base font-semibold">Rekening Koran — {{ $periodLabel }}</p>
                    <p class="text-sm">{{ $accountLabel }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th class="w-28">Tanggal</th>
                                @if($multi)<th>Rekening</th>@endif
                                <th>Keterangan</th>
                                <th class="amount">Masuk (CR)</th>
                                <th class="amount">Keluar (DB)</th>
                                <th class="amount">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="report-row bg-surface-50 dark:bg-surface-800/40">
                                <td class="tabular-nums">{{ $statement['start']->format('d/m/Y') }}</td>
                                @if($multi)<td></td>@endif
                                <td class="font-semibold">SALDO AWAL</td>
                                <td></td><td></td>
                                <td class="amount font-semibold">{{ rupiah($statement['opening'], 0, true, false) }}</td>
                            </tr>
                            @forelse($statement['rows'] as $r)
                                <tr class="report-row">
                                    <td class="tabular-nums whitespace-nowrap">
                                        {{ $r['date']->format('d/m/Y') }}
                                        @if($r['date']->format('H:i') !== '00:00')
                                            <span class="block text-[11px] text-surface-400">{{ $r['date']->format('H:i') }}</span>
                                        @endif
                                    </td>
                                    @if($multi)<td class="text-xs whitespace-nowrap">{{ $r['account'] }}</td>@endif
                                    <td class="min-w-[16rem]">
                                        <div class="text-surface-800 dark:text-surface-200">{{ $r['description'] ?: '—' }}</div>
                                        @if($r['reference'] || $r['category'])
                                            <div class="text-[11px] text-surface-400 mt-0.5">
                                                @if($r['reference'])<span class="font-mono">{{ $r['reference'] }}</span>@endif
                                                @if($r['reference'] && $r['category']) · @endif
                                                {{ $r['category'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="amount text-emerald-700 dark:text-emerald-400">{{ $rpCell($r['in']) }}</td>
                                    <td class="amount text-rose-700 dark:text-rose-400">{{ $rpCell($r['out']) }}</td>
                                    <td class="amount font-medium text-surface-900 dark:text-white {{ $r['balance'] < 0 ? 'amount-neg' : '' }}">{{ rupiah($r['balance'], 0, true, false) }}</td>
                                </tr>
                            @empty
                                <tr class="report-row">
                                    <td colspan="{{ $multi ? 6 : 5 }}" class="py-6 text-center text-surface-500">Tidak ada mutasi pada {{ $periodLabel }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="report-subtotal">
                                <td colspan="{{ $multi ? 3 : 2 }}">Total mutasi ({{ $statement['count_in'] }} masuk · {{ $statement['count_out'] }} keluar)</td>
                                <td class="amount">{{ rupiah($statement['total_in'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($statement['total_out'], 0, true, false) }}</td>
                                <td></td>
                            </tr>
                            <tr class="report-total">
                                <td colspan="{{ $multi ? 5 : 4 }}">Saldo akhir per {{ $statement['end']->locale('id')->translatedFormat('d F Y') }}</td>
                                <td class="amount {{ $statement['closing'] < 0 ? 'amount-neg' : '' }}">{{ rupiah($statement['closing'], 0, true, false) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($multi)
                    <p class="mt-4 text-xs text-surface-500 dark:text-surface-400">Tampilan gabungan: transfer antar-rekening tercatat sebagai keluar di rekening asal dan masuk di rekening tujuan, sehingga tidak mengubah saldo gabungan.</p>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
