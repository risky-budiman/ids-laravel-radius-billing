<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Inventory: Stock Out') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-rose-600 px-8 py-6 text-white">
                <h3 class="font-bold text-lg">Input Barang Keluar</h3>
                <p class="text-rose-100 text-xs mt-1">Catat pengeluaran barang untuk infrastruktur, pemasangan jalur, atau pemeliharaan.</p>
            </div>

            <form action="{{ route('inventory.stock-out.store') }}" method="POST" class="p-8 space-y-6" x-data="{ 
                selectedItem: '', 
                trackSerial: false, 
                quantity: 1,
                items: {{ $items->toJson() }},
                updateItem() {
                    let item = this.items.find(i => i.id == this.selectedItem);
                    this.trackSerial = item ? item.track_serial : false;
                    if(this.trackSerial) this.quantity = 0;
                }
            }">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Barang / Item" />
                        <select name="inventory_item_id" x-model="selectedItem" @change="updateItem()" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Pilih Barang</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (Stok: {{ $item->stock_count }} {{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Tujuan Pengeluaran" />
                        <select name="type" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="infrastructure">Infrastruktur Umum</option>
                            <option value="new_line">Pembangunan Jalur Baru</option>
                            <option value="maintenance">Maintenance / Perbaikan</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div x-show="!trackSerial">
                        <x-input-label value="Jumlah Keluar" />
                        <x-text-input type="number" name="quantity" x-model="quantity" min="1" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label value="Referensi Proyek / Jalur" />
                        <x-text-input name="reference" class="mt-1 w-full" placeholder="e.g. Proyek Jalur Merdeka" />
                    </div>
                </div>

                <!-- Serial Number Selection Section -->
                <div x-show="trackSerial" x-transition class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 space-y-4">
                    <p class="text-xs font-black text-rose-600 uppercase tracking-widest">Pilih Serial Number (SN)</p>
                    <p class="text-[10px] text-gray-500">Pilih satu atau lebih SN yang akan dikeluarkan dari gudang.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2">
                        @foreach($readyStocks as $itemId => $stocks)
                            <template x-if="selectedItem == {{ $itemId }}">
                                <div class="contents">
                                    @foreach($stocks as $stock)
                                        <label class="flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-rose-400 cursor-pointer transition-all">
                                            <input type="checkbox" name="stock_ids[]" value="{{ $stock->id }}" @change="quantity = document.querySelectorAll('input[name=\'stock_ids[]\']:checked').length" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                            <span class="ml-3 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $stock->serial_number }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </template>
                        @endforeach
                    </div>
                    <input type="hidden" name="quantity" x-model="quantity">
                    <p class="text-[10px] font-bold text-gray-500 uppercase">Item terpilih: <span class="text-rose-600" x-text="quantity"></span></p>
                </div>

                <div>
                    <x-input-label value="Catatan Tambahan" />
                    <textarea name="notes" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm" rows="2"></textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('inventory.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</a>
                    <button type="submit" class="bg-rose-500 hover:bg-rose-600 text-white px-10 py-2 rounded-xl text-sm font-bold shadow-lg shadow-rose-500/20 transition-all">Submit Stock Out</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
