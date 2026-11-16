<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            Edit Pelanggan <span class="text-surface-500 dark:text-surface-400 font-normal">· {{ $customer->name }}</span>
        </h2>
    </x-slot>

    <div class="card max-w-4xl mx-auto overflow-hidden">
        <form action="{{ route('customers.update', $customer) }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf
            @method('PUT')
            @php
                // Admin: semua field. Teknisi: kontak + wilayah/STO/STB. Sales: kontak saja.
                // Field lain tetap ditampilkan tapi terkunci; server juga mengabaikannya.
                $canAdmin = auth()->user()->isAdmin();
                $canTech = $canAdmin || auth()->user()->isTeknisi();
            @endphp

            @unless($canAdmin)
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                    Anda hanya bisa mengubah {{ $canTech ? 'No. HP, email, alamat dan wilayah/STO/STB' : 'No. HP, email dan alamat' }}.
                    Paket, diskon, PPN, tagihan dan akun PPPoE diubah oleh admin. Untuk ganti paket gunakan
                    <a href="{{ route('customers.show', $customer) }}" class="font-semibold underline">Ajukan ganti paket</a> di halaman pelanggan.
                </div>
            @endunless

            <!-- Section: RADIUS Auth -->
            <div class="mb-6 p-5 sm:p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Akses Jaringan (RADIUS)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="customer_code" :value="__('Customer ID')" />
                        <x-text-input id="customer_code" type="text" class="mt-1 block w-full bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold tracking-widest text-center" :value="$customer->customer_code" disabled />
                    </div>

                    <div>
                        <x-input-label for="username" :value="__('PPPoE / Hotspot Username')" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full font-mono" :value="old('username', $customer->username)" :disabled="! $canAdmin" required />
                        <x-input-error class="mt-2" :messages="$errors->get('username')" />
                    </div>

                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <x-input-label for="password" :value="__('Network Password')" />
                            @if($canAdmin)
                            <button type="button" id="regen_password" class="text-indigo-600 text-xs hover:text-indigo-800 transition-colors flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Regenerate
                            </button>
                            @endif
                        </div>
                        @if($canTech)
                            <x-text-input id="password" name="password" type="text" class="block w-full font-mono" :value="$canAdmin ? old('password', $customer->password) : $customer->password" :disabled="! $canAdmin" required />
                        @else
                            {{-- Sales tidak melihat password PPPoE (sama seperti halaman detail) --}}
                            <x-text-input id="password" type="text" class="block w-full font-mono" value="••••••••" disabled />
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                </div>
            </div>

            <!-- Section: Subscriber Profile -->
            <div class="mb-6 p-5 sm:p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Data Pribadi Pelanggan
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="customer_type" :value="__('Customer Priority Type')" />
                        <select id="customer_type" name="customer_type" class="mt-1 input" @disabled(! $canAdmin) required>
                            <option value="personal" {{ old('customer_type', $customer->customer_type) == 'personal' ? 'selected' : '' }}>Personal / Residential</option>
                            <option value="corporate" {{ old('customer_type', $customer->customer_type) == 'corporate' ? 'selected' : '' }}>Corporate / Business</option>
                            <option value="vip" {{ old('customer_type', $customer->customer_type) == 'vip' ? 'selected' : '' }}>VIP / Prioritas Tinggi</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Full Name / Company Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer->name)" :disabled="! $canAdmin" required />
                    </div>

                    <div>
                        <x-input-label for="ktp" :value="__('Nomor KTP (NIK) / NPWP')" />
                        <x-text-input id="ktp" name="ktp" type="text" class="mt-1 block w-full" :value="old('ktp', $customer->ktp)" placeholder="16 Digit NIK" :disabled="! $canAdmin" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone (WhatsApp)')" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer->phone)" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="email" :value="__('Email Address')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $customer->email)" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="address" :value="__('Installation Address')" />
                        <textarea id="address" name="address" rows="2" class="mt-1 input">{{ old('address', $customer->address) }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <x-input-label for="region_code" :value="__('Region')" />
                        <select id="region_code" name="region_code" class="mt-1 input" @disabled(! $canTech) required>
                            <option value="">-- Pilih Wilayah --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code', $customer->region_code) == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="sto_code" :value="__('STO')" />
                        <select id="sto_code" name="sto_code" class="mt-1 input" @disabled(! $canTech) required>
                            <option value="">-- Pilih STO --</option>
                            @foreach($stos as $s)
                                <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code', $customer->sto_code) == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="stb_code" :value="__('STB')" />
                        <select id="stb_code" name="stb_code" class="mt-1 input" @disabled(! $canTech) required>
                            <option value="">-- Pilih STB --</option>
                            @foreach($stbs as $t)
                                <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code', $customer->stb_code) == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>


            {{-- Tagihan, mitra/sales, paket, diskon, PPN, status & biaya: hanya admin (terkunci untuk role lain) --}}
            <fieldset @disabled(! $canAdmin) class="min-w-0 {{ $canAdmin ? '' : 'opacity-70' }}">
            <!-- Section: Billing Configuration -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Billing Configuration</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="billing_type" :value="__('Billing Type')" />
                        <select id="billing_type" name="billing_type" class="mt-1 input" required>
                            <option value="postpaid" {{ old('billing_type', $customer->billing_type) == 'postpaid' ? 'selected' : '' }}>Pasca Bayar (Postpaid)</option>
                            <option value="prepaid" {{ old('billing_type', $customer->billing_type) == 'prepaid' ? 'selected' : '' }}>Prabayar (Prepaid)</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('billing_type')" />
                    </div>

                    <div>
                        <x-input-label for="billing_method" :value="__('Billing Method')" />
                        <select id="billing_method" name="billing_method" class="mt-1 input" required>
                            <!-- Options will be populated by JS -->
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('billing_method')" />
                    </div>

                    <div id="billing_cycle_dates" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 hidden">
                        <div>
                            <x-input-label for="billing_day" :value="__('Generate Bill Day (1-28)')" />
                            <x-text-input id="billing_day" name="billing_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_day', $customer->billing_day ?? 1)" />
                            <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">Tanggal invoice diterbitkan tiap bulan (default: tanggal 1)</p>
                        </div>
                        <div>
                            <x-input-label for="billing_due_day" :value="__('Due Date Day (1-28)')" />
                            <x-text-input id="billing_due_day" name="billing_due_day" type="number" min="1" max="28" class="mt-1 block w-full" :value="old('billing_due_day', $customer->billing_due_day ?? 20)" />
                            <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">Tanggal isolir tiap bulan bila belum bayar (default: tanggal 20)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Partner & Reseller -->
            @if(get_setting('enable_partner_module') == '1' && auth()->user()->isAdmin())
            <div class="mb-6 p-5 sm:p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Referal Mitra & Reseller
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <x-input-label for="partner_id" :value="__('Select Partner')" />
                        <select id="partner_id" name="partner_id" class="mt-1 input">
                            <option value="">-- Tanpa Mitra (Langsung) --</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}" {{ old('partner_id', $customer->partner_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="commission_rate" :value="__('Override Commission Rate')" />
                        <x-text-input id="commission_rate" name="commission_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('commission_rate', $customer->commission_rate)" placeholder="Opsional (menimpa default)" />
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="commission_type" :value="__('Override Rate Type')" />
                        <select id="commission_type" name="commission_type" class="mt-1 input">
                            <option value="">-- Pakai Default Mitra --</option>
                            <option value="percentage" {{ old('commission_type', $customer->commission_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('commission_type', $customer->commission_type) == 'fixed' ? 'selected' : '' }}>Nominal Tetap</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Section: Internal Sales Referral -->
            @if(get_setting('enable_sales_commission_module') == '1' && auth()->user()->isAdmin())
            <div class="mb-6 p-5 sm:p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11.401M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Referal Sales Internal (Staf)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <x-input-label for="sales_id" :value="__('Select Sales Staff')" />
                        <select id="sales_id" name="sales_id" class="mt-1 input">
                            <option value="">-- Tanpa Sales (Langsung) --</option>
                            @foreach($sales as $s)
                                <option value="{{ $s->id }}" {{ old('sales_id', $customer->sales_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">Hubungkan pelanggan ini ke sales untuk pencatatan insentif.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="sales_commission_rate" :value="__('Override Incentive Rate')" />
                        <x-text-input id="sales_commission_rate" name="sales_commission_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('sales_commission_rate', $customer->sales_commission_rate)" placeholder="Opsional (menimpa default)" />
                        <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">Kosongkan untuk memakai tarif default umum.</p>
                    </div>

                    <div class="md:col-span-1">
                        <x-input-label for="sales_commission_type" :value="__('Override Rate Type')" />
                        <select id="sales_commission_type" name="sales_commission_type" class="mt-1 input">
                            <option value="">-- Pakai Default Umum --</option>
                            <option value="percentage" {{ old('sales_commission_type', $customer->sales_commission_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('sales_commission_type', $customer->sales_commission_type) == 'fixed' ? 'selected' : '' }}>Nominal Tetap</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif

            <!-- Section: Subscription -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Paket Langganan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="package_id" :value="__('Internet Package')" />
                        <select id="package_id" name="package_id" class="mt-1 input" required>
                            <option value="">-- Pilih Paket --</option>
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

            <!-- Section: Discount Settings -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Diskon Khusus Pelanggan</h3>
                <div class="bg-surface-50/60 dark:bg-surface-800/30 border border-surface-200 dark:border-surface-800 rounded-xl p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="discount_type" :value="__('Tipe Diskon')" />
                            <select id="discount_type" name="discount_type" class="mt-1 input">
                                <option value="">-- Tanpa Diskon --</option>
                                <option value="fixed" {{ old('discount_type', $customer->discount_type) == 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                                <option value="percentage" {{ old('discount_type', $customer->discount_type) == 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih apakah diskon dipotong berupa nominal rupiah tetap atau persen.</p>
                        </div>
                        <div>
                            <x-input-label for="discount_value" :value="__('Nilai Diskon')" />
                            <x-text-input id="discount_value" name="discount_value" type="number" step="0.01" min="0" class="mt-1 block w-full font-semibold" :value="old('discount_value', $customer->discount_value ?? 0)" placeholder="Contoh: 25000 atau 10" />
                            <p class="text-xs text-gray-500 mt-1">Jika tipe fixed isi misal 25000 (Rp 25.000). Jika persentase isi misal 10 (10%).</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Taxation Settings -->
            <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Pengaturan Pajak</h3>
                <div class="bg-surface-50/60 dark:bg-surface-800/30 border border-surface-200 dark:border-surface-800 rounded-xl p-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="use_tax" name="use_tax" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5" {{ old('use_tax', $customer->use_tax) ? 'checked' : '' }}>
                        <div class="ml-4">
                            <label for="use_tax" class="text-sm font-semibold text-gray-900 dark:text-gray-100  cursor-pointer">Kenakan Pajak (PPN)</label>
                            <p class="text-xs text-gray-500 mt-1">Jika diaktifkan, tagihan bulanan pelanggan ini akan ditambah PPN (11%).</p>
                        </div>
                    </div>
                </div>
            </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="is_active" :value="__('Account Status')" />
                        @if(in_array($customer->status, \App\Models\Customer::BILLABLE_STATUSES, true))
                            <select id="is_active" name="is_active" class="mt-1 input">
                                <option value="1" {{ old('is_active', $customer->is_active) == '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ old('is_active', $customer->is_active) == '0' ? 'selected' : '' }}>Terisolir</option>
                            </select>
                        @else
                            {{-- Status di luar aktif/terisolir diubah lewat alurnya sendiri (wizard aktivasi / dismantle) --}}
                            <div class="mt-1 rounded-lg border border-surface-200 bg-surface-50 px-3 py-2 text-sm dark:border-surface-700 dark:bg-surface-800">
                                {{ ['new' => 'Baru', 'waiting_activation' => 'Menunggu aktivasi', 'waiting_dismantle' => 'Menunggu dismantle', 'dismantled' => 'Dicabut', 'canceled' => 'Batal'][$customer->status] ?? $customer->status }}
                            </div>
                            <p class="help-text">
                                @if(in_array($customer->status, \App\Services\CustomerActivationService::ACTIVATABLE, true))
                                    Diaktifkan lewat <a href="{{ route('customers.activate', $customer) }}" class="text-primary-600 hover:underline dark:text-primary-400">wizard aktivasi</a>.
                                @else
                                    Tidak bisa diubah dari form ini.
                                @endif
                            </p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                    </div>

                    <div>
                        <label for="display_installation_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Biaya Instalasi (Rp)</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">Rp</span>
                            </div>
                            <input type="text" id="display_installation_fee" placeholder="0,00" @unless($canAdmin) style="opacity:.6;cursor:not-allowed" @endunless class="block w-full pl-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" oninput="formatCurrency(this, 'installation_fee')" onblur="finalizeCurrency(this, 'installation_fee')">
                            <input type="hidden" id="installation_fee" name="installation_fee" value="{{ old('installation_fee', $customer->installation_fee) }}">
                        </div>
                        <p class="mt-1 text-xs text-surface-500 dark:text-surface-400">Biaya pemasangan awal.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('installation_fee')" />
                    </div>
                </div>
            </div>
            </fieldset>

            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('customers.index') }}" class="mr-4 text-gray-600 dark:text-gray-400 hover:underline">Batal</a>
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

                var isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
                var noHttpsMsg = document.getElementById('locate-no-https');

                if (locateBtn && navigator.geolocation && isSecure) {
                    locateBtn.classList.remove('hidden');
                    locateBtn.classList.add('flex');
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
                } else if (noHttpsMsg && !isSecure) {
                    noHttpsMsg.classList.remove('hidden');
                    noHttpsMsg.classList.add('inline-flex');
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
            // Wilayah/STO/STB terkunci untuk sales: jangan dibuka lagi oleh skrip filter
            var locationLocked = @json(! $canTech);

            function filterStos(preserveValue) {
                var selectedOption = regionSelect.options[regionSelect.selectedIndex];
                var regionId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                var currentValue = stoSelect.value;
                if(!preserveValue) stoSelect.value = '';
                
                if(regionId) {
                    stoSelect.disabled = locationLocked;
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
                    stbSelect.disabled = locationLocked;
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
            if (hidden && hidden.value) {
                let numeric = parseFloat(hidden.value);
                if (!isNaN(numeric)) {
                    display.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
