<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Subscriber') }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('customers.store') }}" method="POST" class="p-8">
            @csrf

            <!-- Section: RADIUS Auth -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Network Access (RADIUS)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="customer_code_info" :value="__('Customer ID (Identity Number)')" />
                        <x-text-input id="customer_code_info" type="text" class="mt-1 block w-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-bold tracking-widest text-center border border-indigo-200 dark:border-indigo-800" value="[ 3 Digit Region ] + [ 3 Digit STO ] + [ 3 Digit STB ] + [ 3 Random ]" disabled />
                    </div>
                
                    <div>
                        <x-input-label for="username" :value="__('PPPoE / Hotspot Username')" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        <p class="mt-1 text-xs text-gray-500">This must exactly match the username dialed by the router.</p>
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" :value="old('password')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                </div>
            </div>

            <!-- Section: Billing Info -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscriber details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="name" :value="__('Full Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone (WhatsApp)')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <x-input-label for="region_code" :value="__('Region Code')" />
                        <select id="region_code" name="region_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">-- Select Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code') == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('region_code')" />
                    </div>
                    <div>
                        <x-input-label for="sto_code" :value="__('STO Code')" />
                        <select id="sto_code" name="sto_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm disabled:opacity-50" required disabled>
                            <option value="">-- Select STO --</option>
                            @foreach($stos as $s)
                                <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code') == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('sto_code')" />
                    </div>
                    <div>
                        <x-input-label for="stb_code" :value="__('STB Code')" />
                        <select id="stb_code" name="stb_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm disabled:opacity-50" required disabled>
                            <option value="">-- Select STB --</option>
                            @foreach($stbs as $t)
                                <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code') == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('stb_code')" />
                    </div>
                </div>

                <div class="mb-6">
                    <x-input-label for="email" :value="__('Email Address')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div class="mb-6">
                    <x-input-label for="address" :value="__('Installation Address')" />
                    <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('address')" />
                </div>
            </div>

            <!-- Section: Subscription -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscription Plan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="package_id" :value="__('Internet Package')" />
                        <select id="package_id" name="package_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">-- Select Package --</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                    {{ $package->name }} - Rp {{ number_format($package->price, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('package_id')" />
                        @if($packages->isEmpty())
                            <p class="mt-2 text-sm text-red-500">You need to create a Package first before assigning it.</p>
                        @endif
                    </div>

                    <div class="md:col-span-1 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 p-4 rounded-xl flex items-start">
                        <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-[11px] text-amber-700 dark:text-amber-500 leading-relaxed">
                            <strong>Note:</strong> Status akun akan menjadi <strong>Inactive</strong> secara otomatis. Aktivasi dan penginputan perangkat dilakukan melalui menu <strong>Details > Activate</strong> setelah pendaftaran selesai.
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('customers.index') }}" class="mr-4 text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                <x-primary-button>
                    {{ __('Save Subscriber') }}
                </x-primary-button>
            </div>
        </form>
    </div>
    <!-- CASCADING DROPDOWNS SCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const regionSelect = document.getElementById('region_code');
            const stoSelect = document.getElementById('sto_code');
            const stbSelect = document.getElementById('stb_code');

            function filterStos(preserveValue = false) {
                const selectedOption = regionSelect.options[regionSelect.selectedIndex];
                const regionId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                const currentValue = stoSelect.value;
                if(!preserveValue) stoSelect.value = '';
                
                if(regionId) {
                    stoSelect.disabled = false;
                    for(let i = 0; i < stoSelect.options.length; i++) {
                        const opt = stoSelect.options[i];
                        if(opt.value === "") continue; 
                        
                        if(opt.getAttribute('data-region-id') === regionId) {
                            opt.style.display = '';
                            opt.classList.remove('hidden');
                        } else {
                            opt.style.display = 'none';
                            opt.classList.add('hidden');
                        }
                    }
                } else {
                    stoSelect.disabled = true;
                }
                if(preserveValue) stoSelect.value = currentValue;
            }

            function filterStbs(preserveValue = false) {
                const selectedOption = stoSelect.options[stoSelect.selectedIndex];
                const stoId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                const currentValue = stbSelect.value;
                if(!preserveValue) stbSelect.value = '';

                if(stoId) {
                    stbSelect.disabled = false;
                    for(let i = 0; i < stbSelect.options.length; i++) {
                        const opt = stbSelect.options[i];
                        if(opt.value === "") continue;
                        
                        if(opt.getAttribute('data-sto-id') === stoId) {
                            opt.style.display = '';
                            opt.classList.remove('hidden');
                        } else {
                            opt.style.display = 'none';
                            opt.classList.add('hidden');
                        }
                    }
                } else {
                    stbSelect.disabled = true;
                }
                if(preserveValue) stbSelect.value = currentValue;
            }

            regionSelect.addEventListener('change', () => { filterStos(false); filterStbs(false); });
            stoSelect.addEventListener('change', () => filterStbs(false));

            if(regionSelect.value) { filterStos(true); }
            if(stoSelect.value) { filterStbs(true); }
        });
    </script>
</x-app-layout>
