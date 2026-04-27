<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Rangkuman Laporan Pajak (PPN)
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Tax Output Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <svg class="w-16 h-16 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pajak Keluaran (Output)</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-emerald-600 mt-2 font-bold uppercase tracking-tighter">Dari Penjualan/Invoice</p>
                </div>

                <!-- Tax Input Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <svg class="w-16 h-16 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Pajak Masukan (Input)</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-amber-600 mt-2 font-bold uppercase tracking-tighter">Dari Pembelian Inventaris</p>
                </div>

                <!-- Net Payable Card -->
                <div class="bg-indigo-600 p-6 rounded-3xl shadow-xl border border-indigo-500 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-xs font-black text-indigo-200 uppercase tracking-widest mb-1">PPN Kurang Bayar (Setor)</p>
                    <h3 class="text-2xl font-bold text-white">Rp {{ number_format($netTaxPayable, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-indigo-200 mt-2 font-bold uppercase tracking-tighter">Estimasi Pajak Terhutang</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-8 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="font-bold text-gray-900 dark:text-white">Detail Transaksi Pajak</h3>
                    <p class="text-xs text-gray-500">Berikut adalah rincian mutasi pada akun pajak selama periode terpilih.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-8 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Jenis Pajak</th>
                                <th class="px-8 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Akun CoA</th>
                                <th class="px-8 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Debit (Masuk)</th>
                                <th class="px-8 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Kredit (Keluar)</th>
                                <th class="px-8 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Saldo Periode</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <!-- Output Row -->
                            <tr>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase tracking-tighter">Pajak Keluaran</span>
                                </td>
                                <td class="px-8 py-6 text-sm text-gray-600 dark:text-gray-400 font-medium">(2103) Hutang PPN</td>
                                <td class="px-8 py-6 text-sm text-gray-400 text-right">-</td>
                                <td class="px-8 py-6 text-sm text-gray-900 dark:text-white font-bold text-right">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-sm text-emerald-600 font-black text-right">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</td>
                            </tr>
                            <!-- Input Row -->
                            <tr>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-black uppercase tracking-tighter">Pajak Masukan</span>
                                </td>
                                <td class="px-8 py-6 text-sm text-gray-600 dark:text-gray-400 font-medium">(1105) PPN Masukan</td>
                                <td class="px-8 py-6 text-sm text-gray-900 dark:text-white font-bold text-right">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-sm text-gray-400 text-right">-</td>
                                <td class="px-8 py-6 text-sm text-amber-600 font-black text-right">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <td colspan="4" class="px-8 py-4 text-xs font-black text-gray-500 uppercase tracking-widest text-right">Total Selisih (PPN Terhutang)</td>
                                <td class="px-8 py-4 text-sm font-black text-indigo-600 text-right">Rp {{ number_format($netTaxPayable, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="mt-8 p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl border border-indigo-100 dark:border-indigo-800/50">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-indigo-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-300">Catatan Perpajakan</h4>
                        <p class="text-sm text-indigo-700 dark:text-indigo-400/80 leading-relaxed">
                            Laporan ini dihitung berdasarkan mutasi pada akun (2103) Hutang PPN dan (1105) PPN Masukan. 
                            Nilai <b>PPN Kurang Bayar</b> adalah jumlah yang harus disetorkan ke DJP setelah dikompensasikan dengan Pajak Masukan dari pembelian barang/aset.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
