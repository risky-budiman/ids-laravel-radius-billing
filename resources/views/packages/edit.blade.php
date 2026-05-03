<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('packages.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    {{ __('Edit Package') }}: {{ $package->name }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Update plan configuration and bandwidth limits</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto pb-12">
        <form action="{{ route('packages.update', $package) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Main Config -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Section: General Information -->
                    <div class="bg-white dark:bg-gray-800/50 backdrop-blur-xl rounded-3xl p-8 shadow-sm border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl mr-4 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">General Information</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Plan Name (Internal)')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$package->name" required />
                            </div>
                            <div>
                                <x-input-label for="mikrotik_group" :value="__('MikroTik Profile / Group')" />
                                <x-text-input id="mikrotik_group" name="mikrotik_group" type="text" class="mt-1 block w-full" :value="$package->mikrotik_group" placeholder="e.g. pppoe-10M" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Public Description')" />
                                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900/50 rounded-2xl focus:ring-indigo-500 focus:border-indigo-500 text-gray-700 dark:text-gray-300 transition-all" placeholder="Tell your customers about this plan...">{{ $package->description }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Bandwidth Profile -->
                    <div class="bg-white dark:bg-gray-800/50 backdrop-blur-xl rounded-3xl p-8 shadow-sm border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center mb-6">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mr-4 text-blue-600 dark:text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Bandwidth Profile</h3>
                        </div>

                        <div class="space-y-8">
                            <!-- Basic Speed -->
                            <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-4">Base Speed (Rx/Tx)</span>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                        </span>
                                        <x-text-input id="upload_speed" name="upload_speed" type="text" class="block w-full pl-10" :value="$package->upload_speed" placeholder="Upload (e.g. 5M)" />
                                    </div>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                        </span>
                                        <x-text-input id="download_speed" name="download_speed" type="text" class="block w-full pl-10" :value="$package->download_speed" placeholder="Download (e.g. 10M)" />
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced QoS -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase flex items-center">
                                        <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span> Burst Configuration
                                    </h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <x-text-input name="burst_limit_up" placeholder="Limit Up" class="text-sm py-2" :value="$package->burst_limit_up" />
                                        <x-text-input name="burst_limit_down" placeholder="Limit Down" class="text-sm py-2" :value="$package->burst_limit_down" />
                                        <x-text-input name="burst_threshold_up" placeholder="Thresh Up" class="text-sm py-2" :value="$package->burst_threshold_up" />
                                        <x-text-input name="burst_threshold_down" placeholder="Thresh Down" class="text-sm py-2" :value="$package->burst_threshold_down" />
                                        <x-text-input name="burst_time_up" placeholder="Time Up (s)" class="text-sm py-2" :value="$package->burst_time_up" />
                                        <x-text-input name="burst_time_down" placeholder="Time Down (s)" class="text-sm py-2" :value="$package->burst_time_down" />
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase flex items-center">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span> Priority & CIR
                                    </h4>
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="priority" value="Traffic Priority (1-8)" />
                                            <select name="priority" id="priority" class="mt-1 block w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl text-sm focus:ring-indigo-500">
                                                @for($i=1; $i<=8; $i++)
                                                    <option value="{{ $i }}" {{ ($package->priority ?? 8) == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? '(Highest)' : ($i == 8 ? '(Lowest)' : '') }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <x-text-input name="limit_at_up" placeholder="Min Up (CIR)" class="text-sm py-2" :value="$package->limit_at_up" />
                                            <x-text-input name="limit_at_down" placeholder="Min Down (CIR)" class="text-sm py-2" :value="$package->limit_at_down" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sidebar (Pricing, FUP, Save) -->
                <div class="space-y-8">
                    <!-- Section: Pricing -->
                    <div class="bg-indigo-600 rounded-3xl p-8 text-white shadow-lg shadow-indigo-500/20">
                        <div class="flex items-center mb-6">
                            <div class="p-2 bg-white/10 rounded-xl mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-lg font-bold tracking-tight">Monthly Rate</h3>
                        </div>
                        <div class="relative">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 text-indigo-300 font-bold text-lg ml-4">Rp</span>
                            <input type="number" name="price" required value="{{ (int)$package->price }}" class="block w-full bg-white/10 border-none rounded-2xl py-4 pl-12 text-2xl font-black placeholder:text-indigo-300 focus:ring-2 focus:ring-white/30 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" placeholder="0">
                        </div>
                        <p class="text-xs text-indigo-200 mt-4 leading-relaxed">Tax calculations and prorata will be applied based on customer settings.</p>
                    </div>

                    <!-- Section: FUP Policy -->
                    @if(get_setting('enable_fup_module') == '1')
                    <div class="bg-white dark:bg-gray-800/50 backdrop-blur-xl rounded-3xl p-8 shadow-sm border border-gray-100 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-xl mr-3 text-amber-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white">FUP Policy</h3>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_fup" id="enable_fup" value="1" {{ $package->enable_fup ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
                            </label>
                        </div>
                        
                        <div id="fup_details" class="space-y-4 animate-fade-in {{ $package->enable_fup ? '' : 'hidden' }}">
                            <div>
                                <x-input-label value="Quota Limit (GB)" />
                                <x-text-input name="fup_limit_gb" type="number" class="mt-1 block w-full text-sm" :value="$package->fup_limit_gb" placeholder="e.g. 500" />
                            </div>
                            <div>
                                <x-input-label value="Throttled Speed" />
                                <x-text-input name="fup_speed_limit" type="text" class="mt-1 block w-full text-sm" :value="$package->fup_speed_limit" placeholder="e.g. 1M/2M" />
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-gray-900 dark:bg-indigo-600 hover:bg-black dark:hover:bg-indigo-700 text-white font-bold py-4 rounded-3xl shadow-xl transition-all hover:scale-[1.02] active:scale-[0.98]">
                            Update Service Plan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        @if(get_setting('enable_fup_module') == '1')
        document.getElementById('enable_fup').addEventListener('change', function() {
            const details = document.getElementById('fup_details');
            if (this.checked) {
                details.classList.remove('hidden');
                details.classList.add('animate-fade-in');
            } else {
                details.classList.add('hidden');
            }
        });
        @endif
    </script>

    <style>
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* Remove arrows/spinners from number inputs */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</x-app-layout>
