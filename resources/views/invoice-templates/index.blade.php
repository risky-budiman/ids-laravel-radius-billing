<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Invoice Template Builder') }}
            </h2>
            <a href="{{ route('invoice-templates.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all active:scale-95 shadow-lg shadow-indigo-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Template Baru
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 px-5 py-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-400 font-medium text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 px-5 py-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl dark:bg-rose-900/20 dark:border-rose-800 dark:text-rose-400 font-medium text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Variable Reference Card -->
        <div class="mb-8 glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Cara Kerja</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                Buat template invoice dengan HTML & CSS lalu sisipkan <strong>variabel</strong> seperti <code class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded text-xs font-bold">{nama_pelanggan}</code>, <code class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded text-xs font-bold">{rp_total}</code>, dll. 
                Variabel akan diganti otomatis dengan data sebenarnya saat mencetak invoice. Anda bisa membuat beberapa template dan memilih yang menjadi default untuk format A4 atau Thermal.
            </p>
        </div>

        @if($templates->isEmpty())
            <div class="text-center py-20">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">Belum ada template</h3>
                <p class="text-sm text-gray-500 mb-6">Buat template invoice pertama Anda untuk mulai mengkustomisasi faktur.</p>
                <a href="{{ route('invoice-templates.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Template
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($templates as $template)
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group hover:shadow-lg transition-all duration-300">
                    <!-- Preview Thumbnail -->
                    <div class="h-48 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 overflow-hidden relative">
                        <div class="transform scale-[0.35] origin-top-left w-[286%] h-[286%] p-8 pointer-events-none">
                            {!! str_replace(
                                ['{nama_perusahaan}','{nomor_invoice}','{nama_pelanggan}','{rp_total}','{status}','{tanggal_invoice}','{tanggal_jatuh_tempo}','{periode_tagihan}','{alamat_perusahaan}','{telepon_perusahaan}','{email_perusahaan}','{alamat_pelanggan}','{telepon_pelanggan}','{username_pelanggan}','{paket_pelanggan}','{subtotal}','{rp_subtotal}','{nama_pajak}','{jumlah_pajak}','{rp_pajak}','{total}','{catatan}','{periode_awal}','{periode_akhir}','{catatan_footer}','{tanggal_cetak}','{logo_perusahaan}','{status_badge}'],
                                [get_setting('company_name','ISP'),'INV-SAMPLE','Ahmad Budiman','Rp 388.500','UNPAID',now()->format('d M Y'),now()->addDays(14)->format('d M Y'),'1 Month',get_setting('company_address','-'),get_setting('company_phone','-'),get_setting('company_email','-'),'Jl. Contoh','0856xxx','ahmad','Fiber 30Mbps','350.000','Rp 350.000','PPN 11%','38.500','Rp 38.500','388.500','Internet Service',now()->startOfMonth()->format('d M Y'),now()->endOfMonth()->format('d M Y'),'Thank you',now()->format('d/m/Y H:i'),'<strong>'.get_setting('company_name','ISP').'</strong>','<span style="color:red;font-weight:bold;">UNPAID</span>'],
                                $template->html_content
                            ) !!}
                        </div>
                        
                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-4">
                            <a href="{{ route('invoice-templates.edit', $template) }}" class="px-4 py-2 bg-white text-gray-800 rounded-lg text-xs font-bold shadow-lg hover:bg-gray-100 transition-colors">
                                Edit Template
                            </a>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ $template->name }}</h4>
                            @if($template->is_default)
                                <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-black uppercase rounded-full tracking-wider">Default</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Format: <strong class="text-gray-700 dark:text-gray-300">{{ $template->format }}</strong>
                            </span>
                            <div class="flex items-center space-x-1">
                                @if(!$template->is_default)
                                <form action="{{ route('invoice-templates.set-default', $template) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all" title="Jadikan Default">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('invoice-templates.edit', $template) }}" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                @if(!$template->is_default)
                                <form action="{{ route('invoice-templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
