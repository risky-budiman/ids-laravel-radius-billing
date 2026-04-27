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

            @if($errors->any())
                <div class="px-8 py-4 bg-red-50 border-b border-red-100">
                    <ul class="list-disc list-inside text-sm text-red-600 font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('inventory.stock-out.store') }}" method="POST" class="p-8 space-y-6" x-data="{ 
                selectedItem: '', 
                trackSerial: false, 
                quantity: 1,
                isSubmitting: false,
                init() {
                    this.updateItem();
                },
                items: {{ $items->toJson() }},
                updateItem() {
                    let item = this.items.find(i => i.id == this.selectedItem);
                    this.trackSerial = item ? item.track_serial : false;
                    if(this.trackSerial) this.quantity = 0;
                },
                handleScan(sn) {
                    const snLabels = Array.from(document.querySelectorAll('.sn-item-label'));
                    const cleanSn = sn.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                    const targetLabel = snLabels.find(l => {
                        const span = l.querySelector('.sn-text');
                        return span && span.innerText.trim().replace(/[^a-zA-Z0-9]/g, '').toUpperCase() === cleanSn;
                    });
                    
                    if (targetLabel) {
                        const cb = targetLabel.querySelector('input[type=checkbox]');
                        if (cb) {
                            if (!cb.checked) {
                                cb.checked = true;
                                cb.dispatchEvent(new Event('change'));
                                // Feedback Visual
                                targetLabel.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20');
                                setTimeout(() => targetLabel.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20'), 2000);
                            } else {
                                // Already checked, just flash
                                targetLabel.classList.add('ring-2', 'ring-amber-500');
                                setTimeout(() => targetLabel.classList.remove('ring-2', 'ring-amber-500'), 1000);
                            }
                        }
                    } else {
                        alert('Serial Number ' + sn + ' tidak ditemukan di stok barang ini.');
                    }
                }
            }" @scan-completed.window="handleScan($event.detail)">
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

                <div x-show="!trackSerial && selectedItem" class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-2xl border border-gray-100 dark:border-gray-800 flex items-center">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-gray-500">Barang ini tidak menggunakan pelacakan Serial Number (Consumables).</p>
                </div>

                <!-- Serial Number Selection Section -->
                <div x-show="trackSerial" x-transition class="bg-rose-50/50 dark:bg-rose-900/10 p-6 rounded-2xl border border-dashed border-rose-200 dark:border-rose-800 space-y-4">
                    <p class="text-xs font-black text-rose-600 uppercase tracking-widest">Pilih Serial Number (SN)</p>
                    <div class="flex justify-between items-center">
                        <p class="text-[10px] text-gray-500">Pilih satu atau lebih SN yang akan dikeluarkan dari gudang.</p>
                        <x-barcode-scanner />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2">
                        @foreach($readyStocks as $itemId => $stocks)
                            <template x-if="selectedItem == {{ $itemId }}">
                                <div class="contents">
                                    @foreach($stocks as $stock)
                                        <label class="sn-item-label flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-rose-400 cursor-pointer transition-all">
                                            <input type="checkbox" name="stock_ids[]" value="{{ $stock->id }}" @change="quantity = document.querySelectorAll('input[name=\'stock_ids[]\']:checked').length" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                            <span class="sn-text ml-3 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $stock->serial_number }}</span>
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
                    <button type="submit" :disabled="isSubmitting" @click="isSubmitting = true" class="bg-rose-500 hover:bg-rose-600 disabled:bg-gray-400 text-white px-10 py-2 rounded-xl text-sm font-bold shadow-lg shadow-rose-500/20 transition-all flex items-center">
                        <template x-if="isSubmitting">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </template>
                        <span x-text="isSubmitting ? 'Processing...' : 'Submit Stock Out'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
