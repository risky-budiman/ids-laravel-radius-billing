<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
            Fitur Tutup Buku & Kunci Periode
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl dark:bg-emerald-900/30 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-tighter">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-8 bg-amber-50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">Governance: Periode Terkunci</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pastikan semua transaksi pada periode tersebut sudah final sebelum melakukan penutupan.</p>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500 uppercase tracking-widest">Status Saat Ini</span>
                            <span class="px-4 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 rounded-full text-xs font-bold">
                                {{ $closedUntil ? 'Terkunci s/d ' . \Carbon\Carbon::parse($closedUntil)->format('d/m/Y') : 'Belum Ada Periode Terkunci' }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('accounting.closing.process') }}" method="POST">
                        @csrf
                        
                        <div class="mb-8">
                            <label for="closed_until" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Kunci Transaksi Hingga Tanggal</label>
                            <input type="date" id="closed_until" name="closed_until" value="{{ old('closed_until', $closedUntil) }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl shadow-sm focus:border-amber-500 focus:ring-amber-500 text-lg" required>
                            <p class="mt-2 text-xs text-gray-500 italic">Sistem tidak akan mengizinkan penambahan, perubahan, atau penghapusan transaksi (Jurnal, Invoice, Bank) pada tanggal tersebut ke belakang.</p>
                            @error('closed_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-8">
                            <label class="flex items-center">
                                <input type="checkbox" name="confirmation" class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" required>
                                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Saya mengerti bahwa tindakan ini akan mengunci data keuangan dan tidak dapat diubah tanpa membuka kembali kunci periode.</span>
                            </label>
                            @error('confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-center">
                            <button type="submit" class="inline-flex items-center px-10 py-4 bg-amber-600 border border-transparent rounded-2xl font-bold text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 transition ease-in-out duration-150 shadow-lg shadow-amber-500/30 w-full justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Proses Tutup Buku & Kunci Periode
                            </button>
                        </div>
                    </form>
                </div>

                <div class="p-8 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Informasi Penting</h4>
                    <ul class="space-y-2">
                        <li class="flex items-start text-xs text-gray-500">
                            <svg class="w-4 h-4 text-amber-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Penutupan buku sebaiknya dilakukan setiap akhir bulan setelah rekonsiliasi bank selesai.
                        </li>
                        <li class="flex items-start text-xs text-gray-500">
                            <svg class="w-4 h-4 text-amber-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Laporan Keuangan (Neraca & Laba Rugi) untuk periode terkunci dianggap sebagai laporan final/audit.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
