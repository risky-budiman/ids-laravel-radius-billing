<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory: Categories') }}
            </h2>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('inventory.category.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-600/20">
                + Add Category
            </a>
            @endif
        </div>
    </x-slot>

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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-indigo-500/5 rounded-full blur-xl group-hover:bg-indigo-500/10 transition-colors pointer-events-none"></div>
                
                <div class="flex justify-between items-start relative z-10">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $category->name }}</h3>
                    @if(auth()->user()->isAdministrator())
                    <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity relative z-20">
                        <a href="{{ route('inventory.category.edit', $category) }}" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </a>
                        <form action="{{ route('inventory.category.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <p class="text-xs text-gray-500 mt-1 h-8 overflow-hidden">{{ $category->description ?? 'No description.' }}</p>
                
                <div class="mt-4 flex justify-between items-end">
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Total Products</span>
                        <p class="text-xl font-black text-indigo-600">{{ $category->items_count }}</p>
                    </div>
                    <a href="{{ route('inventory.index', ['category' => $category->id]) }}" class="text-[10px] font-bold text-gray-400 hover:text-indigo-600 transition-colors uppercase">View Items →</a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500 italic">No categories created yet.</div>
        @endforelse
    </div>
</x-app-layout>
