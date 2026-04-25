<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Package') }}: {{ $package->name }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('packages.update', $package) }}" method="POST" class="p-8">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <x-input-label for="name" :value="__('Package Name (Groupname)')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$package->name" required />
                </div>

                <div class="md:col-span-2 py-2 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wider">Bandwidth Limitation (MikroTik PPPoE)</h3>
                    <p class="text-xs text-gray-500 mt-1">Format: 5M, 512k, etc.</p>
                </div>

                <!-- Rate Limit -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Rate Limit (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="upload_speed" name="upload_speed" type="text" class="block w-full" :value="$package->upload_speed" placeholder="Upload (Rx)" />
                        <x-text-input id="download_speed" name="download_speed" type="text" class="block w-full" :value="$package->download_speed" placeholder="Download (Tx)" />
                    </div>
                </div>

                <!-- Burst Limit -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Limit (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_limit_up" name="burst_limit_up" type="text" class="block w-full" :value="$package->burst_limit_up" placeholder="Burst Up" />
                        <x-text-input id="burst_limit_down" name="burst_limit_down" type="text" class="block w-full" :value="$package->burst_limit_down" placeholder="Burst Down" />
                    </div>
                </div>

                <!-- Burst Threshold -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Threshold (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_threshold_up" name="burst_threshold_up" type="text" class="block w-full" :value="$package->burst_threshold_up" placeholder="Threshold Up" />
                        <x-text-input id="burst_threshold_down" name="burst_threshold_down" type="text" class="block w-full" :value="$package->burst_threshold_down" placeholder="Threshold Down" />
                    </div>
                </div>

                <!-- Burst Time -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Time (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_time_up" name="burst_time_up" type="text" class="block w-full" :value="$package->burst_time_up" placeholder="Time Up (sec)" />
                        <x-text-input id="burst_time_down" name="burst_time_down" type="text" class="block w-full" :value="$package->burst_time_down" placeholder="Time Down (sec)" />
                    </div>
                </div>

                <!-- Limit At -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Limit At (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="limit_at_up" name="limit_at_up" type="text" class="block w-full" :value="$package->limit_at_up" placeholder="Min Up" />
                        <x-text-input id="limit_at_down" name="limit_at_down" type="text" class="block w-full" :value="$package->limit_at_down" placeholder="Min Down" />
                    </div>
                </div>

                <div class="mt-2">
                    <x-input-label for="priority" :value="__('Priority (1-8)')" />
                    <x-text-input id="priority" name="priority" type="number" min="1" max="8" class="mt-1 block w-full" :value="$package->priority ?? 8" />
                </div>

                <div class="md:col-span-2 pt-4 border-t border-gray-100 dark:border-gray-700 mt-4">
                    <x-input-label for="price" :value="__('Monthly Price (Rp)')" />
                    <x-text-input id="price" name="price" type="number" class="mt-1 block w-full" :value="(int)$package->price" required />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="description" :value="__('Description / Notes')" />
                    <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="3">{{ $package->description }}</textarea>
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('packages.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Update Package</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
