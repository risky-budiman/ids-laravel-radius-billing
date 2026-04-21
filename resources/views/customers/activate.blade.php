<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Customer Activation Wizard') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg">Proses Aktivasi: {{ $customer->name }}</h3>
                    <p class="text-indigo-100 text-xs mt-1">Lengkapi data pemasangan perangkat untuk mengaktifkan pelanggan.</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-70">Customer ID</p>
                    <p class="font-mono font-bold">{{ $customer->customer_code }}</p>
                </div>
            </div>

            <form action="{{ route('customers.activate.store', $customer) }}" method="POST" class="p-8 space-y-8" x-data="{ consumables: [] }">
                @csrf
                
                <!-- Main Equipment Selection -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Perangkat Utama (ONU/Modem)</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label value="Pilih Perangkat" />
                            <select name="modem_stock_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                                <option value="">-- Pilih SN Modem Ready --</option>
                                @foreach($serialItems as $item)
                                    <optgroup label="{{ $item->name }}">
                                        @foreach($item->stocks as $stock)
                                            <option value="{{ $stock->id }}">SN: {{ $stock->serial_number }} ({{ strtoupper($stock->condition) }})</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-2 italic">Hanya menampilkan modem dengan status 'Ready' di gudang.</p>
                        </div>
                    </div>
                </div>

                <!-- Consumables / Other Materials -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Material Tambahan (Kabel/Konektor)</h4>
                        </div>
                        <button type="button" @click="consumables.push({ id: '', qty: 0 })" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg hover:bg-indigo-100 transition-colors">
                            + Tambah Material
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(consumable, index) in consumables" :key="index">
                            <div class="grid grid-cols-12 gap-4 items-end bg-gray-50/50 dark:bg-gray-900/30 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                                <div class="col-span-12 md:col-span-7">
                                    <x-input-label value="Pilih Material" />
                                    <select :name="'consumables['+index+'][item_id]'" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm p-2">
                                        <option value="">-- Pilih --</option>
                                        @foreach($consumableItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }} (Stok: {{ $item->stock_count }} {{ $item->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-8 md:col-span-4">
                                    <x-input-label value="Jumlah Terpakai" />
                                    <x-text-input type="number" step="0.1" ::name="'consumables['+index+'][quantity]'" class="mt-1 w-full bg-white dark:bg-gray-800" placeholder="Qty..." />
                                </div>
                                <div class="col-span-4 md:col-span-1 flex justify-center">
                                    <button type="button" @click="consumables.splice(index, 1)" class="text-red-500 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="consumables.length === 0" class="text-center py-8 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
                            <p class="text-xs text-gray-400 italic font-medium">Klik tombol tambah material untuk mencatat kabel atau konektor yang terpakai.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 space-x-3">
                    <a href="{{ route('customers.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-indigo-600/20 transition-all transform hover:-translate-y-0.5">
                        Aktifkan Pelanggan & Simpan Inventaris
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
