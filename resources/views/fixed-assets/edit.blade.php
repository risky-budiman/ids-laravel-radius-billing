<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('fixed-assets.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Edit Fixed Asset: {{ $fixedAsset->asset_code }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6">
        <form action="{{ route('fixed-assets.update', $fixedAsset) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Edit Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="name" value="Asset Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ $fixedAsset->name }}" required />
                    </div>

                    <div>
                        <x-input-label for="useful_life_months" value="Useful Life (Months)" />
                        <x-text-input id="useful_life_months" name="useful_life_months" type="number" min="1" class="mt-1 block w-full" value="{{ $fixedAsset->useful_life_months }}" required />
                    </div>

                    <div>
                        <x-input-label for="salvage_value" value="Salvage Value (Rp)" />
                        <x-text-input id="salvage_value" name="salvage_value" type="number" min="0" class="mt-1 block w-full" value="{{ (int)$fixedAsset->salvage_value }}" required />
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="active" {{ $fixedAsset->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="disposed" {{ $fixedAsset->status === 'disposed' ? 'selected' : '' }}>Disposed / Sold</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="description" value="Notes" />
                        <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="3">{{ $fixedAsset->description }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('fixed-assets.index') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">
                    Update Asset
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
