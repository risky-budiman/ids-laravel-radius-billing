<x-app-layout>
    <!-- Clean Minimalist Header -->
    <div class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 no-print">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">
                        Laporan Buku Besar
                    </h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">General Ledger Detail</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-3">
                        <select name="account_id" class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-indigo-500 min-w-[200px]">
                            <option value="">-- Semua Akun CoA --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                                    [{{ $acc->code }}] {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="flex items-center bg-slate-50 dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 {{ $allTime ? 'opacity-40' : '' }}">
                            <input type="date" name="start_date" value="{{ $startDate }}" class="border-none p-0 bg-transparent text-[11px] font-bold text-slate-600 dark:text-slate-300 focus:ring-0 {{ $allTime ? 'pointer-events-none' : '' }}">
                            <span class="mx-2 text-slate-300">/</span>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="border-none p-0 bg-transparent text-[11px] font-bold text-slate-600 dark:text-slate-300 focus:ring-0 {{ $allTime ? 'pointer-events-none' : '' }}">
                        </div>

                        <label class="flex items-center cursor-pointer bg-slate-50 dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                            <input type="checkbox" name="all_time" value="1" {{ $allTime ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" onchange="this.form.submit()">
                            <span class="ml-2 text-[10px] font-bold text-slate-500 uppercase">Seluruh Periode</span>
                        </label>
                        
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-sm">Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="py-10 bg-slate-50/30 dark:bg-slate-950 min-h-screen">
        <div class="max-w-7xl mx-auto px-6">
            
            @if($itemsByAccount->isEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-20 text-center shadow-sm">
                    <p class="text-slate-400 font-medium italic">Tidak ada mutasi ditemukan untuk kriteria pencarian ini.</p>
                </div>
            @else
                <!-- Summary List (Only for All Accounts View) -->
                @if(!$accountId)
                <div class="mb-10 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm no-print">
                    <div class="px-8 py-5 border-b border-slate-50 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Ringkasan Mutasi Periode</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    <th class="px-8 py-4">Akun</th>
                                    <th class="px-8 py-4 text-right">Awal</th>
                                    <th class="px-8 py-4 text-right">Debit</th>
                                    <th class="px-8 py-4 text-right">Kredit</th>
                                    <th class="px-8 py-4 text-right">Akhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                @foreach($itemsByAccount as $data)
                                <tr class="text-xs">
                                    <td class="px-8 py-4 font-bold text-slate-700 dark:text-slate-300">[{{ $data['account']->code }}] {{ $data['account']->name }}</td>
                                    <td class="px-8 py-4 text-right">{{ number_format($data['opening_balance'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right text-emerald-600">{{ number_format($data['debit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right text-rose-600">{{ number_format($data['credit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right font-bold text-slate-800 dark:text-white">{{ number_format($data['ending_balance'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50/50 dark:bg-slate-800/50 font-bold text-xs">
                                <tr>
                                    <td class="px-8 py-4">TOTAL KONSOLIDASI</td>
                                    <td class="px-8 py-4 text-right">{{ number_format($summary['total_opening'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right text-emerald-600">{{ number_format($summary['total_debit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right text-rose-600">{{ number_format($summary['total_credit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-4 text-right text-indigo-600">{{ number_format($summary['total_ending'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Detailed Tables -->
                @foreach($itemsByAccount as $data)
                <div class="mb-10 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm page-break">
                    <div class="px-8 py-6 bg-slate-50/30 dark:bg-slate-800/30 flex justify-between items-center border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">[{{ $data['account']->code }}] {{ $data['account']->name }}</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ $data['account']->type }} Account</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Saldo Awal</p>
                            <p class="text-md font-bold text-slate-700 dark:text-white">Rp {{ number_format($data['opening_balance'], 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-white dark:bg-slate-900 text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50 dark:border-slate-800">
                                    <th class="px-8 py-4">Tanggal</th>
                                    <th class="px-8 py-4">Referensi & Keterangan</th>
                                    <th class="px-8 py-4 text-right">Debit</th>
                                    <th class="px-8 py-4 text-right">Kredit</th>
                                    <th class="px-8 py-4 text-right">Saldo Berjalan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                @php $curBal = $data['opening_balance']; @endphp
                                @if($data['items']->isEmpty())
                                    <tr>
                                        <td colspan="5" class="px-8 py-10 text-center text-xs text-slate-400 italic">Tidak ada mutasi transaksi pada periode ini.</td>
                                    </tr>
                                @else
                                    @foreach($data['items'] as $item)
                                    @php
                                        if (in_array($data['account']->type, ['asset', 'expense'])) {
                                            $curBal += ($item->debit - $item->credit);
                                        } else {
                                            $curBal += ($item->credit - $item->debit);
                                        }
                                    @endphp
                                    <tr class="text-xs hover:bg-slate-50/30 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-8 py-5 text-slate-500 font-medium">{{ \Carbon\Carbon::parse($item->journal->date)->format('d/m/Y') }}</td>
                                        <td class="px-8 py-5">
                                            <p class="font-bold text-slate-700 dark:text-slate-300">{{ $item->journal->reference }}</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $item->journal->description }}</p>
                                        </td>
                                        <td class="px-8 py-5 text-right font-medium {{ $item->debit > 0 ? 'text-slate-800 dark:text-white' : 'text-slate-300' }}">
                                            {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-8 py-5 text-right font-medium {{ $item->credit > 0 ? 'text-slate-800 dark:text-white' : 'text-slate-300' }}">
                                            {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-8 py-5 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($curBal, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot class="bg-slate-50/20 dark:bg-slate-800/20 font-bold text-xs border-t border-slate-100 dark:border-slate-800">
                                <tr>
                                    <td colspan="2" class="px-8 py-5 text-slate-400 uppercase tracking-widest text-[10px]">Total Mutasi & Saldo Akhir</td>
                                    <td class="px-8 py-5 text-right text-emerald-600">{{ number_format($data['debit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-5 text-right text-rose-600">{{ number_format($data['credit'], 0, ',', '.') }}</td>
                                    <td class="px-8 py-5 text-right text-slate-800 dark:text-white">Rp {{ number_format($data['ending_balance'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endforeach
            @endif

            <!-- Minimalist Actions -->
            <div class="mt-10 flex flex-col md:flex-row justify-between items-center gap-6 no-print bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-8 rounded-3xl">
                <div>
                    <h4 class="font-bold text-slate-800 dark:text-white">Opsi Laporan</h4>
                    <p class="text-xs text-slate-400 mt-1">Unduh atau cetak laporan buku besar ini.</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.print()" class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-8 py-3 rounded-xl text-xs font-bold hover:bg-slate-200 transition-all">Print PDF</button>
                    <a href="{{ route('accounting.reports.ledger.export', request()->all()) }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-sm">Ekspor Excel</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print, header, nav { display: none !important; }
            .py-10 { padding: 0 !important; }
            .max-w-7xl { max-width: 100% !important; padding: 0 !important; }
            .page-break { page-break-after: always; }
            body { background: white !important; font-size: 10px; }
            .rounded-3xl { border-radius: 0 !important; }
            .shadow-sm { box-shadow: none !important; }
            .border { border-color: #eee !important; }
            .bg-white { background: white !important; }
        }
    </style>
</x-app-layout>
