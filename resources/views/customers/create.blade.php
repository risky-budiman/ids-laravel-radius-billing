<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Subscriber') }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf
            
            <!-- Hidden Fields from Mass Discovery -->
            <input type="hidden" name="olt_id" value="{{ request('olt_id') }}">
            <input type="hidden" name="onu_sn" value="{{ request('sn') }}">
            <input type="hidden" name="onu_index" value="{{ request('pos') }}">
            <input type="hidden" name="onu_type" value="{{ request('onu_type') }}">


            <!-- Section: RADIUS Auth -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700 bg-slate-50/30 dark:bg-slate-900/10 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Network Access (RADIUS)
                    </h3>
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

            <!-- Section: Subscriber Profile -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700 bg-blue-50/30 dark:bg-blue-900/10 p-6 rounded-2xl border border-blue-100 dark:border-blue-800">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Subscriber Personal Profile
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="customer_type" :value="__('Customer Priority Type')" />
                        <select id="customer_type" name="customer_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm" required>
                            <option value="personal" {{ old('customer_type') == 'personal' ? 'selected' : '' }}>Personal / Residential</option>
                            <option value="corporate" {{ old('customer_type') == 'corporate' ? 'selected' : '' }}>Corporate / Business</option>
                            <option value="vip" {{ old('customer_type') == 'vip' ? 'selected' : '' }}>VIP / High Priority</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Full Name / Company Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    </div>

                    <div>
                        <x-input-label for="ktp" :value="__('Nomor KTP (NIK) / NPWP')" />
                        <x-text-input id="ktp" name="ktp" type="text" class="mt-1 block w-full" :value="old('ktp')" placeholder="16 Digit NIK" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone (WhatsApp)')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="email" :value="__('Email Address')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="address" :value="__('Installation Address')" />
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm">{{ old('address') }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <x-input-label for="region_code" :value="__('Region')" />
                        <select id="region_code" name="region_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm" required>
                            <option value="">-- Select Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code') == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="sto_code" :value="__('STO')" />
                        <select id="sto_code" name="sto_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm disabled:opacity-50" required disabled>
                            <option value="">-- Select STO --</option>
                            @foreach($stos as $s)
                                <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code') == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="stb_code" :value="__('STB')" />
                        <select id="stb_code" name="stb_code" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm disabled:opacity-50" required disabled>
                            <option value="">-- Select STB --</option>
                            @foreach($stbs as $t)
                                <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code') == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
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

                    <div class="md:col-span-2 mt-4">
                        <x-input-label for="scheduled_activation_at" :value="__('Jadwal Aktivasi (SLA)')" />
                        <x-text-input id="scheduled_activation_at" name="scheduled_activation_at" type="date" class="mt-1 block w-full" :value="old('scheduled_activation_at')" />
                        <p class="mt-1 text-[10px] text-gray-500 italic text-rose-500">Tanggal target pemasangan. Akan muncul sebagai SLA pada tiket aktivasi teknisi.</p>
                    </div>
                </div>
            </div>

            <!-- Section: Partner & Reseller -->
            @if(get_setting('enable_partner_module') == '1' && auth()->user()->isAdmin())
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700 bg-indigo-50/20 dark:bg-indigo-900/10 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Partner & Reseller Referral
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <x-input-label for="partner_id" :value="__('Select Partner')" />
                        <select id="partner_id" name="partner_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">-- No Partner (Direct) --</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-gray-500 italic">Assign this customer to a partner for commission tracking.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="commission_rate" :value="__('Override Commission Rate')" />
                        <x-text-input id="commission_rate" name="commission_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('commission_rate')" placeholder="Optional override" />
                        <p class="mt-1 text-[10px] text-gray-500 italic">Leave empty to use partner's default rate.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="commission_type" :value="__('Override Rate Type')" />
                        <select id="commission_type" name="commission_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            <option value="">-- Use Partner Default --</option>
                            <option value="percentage" {{ old('commission_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('commission_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif

            <!-- Section: Internal Sales Referral -->
            @if(get_setting('enable_sales_commission_module') == '1' && auth()->user()->isAdmin())
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700 bg-emerald-50/20 dark:bg-emerald-900/10 p-6 rounded-2xl border border-emerald-100 dark:border-emerald-800">
                <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11.401M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Internal Sales Referral (Staff)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <x-input-label for="sales_id" :value="__('Select Sales Staff')" />
                        <select id="sales_id" name="sales_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 rounded-md shadow-sm">
                            <option value="">-- No Sales (Direct / Walk-in) --</option>
                            @foreach($sales as $s)
                                <option value="{{ $s->id }}" {{ old('sales_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-gray-500 italic">Assign this customer to a sales staff for incentive tracking.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="sales_commission_rate" :value="__('Override Incentive Rate')" />
                        <x-text-input id="sales_commission_rate" name="sales_commission_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('sales_commission_rate')" placeholder="Optional override" />
                        <p class="mt-1 text-[10px] text-gray-500 italic">Leave empty to use global default rate.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="sales_commission_type" :value="__('Override Rate Type')" />
                        <select id="sales_commission_type" name="sales_commission_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 rounded-md shadow-sm">
                            <option value="">-- Use Global Default --</option>
                            <option value="percentage" {{ old('sales_commission_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('sales_commission_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif

            <!-- Section: Subscription -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscription Plan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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

                    <div>
                        <label for="display_installation_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Biaya Instalasi (Rp)</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">Rp</span>
                            </div>
                            <input type="text" id="display_installation_fee" placeholder="0,00" class="block w-full pl-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" oninput="formatCurrency(this, 'installation_fee')" onblur="finalizeCurrency(this, 'installation_fee')">
                            <input type="hidden" id="installation_fee" name="installation_fee" value="{{ old('installation_fee', 0) }}">
                        </div>
                        <p class="mt-1 text-[10px] text-gray-500 italic">Satu kali bayar saat aktivasi.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('installation_fee')" />
                    </div>
                </div>

                <div id="billing_info_box" class="border p-4 rounded-xl flex items-start">
                    <svg class="w-5 h-5 text-indigo-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div id="method_description" class="text-[11px] leading-relaxed">
                        Pilih metode billing untuk melihat detail aturan penagihan.
                    </div>
                </div>
            </div>

            <!-- Section: Installation Details & Location -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700 bg-amber-50/10 dark:bg-amber-900/5 p-6 rounded-2xl border border-amber-100 dark:border-amber-800">
                <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Installation Details & Location
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="latitude" :value="__('Latitude')" />
                        <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('latitude')" placeholder="-6.xxxxxx" />
                    </div>
                    <div>
                        <x-input-label for="longitude" :value="__('Longitude')" />
                        <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full font-mono text-sm" :value="old('longitude')" placeholder="106.xxxxxx" />
                    </div>
                </div>

                <div class="relative">
                    <div id="map-picker" class="h-64 rounded-xl border border-gray-200 dark:border-gray-700 shadow-inner z-0"></div>
                    <div id="locate-no-https" class="hidden absolute top-2 right-2 bg-rose-50 border border-rose-200 text-rose-600 px-3 py-1 rounded-lg text-[10px] font-bold z-10">
                        HTTPS Required for Auto-Locate
                    </div>
                    <button type="button" id="locate-me" class="hidden absolute bottom-4 right-4 bg-white dark:bg-gray-800 p-3 rounded-full shadow-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all z-10 group">
                        <svg class="w-5 h-5 text-indigo-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </button>
                </div>
                <p class="mt-2 text-[10px] text-gray-500 italic text-center">Klik pada peta untuk menentukan lokasi instalasi.</p>
            </div>

            <!-- Section: Taxation Settings -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Taxation Settings</h3>
                <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="use_tax" name="use_tax" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5" {{ old('use_tax', true) ? 'checked' : '' }}>
                        <div class="ml-4">
                            <label for="use_tax" class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-widest cursor-pointer">Kenakan Pajak (PPN)</label>
                            <p class="text-xs text-gray-500 mt-1">Jika diaktifkan, tagihan bulanan pelanggan ini akan ditambah PPN sesuai aturan yang berlaku (Default: 11%).</p>
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
                var defaultLat = -6.200000;
                var defaultLng = 106.816666;
                
                // Properly destroy any existing Leaflet map instance
                var mapContainer = document.getElementById('map-picker');
                if (mapContainer && mapContainer._leaflet_id) {
                    while (mapContainer.firstChild) {
                        mapContainer.removeChild(mapContainer.firstChild);
                    }
                    delete mapContainer._leaflet_id;
                }
                
                var map = L.map('map-picker').setView([defaultLat, defaultLng], 12);
                
                // Try to get user's current location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var userLat = position.coords.latitude;
                        var userLng = position.coords.longitude;
                        map.setView([userLat, userLng], 14);
                    }, function() {
                        console.log("Geolocation permission denied or failed.");
                    });
                }
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                var marker;
                var locateBtn = document.getElementById('locate-me');

                function updateInputs(lat, lng) {
                    latInput.value = lat.toFixed(8);
                    lngInput.value = lng.toFixed(8);
                }

                function updateMarker(latlng) {
                    if (marker) {
                        marker.setLatLng(latlng);
                    } else {
                        marker = L.marker(latlng, {draggable: true}).addTo(map);
                        marker.on('dragend', function(e) {
                            var position = marker.getLatLng();
                            updateInputs(position.lat, position.lng);
                        });
                    }
                    map.panTo(latlng);
                }

                var isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
                var noHttpsMsg = document.getElementById('locate-no-https');

                if (locateBtn && navigator.geolocation && isSecure) {
                    locateBtn.classList.remove('hidden');
                    locateBtn.classList.add('flex');
                    locateBtn.addEventListener('click', function() {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var userLat = position.coords.latitude;
                            var userLng = position.coords.longitude;
                            updateMarker([userLat, userLng]);
                            map.setView([userLat, userLng], 16);
                        }, function(error) {
                            alert("Gagal mendapatkan lokasi: " + error.message);
                        });
                    });
                    
                    // Auto-locate on load
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var userLat = position.coords.latitude;
                        var userLng = position.coords.longitude;
                        map.setView([userLat, userLng], 14);
                    });
                } else if (noHttpsMsg && !isSecure) {
                    noHttpsMsg.classList.remove('hidden');
                    noHttpsMsg.classList.add('inline-flex');
                }

                latInput.addEventListener('input', function() {
                    var lat = parseFloat(this.value);
                    var lng = parseFloat(lngInput.value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        updateMarker([lat, lng]);
                    }
                });

                lngInput.addEventListener('input', function() {
                    var lat = parseFloat(latInput.value);
                    var lng = parseFloat(this.value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        updateMarker([lat, lng]);
                    }
                });

                map.on('click', function(e) {
                    updateInputs(e.latlng.lat, e.latlng.lng);
                    updateMarker(e.latlng);
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

            regionSelect.addEventListener('change', function() { filterStos(false); filterStbs(false); updateAutoFields(); });
            stoSelect.addEventListener('change', function() { filterStbs(false); updateAutoFields(); });
            stbSelect.addEventListener('change', updateAutoFields);

            if(regionSelect.value) { filterStos(true); }
            if(stoSelect.value) { filterStbs(true); }

            // ========== AUTO-GENERATION LOGIC (always runs) ==========
            var autoGenerate = document.getElementById('auto_generate');
            var usernameInput = document.getElementById('username');
            var passwordInput = document.getElementById('password');
            var customerCodeInput = document.getElementById('customer_code');
            var regenPasswordBtn = document.getElementById('regen_password');
            var companySuffix = "{{ \App\Models\Setting::where('key', 'company_domain')->first()->value ?? 'net.id' }}";

            function generateRandomPassword(length) {
                length = length || 8;
                var charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                var retVal = "";
                for (var i = 0, n = charset.length; i < length; ++i) {
                    retVal += charset.charAt(Math.floor(Math.random() * n));
                }
                return retVal;
            }

            var persistentRandomPart = Math.floor(100 + Math.random() * 900);

            function updateAutoFields() {
                if (!autoGenerate.checked) return;

                var region = regionSelect.value || '000';
                var sto = stoSelect.value || '000';
                var stb = stbSelect.value || '000';
                
                if (region !== '000' && sto !== '000' && stb !== '000') {
                    var fullCode = region + sto + stb + persistentRandomPart;
                    customerCodeInput.value = fullCode;
                    usernameInput.value = fullCode + '@' + companySuffix;
                }
            }

            autoGenerate.addEventListener('change', function() {
                if (this.checked) {
                    usernameInput.setAttribute('readonly', true);
                    customerCodeInput.setAttribute('readonly', true);
                    passwordInput.setAttribute('readonly', true);
                    usernameInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.classList.add('bg-indigo-50', 'cursor-not-allowed', 'opacity-75');
                    passwordInput.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    updateAutoFields();
                    if (!passwordInput.value) passwordInput.value = generateRandomPassword();
                } else {
                    usernameInput.removeAttribute('readonly');
                    customerCodeInput.removeAttribute('readonly');
                    passwordInput.removeAttribute('readonly');
                    usernameInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.classList.remove('bg-indigo-50', 'cursor-not-allowed', 'opacity-75');
                    passwordInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-75');
                    customerCodeInput.placeholder = "Enter ID manually...";
                }
            });

            customerCodeInput.addEventListener('input', function() {
                if (autoGenerate.checked) {
                    usernameInput.value = this.value + '@' + companySuffix;
                }
            });

            regenPasswordBtn.addEventListener('click', function() {
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

            // ========== BILLING LOGIC (always runs) ==========
            var billingType = document.getElementById('billing_type');
            var billingMethod = document.getElementById('billing_method');
            var cycleDates = document.getElementById('billing_cycle_dates');
            var infoBox = document.getElementById('billing_info_box');
            var methodDesc = document.getElementById('method_description');

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
                var oldMethod = billingMethod.value;
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
            
            // Initialize billing methods
            updateMethods();
        })();

        function formatCurrency(input, hiddenId) {
            let val = input.value.replace(/\./g, "").replace(",", ".");
            val = val.replace(/[^0-9.]/g, "");
            let parts = val.split(".");
            if (parts.length > 2) val = parts[0] + "." + parts.slice(1).join("");
            if (parts[1] && parts[1].length > 2) val = parts[0] + "." + parts[1].substring(0, 2);

            document.getElementById(hiddenId).value = val;
            
            if (val !== "") {
                let displayParts = val.split(".");
                let integerPart = new Intl.NumberFormat('id-ID').format(displayParts[0]);
                input.value = displayParts.length > 1 ? integerPart + "," + displayParts[1] : integerPart;
                if (val.endsWith(".") && !input.value.includes(",")) input.value += ",";
            } else {
                input.value = "";
            }
        }

        function finalizeCurrency(input, hiddenId) {
            let val = document.getElementById(hiddenId).value;
            if (val !== "") {
                let numeric = parseFloat(val);
                if (!isNaN(numeric)) {
                    input.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
                    document.getElementById(hiddenId).value = numeric.toFixed(2);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const hidden = document.getElementById('installation_fee');
            const display = document.getElementById('display_installation_fee');
            if (hidden && hidden.value && hidden.value != 0) {
                let numeric = parseFloat(hidden.value);
                if (!isNaN(numeric)) {
                    display.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
