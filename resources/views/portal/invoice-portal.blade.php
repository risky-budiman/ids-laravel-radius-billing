<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Tagihan - {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .card-shadow { box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.05), 0 8px 10px -6px rgb(0 0 0 / 0.05); }
    </style>
</head>
<body class="min-h-screen py-8 px-4 flex flex-col items-center">
    
    <div class="max-w-md w-full space-y-6">
        <!-- Brand Header -->
        <div class="text-center">
            <h1 class="text-2xl font-extrabold text-indigo-600 tracking-tight">ISP NETWORK</h1>
            <p class="text-gray-500 text-sm mt-1">Portal Pembayaran Pelanggan</p>
        </div>

        <!-- Invoice Card -->
        <div class="glass card-shadow rounded-3xl border border-white overflow-hidden">
            <div class="bg-indigo-600 px-8 py-10 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                
                <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest">Total Tagihan</p>
                <h2 class="text-4xl font-extrabold mt-2 tracking-tight">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</h2>
                
                <div class="mt-6 flex justify-between items-center bg-white/10 rounded-2xl p-4 backdrop-blur-md">
                    <div>
                        <p class="text-[10px] text-indigo-200 uppercase font-bold">Nomor Invoice</p>
                        <p class="font-mono text-sm font-bold">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        @if($invoice->status == 'paid')
                            <span class="px-3 py-1 bg-emerald-400 text-emerald-950 text-[10px] font-black rounded-full uppercase">LUNAS</span>
                        @else
                            <span class="px-3 py-1 bg-amber-400 text-amber-950 text-[10px] font-black rounded-full uppercase tracking-wider">BELUM BAYAR</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <!-- Details -->
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-sm">Nama Pelanggan</span>
                        <span class="text-gray-900 font-bold text-sm">{{ $invoice->customer->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-sm">Username</span>
                        <span class="text-gray-700 font-medium text-sm">{{ $invoice->customer->username }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-4">
                        <span class="text-gray-400 text-sm">Jatuh Tempo</span>
                        <span class="text-gray-900 font-bold text-sm">{{ $invoice->due_date->format('d M Y') }}</span>
                    </div>
                </div>

                @if($invoice->status == 'unpaid')
                <!-- Payment Actions -->
                <div class="space-y-3 pt-4 border-t border-gray-100">
                    <p class="text-gray-800 font-extrabold text-sm mb-4">Pilih Metode Pembayaran:</p>
                    
                    @forelse($activeGateways as $gw)
                        <a href="{{ route('invoices.pay', ['invoice' => $invoice, 'gateway' => $gw->provider]) }}" class="relative w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-200 rounded-2xl transition-all group overflow-hidden">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center font-bold text-indigo-600 text-xs">
                                    {{ substr(strtoupper($gw->provider), 0, 2) }}
                                </div>
                                <span class="font-bold text-gray-700 group-hover:text-indigo-700">Bayar via {{ ucfirst($gw->provider) }}</span>
                            </div>
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    @empty
                        <div class="p-4 bg-gray-50 rounded-2xl text-center text-sm text-gray-500 italic">
                            Maaf, metode pembayaran online tidak tersedia untuk sementara. Silakan hubungi admin.
                        </div>
                    @endforelse
                </div>
                @else
                <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl text-center">
                    <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg shadow-emerald-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <p class="text-emerald-800 font-bold">Terima Kasih!</p>
                    <p class="text-emerald-600 text-xs mt-1">Pembayaran tagihan ini telah dikonfirmasi.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Footer Footer -->
        <p class="text-center text-gray-400 text-[10px] leading-relaxed">
            Butuh bantuan? Hubungi Technical Support kami via WhatsApp.<br>
            © 2026 ISP NETWORK - High Speed Broadband.
        </p>
    </div>

</body>
</html>
