<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory: Products & Stock') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('inventory.stock-in') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-emerald-500/20">
                    + Stock In
                </a>
                <button onclick="document.getElementById('addProductModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-600/20">
                    + New Product
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Stock Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl"></div>
            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Total Items</p>
            <h3 class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ $totalItems }}</h3>
        </div>
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl"></div>
            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Low Stock Alerts</p>
            <h3 class="text-3xl font-black text-amber-500 mt-1">{{ $lowStockItems }}</h3>
        </div>
    </div>

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">Product Name</th>
                        <th class="px-6 py-4 font-bold">Category</th>
                        <th class="px-6 py-4 font-bold">SKU</th>
                        <th class="px-6 py-4 font-bold">Curr. Stock</th>
                        <th class="px-6 py-4 font-bold">Min. Stock</th>
                        <th class="px-6 py-4 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-xs text-gray-500">
                                        {{ substr($item->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $item->name }}</span>
                                        @if($item->track_serial)
                                            <span class="ml-2 px-1.5 py-0.5 bg-indigo-50 text-indigo-500 text-[8px] font-black rounded uppercase">SN Tracked</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-gray-500">
                                {{ $item->category->name }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-400 uppercase">
                                {{ $item->sku ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @php $stock = $item->stock_count; @endphp
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-black {{ $stock <= $item->min_stock ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                                        {{ $stock }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase">{{ $item->unit }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-gray-500">
                                {{ $item->min_stock }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('inventory.show', $item) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold">Details</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>

    <!-- Modal Add Product -->
    <div id="addProductModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg border border-white/20 overflow-hidden transform transition-all">
            <div class="bg-indigo-600 px-6 py-4 text-white">
                <h3 class="font-bold text-lg">Create New Product</h3>
            </div>
            <form action="{{ route('inventory.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label value="Category" />
                        <select name="category_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Product Name" />
                        <x-text-input name="name" required class="mt-1 w-full" placeholder="e.g. ONU XPON GM220" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="SKU (Optional)" />
                            <x-text-input name="sku" class="mt-1 w-full" placeholder="ONU-001" />
                        </div>
                        <div>
                            <x-input-label value="Unit" />
                            <select name="unit" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                                <option value="pcs">pcs</option>
                                <option value="meters">meters</option>
                                <option value="unit">unit</option>
                                <option value="roll">roll</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Min Stock Alert" />
                            <x-text-input name="min_stock" type="number" value="10" class="mt-1 w-full" />
                        </div>
                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="track_serial" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-xs font-bold text-gray-600 dark:text-gray-400">Track Serial Numbers (ONU/Router)</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
