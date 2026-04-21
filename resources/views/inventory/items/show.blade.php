<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('inventory.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    Product Details: {{ $item->name }}
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('inventory.stock-in', ['item_id' => $item->id]) }}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-emerald-500/20">
                    Restock Item
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 mb-6 mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Category</p>
                        <p class="text-gray-900 dark:text-white font-bold">{{ $item->category->name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">SKU Number</p>
                        <p class="text-gray-700 dark:text-gray-300 font-mono text-sm uppercase">{{ $item->sku ?? 'NO-SKU' }}</p>
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Description</p>
                        <p class="text-gray-600 dark:text-gray-400 text-xs leading-relaxed mt-1">{{ $item->description ?? 'No description provided for this product.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Current Status Stats -->
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Current Inventory</p>
                <h3 class="text-5xl font-black text-indigo-600 mt-2">{{ $item->stock_count }}</h3>
                <p class="text-xs text-gray-500 font-bold uppercase mt-1">{{ $item->unit }} Available</p>
                
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-around">
                    <div>
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Min Stock</p>
                        <p class="font-bold text-gray-900 dark:text-white">{{ $item->min_stock }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-bold uppercase">Tracking</p>
                        <p class="font-bold {{ $item->track_serial ? 'text-indigo-500' : 'text-gray-500' }}">{{ $item->track_serial ? 'SERIAL' : 'QTY ONLY' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content (SNs or Movements) -->
        <div class="lg:col-span-2 space-y-6">
            
            @if($item->track_serial)
            <!-- Serial Number List -->
            <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex justify-between items-center">
                    <h3 class="font-black text-xs uppercase tracking-widest text-gray-800 dark:text-gray-200">Tracked Units (Serial Numbers)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] text-gray-400 uppercase font-bold border-b border-gray-100 dark:border-gray-700">
                                <th class="px-8 py-4">Serial Number / MAC</th>
                                <th class="px-8 py-4">Condition</th>
                                <th class="px-8 py-4">Status</th>
                                <th class="px-8 py-4">Installed At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800 text-xs">
                            @forelse($item->stocks as $stock)
                                <tr>
                                    <td class="px-8 py-4 font-mono font-bold text-gray-700 dark:text-gray-300">
                                        {{ $stock->serial_number }}
                                        @if($stock->mac_address)
                                            <p class="text-[9px] font-normal text-gray-400">{{ $stock->mac_address }}</p>
                                        @endif
                                    </td>
                                    <td class="px-8 py-4">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $stock->condition == 'new' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                            {{ strtoupper($stock->condition) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4">
                                        @if($stock->status == 'ready')
                                            <span class="text-emerald-500 font-bold">• READY</span>
                                        @elseif($stock->status == 'installed')
                                            <span class="text-indigo-500 font-bold">• INSTALLED</span>
                                        @else
                                            <span class="text-red-400 font-bold uppercase">• {{ $stock->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-4 text-gray-500">
                                        {{ $stock->customer ? $stock->customer->name : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-12 text-center text-gray-400 italic font-medium">No units tracked for this item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Movement Log -->
            <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="font-black text-xs uppercase tracking-widest text-gray-800 dark:text-gray-200">Stock Mutation History</h3>
                </div>
                <div class="px-8 py-6 space-y-6">
                    @forelse($item->movements as $movement)
                        <div class="flex items-start">
                            <div class="shrink-0 mt-1">
                                @if($movement->type == 'in')
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                    </div>
                                @else
                                    <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex justify-between">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        Stock {{ strtoupper($movement->type) }}: {{ number_format($movement->quantity, 0) }} {{ $item->unit }}
                                    </p>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase">{{ $movement->created_at->format('d M Y, H:i') }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    Reference: <span class="text-indigo-500 font-bold">{{ $movement->reference ?? 'Manual Adjustment' }}</span> 
                                    • By: {{ $movement->user->name }}
                                </p>
                                @if($movement->notes)
                                    <p class="text-xs text-gray-400 mt-2 italic bg-gray-50 dark:bg-gray-900/40 p-2 rounded-lg border border-gray-100 dark:border-gray-800">
                                        "{{ $movement->notes }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-gray-400 italic font-medium">No movement history for this item.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
