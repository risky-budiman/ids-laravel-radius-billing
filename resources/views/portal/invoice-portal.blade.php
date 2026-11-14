@php
    $companyName = get_setting('company_name', config('app.name', 'Radius ISP'));
    $companyLogo = get_setting('company_logo');
    $appIcon = get_setting('app_icon');
    $companyAddress = get_setting('company_address');
    $companyPhone = get_setting('company_phone');
    $companyEmail = get_setting('company_email');
    $footerNote = get_setting('invoice_footer_note');
    $waNumber = $companyPhone ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $companyPhone)) : null;

    $customer = $invoice->customer;
    $customerId = $customer ? ($customer->customer_code ?: ($customer->username ? explode('@', $customer->username)[0] : $customer->id)) : '-';
    // Samarkan sebagian nomor HP: link invoice bisa diteruskan ke orang lain.
    $maskedPhone = $customer?->phone ? preg_replace('/(\d{4})\d+(\d{3})/', '$1••••$2', $customer->phone) : null;

    $isPaid = $invoice->status === 'paid';
    $isOverdue = ! $isPaid && $invoice->due_date && $invoice->due_date->isPast();
    $d = fn ($date, $fmt = 'd F Y') => $date ? $date->locale('id')->translatedFormat($fmt) : '-';
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $subtotal = $invoice->subtotal ?: $invoice->amount;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Invoice {{ $invoice->invoice_number }} · {{ $companyName }}</title>
    @if($appIcon || $companyLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . ($appIcon ?: $companyLogo)) }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-flat { box-shadow: none !important; border-color: #e2e8f0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-surface-50 text-surface-900">
    <div class="max-w-2xl mx-auto px-4 py-6 sm:py-10 space-y-4">

        {{-- Bar atas --}}
        <div class="no-print flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                @if($companyLogo)
                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="" class="h-8 w-auto max-w-[7rem] object-contain">
                @else
                    <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white text-sm font-semibold">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</div>
                @endif
                <span class="text-sm font-semibold truncate">{{ $companyName }}</span>
            </div>
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak / PDF
            </button>
        </div>

        @if(session('error'))
            <div class="no-print flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-3" role="alert">
                <x-icon name="exclamation" class="w-5 h-5 text-rose-600 shrink-0" />
                <div class="text-sm text-rose-700">
                    <p class="font-medium">Pembayaran belum dapat diproses</p>
                    <p class="mt-0.5">Silakan coba metode lain atau hubungi kami. <span class="text-rose-500">({{ session('error') }})</span></p>
                </div>
            </div>
        @endif

        {{-- Ringkasan & pembayaran --}}
        <div class="card print-flat overflow-hidden">
            <div class="p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-surface-500">{{ $isPaid ? 'Total dibayar' : 'Total tagihan' }}</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{{ $rp($invoice->amount) }}</p>
                    </div>
                    @if($isPaid)
                        <span class="badge-success">Lunas</span>
                    @elseif($isOverdue)
                        <span class="badge-danger">Lewat jatuh tempo</span>
                    @else
                        <span class="badge-warning">Belum dibayar</span>
                    @endif
                </div>
                <p class="mt-2 text-sm {{ $isOverdue ? 'text-rose-600' : 'text-surface-500' }}">
                    @if($isPaid)
                        Dibayar {{ $d($invoice->paid_at, 'd F Y, H:i') }}
                    @else
                        Jatuh tempo {{ $d($invoice->due_date) }}
                        @if($isOverdue) — segera lakukan pembayaran agar layanan tidak terisolir. @endif
                    @endif
                </p>
            </div>

            @unless($isPaid)
                <div class="no-print border-t border-surface-200 bg-surface-50/60 p-5 sm:p-6">
                    <p class="text-sm font-medium mb-3">Pilih metode pembayaran</p>
                    <div class="space-y-2">
                        @forelse($activeGateways as $gw)
                            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice.pay', ['invoice' => $invoice, 'gateway' => $gw->provider]) }}"
                               class="group flex items-center gap-3 rounded-lg border border-surface-200 bg-white px-4 py-3 hover:border-primary-400 hover:bg-primary-50/40 transition-colors">
                                <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-50 text-primary-600 text-[11px] font-semibold uppercase">{{ substr($gw->provider, 0, 3) }}</span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium">Bayar via {{ ucfirst($gw->provider) }}</span>
                                    <span class="block text-xs text-surface-500">Virtual account, e-wallet, QRIS, dan lainnya</span>
                                </span>
                                <x-icon name="chevron-right" class="w-4 h-4 text-surface-400 group-hover:text-primary-600" />
                            </a>
                        @empty
                            <div class="rounded-lg border border-dashed border-surface-300 bg-white px-4 py-5 text-center">
                                <p class="text-sm font-medium">Pembayaran online belum tersedia</p>
                                <p class="mt-0.5 text-xs text-surface-500">Silakan hubungi kami untuk informasi transfer manual.</p>
                            </div>
                        @endforelse
                    </div>
                    @if($waNumber)
                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo, saya ingin bertanya tentang tagihan ' . $invoice->invoice_number) }}" target="_blank" rel="noopener"
                           class="mt-3 inline-flex text-sm font-medium text-primary-600 hover:underline">Butuh bantuan? Chat WhatsApp</a>
                    @endif
                </div>
            @endunless
        </div>

        {{-- Rincian invoice --}}
        <div class="card print-flat">
            <div class="card-header">
                <div>
                    <p class="card-title">Invoice</p>
                    <p class="text-xs text-surface-500 font-mono mt-0.5">{{ $invoice->invoice_number }}</p>
                </div>
                <p class="text-xs text-surface-500 text-right">Diterbitkan<br>{{ $d($invoice->created_at) }}</p>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 p-5 text-sm">
                <div>
                    <dt class="text-xs text-surface-500">Ditagihkan kepada</dt>
                    <dd class="mt-0.5 font-medium">{{ $customer->name ?? '-' }}</dd>
                    <dd class="text-surface-500">ID {{ $customerId }}</dd>
                    @if($customer?->address)<dd class="text-surface-500">{{ $customer->address }}</dd>@endif
                    @if($maskedPhone)<dd class="text-surface-500">{{ $maskedPhone }}</dd>@endif
                </div>
                <div>
                    <dt class="text-xs text-surface-500">Periode layanan</dt>
                    <dd class="mt-0.5 font-medium">{{ $invoice->billing_period ?: '-' }}</dd>
                    <dd class="text-surface-500">{{ $d($invoice->period_start, 'd M Y') }} – {{ $d($invoice->period_end, 'd M Y') }}</dd>
                </div>
            </dl>

            <div class="border-t border-surface-200 px-5 py-4 text-sm space-y-2.5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium">Internet {{ $customer->package->name ?? '' }}</p>
                        <p class="text-xs text-surface-500">Biaya berlangganan{{ $invoice->billing_period ? ' · ' . $invoice->billing_period : '' }}</p>
                    </div>
                    <p class="tabular-nums">{{ $rp($subtotal) }}</p>
                </div>
                @if((float) $invoice->discount_amount > 0)
                    <div class="flex items-center justify-between gap-4 text-emerald-700">
                        <p>Diskon{{ $invoice->discount_type === 'percentage' ? ' (' . rtrim(rtrim(number_format($invoice->discount_value, 2, ',', '.'), '0'), ',') . '%)' : '' }}</p>
                        <p class="tabular-nums">−{{ $rp($invoice->discount_amount) }}</p>
                    </div>
                @endif
                @if((float) $invoice->tax_amount > 0)
                    <div class="flex items-center justify-between gap-4 text-surface-600">
                        <p>{{ $invoice->tax->name ?? 'Pajak (PPN)' }}</p>
                        <p class="tabular-nums">{{ $rp($invoice->tax_amount) }}</p>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 border-t border-surface-200 pt-3">
                    <p class="font-semibold">Total</p>
                    <p class="font-semibold tabular-nums">{{ $rp($invoice->amount) }}</p>
                </div>
                <p class="text-xs text-surface-500 italic">Terbilang: {{ ucfirst(\App\Services\AccountingService::terbilang($invoice->amount)) }} rupiah</p>
            </div>

            @if($footerNote || $invoice->notes)
                <div class="border-t border-surface-200 px-5 py-4">
                    <p class="text-xs font-medium text-surface-500 mb-1">Catatan</p>
                    <p class="text-sm text-surface-600">{{ $footerNote ?: $invoice->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Penerbit --}}
        <div class="text-center text-xs text-surface-500 space-y-1 pt-2">
            <p class="font-medium text-surface-700">{{ $companyName }}</p>
            @if($companyAddress)<p>{{ $companyAddress }}</p>@endif
            <p>
                @if($companyPhone) Telp/CS {{ $companyPhone }} @endif
                @if($companyPhone && $companyEmail) · @endif
                @if($companyEmail) {{ $companyEmail }} @endif
            </p>
            <p class="pt-2 text-surface-400">Invoice ini diterbitkan secara elektronik dan sah tanpa tanda tangan.</p>
        </div>
    </div>
</body>
</html>
