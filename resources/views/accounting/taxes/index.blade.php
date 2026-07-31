<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight flex items-center gap-2">
                    <span>{{ __('Pusat Kelola & Penyetoran Pajak PPN') }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400 uppercase tracking-widest">Terpadu</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Satu halaman lengkap untuk Laporan Tax Summary, Pengaturan Aturan PPN, Master Pajak & Penyetoran ke Kas Negara.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="window.dispatchEvent(new CustomEvent('open-tax-input-modal'))" type="button" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-5 py-2.5 rounded-2xl text-xs font-black shadow-lg shadow-emerald-600/20 transition-all flex items-center space-x-1.5 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>+ Input Pajak Masukan</span>
                </button>
                <a href="{{ route('accounting.taxes.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-2xl text-xs font-black shadow-lg shadow-indigo-600/20 transition-all active:scale-95">
                    + Skema Pajak Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: 'summary' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alert Feedback -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold flex items-center space-x-2 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Banner Utama Saldo Akuntansi PPN -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white p-8 rounded-[2.5rem] border border-amber-500/30 shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="absolute -top-10 -right-10 w-44 h-44 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span class="text-[10px] font-black text-amber-400 uppercase tracking-[0.25em]">Status Saldo Hutang PPN (Akun 2103)</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight text-white font-mono">Rp {{ number_format($totalTaxLiability, 0, ',', '.') }}</h3>
                    <p class="text-xs text-slate-400 font-semibold mt-1">Total sisa akumulasi saldo PPN terkumpul yang siap & wajib disetorkan ke Kas Negara.</p>
                </div>
                <div class="shrink-0">
                    <button onclick="window.dispatchEvent(new CustomEvent('open-pay-modal'))" type="button" class="font-bold px-6 py-3 rounded-2xl text-xs uppercase tracking-wider transition-all active:scale-95 flex items-center space-x-2" style="background: linear-gradient(135deg, #f59e0b 0%, #eab308 100%) !important; color: #ffffff !important; box-shadow: 0 8px 20px -4px rgba(245, 158, 11, 0.4) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Setor PPN ke Negara &rarr;</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation Header -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
                <button @click="activeTab = 'summary'" :class="activeTab === 'summary' ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold hover:bg-slate-200'" class="px-5 py-2.5 rounded-2xl text-xs uppercase tracking-wider transition-all shrink-0 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>1. Tax Summary Report</span>
                </button>

                <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold hover:bg-slate-200'" class="px-5 py-2.5 rounded-2xl text-xs uppercase tracking-wider transition-all shrink-0 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>2. Riwayat Setoran Kas Negara</span>
                </button>

                <button @click="activeTab = 'rates'" :class="activeTab === 'rates' ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold hover:bg-slate-200'" class="px-5 py-2.5 rounded-2xl text-xs uppercase tracking-wider transition-all shrink-0 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span>3. Master Skema Pajak</span>
                </button>

                <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold hover:bg-slate-200'" class="px-5 py-2.5 rounded-2xl text-xs uppercase tracking-wider transition-all shrink-0 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                    <span>4. Pengaturan Mode Penerapan PPN</span>
                </button>
            </div>

            <!-- TAB 1: TAX SUMMARY REPORT -->
            <div x-show="activeTab === 'summary'" class="space-y-6">
                <!-- Filter Periode Tax Summary -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h4 class="font-black text-slate-900 dark:text-white text-base">Laporan Tax Summary (PPN Masukan & Keluaran)</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Filter rincian mutasi akun PPN berdasarkan periode tanggal transaksi.</p>
                    </div>
                    <form method="GET" class="flex items-center space-x-2">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl text-xs py-2 px-3 font-semibold">
                        <span class="text-slate-400 text-xs font-bold">s/d</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl text-xs py-2 px-3 font-semibold">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-xs font-black shadow-md transition-all">Filter Periode</button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Tax Output Card -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 relative overflow-hidden">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Pajak Keluaran (PPN Diterima)</p>
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</h3>
                        <p class="text-[10px] text-slate-400 mt-2 font-semibold">Akun 2103 - Dari Invoice Penjualan Lunas</p>
                    </div>

                    <!-- Tax Input Card -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] border border-slate-200 dark:border-slate-800 relative overflow-hidden">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Pajak Masukan (PPN Dibayar)</p>
                        <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</h3>
                        <p class="text-[10px] text-slate-400 mt-2 font-semibold">Akun 1105 - Pembelian Ke Supplier/Vendor</p>
                    </div>

                    <!-- Net Tax Payable Card (Dynamic) -->
                    @if($netTaxPayable >= 0)
                        <div class="bg-indigo-600 text-white p-6 rounded-[2rem] shadow-xl relative overflow-hidden">
                            <p class="text-xs font-black text-indigo-200 uppercase tracking-widest mb-1">PPN Kurang Bayar (Periode Ini)</p>
                            <h3 class="text-2xl font-black text-white font-mono">Rp {{ number_format($netTaxPayable, 0, ',', '.') }}</h3>
                            <p class="text-[10px] text-indigo-200 mt-2 font-bold uppercase tracking-tighter">Wajib Disetor ke Negara (Keluaran > Masukan)</p>
                        </div>
                    @else
                        <div class="bg-emerald-600 text-white p-6 rounded-[2rem] shadow-xl relative overflow-hidden">
                            <p class="text-xs font-black text-emerald-200 uppercase tracking-widest mb-1">PPN Lebih Bayar (Periode Ini)</p>
                            <h3 class="text-2xl font-black text-white font-mono">Rp {{ number_format(abs($netTaxPayable), 0, ',', '.') }}</h3>
                            <p class="text-[10px] text-emerald-200 mt-2 font-bold uppercase tracking-tighter">Kelebihan Bayar Pajak (Masukan > Keluaran)</p>
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                        <h4 class="font-black text-slate-900 dark:text-white text-sm">Rincian Akun Akuntansi Pajak PPN</h4>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Jenis Pajak</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Kode & Nama Akun (CoA)</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Kredit (Keluaran)</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Debit (Pengurangan)</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Saldo Net Periode</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-tighter">Pajak Keluaran</span>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-800 dark:text-slate-200">2103 - Hutang Pajak (PPN)</td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-900 dark:text-white text-right">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-xs text-slate-400 text-right">-</td>
                                <td class="px-6 py-4 text-xs font-black text-emerald-600 dark:text-emerald-400 text-right font-mono">Rp {{ number_format($totalTaxOutput, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-[10px] font-black uppercase tracking-tighter">Pajak Masukan</span>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-800 dark:text-slate-200">1105 - PPN Masukan</td>
                                <td class="px-6 py-4 text-xs text-slate-400 text-right">-</td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-900 dark:text-white text-right">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-xs font-black text-amber-600 dark:text-amber-400 text-right font-mono">Rp {{ number_format($totalTaxInput, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: RIWAYAT SETORAN KAS NEGARA -->
            <div x-show="activeTab === 'history'" class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-[2rem] border border-slate-200 dark:border-slate-800">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-base font-black text-slate-900 dark:text-white">Riwayat Setoran Pajak PPN ke Kas Negara</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar transaksi penyetoran PPN yang telah diproses ke negara.</p>
                        </div>
                        <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 text-xs font-bold rounded-xl">{{ count($taxPayments) }} Transaksi</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Tanggal Setor</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Keterangan / Deskripsi</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Rekening Sumber</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">No. Referensi / NTPN</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Nominal Setor (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($taxPayments as $payment)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 text-xs font-bold text-slate-700 dark:text-slate-300">
                                            {{ \Carbon\Carbon::parse($payment->transaction_date)->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900 dark:text-white text-xs">{{ str_replace('[Setor Pajak Negara] ', '', $payment->description) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-400 font-semibold">
                                            {{ $payment->bankAccount->bank_name ?? '-' }} ({{ $payment->bankAccount->account_name ?? '-' }})
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-amber-600 dark:text-amber-400 font-bold">
                                            {{ $payment->reference_number ?: '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">- Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-500 text-xs font-medium">Belum ada riwayat setoran PPN ke Kas Negara.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: MASTER SKEMA PAJAK -->
            <div x-show="activeTab === 'rates'" class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-[2rem] border border-slate-200 dark:border-slate-800">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-base font-black">Master Skema Pajak Terdaftar</h4>
                        <a href="{{ route('accounting.taxes.create') }}" class="text-xs font-black text-indigo-600 dark:text-indigo-400 hover:underline">+ Tambah Skema Baru</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Pajak</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Kode</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Tarif (%)</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Akun Akuntansi</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($taxes as $tax)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $tax->name }}</div>
                                            @if($tax->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-tighter">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-mono text-sm text-slate-500">{{ $tax->code }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ number_format($tax->rate, 0) }}%</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                                {{ $tax->chartOfAccount->code }} - {{ $tax->chartOfAccount->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <a href="{{ route('accounting.taxes.edit', $tax) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-bold text-xs uppercase tracking-widest">Edit</a>
                                            <form action="{{ route('accounting.taxes.destroy', $tax) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold text-xs uppercase tracking-widest" onclick="return confirm('Hapus pajak ini?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-500">Belum ada data pajak.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: PENGATURAN MODE PAJAK GLOBAL -->
            <div x-show="activeTab === 'settings'" class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-[2rem] border border-slate-200 dark:border-slate-800 p-8">
                <form action="{{ route('accounting.tax-settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <h4 class="text-lg font-black text-slate-900 dark:text-white">Aturan Mode Penerapan PPN</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tentukan bagaimana PPN (11%) akan diterapkan pada setiap tagihan invoice pelanggan Anda.</p>
                    </div>

                    <div class="space-y-4">
                        <!-- Mode All -->
                        <label class="relative flex items-start p-6 border-2 rounded-2xl cursor-pointer transition-all {{ $taxMode === 'all' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-indigo-200' }}">
                            <input type="radio" name="tax_mode" value="all" class="mt-1 h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-600" {{ $taxMode === 'all' ? 'checked' : '' }}>
                            <div class="ml-4">
                                <span class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Aktif Seluruhnya (Global ON)</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">Seluruh pelanggan tanpa pengecualian akan dikenakan PPN 11% pada setiap invoice bulanan.</span>
                            </div>
                        </label>

                        <!-- Mode Individual -->
                        <label class="relative flex items-start p-6 border-2 rounded-2xl cursor-pointer transition-all {{ $taxMode === 'individual' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-indigo-200' }}">
                            <input type="radio" name="tax_mode" value="individual" class="mt-1 h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-600" {{ $taxMode === 'individual' ? 'checked' : '' }}>
                            <div class="ml-4">
                                <span class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Per-Pelanggan (Opsional)</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">PPN hanya akan diterapkan jika opsi "Kenakan Pajak" diaktifkan pada profil masing-masing pelanggan.</span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-4 flex justify-between items-center border-t border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Tarif PPN Aktif: {{ $taxRate }}%</span>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-indigo-600/20 transition-all">Simpan Pengaturan Pajak</button>
                    </div>
                </form>
            </div>

    </div>

@push('modals')
{{-- Modal Setor PPN ke Kas Negara — rendered at <body> level via @stack('modals') --}}
<div x-data="{ open: false }" @open-pay-modal.window="open = true" x-cloak>
    {{-- Backdrop --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-md" 
         @click="open = false"
         style="z-index: 999999; display: none;"></div>

    {{-- Scrollable Container --}}
    <div x-show="open" 
         class="fixed inset-0 overflow-y-auto"
         style="z-index: 999999; display: none;">
        <div class="flex min-h-full items-start justify-center p-4 py-12 md:py-16">
            {{-- Modal Card --}}
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                 x-transition:leave="transition ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95" 
                 class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-lg w-full p-6 md:p-8 border border-slate-200 dark:border-slate-800 shadow-2xl">
                <div class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Pembayaran Pajak (PPN)</h3>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Setor saldo Hutang PPN ke Kas Negara</p>
                        </div>
                    </div>
                    <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-2 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="{{ route('accounting.taxes.pay') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Pilih Rekening Sumber Pembayaran</label>
                        <select name="bank_account_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" required>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->bank_name }} - {{ $acc->account_name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nominal Disetorkan (Rp)</label>
                        <input type="number" name="amount" value="{{ $totalTaxLiability > 0 ? $totalTaxLiability : '' }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3.5 px-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" placeholder="0" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tanggal Setor</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nomor Referensi / NTPN (Opsional)</label>
                        <input type="text" name="reference_number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" placeholder="Contoh: NTPN-9821831923" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Keterangan / Catatan</label>
                        <input type="text" name="description" value="Setoran PPN Masa {{ date('F Y') }}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500" required />
                    </div>

                    <div class="pt-4 flex items-center space-x-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="open = false" class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 uppercase tracking-wider transition-colors" style="padding: 12px 24px !important; text-align: center; width: 50% !important;">Batal</button>
                        <button type="submit" class="rounded-2xl text-xs font-bold uppercase tracking-wider active:scale-95 transition-all" style="background: linear-gradient(135deg, #f59e0b 0%, #eab308 100%) !important; color: #ffffff !important; box-shadow: 0 8px 20px -4px rgba(245, 158, 11, 0.4) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; padding: 12px 24px !important; text-align: center; width: 50% !important; display: inline-block !important;">Proses Setor PPN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Input Pajak Masukan — rendered at <body> level via @stack('modals') --}}
<div x-data="{ open: false }" @open-tax-input-modal.window="open = true" x-cloak>
    {{-- Backdrop --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-md" 
         @click="open = false"
         style="z-index: 999999; display: none;"></div>

    {{-- Scrollable Container --}}
    <div x-show="open" 
         class="fixed inset-0 overflow-y-auto"
         style="z-index: 999999; display: none;">
        <div class="flex min-h-full items-start justify-center p-4 py-12 md:py-16">
            {{-- Modal Card --}}
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                 x-transition:leave="transition ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95" 
                 class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] max-w-lg w-full p-6 md:p-8 border border-slate-200 dark:border-slate-800 shadow-2xl">
                <div class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Pencatatan Pajak Masukan</h3>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Input PPN Pembelian / Faktur dari Supplier</p>
                        </div>
                    </div>
                    <button @click="open = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-2 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form action="{{ route('accounting.taxes.input') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Nama Vendor / Supplier</label>
                        <input type="text" name="vendor_name" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Contoh: PT Fiber Optik Indonesia" required />
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Nominal PPN Masukan (Rp)</label>
                        <input type="number" name="amount" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Contoh: 110000" required />
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Tanggal Faktur / Pembelian</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required />
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">No. Faktur Pajak / Invoice Vendor (Opsional)</label>
                        <input type="text" name="tax_invoice_number" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-mono font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Contoh: 010.000-24.0000123" />
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Rekening Pembayaran / Sumber Kas (Opsional)</label>
                        <select name="bank_account_id" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            <option value="">-- Non-Tunai / Kredit (Tanpa Pengurangan Kas) --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->bank_name }} - {{ $acc->account_name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Keterangan Barang / Perangkat</label>
                        <input type="text" name="description" class="w-full bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Contoh: PPN Pembelian Router OLT Huawei" required />
                    </div>

                    <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="open = false" class="px-6 py-3 rounded-2xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-wider transition-colors">Batal</button>
                        <button type="submit" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black px-6 py-3 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 active:scale-95 transition-all">Simpan Pajak Masukan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

</x-app-layout>
