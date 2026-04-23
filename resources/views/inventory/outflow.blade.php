<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center print:hidden">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory Audit: Item Outflows') }}
            </h2>
            <div class="flex space-x-3">
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-2xl text-sm font-bold transition-all flex items-center shadow-lg shadow-indigo-600/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Laporan Formal
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        /* Modern App UI Styles */
        .glass { backdrop-filter: blur(16px); }
        
        /* Formal Report Styles for Printing */
        @media print {
            /* Hide EVERYTHING from the App UI */
            nav, aside, header, footer, .print\:hidden, .no-print, button { display: none !important; }
            
            /* Reset Page Defaults & Break Dashboard Restrictions */
            @page { size: A4; margin: 2cm; }
            html, body { height: auto !important; overflow: visible !important; }
            .flex.h-screen { display: block !important; height: auto !important; overflow: visible !important; }
            .overflow-y-auto { overflow: visible !important; height: auto !important; }

            body { background: white !important; margin: 0 !important; padding: 0 !important; color: black !important; font-family: "Times New Roman", Times, serif !important; }
            
            /* Show only the Report Container */
            .formal-report { display: block !important; visibility: visible !important; position: relative !important; width: 100%; }
            
            /* Kop Surat (Letterhead) */
            .kop-surat { border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; }
            .kop-logo { width: 80px; height: 80px; margin-right: 20px; object-contain; }
            .kop-text { text-align: center; flex: 1; }
            .kop-text h1 { font-size: 18pt; font-weight: bold; margin: 0; text-transform: uppercase; }
            .kop-text p { font-size: 9pt; margin: 2px 0; }
            
            /* Document Info */
            .doc-info { margin-bottom: 20px; font-size: 10pt; }
            .doc-info table { width: auto !important; border: none !important; }
            .doc-info td { border: none !important; padding: 2px 0 !important; }
            
            /* Report Title */
            .report-title { text-align: center; margin: 30px 0; }
            .report-title h2 { font-size: 14pt; text-decoration: underline; font-weight: bold; margin-bottom: 5px; }
            
            /* Tables */
            .report-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; table-layout: auto; }
            .report-table thead { display: table-header-group !important; } /* Repeat header on every page */
            .report-table tr { page-break-inside: avoid !important; page-break-after: auto !important; }
            .report-table th, .report-table td { border: 1px solid #000 !important; padding: 8px !important; font-size: 9pt !important; vertical-align: top; }
            .report-table th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; text-align: center !important; font-weight: bold; }
            
            /* Signatures */
            .signature-section { margin-top: 30px; page-break-inside: avoid !important; } /* Keep signatures together */
            .signature-grid { display: grid; grid-template-cols: 1fr 1fr; gap: 50px; }
            .signature-box { text-align: center; }
            .signature-space { height: 80px; }
            .signature-name { font-weight: bold; text-decoration: underline; }
        }
    </style>

    <!-- FORMAL REPORT CONTAINER (Hidden in App, Visible in Print) -->
    <div class="hidden formal-report">
        <!-- Kop Surat -->
        <div class="kop-surat">
            <div class="w-[80px]">
                @if(get_setting('company_logo'))
                    <img src="{{ asset('storage/' . get_setting('company_logo')) }}" class="kop-logo">
                @else
                    <div class="w-16 h-16 bg-black flex items-center justify-center text-white font-bold text-3xl">R</div>
                @endif
            </div>
            <div class="kop-text">
                <h1>{{ get_setting('company_name', 'PT. RADIUS MEDIA NETWORK') }}</h1>
                <p>Internet Service Provider & Network Infrastructure Solutions</p>
                <p>{{ get_setting('company_address', 'Jl. Raya Utama No. 123, Indonesia') }}</p>
                <p>Telp: {{ get_setting('company_phone', '(021) 1234567') }} | Email: {{ get_setting('company_email', 'admin@radius.net.id') }}</p>
            </div>
        </div>

        <!-- Document Metadata -->
        <div class="flex justify-between doc-info">
            <table>
                <tr><td class="w-24">Nomor</td><td>: {{ date('Ymd') }}/INV/OUT/{{ $outflows->currentPage() }}</td></tr>
                <tr><td>Lampiran</td><td>: 1 (Satu) Berkas</td></tr>
                <tr><td>Perihal</td><td>: Laporan Mutasi Pengeluaran Barang Inventaris</td></tr>
            </table>
            <div class="text-right">
                <p>{{ now()->format('d F Y') }}</p>
            </div>
        </div>

        <div class="report-title">
            <h2>LAPORAN PENGELUARAN BARANG</h2>
            <p>Periode Audit: {{ now()->startOfMonth()->format('d M Y') }} s/d {{ now()->format('d M Y') }}</p>
        </div>

        <p class="text-xs mb-4">Bersama ini disampaikan rincian data pengeluaran barang inventaris untuk keperluan aktivasi pelanggan dan pemeliharaan infrastruktur jaringan sebagai berikut:</p>

        <!-- Main Data Table -->
        <table class="report-table">
            <thead>
                <tr>
                    <th class="w-10">No</th>
                    <th class="w-24">Tanggal</th>
                    <th>Nama Barang</th>
                    <th class="w-16">Jumlah</th>
                    <th>Tujuan / Referensi</th>
                    <th>Pelaksana</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outflows as $index => $flow)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $flow->created_at->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $flow->item->name }}</strong>
                        <br><span class="text-[8pt] text-gray-600">SKU: {{ $flow->item->sku ?? '-' }}</span>
                    </td>
                    <td class="text-center">{{ number_format($flow->quantity, 0) }} {{ $flow->item->unit }}</td>
                    <td>
                        {{ $flow->customer ? 'Aktivasi: ' . $flow->customer->name : 'Teknis: ' . $flow->reference }}
                        @if($flow->notes)
                            <br><small>Ket: {{ $flow->notes }}</small>
                        @endif
                    </td>
                    <td>{{ $flow->user->name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signature-section">
            <p class="text-xs italic mb-8">Demikian laporan ini dibuat dengan sebenar-benarnya untuk dipergunakan sebagai data pendukung audit dan dokumentasi perusahaan.</p>
            
            <div class="signature-grid">
                <div class="signature-box">
                    <p>Disiapkan Oleh,</p>
                    <p><strong>Staff Warehouse & Logistik</strong></p>
                    <div class="signature-space"></div>
                    <p class="signature-name">( __________________________ )</p>
                    <p class="text-xs">NIK. .................................</p>
                </div>
                <div class="signature-box">
                    <p>Diketahui/Disetujui Oleh,</p>
                    <p><strong>Kepala Divisi Infrastruktur</strong></p>
                    <div class="signature-space"></div>
                    <p class="signature-name">( __________________________ )</p>
                    <p class="text-xs">NIK. .................................</p>
                </div>
            </div>
            
            <div class="mt-12 text-center text-[8pt] border-t pt-2 italic">
                Dokumen ini merupakan lampiran resmi dari sistem ERP {{ get_setting('company_name') }}
            </div>
        </div>
    </div>

    <!-- APP UI VIEW (Visible in App, Hidden in Print) -->
    <div class="print:hidden space-y-6">
        <!-- Summary Cards (App UI Style) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-black">
                    {{ $outflows->total() }}
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Total Record</p>
                    <p class="text-sm font-bold text-gray-600 dark:text-gray-300">Riwayat Keluar</p>
                </div>
            </div>
        </div>

        <!-- Table View (App UI Style) -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-400 text-[10px] uppercase tracking-widest font-black border-b border-gray-100 dark:border-gray-700">
                            <th class="px-8 py-5">Timestamp</th>
                            <th class="px-8 py-5">Product</th>
                            <th class="px-8 py-5 text-center">Qty</th>
                            <th class="px-8 py-5">Purpose</th>
                            <th class="px-8 py-5">Authorized By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($outflows as $flow)
                            <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-900/30 transition-colors">
                                <td class="px-8 py-5">
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $flow->created_at->format('d M Y') }}</p>
                                    <p class="text-[10px] text-gray-400 font-medium">{{ $flow->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $flow->item->name }}</p>
                                </td>
                                <td class="px-8 py-5 text-center font-black text-rose-500">
                                    -{{ number_format($flow->quantity, 0) }}
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-xs font-bold text-gray-600">{{ $flow->customer ? 'Activation' : 'Technical' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $flow->reference }}</p>
                                </td>
                                <td class="px-8 py-5 text-xs text-gray-500">
                                    {{ $flow->user->name }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-8 py-20 text-center text-gray-400">Belum ada data audit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-8 py-6 border-t border-gray-100 dark:border-gray-700">
                {{ $outflows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
