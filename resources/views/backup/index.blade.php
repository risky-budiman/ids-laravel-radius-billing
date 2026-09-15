<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-3">
                    <span class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    </span>
                    {{ __('Backup & Restore Database') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Cadangkan dan pulihkan data sistem billing secara selektif dengan enkripsi aman (.bak).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Enkripsi AES-256 (.bak)
                </span>
            </div>
        </div>
    </x-slot>

    <div x-data="{
        activeTab: 'create',
        selectAll: true,
        toggleAll() {
            const checkboxes = document.querySelectorAll('.module-checkbox');
            checkboxes.forEach(cb => cb.checked = this.selectAll);
        },
        restoreModalOpen: false,
        restoreSource: 'local',
        restoreFilename: '',
        restoreFileDisplay: '',
        restoreNotes: '',
        restoreTableCount: '',
        restoreRowCount: '',
        restorePassword: '',
        wipeExisting: true,
        confirmCheckbox: false,
        openLocalRestore(item) {
            this.restoreSource = 'local';
            this.restoreFilename = item.filename;
            this.restoreFileDisplay = item.filename;
            this.restoreNotes = item.notes || '-';
            this.restoreTableCount = item.tables_count || '-';
            this.restoreRowCount = item.total_records || '-';
            this.restorePassword = '';
            this.confirmCheckbox = false;
            this.wipeExisting = true;
            this.restoreModalOpen = true;
        }
    }" class="space-y-6 pb-12">

        <!-- Flash Notifications -->
        @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center gap-3 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 space-y-1 text-sm">
                <p class="font-bold">Terjadi kesalahan validasi:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-800 space-x-4">
            <button @click="activeTab = 'create'"
                    :class="activeTab === 'create' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Buat Cadangan (.bak)
            </button>

            <button @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                File Cadangan di Server
                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ count($localBackups) }}</span>
            </button>

            <button @click="activeTab = 'restore_upload'"
                    :class="activeTab === 'restore_upload' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-4 border-b-2 text-sm flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Pulihkan dari File Luar (.bak)
            </button>
        </div>

        <!-- TAB 1: CREATE BACKUP -->
        <div x-show="activeTab === 'create'" class="space-y-6">
            <form action="{{ route('backup.store') }}" method="POST">
                @csrf

                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 md:p-8 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-gray-100 dark:border-gray-700 gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pilih Data yang Ingin Dicadangkan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Anda dapat memilih modul tertentu atau mencadangkan seluruh data operasional sistem.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                                <span>Pilih Semua Modul</span>
                            </label>
                        </div>
                    </div>

                    <!-- Modules Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach($modules as $key => $module)
                            <div class="relative p-5 rounded-2xl border border-gray-200 dark:border-gray-700/80 hover:border-emerald-400 dark:hover:border-emerald-500 transition-all bg-gray-50/70 dark:bg-gray-900/40 flex flex-col justify-between">
                                <label class="flex items-start gap-3.5 cursor-pointer select-none">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" class="module-checkbox mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4 shrink-0" checked>
                                    <div class="min-w-0 flex-1 space-y-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-sm text-gray-900 dark:text-white truncate" title="{{ $module['name'] ?? $module['label'] }}">
                                                {{ $module['name'] ?? $module['label'] }}
                                            </span>
                                            <span class="shrink-0 text-[11px] font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-2 py-0.5 rounded-lg shadow-2xs">
                                                {{ count($module['tables']) }} tabel
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed line-clamp-2" title="{{ $module['description'] }}">
                                            {{ $module['description'] }}
                                        </p>
                                    </div>
                                </label>
                                <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-800 text-[10.5px] text-gray-400 dark:text-gray-500 truncate" title="{{ implode(', ', $module['tables']) }}">
                                    <span class="font-medium">Tabel:</span> {{ implode(', ', array_slice($module['tables'], 0, 3)) }}@if(count($module['tables']) > 3)...@endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Backup Options -->
                    <div class="pt-6 border-t border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Catatan Cadangan (Opsional)
                            </label>
                            <input type="text" name="notes" placeholder="Contoh: Backup sebelum migrasi paket bulanan" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="text-xs text-gray-400 mt-1">Catatan ini akan tersimpan di metadata file .bak</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Password Enkripsi Tambahan (Opsional)
                            </label>
                            <input type="password" name="custom_password" placeholder="Kosongkan jika menggunakan kunci default aplikasi" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="text-xs text-gray-400 mt-1">Jika diisi, file cadangan hanya dapat dipulihkan dengan memasukkan password ini kembali.</p>
                        </div>
                    </div>

                    <!-- Security Alert -->
                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <span class="font-bold">Keamanan Terjamin (.bak):</span> Berkas tidak berisi teks SQL telanjang sehingga struktur tabel dan data tidak dapat dibaca langsung oleh publik jika berkas terpapar. Seluruh muatan dienkripsi menggunakan AES-256 dan dilindungi di direktori penyimpanan privat.
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Mulai Proses Cadangkan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB 2: LOCAL BACKUPS HISTORY -->
        <div x-show="activeTab === 'history'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-6 md:p-8 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Daftar Cadangan Tersimpan di Server</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Berkas tersimpan pada direktori internal <code>storage/app/backups/</code> dan hanya dapat diunduh oleh Administrator.
                    </p>
                </div>

                @if(count($localBackups) === 0)
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-700 dark:text-gray-300">Belum Ada Cadangan Data</h4>
                        <p class="text-sm text-gray-500 mt-1">Silakan buat cadangan baru pada tab "Buat Cadangan".</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-4">Nama File & Catatan</th>
                                    <th class="px-6 py-4">Ukuran</th>
                                    <th class="px-6 py-4">Dibuat Oleh</th>
                                    <th class="px-6 py-4">Tabel / Baris</th>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($localBackups as $item)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                         <td class="px-6 py-4">
                                             <div class="flex items-center gap-2">
                                                 <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                     {{ $item['extension'] ?? 'BAK' }}
                                                 </span>
                                                 <span class="font-semibold text-gray-900 dark:text-white">{{ $item['filename'] }}</span>
                                             </div>
                                             @if(!empty($item['notes']))
                                                 <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ $item['notes'] }}</p>
                                             @endif
                                         </td>
                                         <td class="px-6 py-4 font-mono text-xs">{{ $item['size_formatted'] ?? ($item['size'] ?? '-') }}</td>
                                         <td class="px-6 py-4 text-xs font-medium">{{ $item['created_by'] ?? '-' }}</td>
                                         <td class="px-6 py-4 text-xs">
                                             @if(!empty($item['tables_count']))
                                                 <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['tables_count'] }} tabel</span>
                                                 @if(isset($item['total_records']) && is_numeric($item['total_records']))
                                                     <span class="text-gray-400">({{ number_format($item['total_records']) }} baris)</span>
                                                 @endif
                                             @else
                                                 <span class="text-gray-400">-</span>
                                             @endif
                                         </td>
                                         <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $item['created_at'] ?? ($item['modified_at'] ?? '-') }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- Download -->
                                                <a href="{{ route('backup.download', $item['filename']) }}" title="Download File .bak" class="p-2 rounded-xl text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </a>

                                                <!-- Restore Button -->
                                                <button type="button" @click="openLocalRestore({{ json_encode($item) }})" title="Pulihkan ke Database" class="px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20 font-semibold text-xs transition-all flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    Pulihkan
                                                </button>

                                                <!-- Delete -->
                                                <form action="{{ route('backup.destroy', $item['filename']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file cadangan {{ $item['filename'] }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus Cadangan" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- TAB 3: RESTORE FROM EXTERNAL UPLOAD -->
        <div x-show="activeTab === 'restore_upload'" class="space-y-6">
            <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="restore_source" value="upload">

                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 md:p-8 space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Unggah dan Pulihkan Berkas Cadangan (.bak)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Gunakan opsi ini jika Anda memiliki file cadangan dari server lain atau hasil download sebelumnya.
                        </p>
                    </div>

                    <!-- File input -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Pilih File Cadangan (.bak)
                        </label>
                        <div class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-emerald-400 rounded-2xl p-8 text-center bg-gray-50/50 dark:bg-gray-900/30 transition-all">
                            <input type="file" name="backup_file" accept=".bak" required class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                            <div class="flex flex-col items-center justify-center pointer-events-none">
                                <svg class="w-10 h-10 text-emerald-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Klik untuk memilih atau seret file .bak ke sini</span>
                                <span class="text-xs text-gray-400 mt-1">Maksimal ukuran file: 500 MB</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Password Dekripsi (Opsional)
                            </label>
                            <input type="password" name="password" placeholder="Isi hanya jika backup dibuat dengan password khusus" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="flex items-center pt-6">
                            <label class="inline-flex items-start gap-2 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="wipe_existing" value="1" checked class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                                <div>
                                    <span>Bersihkan data lama pada tabel target</span>
                                    <p class="text-xs text-gray-400">Hanya membersihkan tabel-tabel yang terdapat dalam berkas cadangan ini.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Confirmation Warning -->
                    <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 space-y-3">
                        <div class="flex items-center gap-2 font-bold text-sm">
                            <svg class="w-5 h-5 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Peringatan Pemulihan Database
                        </div>
                        <p class="text-xs leading-relaxed">
                            Proses ini akan menimpa data pada tabel-tabel yang ada di dalam berkas cadangan dengan data yang baru dipulihkan. Pastikan Anda telah membuat cadangan database saat ini sebelum melanjutkan!
                        </p>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-rose-700 dark:text-rose-400">
                            <input type="checkbox" name="confirm_restore" value="1" required class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                            <span>Saya memahami resiko dan setuju untuk melakukan pemulihan database dari file ini</span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm rounded-xl shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Unggah & Pulihkan Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RESTORE MODAL (FOR LOCAL SERVER BACKUPS) -->
        <div x-show="restoreModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="restoreModalOpen" @click="restoreModalOpen = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="restoreModalOpen" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                    <form action="{{ route('backup.restore') }}" method="POST">
                        @csrf
                        <input type="hidden" name="restore_source" value="local">
                        <input type="hidden" name="local_filename" :value="restoreFilename">

                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-3 bg-amber-500/10 text-amber-600 rounded-2xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Konfirmasi Pemulihan Database</h3>
                                <p class="text-xs text-gray-500">Pulihkan data dari arsip server</p>
                            </div>
                        </div>

                        <div class="space-y-4 my-4">
                            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/50 space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">File Cadangan:</span>
                                    <span class="font-mono font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[240px]" x-text="restoreFileDisplay"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Isi Cadangan:</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                                        <span x-text="restoreTableCount"></span> Tabel (<span x-text="restoreRowCount"></span> Baris)
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Catatan:</span>
                                    <span class="text-gray-600 dark:text-gray-300" x-text="restoreNotes"></span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Password Dekripsi (Jika ada)
                                </label>
                                <input type="password" name="password" x-model="restorePassword" placeholder="Kosongkan bila menggunakan enkripsi default" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="wipe_existing" value="1" x-model="wipeExisting" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                                <span>Timpa/Segarkan data pada tabel-tabel target</span>
                            </label>

                            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 text-xs">
                                <label class="flex items-start gap-2 cursor-pointer font-semibold">
                                    <input type="checkbox" name="confirm_restore" value="1" x-model="confirmCheckbox" class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500 h-4 w-4" required>
                                    <span>Saya yakin ingin menimpa data database dengan isi cadangan ini.</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="restoreModalOpen = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">
                                Batal
                            </button>
                            <button type="submit" :disabled="!confirmCheckbox" :class="confirmCheckbox ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'" class="px-5 py-2.5 font-semibold text-sm rounded-xl transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Ya, Pulihkan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
