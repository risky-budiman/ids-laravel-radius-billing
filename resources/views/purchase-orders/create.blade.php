<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('purchase-orders.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Create Purchase Order
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6">
        <form action="{{ route('purchase-orders.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">PO Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="supplier_id" value="Supplier" />
                        <select name="supplier_id" id="supplier_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                            <option value="">Select Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="date" value="Date" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" value="{{ date('Y-m-d') }}" required />
                    </div>
                </div>
            </div>

            <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-sm border border-gray-100 dark:border-gray-700" x-data="{ items: [{id: Date.now(), inventory_item_id: '', quantity: 1, unit_price: 0}] }">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Items</h3>
                    <button type="button" @click="items.push({id: Date.now(), inventory_item_id: '', quantity: 1, unit_price: 0})" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-colors">
                        + Add Item
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex-1">
                                <select x-bind:name="`items[${index}][inventory_item_id]`" x-model="item.inventory_item_id" class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Item...</option>
                                    @foreach($inventoryItems as $inv)
                                        <option value="{{ $inv->id }}">{{ $inv->name }} ({{ $inv->category->name ?? 'Uncategorized' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-24">
                                <input type="number" x-bind:name="`items[${index}][quantity]`" x-model="item.quantity" min="1" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm text-center" placeholder="Qty" required />
                            </div>
                            <div class="w-40">
                                <input type="number" x-bind:name="`items[${index}][unit_price]`" x-model="item.unit_price" min="0" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm" placeholder="Unit Price" required />
                            </div>
                            <button type="button" @click="items.splice(index, 1)" class="text-red-400 hover:text-red-600 transition-colors p-2" x-show="items.length > 1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('purchase-orders.index') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">
                    Save & Receive
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
