<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-3xl text-gray-900 dark:text-white tracking-tight">
                    Halo, {{ $customer->name }}! 👋
                </h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Selamat datang di Portal Pelanggan {{ get_setting('company_name', 'Radius ISP') }}.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-4 py-2 rounded-2xl {{ $isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} text-sm font-bold shadow-sm">
                    <span class="w-2 h-2 rounded-full {{ $isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }} mr-2"></span>
                    Internet {{ $isOnline ? 'Terhubung' : 'Terputus' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Quick Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Current Package -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-20 h-20 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <p class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-4">Paket Langganan</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $customer->package->name }}</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ $customer->package->speed_limit }} Mbps Speed</p>
                </div>

                <!-- Signal Quality -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-20 h-20 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <p class="text-xs font-black text-emerald-500 uppercase tracking-widest mb-4">Kualitas Sinyal</p>
                    @php 
                        $rx = $customer->signalCache->rx_power ?? null;
                        $status = 'Bagus';
                        $color = 'text-emerald-500';
                        if ($rx === null) { $status = 'N/A'; $color = 'text-gray-400'; }
                        elseif ($rx < -27) { $status = 'Lemah'; $color = 'text-rose-500'; }
                        elseif ($rx < -24) { $status = 'Normal'; $color = 'text-amber-500'; }
                    @endphp
                    <h3 class="text-2xl font-black {{ $color }}">{{ $rx ? $rx . ' dBm' : 'Unknown' }}</h3>
                    <p class="text-sm text-gray-500 mt-2">Kondisi: {{ $status }}</p>
                </div>

                <!-- Billing Info -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-20 h-20 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <p class="text-xs font-black text-amber-500 uppercase tracking-widest mb-4">Tagihan Berjalan</p>
                    @php $unpaid = $unpaidInvoices->sum('amount'); @endphp
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($unpaid, 0, ',', '.') }}</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ $unpaidInvoices->count() }} Invoice Belum Bayar</p>
                </div>

                <!-- Customer Code -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-20 h-20 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </div>
                    <p class="text-xs font-black text-purple-500 uppercase tracking-widest mb-4">ID Pelanggan</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $customer->customer_code }}</h3>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('customer.invoices') }}" class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-8 py-4 rounded-3xl text-sm font-black text-gray-700 dark:text-white hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Riwayat Tagihan
                </a>
                <a href="{{ route('customer.boosters') }}" class="bg-indigo-600 px-8 py-4 rounded-3xl text-sm font-black text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Beli Booster
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Billing List -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="font-black text-gray-900 dark:text-white">Tagihan Terakhir</h3>
                        <a href="#" class="text-xs font-bold text-indigo-600 hover:underline">Lihat Semua</a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($unpaidInvoices as $invoice)
                            <div class="px-8 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">Invoice #{{ $invoice->invoice_number }}</p>
                                    <p class="text-xs text-gray-500">Jatuh Tempo: {{ $invoice->due_date->format('d M Y') }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-lg font-black text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-xs font-bold transition-all shadow-lg shadow-indigo-500/20">Bayar Sekarang</a>
                                </div>
                            </div>
                        @empty
                            <div class="px-8 py-12 text-center">
                                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white">Semua Tagihan Sudah Terbayar!</p>
                                <p class="text-sm text-gray-500">Terima kasih telah berlangganan tepat waktu.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Network Status Detail -->
                <div class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-indigo-500/5 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-black text-gray-900 dark:text-white">Informasi Koneksi</h3>
                    </div>
                    <div class="p-8 space-y-6">
                        @if($session)
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">IP Address</p>
                                <p class="font-mono text-gray-900 dark:text-white">{{ $session->framedipaddress }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Terhubung Sejak</p>
                                <p class="text-gray-900 dark:text-white">{{ $session->acctstarttime->format('d M Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Durasi Sesi</p>
                                <p class="text-gray-900 dark:text-white">{{ floor($session->acctsessiontime / 3600) }} Jam {{ floor(($session->acctsessiontime % 3600) / 60) }} Menit</p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Total Download</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ round($session->acctoutputoctets / (1024*1024*1024), 2) }} GB</span>
                                </div>
                                <div class="flex items-center justify-between text-sm mt-2">
                                    <span class="text-gray-500">Total Upload</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ round($session->acctinputoctets / (1024*1024*1024), 2) }} GB</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">Belum ada data sesi aktif.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Maintenance/Announcements Placeholder -->
            <div class="bg-indigo-600 rounded-[2rem] p-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-20">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-11v6h2v-6h-2zm0-4v2h2V7h-2z"/></svg>
                </div>
                <h4 class="text-xl font-black mb-2">Informasi Layanan</h4>
                <p class="text-indigo-100 max-w-2xl">Layanan internet Anda dalam kondisi optimal. Jika mengalami gangguan, silakan hubungi pusat bantuan melalui WhatsApp di nomor 0812-XXXX-XXXX.</p>
            </div>

        </div>
    </div>
</x-app-layout>
