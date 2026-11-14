@php
    $n = fn ($v) => rupiah($v, 2, true, false);
    $totalDebit = $journal->items->sum('debit');
    $totalCredit = $journal->items->sum('credit');
    $balanced = abs($totalDebit - $totalCredit) < 0.01;
    $date = \Carbon\Carbon::parse($journal->date)->locale('id')->translatedFormat('d F Y');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Bukti Jurnal" :subtitle="$journal->reference . ' · ' . $date">
            <x-slot name="actions">
                <a href="{{ route('accounting.journals.index') }}" class="btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="report-doc">
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4 pb-5 mb-2 border-b border-surface-200 dark:border-surface-800 text-sm">
                <div>
                    <dt class="text-xs text-surface-500 dark:text-surface-400">Tanggal</dt>
                    <dd class="mt-0.5 font-medium text-surface-900 dark:text-white">{{ $date }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-surface-500 dark:text-surface-400">No. referensi</dt>
                    <dd class="mt-0.5 font-medium font-mono text-surface-900 dark:text-white">{{ $journal->reference }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs text-surface-500 dark:text-surface-400">Keterangan</dt>
                    <dd class="mt-0.5 text-surface-800 dark:text-surface-200">{{ $journal->description ?: '—' }}</dd>
                </div>
            </dl>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th class="amount w-40">Debit</th>
                        <th class="amount w-40">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->items as $item)
                        <tr class="report-row">
                            {{-- Konvensi jurnal: akun kredit ditulis menjorok --}}
                            <td class="{{ $item->credit > 0 ? '!pl-10' : '' }}">
                                <span class="report-code">{{ $item->account->code ?? '—' }}</span>{{ $item->account->name ?? 'Akun terhapus' }}
                                @if(!empty($item->description))
                                    <span class="block text-xs text-surface-500">{{ $item->description }}</span>
                                @endif
                            </td>
                            <td class="amount">{{ $item->debit > 0 ? $n($item->debit) : '' }}</td>
                            <td class="amount">{{ $item->credit > 0 ? $n($item->credit) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="report-total">
                        <td>Total</td>
                        <td class="amount">{{ $n($totalDebit) }}</td>
                        <td class="amount">{{ $n($totalCredit) }}</td>
                    </tr>
                </tfoot>
            </table>

            @unless($balanced)
                <div class="mt-4 flex items-start gap-2.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-300">
                    <x-icon name="exclamation" class="w-5 h-5 shrink-0" />
                    <p>Jurnal tidak seimbang: selisih debit dan kredit {{ rupiah(abs($totalDebit - $totalCredit), 2) }}.</p>
                </div>
            @endunless

            <p class="mt-4 text-xs text-surface-500 dark:text-surface-400">
                Dicatat oleh {{ $journal->creator->name ?? 'Sistem' }} · terakhir diperbarui {{ $journal->updated_at->locale('id')->translatedFormat('d M Y, H:i') }}
            </p>

            {{-- Kolom tanda tangan: hanya saat dicetak --}}
            <div class="print-only mt-12">
                <table style="width:100%; border-collapse:collapse; font-size:12px; text-align:center;">
                    <tr>
                        <td style="width:33%;">Dibuat oleh,</td>
                        <td style="width:33%;">Diperiksa oleh,</td>
                        <td style="width:33%;">Disetujui oleh,</td>
                    </tr>
                    <tr><td style="height:64px;"></td><td></td><td></td></tr>
                    <tr>
                        <td>( {{ $journal->creator->name ?? '....................' }} )</td>
                        <td>( .................... )</td>
                        <td>( .................... )</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
