<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory: Categories') }}
            </h2>
            <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-600/20">
                + Add Category
            </button>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-100/80 border border-emerald-200 text-emerald-700 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden group">
                <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-indigo-500/5 rounded-full blur-xl group-hover:bg-indigo-500/10 transition-colors"></div>
                
                <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $category->name }}</h3>
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

    <!-- Modal Add Category -->
    <div id="addCategoryModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md border border-white/20 overflow-hidden transform transition-all">
            <div class="bg-indigo-600 px-6 py-4 text-white">
                <h3 class="font-bold text-lg">Add New Category</h3>
            </div>
            <form action="{{ route('inventory.category.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label value="Category Name" />
                    <x-text-input name="name" required class="mt-1 w-full" placeholder="e.g. Active Equipment" />
                </div>
                <div>
                    <x-input-label value="Description" />
                    <textarea name="description" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm" rows="3"></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
