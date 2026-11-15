<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Equipment Dismantle Wizard') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="bg-rose-600 px-8 py-6 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg">Penarikan Barang: {{ $customer->name }}</h3>
                    <p class="text-rose-100 text-xs mt-1">Konfirmasi perangkat yang ditarik dari lokasi pelanggan.</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold tracking-widest opacity-70">Status</p>
                    <p class="font-bold text-sm">DISMANTLE MODE</p>
                </div>
            </div>

            <form action="{{ route('customers.dismantle.store', $customer) }}" method="POST" class="p-8 space-y-6">
                @csrf
                
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-rose-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Daftar Perangkat Terpasang</h4>
                    </div>

                    @if($installedEquipment->count() > 0)
                        <div class="space-y-3">
                            @foreach($installedEquipment as $stock)
                                <label class="flex items-center p-4 bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-800 cursor-pointer hover:border-rose-300 transition-all group">
                                    <input type="checkbox" name="stock_ids[]" value="{{ $stock->id }}" checked class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500 w-5 h-5">
                                    <div class="ml-4 flex-1">
                                        <h5 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-rose-600 transition-colors">{{ $stock->item->name }}</h5>
                                        <p class="text-xs text-gray-400 font-mono italic">Serial Number: {{ $stock->serial_number }}</p>
                                    </div>
                                    {{-- Perangkat rusak masuk gudang tetapi tidak dihitung stok siap pakai --}}
                                    <span class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <input type="checkbox" id="broken_{{ $stock->id }}" name="broken_stock_ids[]" value="{{ $stock->id }}" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        <span onclick="event.preventDefault(); document.getElementById('broken_{{ $stock->id }}').click()">Kondisi rusak</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-xl">
                            <p class="text-sm text-gray-400 italic font-medium">Tidak ada perangkat tersinkronisasi yang terdeteksi di pelanggan ini.</p>
                        </div>
                    @endif
                </div>

                <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 p-4 rounded-xl flex items-start">
                    <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div class="text-xs text-amber-700 dark:text-amber-500 leading-relaxed font-medium">
                        <strong>Perhatian:</strong> Dengan memproses dismantle, status pelanggan akan berubah menjadi non-aktif dan perangkat yang dipilih akan kembali ke inventaris gudang dengan status <strong>Dicabut</strong>.
                    </div>
                </div>

                <div class="flex justify-end pt-4 space-x-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('customers.show', $customer) }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Batal</a>
                    <button type="submit" 
                        class="bg-rose-600 hover:bg-rose-700 text-white px-10 py-3 rounded-xl text-sm font-bold shadow-sm transition-all transform ">
                        Proses Penarikan Barang (Dismantle)
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
