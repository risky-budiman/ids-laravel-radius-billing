<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} — Invoice</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #333;
        }

        /* Toolbar - only visible on screen */
        .toolbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .toolbar-actions { display: flex; align-items: center; gap: 8px; }
        .toolbar .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            text-decoration: none;
        }
        .btn-back { background: #f1f5f9; color: #475569; }
        .btn-back:hover { background: #e2e8f0; }
        .btn-thermal { background: #1e293b; color: #fff; }
        .btn-thermal:hover { background: #0f172a; }
        .btn-a4 { background: #4f46e5; color: #fff; }
        .btn-a4:hover { background: #4338ca; }
        .btn-template { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .btn-template:hover { background: #fef3c7; }
        .btn svg { width: 16px; height: 16px; margin-right: 6px; }
        .toolbar-title {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }
        .toolbar-title strong { color: #334155; }

        /* Dropdown */
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 220px;
            z-index: 50;
            overflow: hidden;
        }
        .dropdown-item {
            display: block;
            width: 100%;
            padding: 10px 16px;
            font-size: 13px;
            color: #475569;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            transition: background 0.1s;
            text-decoration: none;
        }
        .dropdown-item:hover { background: #f8fafc; }
        .dropdown-item.active { color: #4f46e5; font-weight: 700; }
        .dropdown-sep { border-top: 1px solid #f1f5f9; }

        /* Paper Container */
        .paper-wrapper {
            display: flex;
            justify-content: center;
            padding: 40px 20px 60px;
            min-height: calc(100vh - 60px);
        }
        .paper {
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 210mm;
            min-height: 280mm;
            padding: 15mm 18mm;
            position: relative;
            color: #333;
        }

        /* Invoice Typography */
        .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
        .inv-header-left img { max-height: 56px; object-fit: contain; margin-bottom: 10px; }
        .inv-company-name { font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 6px; }
        .inv-company-info { font-size: 12px; color: #64748b; line-height: 1.6; }
        .inv-title { font-size: 36px; font-weight: 800; color: #cbd5e1; letter-spacing: 6px; text-align: right; }
        .inv-number { font-size: 15px; font-weight: 700; color: #1e293b; text-align: right; margin-top: 6px; }
        .inv-status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .inv-status-paid { background: #dcfce7; color: #166534; }
        .inv-status-unpaid { background: #fee2e2; color: #991b1b; }

        .inv-divider { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }

        .inv-info { display: flex; justify-content: space-between; margin-bottom: 28px; }
        .inv-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .inv-customer-name { font-size: 16px; font-weight: 700; color: #1e293b; }
        .inv-customer-detail { font-size: 12px; color: #64748b; margin-top: 3px; }
        .inv-meta-table { font-size: 12px; }
        .inv-meta-table td { padding: 3px 0; }
        .inv-meta-label { color: #94a3b8; padding-right: 16px; }
        .inv-meta-value { font-weight: 700; color: #1e293b; }

        /* Items Table */
        .inv-table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        .inv-table thead tr {
            border-top: 2px solid #1e293b;
            border-bottom: 2px solid #1e293b;
        }
        .inv-table th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e293b;
            letter-spacing: 0.5px;
        }
        .inv-table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .inv-item-name { font-weight: 700; color: #1e293b; }
        .inv-item-desc { font-size: 11px; color: #94a3b8; margin-top: 3px; }

        /* Totals */
        .inv-totals { display: flex; justify-content: flex-end; margin: 12px 0 32px; }
        .inv-totals-box { width: 260px; }
        .inv-total-row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
        .inv-total-row span:first-child { color: #64748b; }
        .inv-total-row span:last-child { font-weight: 600; color: #1e293b; }
        .inv-grand-total {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
        }

        .inv-footer { border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 32px; }
        .inv-footer-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .inv-footer-text { font-size: 12px; color: #64748b; white-space: pre-line; line-height: 1.6; }

        /* Watermark */
        .inv-watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0.04;
            z-index: 0;
        }
        .inv-watermark span {
            font-size: 140px;
            font-weight: 900;
            color: #10b981;
            transform: rotate(-30deg);
            letter-spacing: 12px;
        }

        /* Thermal */
        #thermal-print-area {
            display: none;
            font-family: monospace;
            width: 80mm;
            color: #000;
            background: #fff;
            padding: 5mm;
            font-size: 12px;
            margin: 0 auto;
        }
        #thermal-print-area .dashed-line { border-bottom: 1px dashed #000; margin: 8px 0; }

        /* ============ PRINT STYLES ============ */
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .toolbar { display: none !important; }
            .paper-wrapper {
                padding: 0 !important;
                min-height: auto !important;
                display: block !important;
            }

            /* A4 Print */
            body.printing-a4 .paper {
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 8mm 12mm !important;
                margin: 0 !important;
            }
            body.printing-a4 #thermal-print-area { display: none !important; }

            /* Thermal Print */
            body.printing-thermal .paper-wrapper { display: none !important; }
            body.printing-thermal #thermal-print-area {
                display: block !important;
                width: 80mm;
                margin: 0;
                padding: 5mm;
            }

            /* Remove browser headers/footers (URL, date, page number) */
            @page {
                margin: 0;
                size: A4;
            }
        }
    </style>
</head>
<body>
    @php
        $companyName = get_setting('company_name', config('app.name'));
        $companyAddress = get_setting('company_address', '');
        $companyPhone = get_setting('company_phone', '');
        $companyEmail = get_setting('company_email', '');
        $companyLogo = get_setting('company_logo');
        $footerNote = get_setting('invoice_footer_note', 'Thank you for your business!');
        $hasCustom = !empty($customA4Html);
    @endphp

    <!-- Toolbar -->
    <div class="toolbar" x-data="{ templateMode: '{{ $hasCustom ? 'custom' : 'default' }}', dropOpen: false }">
        <div class="toolbar-title">
            <strong>{{ $invoice->invoice_number }}</strong> &mdash; {{ $invoice->customer->name ?? 'Customer' }}
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('invoices.index') }}" class="btn btn-back">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>

            <!-- Template Switcher -->
            <div class="dropdown">
                <button class="btn btn-template" @click="dropOpen = !dropOpen">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"></path></svg>
                    <span x-text="templateMode === 'custom' ? 'Custom' : 'Bawaan'"></span>
                    <svg style="margin-left:4px;margin-right:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div class="dropdown-menu" x-show="dropOpen" @click.away="dropOpen = false" x-transition style="display:none;">
                    <button class="dropdown-item" :class="templateMode === 'default' && 'active'"
                        @click="templateMode='default'; dropOpen=false; 
                               if(document.getElementById('customA4Area')) document.getElementById('customA4Area').style.display='none'; 
                               document.getElementById('defaultA4Area').style.display='block';">
                        📄 Template Bawaan
                    </button>
                    @if($hasCustom)
                    <button class="dropdown-item" :class="templateMode === 'custom' && 'active'"
                        @click="templateMode='custom'; dropOpen=false; 
                               document.getElementById('defaultA4Area').style.display='none'; 
                               document.getElementById('customA4Area').style.display='block';">
                        🎨 Template Custom
                    </button>
                    @endif
                    <div class="dropdown-sep"></div>
                    <a href="{{ route('invoice-templates.index') }}" class="dropdown-item">⚙️ Kelola Template...</a>
                </div>
            </div>

            <button onclick="printInvoice('thermal')" class="btn btn-thermal">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Struk 80mm
            </button>
            <button onclick="printInvoice('a4')" class="btn btn-a4">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak A4
            </button>
        </div>
    </div>

    <!-- Paper -->
    <div class="paper-wrapper">
        <!-- Custom Template -->
        @if($hasCustom)
        <div id="customA4Area" class="paper">
            {!! $customA4Html !!}
        </div>
        @endif

        <!-- Default Built-in Template -->
        <div id="defaultA4Area" class="paper" style="{{ $hasCustom ? 'display:none;' : '' }}">
            @if($invoice->status == 'paid')
            <div class="inv-watermark"><span>PAID</span></div>
            @endif

            <!-- Header -->
            <div class="inv-header">
                <div class="inv-header-left">
                    @if($companyLogo)
                        <img src="{{ asset('storage/' . $companyLogo) }}" alt="{{ $companyName }}">
                    @else
                        <div class="inv-company-name">{{ $companyName }}</div>
                    @endif
                    @if($companyAddress || $companyPhone || $companyEmail)
                    <div class="inv-company-info">
                        @if($companyAddress){{ $companyAddress }}@endif
                        @if($companyPhone || $companyEmail)<br>{{ $companyPhone }}@if($companyPhone && $companyEmail) | @endif{{ $companyEmail }}@endif
                    </div>
                    @endif
                </div>
                <div>
                    <div class="inv-title">INVOICE</div>
                    <div class="inv-number">{{ $invoice->invoice_number }}</div>
                    <div style="text-align:right; margin-top:8px;">
                        <span class="inv-status {{ $invoice->status == 'paid' ? 'inv-status-paid' : 'inv-status-unpaid' }}">
                            {{ $invoice->status }}
                        </span>
                    </div>
                </div>
            </div>

            <hr class="inv-divider">

            <!-- Info -->
            <div class="inv-info">
                <div>
                    <div class="inv-label">Billed To</div>
                    <div class="inv-customer-name">{{ $invoice->customer->name ?? 'Unknown Customer' }}</div>
                    <div class="inv-customer-detail">{{ $invoice->customer->address ?? '-' }}</div>
                    <div class="inv-customer-detail">{{ $invoice->customer->phone ?? '-' }}</div>
                </div>
                <div style="text-align:right;">
                    <table class="inv-meta-table" style="margin-left:auto;">
                        <tr><td class="inv-meta-label">Invoice Date:</td><td class="inv-meta-value">{{ $invoice->created_at->format('d M Y') }}</td></tr>
                        <tr><td class="inv-meta-label">Due Date:</td><td class="inv-meta-value">{{ $invoice->due_date->format('d M Y') }}</td></tr>
                        <tr><td class="inv-meta-label">Billing Period:</td><td class="inv-meta-value">{{ $invoice->period_start->format('d M Y') }} — {{ $invoice->period_end->format('d M Y') }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- Items -->
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width:5%">No</th>
                        <th style="width:55%">Description</th>
                        <th style="width:15%;text-align:center;">Qty</th>
                        <th style="width:25%;text-align:right;">Amount (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td>
                            <div class="inv-item-name">Internet Service Subscription ({{ $invoice->billing_period }})</div>
                            @if($invoice->notes)
                                <div class="inv-item-desc">{{ $invoice->notes }}</div>
                            @endif
                        </td>
                        <td style="text-align:center;">1</td>
                        <td style="text-align:right;font-weight:600;">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="inv-totals">
                <div class="inv-totals-box">
                    <div class="inv-total-row">
                        <span>Subtotal</span>
                        <span>{{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->tax_id)
                    <div class="inv-total-row">
                        <span>Tax ({{ $invoice->tax->name ?? 'PPN' }} {{ $invoice->tax->rate ?? 0 }}%)</span>
                        <span>{{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="inv-grand-total">
                        <span>Total Due</span>
                        <span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            @if($footerNote)
            <div class="inv-footer">
                <div class="inv-footer-label">Notes & Terms</div>
                <div class="inv-footer-text">{{ $footerNote }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Thermal Print Area -->
    <div id="thermal-print-area">
        <div style="text-align:center; margin-bottom:10px;">
            <h2 style="margin:0; font-size:14px; text-transform:uppercase;">{{ $companyName }}</h2>
            <p style="margin:2px 0; font-size:10px;">{{ $companyAddress }}</p>
            <p style="margin:2px 0; font-size:10px;">{{ $companyPhone }}</p>
        </div>
        <div class="dashed-line"></div>
        <div style="margin-bottom:10px; font-size:11px;">
            <p style="margin:2px 0;"><strong>INV:</strong> {{ $invoice->invoice_number }}</p>
            <p style="margin:2px 0;"><strong>TGL:</strong> {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
            <p style="margin:2px 0;"><strong>JTH TEMPO:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
            <p style="margin:2px 0;"><strong>STATUS:</strong> {{ strtoupper($invoice->status) }}</p>
        </div>
        <div class="dashed-line"></div>
        <div style="margin-bottom:10px; font-size:11px;">
            <p style="margin:2px 0;"><strong>CUST:</strong> {{ $invoice->customer->name ?? 'Unknown' }}</p>
            <p style="margin:2px 0;"><strong>PERIODE:</strong> {{ $invoice->period_start->format('d/m/y') }} - {{ $invoice->period_end->format('d/m/y') }}</p>
        </div>
        <div class="dashed-line"></div>
        <table style="width:100%; font-size:11px; margin-bottom:10px;">
            <tr><td colspan="2" style="padding-bottom:4px;">Internet Service ({{ $invoice->billing_period }})</td></tr>
            <tr><td>Subtotal</td><td style="text-align:right;">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
            @if($invoice->tax_id)
            <tr><td>Tax ({{ $invoice->tax->rate ?? 0 }}%)</td><td style="text-align:right;">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td></tr>
            @endif
        </table>
        <div class="dashed-line"></div>
        <table style="width:100%; font-size:12px; font-weight:bold;">
            <tr><td>TOTAL</td><td style="text-align:right;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr>
        </table>
        <div class="dashed-line"></div>
        @if($footerNote)
        <div style="text-align:center; font-size:10px; margin-top:15px; white-space:pre-line;">{{ $footerNote }}</div>
        @endif
        <div style="text-align:center; font-size:9px; margin-top:10px; opacity:0.7;">Printed: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <script>
        function printInvoice(format) {
            document.body.classList.remove('printing-a4', 'printing-thermal');
            document.body.classList.add(format === 'thermal' ? 'printing-thermal' : 'printing-a4');
            window.print();
            setTimeout(() => document.body.classList.remove('printing-a4', 'printing-thermal'), 1000);
        }
    </script>
</body>
</html>
