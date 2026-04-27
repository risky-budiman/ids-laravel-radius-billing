<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Inventory: Stock In') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white">
                <h3 class="font-bold text-lg">Input Barang Masuk</h3>
                <p class="text-indigo-100 text-xs mt-1">Tambahkan stok baru dari hasil pembelian atau pengadaan.</p>
            </div>

            <form action="{{ route('inventory.stock-in.store') }}" method="POST" class="p-8 space-y-6" x-data="{ 
                trackSerial: false, 
                quantity: 1,
                updateProduct(e) {
                    const selected = e.target.options[e.target.selectedIndex];
                    this.trackSerial = selected.getAttribute('data-serial') === '1';
                }
            }">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Product / Item" />
                        <select name="inventory_item_id" required @change="updateProduct" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Pilih Barang</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-serial="{{ $item->track_serial }}">{{ $item->name }} ({{ $item->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Supplier (Vendor)" />
                        <select name="supplier_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Pilih Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Harga Beli Per Unit (Rp)" />
                        <x-text-input name="unit_price" type="number" class="mt-1 w-full" required placeholder="0" />
                    </div>
                    <div>
                        <x-input-label value="Pajak Pembelian (Tax Input)" />
                        <select name="tax_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Tanpa Pajak</option>
                            @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Quantity / Jumlah" />
                        <x-text-input name="quantity" type="number" x-model="quantity" min="1" class="mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label value="Reference / Invoice No." />
                        <x-text-input name="reference" class="mt-1 w-full" placeholder="e.g. INV/2026/001" />
                    </div>
                </div>

                <!-- Dynamic Serial Number Input Section -->
                <div x-show="trackSerial" x-transition class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 space-y-4">
                    <p class="text-xs font-black text-indigo-600 uppercase tracking-widest">Serial Number Tracking</p>
                    <p class="text-[10px] text-gray-500">Masukkan Serial Number untuk setiap unit barang yang masuk.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2">
                        <template x-for="i in parseInt(quantity)" :key="i">
                            <div>
                                <label class="text-[9px] text-gray-400 font-bold ml-1">SN Unit <span x-text="i"></span></label>
                                <input type="text" name="serials[]" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm text-xs p-2 uppercase" placeholder="Enter SN...">
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <x-input-label value="Notes" />
                    <textarea name="notes" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm" rows="2"></textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('inventory.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</a>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-10 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/20 transition-all">Submit Stock In</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
