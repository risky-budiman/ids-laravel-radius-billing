<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Inventory: Stock In') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white">
                <h3 class="font-bold text-lg">Input Barang Masuk</h3>
                <p class="text-indigo-100 text-xs mt-1">Tambahkan stok baru dari hasil pembelian atau pengadaan.</p>
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

            <form action="{{ route('inventory.stock-in.store') }}" method="POST" class="p-8 space-y-6" x-data="{ 
                trackSerial: false, 
                quantity: 1,
                updateProduct(e) {
                    const sel = e.target.options[e.target.selectedIndex];
                    this.trackSerial = sel && sel.getAttribute('data-serial') === '1';
                },
                handleScan(sn) {
                    const inputs = document.querySelectorAll('input[name=\'serials[]\']');
                    for (let input of inputs) {
                        if (!input.value) {
                            input.value = sn;
                            input.dispatchEvent(new Event('input'));
                            input.dispatchEvent(new Event('change'));
                            return;
                        }
                    }
                    alert('Semua slot SN sudah terisi.');
                }
            }" @scan-completed.window="handleScan($event.detail)">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Barang" />
                        <select name="inventory_item_id" required @change="updateProduct" class="mt-1 input">
                            <option value="">Pilih Barang</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-serial="{{ $item->track_serial }}">{{ $item->name }} ({{ $item->sku }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-data="{ isExisting: false }">
                        <div class="flex items-center mt-6">
                            <input type="checkbox" name="is_existing" value="1" x-model="isExisting" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label class="ml-2 text-sm text-gray-600 dark:text-gray-400">Barang sudah ada di pelanggan?</label>
                        </div>
                        
                        <div x-show="isExisting" x-transition class="mt-4">
                            <x-input-label value="Pilih Pelanggan" />
                            <x-customer-picker name="customer_id" :value="old('customer_id')" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Supplier (Vendor) / Pengadaan" />
                        <select name="supplier_id" class="mt-1 input">
                            <option value="">Pilih Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Referensi / No. Faktur" />
                        <x-text-input name="reference" class="mt-1 w-full" placeholder="mis. INV/2026/001" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Harga Beli Per Unit (Rp)" />
                        <x-text-input name="unit_price" type="number" step="any" min="0" class="mt-1 w-full" :value="old('unit_price', 0)" placeholder="0" />
                    </div>
                    <div>
                        <x-input-label value="Pajak Pembelian (Masukan)" />
                        <select name="tax_id" class="mt-1 input">
                            <option value="">Tanpa Pajak</option>
                            @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ source: '{{ old('payment_source', 'payable') }}' }">
                    <div>
                        <x-input-label value="Dibayar dari" />
                        <select name="payment_source" x-model="source" class="mt-1 input">
                            <option value="payable">Hutang supplier (bayar belakangan)</option>
                            <option value="bank">Kas / rekening (sudah dibayar)</option>
                            <option value="none">Tanpa pembelian (stok awal / hibah)</option>
                        </select>
                        <p class="help-text">Kas/rekening: saldo rekening berkurang & tercatat di Kas & Bank. Diabaikan untuk barang yang sudah ada di pelanggan.</p>
                    </div>
                    <div x-show="source === 'bank'" x-cloak>
                        <x-input-label value="Rekening pembayaran" />
                        <select name="bank_account_id" class="mt-1 input" :required="source === 'bank'">
                            <option value="">Pilih rekening</option>
                            @foreach(\App\Models\BankAccount::where('is_active', true)->orderBy('bank_name')->get() as $acc)
                                <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>{{ $acc->bank_name }} — {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Quantity / Jumlah" />
                        <x-text-input name="quantity" type="number" x-model="quantity" min="1" class="mt-1 w-full" required />
                    </div>
                    <div class="flex items-center pt-6">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs text-gray-500 italic">Gunakan nominal 0 jika barang adalah aset lama atau hibah.</p>
                    </div>
                </div>

                <div x-show="!trackSerial && quantity > 0" class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-gray-500">Barang ini tidak menggunakan pelacakan Serial Number (Consumables).</p>
                </div>

                <!-- Dynamic Serial Number Input Section -->
                <div x-show="trackSerial" x-transition class="bg-indigo-50/50 dark:bg-indigo-900/10 p-6 rounded-xl border border-dashed border-indigo-200 dark:border-indigo-800 space-y-4">
                    <p class="text-xs font-semibold text-indigo-600 ">Pelacakan Nomor Seri</p>
                    <div class="flex justify-between items-center">
                        <p class="text-xs text-gray-500">Masukkan Serial Number untuk setiap unit barang yang masuk.</p>
                        <x-barcode-scanner />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-2">
                        <template x-for="i in (parseInt(quantity) || 0)" :key="i">
                            <div class="animate-in fade-in slide-in- duration-300">
                                <label class="text-xs text-gray-400 font-bold ml-1">SN Unit <span x-text="i"></span></label>
                                <input type="text" name="serials[]" required class="uppercase input" placeholder="Masukkan SN...">
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <x-input-label value="Catatan" />
                    <textarea name="notes" class="mt-1 input" rows="2"></textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('inventory.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Batal</a>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-10 py-2 rounded-xl text-sm font-bold shadow-sm transition-all">Simpan Barang Masuk</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
