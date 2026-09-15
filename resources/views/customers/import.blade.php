<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                    <span class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </span>
                    {{ __('Import Pelanggan (Excel / CSV)') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Unggah data pelanggan existing secara massal dengan template Excel yang dilengkapi pilihan dropdown otomatis.
                </p>
            </div>
            <div>
                <a href="{{ route('customers.index') }}" class="px-4 py-2 text-sm font-semibold rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Daftar Pelanggan
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 pb-12">
        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm font-medium leading-relaxed">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm font-medium leading-relaxed">{{ session('error') }}</div>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 space-y-1 text-sm">
                <p class="font-bold">Terjadi kesalahan validasi input:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Import Results Detail (If any) -->
        @if(session('import_result'))
            @php $res = session('import_result'); @endphp
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm space-y-4">
                <h4 class="font-bold text-base text-gray-900 dark:text-white">Rekapitulasi Hasil Impor</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/50">
                        <span class="text-xs text-gray-400">Total Baris Diproses</span>
                        <p class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">{{ $res['total_rows'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/30">
                        <span class="text-xs text-emerald-600 dark:text-emerald-400">Berhasil Dibuat</span>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $res['success'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30">
                        <span class="text-xs text-indigo-600 dark:text-indigo-400">Diperbarui</span>
                        <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $res['updated'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-amber-50/50 dark:bg-amber-950/30">
                        <span class="text-xs text-amber-600 dark:text-amber-400">Dilewati / Galat</span>
                        <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $res['skipped'] }}</p>
                    </div>
                </div>

                @if(!empty($res['errors']))
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <h5 class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-2">Daftar Baris yang Dilewati / Bermasalah:</h5>
                        <div class="max-h-48 overflow-y-auto space-y-2">
                            @foreach($res['errors'] as $err)
                                <div class="text-xs p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 flex items-start gap-2">
                                    <span class="font-bold px-1.5 py-0.5 rounded bg-rose-200 dark:bg-rose-900 text-[10px]">Baris {{ $err['row'] }}</span>
                                    <span class="font-mono font-semibold">[{{ $err['username'] }}]</span>
                                    <span>{{ $err['reason'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Side: Upload Form -->
            <div class="lg:col-span-2 space-y-6">
                <form action="{{ route('customers.import.store') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 md:p-8 space-y-6">
                    @csrf

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Unggah Berkas Spreadsheet</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Pilih file Excel (<code>.xlsx</code>, <code>.xls</code>) atau <code>.csv</code> yang sudah diisi berdasarkan template resmi.
                        </p>
                    </div>

                    <!-- File Drop Area -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Pilih File Excel / CSV
                        </label>
                        <div class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 rounded-3xl p-8 text-center bg-gray-50/50 dark:bg-gray-900/30 transition-all group">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                            <div class="flex flex-col items-center justify-center pointer-events-none">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                </div>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Klik untuk memilih file Excel atau seret file ke sini</span>
                                <span class="text-xs text-gray-400 mt-1">Mendukung file .xlsx, .xls, dan .csv (Maksimal 20 MB)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Duplicate Handling Option -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                            Tindakan Jika Username Sudah Terdaftar:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-start gap-3 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-emerald-400 transition-all">
                                <input type="radio" name="duplicate_action" value="skip" checked class="mt-1 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-gray-900 dark:text-white block">Lewati (Skip)</span>
                                    <span class="text-xs text-gray-400">Data lama tidak diubah, baris duplikat diabaikan dengan aman.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-emerald-400 transition-all">
                                <input type="radio" name="duplicate_action" value="update" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <span class="font-bold text-sm text-gray-900 dark:text-white block">Perbarui (Update)</span>
                                    <span class="text-xs text-gray-400">Memperbarui paket, password, diskon, dan data pelanggan lama sesuai file.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- RADIUS Sync Info -->
                    <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 text-indigo-900 dark:text-indigo-300 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-indigo-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="leading-relaxed">
                            <span class="font-bold">Sinkronisasi Otomatis RADIUS:</span> Setiap pelanggan yang berhasil diimpor akan otomatis dibuatkan akun di tabel <code>radcheck</code> (username & password) serta dihubungkan ke profil bandwidth paket di <code>radusergroup</code>.
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Mulai Proses Impor
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Side: Download Template & Help -->
            <div class="space-y-6">
                <!-- Template Card -->
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-3xl p-6 md:p-8 shadow-lg shadow-emerald-600/20 space-y-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-bold text-lg leading-snug">Unduh Template Excel Resmi (.xlsx)</h3>
                    <p class="text-xs text-emerald-100 leading-relaxed">
                        Template memiliki 2 sheet: <strong>Format Import</strong> (bersih & siap diisi) dan <strong>Contoh & Panduan</strong> (sampel data di luar tabel import agar tidak ikut terimpor).
                    </p>
                    <a href="{{ route('customers.import.template') }}" class="inline-flex items-center justify-center w-full px-5 py-3 bg-white hover:bg-emerald-50 text-emerald-800 font-bold text-sm rounded-xl transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Template Excel (.xlsx)
                    </a>
                </div>

                <!-- Guidelines Card -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm space-y-4 text-xs">
                    <h4 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Petunjuk Kolom Kunci:</h4>
                    <ul class="space-y-2.5 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-rose-600 min-w-[125px]">nama_pelanggan:</span>
                            <span class="text-rose-600 font-semibold">[WAJIB]</span>
                            <span>Nama lengkap pelanggan.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">username:</span>
                            <span class="text-emerald-600 font-semibold">[OPSIONAL]</span>
                            <span>Bisa dikosongkan (otomatis di-generate: <code>ID Pelanggan @ domain</code>).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">password:</span>
                            <span class="text-emerald-600 font-semibold">[OPSIONAL]</span>
                            <span>Bisa dikosongkan (otomatis dibuatkan password acak aman).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-rose-600 min-w-[125px]">nama_paket:</span>
                            <span class="text-rose-600 font-semibold">[WAJIB]</span>
                            <span>Pilih dari dropdown paket aktif.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-rose-600 min-w-[125px]">no_telepon:</span>
                            <span class="text-rose-600 font-semibold">[WAJIB]</span>
                            <span>Nomor WhatsApp/HP pelanggan.</span>
                        </li>
                        <li class="flex items-start gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                            <span class="font-bold text-emerald-600 min-w-[125px]">tipe_tagihan:</span>
                            <span>[Opsional] Dropdown <code>prepaid</code> atau <code>postpaid</code>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">metode_tagihan:</span>
                            <span>[Opsional] Dropdown <code>cycle</code>, <code>fixed</code>, atau <code>renewal</code>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">kenakan_ppn:</span>
                            <span>[Opsional] Pilihan <code>YA</code> atau <code>TIDAK</code>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">tipe_diskon:</span>
                            <span>[Opsional] Pilihan <code>nominal</code>, <code>persen</code>, atau <code>tanpa_diskon</code>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">tgl_jatuh_tempo:</span>
                            <span>[Opsional] Tanggal <code>1</code> - <code>28</code> per bulan (Default: 20).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-emerald-600 min-w-[125px]">tgl_aktivasi:</span>
                            <span>[Opsional] Format <code>YYYY-MM-DD</code> (cth: 2026-01-15). Digunakan untuk siklus anniversary/billing.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
