<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('inventory.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Add New Product') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <h3 class="font-bold text-xl relative z-10">Product Information</h3>
                <p class="text-indigo-100 text-sm relative z-10">Register a new item in your inventory system.</p>
            </div>

            <form action="{{ route('inventory.store') }}" method="POST" class="p-8 space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label value="Product Category" />
                        <select name="category_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label value="Product Name" />
                        <x-text-input name="name" :value="old('name')" required class="mt-1 w-full" placeholder="e.g. ONU XPON GM220" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="SKU (Optional)" />
                        <x-text-input name="sku" :value="old('sku')" class="mt-1 w-full uppercase" placeholder="e.g. ONU-001" />
                        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Unit of Measurement" />
                        <select name="unit" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>pcs</option>
                            <option value="meters" {{ old('unit') == 'meters' ? 'selected' : '' }}>meters</option>
                            <option value="unit" {{ old('unit') == 'unit' ? 'selected' : '' }}>unit</option>
                            <option value="roll" {{ old('unit') == 'roll' ? 'selected' : '' }}>roll</option>
                        </select>
                        <x-input-error :messages="$errors->get('unit')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Minimum Stock Alert" />
                        <x-text-input name="min_stock" type="number" :value="old('min_stock', 10)" required class="mt-1 w-full" />
                        <p class="text-[10px] text-gray-400 mt-1">Get notified when stock drops below this value.</p>
                        <x-input-error :messages="$errors->get('min_stock')" class="mt-1" />
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="track_serial" value="1" {{ old('track_serial') ? 'checked' : '' }} class="rounded-lg border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                            <span class="ml-3 text-sm font-bold text-gray-700 dark:text-gray-300">Track Serial Numbers</span>
                        </label>
                    </div>
                </div>

                <div>
                    <x-input-label value="Description (Optional)" />
                    <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-2xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Technical specifications or notes...">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('inventory.index') }}" class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-2.5 rounded-2xl font-bold transition-all shadow-lg shadow-indigo-600/20 transform hover:-translate-y-0.5">
                        Create Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
