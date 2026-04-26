<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Subscriber: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $customer->username }}</span>
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('customers.update', $customer) }}" method="POST" class="p-8">
            @csrf
            @method('PUT')

            <!-- Section: RADIUS Auth -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Network Access (RADIUS)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="customer_code" :value="__('Customer ID')" />
                        <x-text-input id="customer_code" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-bold tracking-widest text-center" :value="$customer->customer_code" disabled />
                    </div>

                    <div>
                        <x-input-label for="username" :value="__('PPPoE / Hotspot Username')" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $customer->username)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        <p class="mt-1 text-[10px] text-amber-600 font-bold uppercase">Warning: Changing username will sync all RADIUS records.</p>
                    </div>

                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <x-input-label for="password" :value="__('Network Password')" />
                            <button type="button" id="regen_password" class="text-indigo-600 text-[10px] font-black uppercase hover:text-indigo-800 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Regenerate
                            </button>
                        </div>
                        <x-text-input id="password" name="password" type="text" class="block w-full font-mono" :value="old('password', $customer->password)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        <p class="mt-1 text-[10px] text-gray-400 italic">Current RADIUS password is shown.</p>
                    </div>
                </div>
            </div>

            <!-- Section: Billing Info -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscriber details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="ktp" :value="__('Nomor KTP (NIK)')" />
                        <x-text-input id="ktp" name="ktp" type="text" class="mt-1 block w-full" :value="old('ktp', $customer->ktp)" placeholder="16 Digit NIK" />
                        <x-input-error class="mt-2" :messages="$errors->get('ktp')" />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Full Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone (WhatsApp)')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer->phone)" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <x-input-label for="region_code" :value="__('Region Code')" />
                        <select id="region_code" name="region_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">-- Select Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code', $customer->region_code) == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('region_code')" />
                    </div>
                    <div>
                        <x-input-label for="sto_code" :value="__('STO Code')" />
                        <select id="sto_code" name="sto_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm disabled:opacity-50" required>
                            <option value="">-- Select STO --</option>
                            @foreach($stos as $s)
                                <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code', $customer->sto_code) == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('sto_code')" />
                    </div>
                    <div>
                        <x-input-label for="stb_code" :value="__('STB Code')" />
                        <select id="stb_code" name="stb_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm disabled:opacity-50" required>
                            <option value="">-- Select STB --</option>
                            @foreach($stbs as $t)
                                <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code', $customer->stb_code) == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('stb_code')" />
                    </div>
                </div>

                <div class="mb-6">
                    <x-input-label for="email" :value="__('Email Address')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $customer->email)" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div class="mb-6">
                    <x-input-label for="address" :value="__('Installation Address')" />
                    <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address', $customer->address) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('address')" />
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <x-input-label :value="__('Installation Location')" />
                        <button type="button" id="locate-me" class="text-[10px] bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded-full font-bold uppercase hover:bg-indigo-100 transition-all flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Gunakan Lokasi Saya
                        </button>
                    </div>
                    <div class="mt-1 border-4 border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-inner">
                        <div id="map-picker" style="height: 300px; width: 100%;"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-input-label for="latitude" :value="__('Latitude')" />
                            <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-900/50" :value="old('latitude', $customer->latitude)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('latitude')" />
                        </div>
                        <div>
                            <x-input-label for="longitude" :value="__('Longitude')" />
                            <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-900/50" :value="old('longitude', $customer->longitude)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('longitude')" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 italic">Drag marker or click on the map to update coordinates.</p>
                </div>
            </div>

            <!-- Section: Billing Configuration -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Billing Configuration</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="billing_type" :value="__('Billing Type')" />
                        <select id="billing_type" name="billing_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="postpaid" {{ old('billing_type', $customer->billing_type) == 'postpaid' ? 'selected' : '' }}>Pasca Bayar (Postpaid)</option>
                            <option value="prepaid" {{ old('billing_type', $customer->billing_type) == 'prepaid' ? 'selected' : '' }}>Prabayar (Prepaid)</option>
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
                            <x-text-input id="billing_day" name="billing_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_day', $customer->billing_day ?? 1)" />
                            <p class="mt-1 text-[10px] text-gray-500 italic">Day of the month to generate invoice (Default: 1st)</p>
                        </div>
                        <div>
                            <x-input-label for="billing_due_day" :value="__('Due Date Day (1-28)')" />
                            <x-text-input id="billing_due_day" name="billing_due_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_due_day', $customer->billing_due_day ?? 20)" />
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
                                <option value="{{ $package->id }}" {{ old('package_id', $customer->package_id) == $package->id ? 'selected' : '' }}>
                                    {{ $package->name }} - Rp {{ number_format($package->price, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('package_id')" />
                    </div>

                    <div id="billing_info_box" class="md:col-span-1 border p-4 rounded-xl flex items-start">
                        <svg class="w-5 h-5 text-indigo-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div id="method_description" class="text-[11px] leading-relaxed">
                            Pilih metode billing untuk melihat detail aturan penagihan.
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="is_active" :value="__('Account Status')" />
                        <select id="is_active" name="is_active" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm disabled:opacity-50" {{ auth()->user()->isSales() ? 'disabled' : '' }}>
                            <option value="1" {{ old('is_active', $customer->is_active) == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $customer->is_active) == '0' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('customers.index') }}" class="mr-4 text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                <x-primary-button>
                    {{ __('Update Subscriber') }}
                </x-primary-button>
            </div>
        </form>
    </div>
    <!-- CASCADING DROPDOWNS SCRIPT -->
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function() {
            var latInput = document.getElementById('latitude');
            var lngInput = document.getElementById('longitude');

            // ========== MAP INITIALIZATION (wrapped in try-catch) ==========
            try {
                var initialLat = parseFloat(@json($customer->latitude)) || -6.200000;
                var initialLng = parseFloat(@json($customer->longitude)) || 106.816666;
                
                // Properly destroy any existing Leaflet map instance
                var mapContainer = document.getElementById('map-picker');
                if (mapContainer && mapContainer._leaflet_id) {
                    // Remove all child nodes and reset leaflet internal state
                    while (mapContainer.firstChild) {
                        mapContainer.removeChild(mapContainer.firstChild);
                    }
                    delete mapContainer._leaflet_id;
                }
                
                var map = L.map('map-picker').setView([initialLat, initialLng], 14);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                var marker = L.marker([initialLat, initialLng], {draggable: true}).addTo(map);
                var locateBtn = document.getElementById('locate-me');

                function updateInputs(lat, lng) {
                    if (latInput && lngInput) {
                        latInput.value = lat.toFixed(8);
                        lngInput.value = lng.toFixed(8);
                        
                        // Visual feedback
                        latInput.style.backgroundColor = '#dcfce7';
                        lngInput.style.backgroundColor = '#dcfce7';
                        setTimeout(function() {
                            latInput.style.backgroundColor = '';
                            lngInput.style.backgroundColor = '';
                        }, 500);
                    }
                }

                // Initial population
                updateInputs(initialLat, initialLng);

                if (locateBtn && navigator.geolocation) {
                    locateBtn.addEventListener('click', function() {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var userLat = position.coords.latitude;
                            var userLng = position.coords.longitude;
                            marker.setLatLng([userLat, userLng]);
                            map.setView([userLat, userLng], 16);
                            updateInputs(userLat, userLng);
                        }, function(error) {
                            alert("Gagal mendapatkan lokasi: " + error.message);
                        });
                    });
                }

                latInput.addEventListener('input', function() {
                    var lat = parseFloat(this.value);
                    var lng = parseFloat(lngInput.value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        marker.setLatLng([lat, lng]);
                        map.panTo([lat, lng]);
                    }
                });

                lngInput.addEventListener('input', function() {
                    var lat = parseFloat(latInput.value);
                    var lng = parseFloat(this.value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        marker.setLatLng([lat, lng]);
                        map.panTo([lat, lng]);
                    }
                });

                marker.on('dragend', function(e) {
                    var position = marker.getLatLng();
                    updateInputs(position.lat, position.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateInputs(e.latlng.lat, e.latlng.lng);
                });
            } catch(mapError) {
                console.warn('Map initialization skipped:', mapError.message);
            }

            // ========== CASCADING DROPDOWNS (always runs) ==========
            var regionSelect = document.getElementById('region_code');
            var stoSelect = document.getElementById('sto_code');
            var stbSelect = document.getElementById('stb_code');

            function filterStos(preserveValue) {
                var selectedOption = regionSelect.options[regionSelect.selectedIndex];
                var regionId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                var currentValue = stoSelect.value;
                if(!preserveValue) stoSelect.value = '';
                
                if(regionId) {
                    stoSelect.disabled = false;
                    for(var i = 0; i < stoSelect.options.length; i++) {
                        var opt = stoSelect.options[i];
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

            function filterStbs(preserveValue) {
                var selectedOption = stoSelect.options[stoSelect.selectedIndex];
                var stoId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                var currentValue = stbSelect.value;
                if(!preserveValue) stbSelect.value = '';

                if(stoId) {
                    stbSelect.disabled = false;
                    for(var i = 0; i < stbSelect.options.length; i++) {
                        var opt = stbSelect.options[i];
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

            regionSelect.addEventListener('change', function() { filterStos(false); filterStbs(false); });
            stoSelect.addEventListener('change', function() { filterStbs(false); });

            if(regionSelect.value) { filterStos(true); }
            if(stoSelect.value) { filterStbs(true); }

            // ========== BILLING LOGIC (always runs) ==========
            var billingType = document.getElementById('billing_type');
            var billingMethod = document.getElementById('billing_method');
            var cycleDates = document.getElementById('billing_cycle_dates');
            var infoBox = document.getElementById('billing_info_box');
            var methodDesc = document.getElementById('method_description');
            
            var initialMethod = @json(old('billing_method', $customer->billing_method));

            var methods = {
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
                var type = billingType.value;
                var oldMethod = billingMethod.value || initialMethod;
                billingMethod.innerHTML = '';
                
                methods[type].forEach(function(m) {
                    var opt = document.createElement('option');
                    opt.value = m.value;
                    opt.textContent = m.label;
                    if(m.value === oldMethod) opt.selected = true;
                    billingMethod.appendChild(opt);
                });

                updateDescription();
            }

            function updateDescription() {
                var type = billingType.value;
                var method = billingMethod.value;
                var activeMethod = methods[type].find(function(m) { return m.value === method; });
                
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
            
            // ========== PASSWORD REGENERATION (always runs) ==========
            var regenBtn = document.getElementById('regen_password');
            var passwordInput = document.getElementById('password');

            function generateRandomPassword(length) {
                length = length || 8;
                var charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                var retVal = "";
                for (var i = 0, n = charset.length; i < length; ++i) {
                    retVal += charset.charAt(Math.floor(Math.random() * n));
                }
                return retVal;
            }

            if (regenBtn && passwordInput) {
                regenBtn.addEventListener('click', function() {
                    passwordInput.value = generateRandomPassword();
                });
            }

            // Initialize billing methods
            updateMethods();
        })();
    </script>
    @endpush
</x-app-layout>
