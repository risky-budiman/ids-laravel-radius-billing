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
                <div class="md:col-span-2">
                    <x-input-label for="name" :value="__('Package Name (Groupname)')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required placeholder="e.g. Bronze_5M" />
                </div>

                <div class="md:col-span-2 py-2 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wider">Bandwidth Limitation (MikroTik PPPoE)</h3>
                    <p class="text-xs text-gray-500 mt-1">Format: 5M, 512k, etc.</p>
                </div>

                <!-- Rate Limit -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Rate Limit (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="upload_speed" name="upload_speed" type="text" class="block w-full" placeholder="Upload (Rx) e.g. 1M" />
                        <x-text-input id="download_speed" name="download_speed" type="text" class="block w-full" placeholder="Download (Tx) e.g. 5M" />
                    </div>
                </div>

                <!-- Burst Limit -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Limit (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_limit_up" name="burst_limit_up" type="text" class="block w-full" placeholder="Burst Up e.g. 2M" />
                        <x-text-input id="burst_limit_down" name="burst_limit_down" type="text" class="block w-full" placeholder="Burst Down e.g. 10M" />
                    </div>
                </div>

                <!-- Burst Threshold -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Threshold (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_threshold_up" name="burst_threshold_up" type="text" class="block w-full" placeholder="Threshold Up" />
                        <x-text-input id="burst_threshold_down" name="burst_threshold_down" type="text" class="block w-full" placeholder="Threshold Down" />
                    </div>
                </div>

                <!-- Burst Time -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Burst Time (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="burst_time_up" name="burst_time_up" type="text" class="block w-full" placeholder="Time Up (sec)" />
                        <x-text-input id="burst_time_down" name="burst_time_down" type="text" class="block w-full" placeholder="Time Down (sec)" />
                    </div>
                </div>

                <!-- Limit At -->
                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase">Limit At (Upload / Download)</span>
                    <div class="grid grid-cols-2 gap-4 mt-1">
                        <x-text-input id="limit_at_up" name="limit_at_up" type="text" class="block w-full" placeholder="Min Up" />
                        <x-text-input id="limit_at_down" name="limit_at_down" type="text" class="block w-full" placeholder="Min Down" />
                    </div>
                </div>

                <div class="mt-2">
                    <x-input-label for="priority" :value="__('Priority (1-8)')" />
                    <x-text-input id="priority" name="priority" type="number" min="1" max="8" class="mt-1 block w-full" value="8" />
                </div>

                @if(get_setting('enable_fup_module') == '1')
                <div class="md:col-span-2 py-2 border-b border-gray-100 dark:border-gray-700 mt-4">
                    <h3 class="text-sm font-bold text-red-600 uppercase tracking-wider">FUP & Quota Management</h3>
                    <p class="text-xs text-gray-500 mt-1">Automatic speed reduction after quota reached.</p>
                </div>

                <div class="md:col-span-2 flex items-center bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl">
                    <input type="checkbox" id="enable_fup" name="enable_fup" value="1" class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <label for="enable_fup" class="ml-3 font-bold text-gray-700 dark:text-gray-300">Aktifkan Kebijakan FUP</label>
                </div>

                <div id="fup_details" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-xl hidden">
                    <div>
                        <x-input-label for="fup_limit_gb" :value="__('Quota Limit (GB)')" />
                        <x-text-input id="fup_limit_gb" name="fup_limit_gb" type="number" class="mt-1 block w-full" placeholder="e.g. 500" />
                    </div>
                    <div>
                        <x-input-label for="fup_speed_limit" :value="__('Post-FUP Speed (Up/Down)')" />
                        <x-text-input id="fup_speed_limit" name="fup_speed_limit" type="text" class="mt-1 block w-full" placeholder="e.g. 1M/2M" />
                    </div>
                </div>

                <script>
                    document.getElementById('enable_fup').addEventListener('change', function() {
                        const details = document.getElementById('fup_details');
                        if (this.checked) {
                            details.classList.remove('hidden');
                        } else {
                            details.classList.add('hidden');
                        }
                    });
                </script>
                @endif

                <div class="md:col-span-2 pt-4 border-t border-gray-100 dark:border-gray-700 mt-4">
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
