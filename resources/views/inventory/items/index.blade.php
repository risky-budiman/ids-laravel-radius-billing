<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory: Products & Stock') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('inventory.stock-in') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm">
                    + Barang Masuk
                </a>
                <a href="{{ route('inventory.stock-out') }}" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm">
                    - Barang Keluar
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('inventory.outflow') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold transition-all">
                    Audit Log
                </a>
                <a href="{{ route('inventory.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm">
                    + Barang Baru
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Stock Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl"></div>
            <p class="text-xs font-bold text-gray-400 tracking-wider">Total Barang</p>
            <h3 class="text-3xl font-semibold text-gray-900 dark:text-white mt-1">{{ $totalItems }}</h3>
        </div>
        <div class="glass bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl"></div>
            <p class="text-xs font-bold text-gray-400 tracking-wider">Peringatan Stok Menipis</p>
            <h3 class="text-3xl font-semibold text-amber-500 mt-1">{{ $lowStockItems }}</h3>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-100/80 border border-emerald-200 text-emerald-700 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-100/80 border border-red-200 text-red-700 rounded-xl font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="card overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">#</th>
                        <th class="px-6 py-4 font-bold">Nama Barang</th>
                        <th class="px-6 py-4 font-bold">Kategori</th>
                        <th class="px-6 py-4 font-bold">SKU</th>
                        <th class="px-6 py-4 font-bold">Stok Kini</th>
                        <th class="px-6 py-4 font-bold">Stok Min.</th>
                        <th class="px-6 py-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30 transition-colors group">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $items->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-xs text-gray-500">
                                        {{ substr($item->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $item->name }}</span>
                                        @if($item->track_serial)
                                            <span class="ml-2 px-1.5 py-0.5 bg-indigo-50 text-indigo-500 text-xs font-semibold rounded">SN Tracked</span>
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
                                    <span class="text-sm font-semibold {{ $stock <= $item->min_stock ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                                        {{ $stock }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $item->unit }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-gray-500">
                                {{ $item->min_stock }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('inventory.show', $item) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold" title="Lihat Rincian">Rincian</a>
                                    
                                    @if(auth()->user()->isAdministrator())
                                        <a href="{{ route('inventory.edit', $item) }}" class="text-amber-500 hover:text-amber-700 text-xs font-bold" title="Ubah Barang">Ubah</a>
                                        
                                        <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this product?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold" title="Hapus Barang">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">Belum ada barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $items->links() }}
</x-app-layout>
