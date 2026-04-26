<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('whatsapp-templates.index') }}" class="mr-4 text-gray-500 hover:text-emerald-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Edit Template WhatsApp
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Sesuaikan redaksi pesan otomatis untuk <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $whatsappTemplate->name }}</span>
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6">
            
            <!-- Form Kiri -->
            <div class="lg:w-2/3">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <form action="{{ route('whatsapp-templates.update', $whatsappTemplate) }}" method="POST" class="p-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-6">
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Isi Pesan (Message)</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Gunakan *teks* untuk tebal, _teks_ untuk miring, dan ~teks~ untuk coret sesuai format WhatsApp.
                            </p>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="12" 
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 font-mono text-sm shadow-sm"
                                required
                            >{{ old('message', $whatsappTemplate->message) }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-8">
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="is_active" value="1" class="sr-only" {{ old('is_active', $whatsappTemplate->is_active) ? 'checked' : '' }}>
                                    <div class="block bg-gray-200 dark:bg-gray-600 w-14 h-8 rounded-full transition-colors duration-300"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform duration-300 shadow-sm"></div>
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Aktif</span>
                                    <span class="block text-xs text-gray-500">Pesan ini hanya akan dikirim otomatis jika tombol ini menyala hijau.</span>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end border-t border-gray-100 dark:border-gray-700 pt-6">
                            <a href="{{ route('whatsapp-templates.index') }}" class="mr-4 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Batal</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-emerald-600 border border-transparent rounded-xl font-semibold text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm shadow-emerald-500/30">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Variabel Kanan -->
            <div class="lg:w-1/3">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30 rounded-2xl p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-300 flex items-center mb-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Variabel Dinamis
                    </h3>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400 mb-6">
                        Klik pada salah satu variabel di bawah ini untuk menyalinnya (*copy*), lalu tempelkan (*paste*) ke dalam kotak pesan di samping.
                    </p>

                    <div class="flex flex-col gap-2">
                        @forelse($variables as $var)
                            <button onclick="copyToClipboard('{{ $var }}', this)" type="button" class="group flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-indigo-100 dark:border-indigo-700/50 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-500 transition-all text-left">
                                <span class="font-mono text-sm text-gray-800 dark:text-gray-200 font-semibold">{{ $var }}</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                            </button>
                        @empty
                            <p class="text-sm text-indigo-600/70 dark:text-indigo-400/70 italic">Tidak ada variabel khusus untuk template ini.</p>
                        @endforelse
                    </div>

                    <div class="mt-6 pt-6 border-t border-indigo-200 dark:border-indigo-800/50">
                        <h4 class="text-xs font-bold text-indigo-900 dark:text-indigo-400 uppercase tracking-wider mb-2">Simulasi Hasil</h4>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-xs text-gray-600 dark:text-gray-400 font-mono italic">
                            Halo *Budi* (ID: 1234), tagihan Anda sebesar *Rp 150.000*...
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <style>
        input:checked ~ .dot {
            transform: translateX(100%);
        }
        input:checked ~ .block {
            background-color: #10b981; /* emerald-500 */
        }
    </style>
    <script>
        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('svg');
                const originalHTML = icon.outerHTML;
                
                // Ganti icon ke check
                icon.outerHTML = '<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                btn.classList.add('border-emerald-300', 'bg-emerald-50');
                
                setTimeout(() => {
                    btn.innerHTML = btn.innerHTML.replace(/<svg.*<\/svg>/, originalHTML);
                    btn.classList.remove('border-emerald-300', 'bg-emerald-50');
                }, 1500);
            });
        }
    </script>
    @endpush
</x-app-layout>
