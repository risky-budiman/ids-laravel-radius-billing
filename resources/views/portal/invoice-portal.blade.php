<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} - {{ config('app.name', 'ISP NETWORK') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.4); }
        .invoice-box { box-shadow: 0 40px 100px rgba(0,0,0,0.05); }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .invoice-box { box-shadow: none; border: none; width: 100%; max-width: 100%; } .glass-card { border: none; background: transparent; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen py-12 px-4">

    <div class="max-w-4xl mx-auto">
        <!-- Top Controls (No Print) -->
        <div class="no-print flex justify-between items-center mb-10">
            <div class="flex items-center space-x-4">
                <a href="javascript:history.back()" class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400 hover:text-indigo-600 border border-slate-200 transition-all hover:shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <span class="text-sm font-bold text-slate-400">Portal Pembayaran Resmi</span>
            </div>
            <button onclick="window.print()" class="flex items-center px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4"></path></svg>
                Simpan PDF
            </button>
        </div>

        <div class="invoice-box bg-white rounded-[3rem] overflow-hidden relative border border-slate-100">
            <!-- Accent Top Line -->
            <div class="h-2 w-full {{ $invoice->status == 'paid' ? 'bg-emerald-500' : 'bg-amber-500' }}"></div>

            <div class="p-8 md:p-16">
                <!-- Branding & Status Header -->
                <div class="flex flex-col md:flex-row justify-between items-start mb-20 gap-12">
                    <div>
                        <div class="flex items-center mb-6">
                            <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white mr-5 shadow-xl shadow-indigo-600/20">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black tracking-tight text-slate-900 uppercase">{{ config('app.name', 'ISP NETWORK') }}</h1>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">High-Speed Connectivity</p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-slate-500 font-medium">Headquarters: Jawa Barat, Indonesia</p>
                            <p class="text-sm text-slate-500 font-medium">Customer Care: support@isp-network.com</p>
                        </div>
                    </div>

                    <div class="md:text-right">
                        <div class="inline-block px-5 py-2 rounded-2xl mb-6 font-black uppercase tracking-widest text-[10px] border {{ $invoice->status == 'paid' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100' }}">
                            {{ $invoice->status == 'paid' ? 'Lunas / Paid' : 'Belum Bayar / Unpaid' }}
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2">Invoice Number</p>
                        <p class="text-3xl font-black text-slate-900 leading-none">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm font-bold text-slate-400 mt-2">Diterbitkan: {{ $invoice->created_at->translatedFormat('d F Y') }}</p>
                    </div>
                </div>

                <!-- Client & Summary Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-16 mb-20">
                    <div class="p-8 rounded-[2rem] bg-slate-50 border border-slate-100">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-200 pb-2">Ditagihkan Kepada:</h4>
                        <p class="text-xl font-black text-slate-900 mb-1">{{ $invoice->customer->name }}</p>
                        <p class="text-sm font-bold text-indigo-600 mb-4">ID Pelanggan: #{{ $invoice->customer->username }}</p>
                        <div class="space-y-1 text-sm text-slate-500 font-medium leading-relaxed">
                            <p>{{ $invoice->customer->address }}</p>
                            <p>{{ $invoice->customer->phone }}</p>
                        </div>
                    </div>

                    <div class="md:text-right px-4">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-200 pb-2">Ringkasan Tagihan:</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Periode Layanan</p>
                                <p class="text-sm font-black text-slate-900">{{ $invoice->billing_period }}</p>
                                <p class="text-xs font-medium text-slate-400">{{ $invoice->period_start->translatedFormat('d M Y') }} - {{ $invoice->period_end->translatedFormat('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal Jatuh Tempo</p>
                                <p class="text-lg font-black text-rose-600">{{ $invoice->due_date->translatedFormat('d F Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="mb-20">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b-2 border-slate-100">
                                <th class="pb-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Deskripsi Layanan</th>
                                <th class="pb-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr>
                                <td class="py-8">
                                    <p class="text-base font-black text-slate-900">Internet Subscription: {{ $invoice->customer->package->name ?? 'Service Fee' }}</p>
                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-1">Unlimited High-Speed Data Plan</p>
                                </td>
                                <td class="py-8 text-right font-black text-slate-900 text-lg">
                                    Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <td class="py-6">
                                    <p class="text-sm font-bold text-slate-500 italic">Pajak (PPN 11%)</p>
                                </td>
                                <td class="py-6 text-right font-bold text-slate-500 text-base">
                                    Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="pt-8">
                                    <div class="bg-slate-900 rounded-[2.5rem] p-10 flex flex-col md:flex-row justify-between items-center text-white relative overflow-hidden group">
                                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl -mt-32 -mr-32 group-hover:bg-indigo-500/20 transition-all"></div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-1">Total Amount Due</p>
                                            <p class="text-sm text-indigo-400 font-bold italic">Terbilang: # {{ strtoupper(\App\Services\AccountingService::terbilang($invoice->amount)) }} RUPIAH #</p>
                                        </div>
                                        <div class="mt-6 md:mt-0 text-right">
                                            <p class="text-4xl font-black tracking-tight">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bottom Section: Payment & Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                    <div>
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Instruksi Penting:</h4>
                        <div class="p-8 bg-slate-50 rounded-[2rem] border border-slate-100 relative overflow-hidden">
                            <div class="absolute left-0 top-0 h-full w-1.5 bg-indigo-600"></div>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                {{ $invoice->notes ?: 'Silakan lakukan pembayaran sebelum tanggal jatuh tempo. Keterlambatan dapat menyebabkan isolir layanan secara otomatis oleh sistem kami. Simpan bukti pembayaran ini sebagai referensi di masa depan.' }}
                            </p>
                        </div>
                    </div>

                    @if($invoice->status == 'unpaid')
                    <div class="no-print space-y-4">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest md:text-right mb-4">Pilih Metode Pembayaran:</p>
                        
                        <div class="flex flex-col gap-3">
                            @forelse($activeGateways as $gw)
                                <a href="{{ route('invoices.pay', ['invoice' => $invoice, 'gateway' => $gw->provider]) }}" 
                                   class="group flex items-center justify-between p-5 bg-white border border-slate-200 rounded-2xl hover:border-indigo-600 transition-all hover:shadow-lg active:scale-95">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center font-black text-indigo-600 text-[10px] border border-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                            {{ strtoupper(substr($gw->provider, 0, 3)) }}
                                        </div>
                                        <span class="font-black text-slate-900 uppercase tracking-tighter">BAYAR VIA {{ ucfirst($gw->provider) }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-300 group-hover:translate-x-1 group-hover:text-indigo-600 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7"></path></svg>
                                </a>
                            @empty
                                <div class="p-6 bg-slate-50 rounded-2xl text-center">
                                    <p class="text-xs text-slate-500 font-bold uppercase italic">Metode pembayaran online tidak tersedia.</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Silakan hubungi Billing Support untuk info transfer manual.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    @else
                    <div class="md:text-right flex flex-col md:items-end">
                        <div class="inline-flex items-center text-emerald-600 font-black px-8 py-4 bg-emerald-50 rounded-[2rem] border border-emerald-100 shadow-sm">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            PEMBAYARAN SUDAH DITERIMA
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 mt-3 uppercase tracking-widest">Paid at: {{ $invoice->paid_at ? $invoice->paid_at->translatedFormat('d F Y, H:i') : '-' }}</p>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-slate-900 py-10 px-8 text-center border-t border-white/5">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6 max-w-4xl mx-auto">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em]">{{ config('app.name') }} OFFICIAL DIGITAL RECEIPT</p>
                    <div class="flex space-x-6">
                        <span class="text-[10px] font-bold text-slate-600 uppercase">Trusted by {{ \App\Models\Customer::count() }}+ Clients</span>
                        <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Ver: 2.0.4</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-12 text-center space-y-4">
            <p class="text-slate-400 text-[10px] font-bold leading-relaxed max-w-2xl mx-auto uppercase tracking-wider">
                Dokumen ini sah secara hukum dan diterbitkan secara elektronik. <br>Segala bentuk manipulasi data pada invoice ini akan ditindak sesuai peraturan perundang-undangan yang berlaku.
            </p>
        </div>
    </div>

</body>
</html>
