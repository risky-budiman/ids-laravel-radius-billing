<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('customer.tickets.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Buat Laporan Gangguan
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto py-10 sm:px-6 lg:px-8">
        <form action="{{ route('customer.tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Detail Keluhan</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Ceritakan kendala koneksi yang Anda alami agar teknisi kami dapat segera memperbaikinya.</p>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <x-input-label for="subject" value="Topik Kendala (Judul)" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" placeholder="Contoh: Internet Mati Total / Lampu LOS Merah / Koneksi Lambat" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Deskripsi Detail" />
                        <textarea id="description" name="description" rows="5" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm" placeholder="Jelaskan sejak kapan internet bermasalah, lampu apa saja yang menyala di modem, dsb..." required></textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div>
                        <x-input-label for="attachment" value="Lampiran Foto (Opsional)" />
                        <input type="file" id="attachment" name="attachment" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-xl file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100
                            dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                        "/>
                        <p class="text-xs text-gray-500 mt-1">Format gambar (JPG, PNG). Maksimal 5MB. Lampirkan foto lampu modem atau pesan error untuk mempercepat perbaikan.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                    </div>
                    
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                Pastikan modem dalam keadaan menyala agar sistem kami dapat mengecek koneksi Anda secara langsung. Laporan Anda akan segera masuk ke antrean pengecekan teknisi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('customer.tickets.index') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                    Batal
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
