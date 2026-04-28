<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Fixed Assets') }}
            </h2>
            <a href="{{ route('fixed-assets.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Register Asset
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-100/80 border border-red-200 text-red-700 rounded-xl dark:bg-red-900/30 dark:border-red-800 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Asset Code</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase Value</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Book Value</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Useful Life</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">
                                {{ $asset->asset_code }}
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">
                                {{ $asset->name }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 font-black text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($asset->net_book_value, 0, ',', '.') }}
                                <div class="text-[10px] text-gray-400 font-normal">Depreciated: Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $asset->useful_life_months }} months
                            </td>
                            <td class="px-6 py-4">
                                @if($asset->status === 'active')
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase tracking-widest">Active</span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-[10px] font-black uppercase tracking-widest">Disposed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('fixed-assets.edit', $asset) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium mr-3">Edit</a>
                                <form action="{{ route('fixed-assets.destroy', $asset) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No Fixed Assets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $assets->links() }}
        </div>
    </div>
</x-app-layout>
