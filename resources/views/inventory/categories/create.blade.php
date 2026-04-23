<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('inventory.categories') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Add New Category') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <h3 class="font-bold text-xl relative z-10">Category Details</h3>
                <p class="text-indigo-100 text-sm relative z-10">Organize your products with meaningful categories.</p>
            </div>

            <form action="{{ route('inventory.category.store') }}" method="POST" class="p-8 space-y-6">
                @csrf
                
                <div>
                    <x-input-label value="Category Name" />
                    <x-text-input name="name" :value="old('name')" required class="mt-1 w-full" placeholder="e.g. Active Equipment" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label value="Description (Optional)" />
                    <textarea name="description" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Describe what kind of items belong here...">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('inventory.categories') }}" class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-2.5 rounded-2xl font-bold transition-all shadow-lg shadow-indigo-600/20 transform hover:-translate-y-0.5">
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
