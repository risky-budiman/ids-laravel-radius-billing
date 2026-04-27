<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
            Pengaturan Pajak Global (PPN)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <form action="{{ route('accounting.tax-settings.update') }}" method="POST">
                @csrf
                
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-8 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                        <h3 class="font-bold text-gray-900 dark:text-white">Aturan Penerapan Pajak</h3>
                        <p class="text-xs text-gray-500">Tentukan bagaimana PPN (11%) akan diterapkan pada tagihan pelanggan Anda.</p>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Mode All -->
                        <label class="relative flex items-start p-6 border-2 rounded-2xl cursor-pointer transition-all {{ $taxMode === 'all' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/10' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200' }}">
                            <input type="radio" name="tax_mode" value="all" class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-600" {{ $taxMode === 'all' ? 'checked' : '' }}>
                            <div class="ml-4">
                                <span class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Aktif Seluruhnya (Global ON)</span>
                                <span class="block text-xs text-gray-500 mt-1">Seluruh pelanggan tanpa pengecualian akan dikenakan PPN 11% pada setiap invoice bulanan.</span>
                            </div>
                        </label>

                        <!-- Mode Individual -->
                        <label class="relative flex items-start p-6 border-2 rounded-2xl cursor-pointer transition-all {{ $taxMode === 'individual' ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-900/10' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200' }}">
                            <input type="radio" name="tax_mode" value="individual" class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-600" {{ $taxMode === 'individual' ? 'checked' : '' }}>
                            <div class="ml-4">
                                <span class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Per-Pelanggan (Optional)</span>
                                <span class="block text-xs text-gray-500 mt-1">PPN hanya akan diterapkan jika opsi "Kenakan Pajak" diaktifkan pada profil masing-masing pelanggan.</span>
                            </div>
                        </label>
                    </div>

                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center text-xs text-amber-600 font-bold uppercase tracking-tighter">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Tarif PPN saat ini: {{ $taxRate }}%
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-8 p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl border border-indigo-100 dark:border-indigo-800/50">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-indigo-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-300">Catatan Penting</h4>
                        <p class="text-sm text-indigo-700 dark:text-indigo-400/80 leading-relaxed">
                            Pengubahan mode pajak akan berdampak pada <b>invoice yang akan datang</b>. Invoice yang sudah terbit (Existing Invoices) tidak akan terpengaruh oleh perubahan pengaturan ini.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
