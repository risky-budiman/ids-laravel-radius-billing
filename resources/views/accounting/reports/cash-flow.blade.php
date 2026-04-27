<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Laporan Arus Kas (Cash Flow)
            </h2>
            <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 p-2 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <form method="GET" class="flex items-center space-x-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl text-xs">
                    <span class="text-gray-400 text-xs">s/d</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl text-xs">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors">Filter</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden print:border-none">
                <div class="p-10 border-b border-gray-100 dark:border-gray-700 text-center">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-widest">Laporan Arus Kas</h3>
                    <p class="text-sm text-gray-500 mt-1">Metode Langsung (Direct Method)</p>
                    <p class="text-[10px] font-bold text-indigo-600 mt-2 uppercase tracking-tighter">Periode: {{ Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>

                <div class="p-10 space-y-8">
                    <!-- Operating Activities -->
                    <section>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-b border-gray-50 dark:border-gray-700/50 pb-2">Arus Kas dari Aktivitas Operasional</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Penerimaan Kas dari Pelanggan</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($operatingIn, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Pembayaran Kas kepada Pemasok & Karyawan</span>
                                <span class="text-sm font-bold text-red-500">(Rp {{ number_format($operatingOut, 0, ',', '.') }})</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-50 dark:border-gray-700">
                                <span class="text-sm font-black text-gray-900 dark:text-white">Kas Neto dari Aktivitas Operasional</span>
                                <span class="text-sm font-black {{ $netOperating >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netOperating, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Investing Activities -->
                    <section>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-b border-gray-50 dark:border-gray-700/50 pb-2">Arus Kas dari Aktivitas Investasi</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Pembelian Inventaris & Aset Tetap</span>
                                <span class="text-sm font-bold text-red-500">(Rp {{ number_format($investingOut, 0, ',', '.') }})</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-50 dark:border-gray-700">
                                <span class="text-sm font-black text-gray-900 dark:text-white">Kas Neto dari Aktivitas Investasi</span>
                                <span class="text-sm font-black {{ $netInvesting >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netInvesting, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Financing Activities -->
                    <section>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-b border-gray-50 dark:border-gray-700/50 pb-2">Arus Kas dari Aktivitas Pendanaan</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Penerimaan Modal / Pinjaman</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($financingIn, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-50 dark:border-gray-700">
                                <span class="text-sm font-black text-gray-900 dark:text-white">Kas Neto dari Aktivitas Pendanaan</span>
                                <span class="text-sm font-black {{ $netFinancing >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netFinancing, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- Summary Reconciliation -->
                    <section class="bg-gray-50 dark:bg-gray-900/50 p-8 rounded-2xl space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Kenaikan (Penurunan) Kas Neto</span>
                            <span class="text-md font-black {{ $netIncrease >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netIncrease, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Saldo Kas pada Awal Periode</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($openingCash, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t-2 border-dashed border-gray-200 dark:border-gray-700">
                            <span class="text-md font-black text-gray-900 dark:text-white uppercase tracking-widest">Saldo Kas pada Akhir Periode</span>
                            <span class="text-xl font-black text-indigo-600">Rp {{ number_format($closingCash, 0, ',', '.') }}</span>
                        </div>
                    </section>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3 no-print">
                <button onclick="window.print()" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-8 py-3 rounded-2xl text-xs font-bold hover:bg-gray-50 transition-colors shadow-sm">Print PDF</button>
                <a href="{{ route('accounting.reports.cash-flow.export', array_merge(request()->all(), ['all_time' => 1])) }}" class="bg-gray-800 text-white px-8 py-3 rounded-2xl text-xs font-bold hover:bg-gray-900 transition-colors shadow-lg shadow-gray-900/20">Ekspor Seluruh Data</a>
                <a href="{{ route('accounting.reports.cash-flow.export', request()->all()) }}" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/20">Ekspor Filtered (Excel)</a>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print, header, nav {
                display: none !important;
            }
            .py-12 {
                padding-top: 0 !important;
            }
            body {
                background: white !important;
            }
            .max-w-4xl {
                max-width: 100% !important;
            }
        }
    </style>
</x-app-layout>
