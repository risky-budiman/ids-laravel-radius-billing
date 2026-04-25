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
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Network Access (RADIUS)</h3>
                    <div class="flex items-center">
                        <input type="checkbox" id="auto_generate" name="auto_generate" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                        <label for="auto_generate" class="ml-2 text-sm font-bold text-indigo-600 uppercase tracking-wider cursor-pointer">Automatic Generation</label>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="customer_code" :value="__('Customer ID (Identity Number)')" />
                        <x-text-input id="customer_code" name="customer_code" type="text" class="mt-1 block w-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-bold tracking-widest text-center border border-indigo-200 dark:border-indigo-800" :value="old('customer_code')" placeholder="Select location or type manually" />
                        <x-input-error class="mt-2" :messages="$errors->get('customer_code')" />
                    </div>
                
                    <div>
                        <x-input-label for="username" :value="__('PPPoE / Hotspot Username')" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        <p class="mt-1 text-xs text-gray-500" id="username_hint">Auto-generated based on Customer ID if Auto is checked.</p>
                    </div>

                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <x-input-label for="password" :value="__('Password')" />
                            <button type="button" id="regen_password" class="text-indigo-600 text-[10px] font-black uppercase hover:text-indigo-800 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Regenerate
                            </button>
                        </div>
                        <x-text-input id="password" name="password" type="text" class="block w-full font-mono" :value="old('password')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        <p class="mt-1 text-[10px] text-gray-400 italic">Auto-generated password for security.</p>
                    </div>
                </div>
            </div>

            <!-- Section: Billing Info -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscriber details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="ktp" :value="__('Nomor KTP (NIK)')" />
                        <x-text-input id="ktp" name="ktp" type="text" class="mt-1 block w-full" :value="old('ktp')" placeholder="16 Digit NIK" />
                        <x-input-error class="mt-2" :messages="$errors->get('ktp')" />
                    </div>

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

            <!-- Section: Billing Configuration -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Billing Configuration</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="billing_type" :value="__('Billing Type')" />
                        <select id="billing_type" name="billing_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="postpaid" {{ old('billing_type') == 'postpaid' ? 'selected' : '' }}>Pasca Bayar (Postpaid)</option>
                            <option value="prepaid" {{ old('billing_type') == 'prepaid' ? 'selected' : '' }}>Prabayar (Prepaid)</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('billing_type')" />
                    </div>

                    <div>
                        <x-input-label for="billing_method" :value="__('Billing Method')" />
                        <select id="billing_method" name="billing_method" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <!-- Options will be populated by JS -->
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('billing_method')" />
                    </div>

                    <div id="billing_cycle_dates" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 hidden">
                        <div>
                            <x-input-label for="billing_day" :value="__('Generate Bill Day (1-28)')" />
                            <x-text-input id="billing_day" name="billing_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_day', 1)" />
                            <p class="mt-1 text-[10px] text-gray-500 italic">Day of the month to generate invoice (Default: 1st)</p>
                        </div>
                        <div>
                            <x-input-label for="billing_due_day" :value="__('Due Date Day (1-28)')" />
                            <x-text-input id="billing_due_day" name="billing_due_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_due_day', 20)" />
                            <p class="mt-1 text-[10px] text-gray-500 italic">Day of the month to suspend if unpaid (Default: 20th)</p>
                        </div>
                    </div>
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

                    <div id="billing_info_box" class="md:col-span-1 border p-4 rounded-xl flex items-start">
                        <svg class="w-5 h-5 text-indigo-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div id="method_description" class="text-[11px] leading-relaxed">
                            Pilih metode billing untuk melihat detail aturan penagihan.
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

            regionSelect.addEventListener('change', () => { filterStos(false); filterStbs(false); updateAutoFields(); });
            stoSelect.addEventListener('change', () => { filterStbs(false); updateAutoFields(); });
            stbSelect.addEventListener('change', updateAutoFields);

            if(regionSelect.value) { filterStos(true); }
            if(stoSelect.value) { filterStbs(true); }

            // Auto-generation Logic
            const autoGenerate = document.getElementById('auto_generate');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const customerCodeInput = document.getElementById('customer_code');
            const regenPasswordBtn = document.getElementById('regen_password');
            const companySuffix = "{{ \App\Models\Setting::where('key', 'company_domain')->first()->value ?? 'net.id' }}";

            function generateRandomPassword(length = 8) {
                const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                let retVal = "";
                for (let i = 0, n = charset.length; i < length; ++i) {
                    retVal += charset.charAt(Math.floor(Math.random() * n));
                }
                return retVal;
            }

            let persistentRandomPart = Math.floor(100 + Math.random() * 900);

            function updateAutoFields() {
                if (!autoGenerate.checked) return;

                const region = regionSelect.value || '000';
                const sto = stoSelect.value || '000';
                const stb = stbSelect.value || '000';
                
                if (region !== '000' && sto !== '000' && stb !== '000') {
                    const fullCode = `${region}${sto}${stb}${persistentRandomPart}`;
                    customerCodeInput.value = fullCode;
                    usernameInput.value = `${fullCode}@${companySuffix}`;
                }
            }

            autoGenerate.addEventListener('change', function() {
                if (this.checked) {
                    // Lock fields for Auto
                    usernameInput.setAttribute('readonly', true);
                    customerCodeInput.setAttribute('readonly', true);
                    passwordInput.setAttribute('readonly', true);
                    usernameInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.classList.add('bg-indigo-50', 'cursor-not-allowed', 'opacity-75');
                    passwordInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    updateAutoFields();
                    if (!passwordInput.value) passwordInput.value = generateRandomPassword();
                } else {
                    // Unlock fields for Manual
                    usernameInput.removeAttribute('readonly');
                    customerCodeInput.removeAttribute('readonly');
                    passwordInput.removeAttribute('readonly');
                    usernameInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.classList.remove('bg-indigo-50', 'cursor-not-allowed', 'opacity-75');
                    passwordInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.placeholder = "Enter ID manually...";
                }
            });

            // Listen for manual changes in customer_code to update username if in auto mode (though it should be locked)
            customerCodeInput.addEventListener('input', function() {
                if (autoGenerate.checked) {
                    usernameInput.value = `${this.value}@${companySuffix}`;
                }
            });

            regenPasswordBtn.addEventListener('click', () => {
                passwordInput.value = generateRandomPassword();
            });

            // Initial call based on checkbox state
            if (autoGenerate.checked) {
                usernameInput.setAttribute('readonly', true);
                customerCodeInput.setAttribute('readonly', true);
                passwordInput.setAttribute('readonly', true);
                usernameInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                customerCodeInput.classList.add('bg-indigo-50', 'cursor-not-allowed', 'opacity-75');
                passwordInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                if (!passwordInput.value) passwordInput.value = generateRandomPassword();
                updateAutoFields();
            }

            // Billing Logic script
            const billingType = document.getElementById('billing_type');
            const billingMethod = document.getElementById('billing_method');
            const cycleDates = document.getElementById('billing_cycle_dates');
            const infoBox = document.getElementById('billing_info_box');
            const methodDesc = document.getElementById('method_description');

            const methods = {
                postpaid: [
                    { value: 'cycle', label: 'Cycle (Invoice tgl 1, Jatuh Tempo tgl 20)', desc: '<strong>Pasca Bayar Cycle:</strong> Layanan dipakai dulu. Invoice terbit setiap tanggal 1, jatuh tempo tanggal 20. Pembayaran pertama dihitung prorata.' },
                    { value: 'fixed', label: 'Fixed (Jatuh Tempo Tgl Aktif, -7 Hari)', desc: '<strong>Pasca Bayar Fixed:</strong> Layanan dipakai dulu. Jatuh tempo setiap tanggal pendaftaran (anniversary). Invoice terbit 7 hari sebelum jatuh tempo.' }
                ],
                prepaid: [
                    { value: 'fixed', label: 'Fixed (Bayar di Depan, Anniversary Tgl)', desc: '<strong>Prabayar Fixed:</strong> Bayar dulu baru layanan aktif. Jatuh tempo setiap tanggal pendaftaran. Invoice terbit 7 hari sebelum masa aktif periode berikutnya dimulai.' },
                    { value: 'renewal', label: 'Renewal (Top-up / +30 Hari)', desc: '<strong>Prabayar Renewal:</strong> Bayar secara manual untuk memperpanjang masa aktif. Setiap pembayaran menambah masa aktif sebanyak 30 hari.' }
                ]
            };

            function updateMethods() {
                const type = billingType.value;
                const oldMethod = billingMethod.value;
                billingMethod.innerHTML = '';
                
                methods[type].forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.value;
                    opt.textContent = m.label;
                    if(m.value === oldMethod) opt.selected = true;
                    billingMethod.appendChild(opt);
                });

                updateDescription();
            }

            function updateDescription() {
                const type = billingType.value;
                const method = billingMethod.value;
                const activeMethod = methods[type].find(m => m.value === method);
                
                if (activeMethod) {
                    methodDesc.innerHTML = activeMethod.desc;
                    infoBox.className = 'md:col-span-1 border p-4 rounded-xl flex items-start ' + 
                                       (type === 'postpaid' ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/30' : 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-900/30');
                }

                // Show cycle dates ONLY for postpaid cycle
                if (type === 'postpaid' && method === 'cycle') {
                    cycleDates.classList.remove('hidden');
                } else {
                    cycleDates.classList.add('hidden');
                }
            }

            billingType.addEventListener('change', updateMethods);
            billingMethod.addEventListener('change', updateDescription);
            
            updateMethods();
        });
    </script>
</x-app-layout>
