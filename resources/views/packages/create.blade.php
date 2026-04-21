<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add Package') }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('packages.store') }}" method="POST" class="p-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <x-input-label for="name" :value="__('Package Name (Groupname)')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="type" :value="__('Type')" />
                    <select id="type" name="type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                        <option value="pppoe">PPPoE</option>
                        <option value="hotspot">Hotspot</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="download_speed" :value="__('Download Speed (Mbps)')" />
                    <x-text-input id="download_speed" name="download_speed" type="number" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="upload_speed" :value="__('Upload Speed (Mbps)')" />
                    <x-text-input id="upload_speed" name="upload_speed" type="number" class="mt-1 block w-full" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="price" :value="__('Monthly Price (Rp)')" />
                    <x-text-input id="price" name="price" type="number" class="mt-1 block w-full" required />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="description" :value="__('Description / Notes')" />
                    <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="3"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('packages.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Save Package</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
