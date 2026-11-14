@php
    $companyName = get_setting('company_name', config('app.name'));
    $companyAddress = get_setting('company_address', '');
    $companyPhone = get_setting('company_phone', '');
    $companyEmail = get_setting('company_email', '');
    $companyWebsite = get_setting('company_website', '');
    $companyTaxId = get_setting('company_tax_id', '');
    $companyLogo = get_setting('company_logo');
    $footerNote = get_setting('invoice_footer_note', 'Terima kasih telah menggunakan layanan kami.');
    $hasCustom = !empty($customA4Html);
    $hasCustomThermal = !empty($customThermalHtml ?? null);

    $customer = $invoice->customer;
    $customerCode = $customer ? ($customer->customer_code ?: $customer->username) : '-';
    $isPaid = $invoice->status === 'paid';
    $isOverdue = ! $isPaid && $invoice->due_date && $invoice->due_date->isPast();
    $subtotal = $invoice->subtotal ?: $invoice->amount;
    $d = fn ($date, $fmt = 'd M Y') => $date ? $date->locale('id')->translatedFormat($fmt) : '-';
    $n = fn ($v) => number_format((float) $v, 0, ',', '.');
    $period = ($invoice->period_start || $invoice->period_end)
        ? $d($invoice->period_start) . ' – ' . $d($invoice->period_end)
        : ($invoice->billing_period ?: '-');
    $discountLabel = $invoice->discount_type === 'percentage'
        ? 'Diskon (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2, ',', '.'), '0'), ',') . '%)'
        : 'Diskon';
    $taxLabel = $invoice->tax ? trim(($invoice->tax->name ?? 'PPN') . (isset($invoice->tax->rate) ? ' ' . rtrim(rtrim(number_format((float) $invoice->tax->rate, 2, ',', '.'), '0'), ',') . '%' : '')) : 'Pajak';
    $payUrl = url('/pay/' . $invoice->invoice_number);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $invoice->invoice_number }} · Invoice {{ $companyName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    {{-- Halaman ini sengaja tidak memuat CSS aplikasi agar template custom (Invoice Builder) tampil apa adanya. --}}
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #0f172a; -webkit-font-smoothing: antialiased; }
        [x-cloak] { display: none !important; }

        /* ---------- Toolbar ---------- */
        .toolbar { position: sticky; top: 0; z-index: 50; display: flex; align-items: center; justify-content: space-between; gap: 12px;
                   padding: 10px 20px; background: rgba(255,255,255,.92); backdrop-filter: blur(8px); border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .toolbar-title { font-size: 14px; color: #475569; min-width: 0; }
        .toolbar-title strong { color: #0f172a; font-weight: 600; }
        .toolbar-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 8px; font: 500 13px/1 'Inter', sans-serif;
               cursor: pointer; text-decoration: none; border: 1px solid transparent; transition: background-color .15s, border-color .15s; white-space: nowrap; }
        .btn svg { width: 16px; height: 16px; }
        .btn-ghost { color: #475569; background: transparent; }
        .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
        .btn-outline { color: #334155; background: #fff; border-color: #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; }
        .btn-primary { color: #fff; background: #4f46e5; }
        .btn-primary:hover { background: #4338ca; }
        .dropdown { position: relative; }
        .dropdown-menu { position: absolute; right: 0; top: calc(100% + 6px); min-width: 210px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
                         box-shadow: 0 12px 32px -12px rgba(15,23,42,.25); padding: 4px; z-index: 60; }
        .dropdown-item { display: flex; width: 100%; align-items: center; gap: 8px; padding: 8px 10px; font: 400 13px 'Inter', sans-serif; color: #334155;
                         background: none; border: 0; border-radius: 6px; cursor: pointer; text-decoration: none; text-align: left; }
        .dropdown-item:hover { background: #f1f5f9; }
        .dropdown-item.active { color: #4f46e5; font-weight: 600; }
        .dropdown-sep { border-top: 1px solid #f1f5f9; margin: 4px 0; }

        /* ---------- Kertas A4 ---------- */
        .paper-wrapper { display: flex; justify-content: center; padding: 32px 16px 64px; }
        .paper { position: relative; background: #fff; width: 210mm; min-height: 297mm; padding: 16mm 18mm; box-shadow: 0 1px 3px rgba(15,23,42,.06), 0 12px 40px -12px rgba(15,23,42,.18); }
        @media (max-width: 860px) { .paper { width: 100%; min-height: auto; padding: 24px; } }

        .inv { position: relative; z-index: 1; font-size: 13px; line-height: 1.55; color: #334155; }
        .inv-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .inv-brand img { max-height: 52px; max-width: 200px; object-fit: contain; display: block; margin-bottom: 10px; }
        .inv-brand-name { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .inv-brand-info { font-size: 12px; color: #64748b; }
        .inv-heading { text-align: right; }
        .inv-heading h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -.02em; color: #0f172a; }
        .inv-heading .no { margin-top: 2px; font-size: 13px; color: #64748b; font-variant-numeric: tabular-nums; }
        .badge { display: inline-block; margin-top: 10px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }

        .inv-meta { display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; margin: 32px 0 28px; padding: 18px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
        .lbl { font-size: 11px; font-weight: 500; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .cust-name { font-size: 15px; font-weight: 600; color: #0f172a; }
        .muted { color: #64748b; }
        .meta-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .meta-table td { padding: 2px 0; vertical-align: top; }
        .meta-table td:first-child { color: #64748b; padding-right: 12px; white-space: nowrap; }
        .meta-table td:last-child { text-align: right; font-weight: 500; color: #0f172a; white-space: nowrap; }
        .text-danger { color: #b91c1c !important; }

        .items { width: 100%; border-collapse: collapse; }
        .items th { padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; text-align: left; border-bottom: 1px solid #cbd5e1; }
        .items td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .items .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .item-name { font-weight: 600; color: #0f172a; }
        .item-desc { font-size: 12px; color: #94a3b8; margin-top: 2px; }

        .totals { display: flex; justify-content: flex-end; margin-top: 16px; }
        .totals-box { width: 290px; }
        .t-row { display: flex; justify-content: space-between; padding: 6px 0; font-variant-numeric: tabular-nums; }
        .t-row span:first-child { color: #64748b; }
        .t-row.discount span { color: #047857; }
        .t-grand { display: flex; justify-content: space-between; align-items: baseline; margin-top: 8px; padding: 12px 14px; border-radius: 8px; background: #0f172a; color: #fff; font-variant-numeric: tabular-nums; }
        .t-grand span:first-child { font-size: 12px; color: #cbd5e1; }
        .t-grand span:last-child { font-size: 18px; font-weight: 700; }
        .terbilang { margin-top: 8px; font-size: 11.5px; font-style: italic; color: #64748b; text-align: right; }

        .pay-box { margin-top: 28px; padding: 14px 16px; border: 1px dashed #c7d2fe; border-radius: 10px; background: #eef2ff; font-size: 12.5px; color: #3730a3; }
        .pay-box a { color: #3730a3; font-weight: 600; word-break: break-all; }
        .paid-box { margin-top: 28px; padding: 14px 16px; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; font-size: 12.5px; color: #166534; }

        .inv-foot { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 24px; font-size: 12px; color: #64748b; }
        .inv-foot p { margin: 0; white-space: pre-line; }

        .watermark { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; z-index: 0; }
        .watermark span { font-size: 120px; font-weight: 800; color: #16a34a; opacity: .05; transform: rotate(-24deg); letter-spacing: .1em; }

        /* ---------- Struk thermal 80mm ---------- */
        #thermal-print-area { display: none; width: 80mm; margin: 0 auto; padding: 4mm; background: #fff; color: #000;
                              font-family: 'Courier New', ui-monospace, monospace; font-size: 11.5px; line-height: 1.45; }
        #thermal-print-area .c { text-align: center; }
        #thermal-print-area .hr { border-top: 1px dashed #000; margin: 6px 0; }
        #thermal-print-area table { width: 100%; border-collapse: collapse; }
        #thermal-print-area td { padding: 1px 0; vertical-align: top; }
        #thermal-print-area td.r { text-align: right; white-space: nowrap; }

        /* ---------- Cetak ---------- */
        @media print {
            html, body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .toolbar { display: none !important; }
            .paper-wrapper { padding: 0 !important; display: block !important; }
            body.printing-a4 .paper { box-shadow: none !important; width: 100% !important; min-height: auto !important; padding: 10mm 12mm !important; }
            body.printing-a4 #thermal-print-area { display: none !important; }
            body.printing-thermal .paper-wrapper { display: none !important; }
            body.printing-thermal #thermal-print-area { display: block !important; margin: 0; }
        }
    </style>
    {{-- Ukuran kertas diganti sesuai mode cetak (A4 atau gulungan 80mm) --}}
    <style id="page-size">@page { size: A4; margin: 0; }</style>
</head>
<body>
    {{-- Toolbar --}}
    <div class="toolbar" x-data="{ templateMode: '{{ $hasCustom ? 'custom' : 'default' }}', dropOpen: false }">
        <div class="toolbar-title">
            <strong>{{ $invoice->invoice_number }}</strong> · {{ $customer->name ?? 'Pelanggan' }}
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>

            <div class="dropdown">
                <button type="button" class="btn btn-outline" @click="dropOpen = !dropOpen">
                    Template: <span x-text="templateMode === 'custom' ? 'Custom' : 'Bawaan'"></span>
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="dropdown-menu" x-show="dropOpen" x-cloak @click.outside="dropOpen = false">
                    <button type="button" class="dropdown-item" :class="templateMode === 'default' && 'active'"
                            @click="templateMode = 'default'; dropOpen = false;
                                    document.getElementById('customA4Area')?.style.setProperty('display', 'none');
                                    document.getElementById('defaultA4Area').style.display = 'block';">
                        Template bawaan
                    </button>
                    @if($hasCustom)
                        <button type="button" class="dropdown-item" :class="templateMode === 'custom' && 'active'"
                                @click="templateMode = 'custom'; dropOpen = false;
                                        document.getElementById('defaultA4Area').style.display = 'none';
                                        document.getElementById('customA4Area').style.display = 'block';">
                            Template custom
                        </button>
                    @endif
                    <div class="dropdown-sep"></div>
                    <a href="{{ route('invoice-templates.index') }}" class="dropdown-item">Kelola template…</a>
                </div>
            </div>

            <button type="button" onclick="printInvoice('thermal')" class="btn btn-outline">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Struk 80mm
            </button>
            <button type="button" onclick="printInvoice('a4')" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak A4
            </button>
        </div>
    </div>

    <div class="paper-wrapper">
        {{-- Template custom (Invoice Builder) --}}
        @if($hasCustom)
            <div id="customA4Area" class="paper">
                {!! $customA4Html !!}
            </div>
        @endif

        {{-- Template bawaan --}}
        <div id="defaultA4Area" class="paper" style="{{ $hasCustom ? 'display:none;' : '' }}">
            @if($isPaid)
                <div class="watermark"><span>LUNAS</span></div>
            @endif

            <div class="inv">
                <div class="inv-top">
                    <div class="inv-brand">
                        @if($companyLogo)
                            <img src="{{ asset('storage/' . $companyLogo) }}" alt="{{ $companyName }}">
                        @endif
                        <div class="inv-brand-name">{{ $companyName }}</div>
                        <div class="inv-brand-info">
                            @if($companyAddress){{ $companyAddress }}<br>@endif
                            {{ collect([$companyPhone, $companyEmail, $companyWebsite])->filter()->implode(' · ') }}
                            @if($companyTaxId)<br>NPWP {{ $companyTaxId }}@endif
                        </div>
                    </div>
                    <div class="inv-heading">
                        <h1>Invoice</h1>
                        <div class="no">{{ $invoice->invoice_number }}</div>
                        @if($isPaid)
                            <span class="badge badge-paid">Lunas</span>
                        @elseif($isOverdue)
                            <span class="badge badge-overdue">Lewat jatuh tempo</span>
                        @else
                            <span class="badge badge-unpaid">Belum dibayar</span>
                        @endif
                    </div>
                </div>

                <div class="inv-meta">
                    <div>
                        <div class="lbl">Ditagihkan kepada</div>
                        <div class="cust-name">{{ $customer->name ?? 'Pelanggan' }}</div>
                        <div class="muted">ID Pelanggan: {{ $customerCode }}</div>
                        @if($customer?->address)<div class="muted">{{ $customer->address }}</div>@endif
                        @if($customer?->phone)<div class="muted">{{ $customer->phone }}</div>@endif
                    </div>
                    <div>
                        <div class="lbl">Detail invoice</div>
                        <table class="meta-table">
                            <tr><td>Tanggal terbit</td><td>{{ $d($invoice->created_at, 'd F Y') }}</td></tr>
                            <tr><td>Jatuh tempo</td><td class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $d($invoice->due_date, 'd F Y') }}</td></tr>
                            <tr><td>Periode layanan</td><td>{{ $period }}</td></tr>
                            @if($isPaid && $invoice->paid_at)
                                <tr><td>Tanggal bayar</td><td>{{ $d($invoice->paid_at, 'd F Y') }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>

                <table class="items">
                    <thead>
                        <tr>
                            <th style="width:6%">No</th>
                            <th>Deskripsi</th>
                            <th class="num" style="width:10%">Qty</th>
                            <th class="num" style="width:24%">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>
                                <div class="item-name">Layanan Internet{{ $customer?->package ? ' – ' . $customer->package->name : '' }}</div>
                                <div class="item-desc">
                                    {{ $invoice->billing_period ? 'Berlangganan ' . $invoice->billing_period : 'Biaya berlangganan' }}{{ ($invoice->period_start || $invoice->period_end) ? ' · ' . $period : '' }}
                                </div>
                                @if($invoice->notes)
                                    <div class="item-desc">{{ $invoice->notes }}</div>
                                @endif
                            </td>
                            <td class="num">1</td>
                            <td class="num">{{ $n($subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="totals">
                    <div class="totals-box">
                        <div class="t-row"><span>Subtotal</span><span>{{ $n($subtotal) }}</span></div>
                        @if((float) $invoice->discount_amount > 0)
                            <div class="t-row discount"><span>{{ $discountLabel }}</span><span>−{{ $n($invoice->discount_amount) }}</span></div>
                        @endif
                        @if((float) $invoice->tax_amount > 0)
                            <div class="t-row"><span>{{ $taxLabel }}</span><span>{{ $n($invoice->tax_amount) }}</span></div>
                        @endif
                        <div class="t-grand"><span>Total</span><span>Rp {{ $n($invoice->amount) }}</span></div>
                        <div class="terbilang">{{ ucfirst(\App\Services\AccountingService::terbilang($invoice->amount)) }} rupiah</div>
                    </div>
                </div>

                @if($isPaid)
                    <div class="paid-box">Pembayaran telah diterima{{ $invoice->paid_at ? ' pada ' . $d($invoice->paid_at, 'd F Y, H:i') : '' }}. Terima kasih.</div>
                @else
                    <div class="pay-box">
                        Bayar online sebelum {{ $d($invoice->due_date, 'd F Y') }} melalui:<br>
                        <a href="{{ $payUrl }}">{{ $payUrl }}</a>
                    </div>
                @endif

                <div class="inv-foot">
                    <div>
                        @if($footerNote)
                            <div class="lbl">Catatan</div>
                            <p>{{ $footerNote }}</p>
                        @endif
                    </div>
                    <p style="text-align:right; white-space:nowrap;">Dokumen elektronik,<br>sah tanpa tanda tangan.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Struk thermal 80mm --}}
    <div id="thermal-print-area">
        @if($hasCustomThermal)
            {!! $customThermalHtml !!}
        @else
            <div class="c">
                <strong style="font-size:13px;">{{ mb_strtoupper($companyName) }}</strong><br>
                @if($companyAddress){{ $companyAddress }}<br>@endif
                @if($companyPhone){{ $companyPhone }}@endif
            </div>
            <div class="hr"></div>
            <table>
                <tr><td>No</td><td class="r">{{ $invoice->invoice_number }}</td></tr>
                <tr><td>Tanggal</td><td class="r">{{ $invoice->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><td>Jatuh tempo</td><td class="r">{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td></tr>
                <tr><td>Status</td><td class="r">{{ $isPaid ? 'LUNAS' : 'BELUM BAYAR' }}</td></tr>
            </table>
            <div class="hr"></div>
            <table>
                <tr><td>Pelanggan</td><td class="r">{{ \Illuminate\Support\Str::limit($customer->name ?? '-', 20) }}</td></tr>
                <tr><td>ID</td><td class="r">{{ $customerCode }}</td></tr>
                <tr><td>Periode</td><td class="r">{{ ($invoice->period_start || $invoice->period_end) ? ($invoice->period_start?->format('d/m/y') ?? '-') . '-' . ($invoice->period_end?->format('d/m/y') ?? '-') : ($invoice->billing_period ?: '-') }}</td></tr>
            </table>
            <div class="hr"></div>
            <table>
                <tr><td colspan="2">Internet {{ $customer?->package?->name ?? '' }}</td></tr>
                <tr><td>Subtotal</td><td class="r">{{ $n($subtotal) }}</td></tr>
                @if((float) $invoice->discount_amount > 0)
                    <tr><td>{{ $discountLabel }}</td><td class="r">-{{ $n($invoice->discount_amount) }}</td></tr>
                @endif
                @if((float) $invoice->tax_amount > 0)
                    <tr><td>{{ $taxLabel }}</td><td class="r">{{ $n($invoice->tax_amount) }}</td></tr>
                @endif
            </table>
            <div class="hr"></div>
            <table style="font-size:13px; font-weight:bold;">
                <tr><td>TOTAL</td><td class="r">Rp {{ $n($invoice->amount) }}</td></tr>
            </table>
            <div class="hr"></div>
            @if(! $isPaid)
                <div class="c" style="font-size:10.5px;">Bayar online:<br>{{ $payUrl }}</div>
                <div class="hr"></div>
            @endif
            @if($footerNote)
                <div class="c" style="font-size:10.5px; white-space:pre-line;">{{ $footerNote }}</div>
            @endif
            <div class="c" style="font-size:9.5px; margin-top:6px;">Dicetak {{ now()->format('d/m/Y H:i') }}</div>
        @endif
    </div>

    <script>
        function printInvoice(format) {
            // Ganti ukuran kertas: A4 untuk invoice, gulungan 80mm untuk struk thermal.
            document.getElementById('page-size').textContent = format === 'thermal'
                ? '@page { size: 80mm auto; margin: 0; }'
                : '@page { size: A4; margin: 0; }';
            document.body.classList.remove('printing-a4', 'printing-thermal');
            document.body.classList.add(format === 'thermal' ? 'printing-thermal' : 'printing-a4');
            window.print();
            setTimeout(() => {
                document.body.classList.remove('printing-a4', 'printing-thermal');
                document.getElementById('page-size').textContent = '@page { size: A4; margin: 0; }';
            }, 1000);
        }
    </script>
</body>
</html>
