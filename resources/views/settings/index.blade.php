<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Application Settings') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-8">
                <div class="flex items-center mb-8">
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Company Profile</h3>
                        <p class="text-sm text-gray-500">Manage your business identity and automation rules.</p>
                    </div>
                </div>

                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Left: Logo & Icon Upload -->
                        <div class="space-y-6">
                            <div>
                                <x-input-label :value="__('Company Logo')" />
                                <div class="relative group mt-2">
                                    <div class="w-full h-32 bg-slate-50 dark:bg-slate-900 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center overflow-hidden transition-all group-hover:border-indigo-300">
                                        @if(isset($settings['company_logo']))
                                            <img src="{{ asset('storage/' . $settings['company_logo']->value) }}" class="w-full h-full object-contain p-4" id="logo_preview">
                                        @else
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        @endif
                                    </div>
                                    <input type="file" name="company_logo" class="absolute inset-0 opacity-0 cursor-pointer" onchange="const img = document.getElementById('logo_preview'); if(img) img.src = window.URL.createObjectURL(this.files[0]);">
                                </div>
                                <p class="text-[9px] text-slate-400 mt-2 italic">* Digunakan pada Laporan & Halaman Login</p>
                            </div>

                            <div>
                                <x-input-label :value="__('App Icon (Favicon)')" />
                                <div class="flex items-center gap-4 mt-2">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden relative group">
                                        @if(isset($settings['app_icon']))
                                            <img src="{{ asset('storage/' . $settings['app_icon']->value) }}" class="w-full h-full object-cover" id="icon_preview">
                                        @else
                                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                                        @endif
                                        <input type="file" name="app_icon" class="absolute inset-0 opacity-0 cursor-pointer" onchange="const img = document.getElementById('icon_preview'); if(img) img.src = window.URL.createObjectURL(this.files[0]);">
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Upload Icon</p>
                                        <p class="text-[9px] text-slate-400">Format: ICO, PNG (Square)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Details -->
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <x-input-label for="company_name" :value="__('Company Name')" />
                            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="$settings['company_name']->value ?? ''" />
                        </div>

                        <div class="md:col-span-1">
                            <x-input-label for="company_tax_id" :value="__('NPWP / Tax ID')" />
                            <x-text-input id="company_tax_id" name="company_tax_id" type="text" class="mt-1 block w-full" :value="$settings['company_tax_id']->value ?? ''" placeholder="00.000.000.0-000.000" />
                        </div>

                        <div>
                            <x-input-label for="company_email" :value="__('Business Email')" />
                                <x-text-input id="company_email" name="company_email" type="email" class="mt-1 block w-full" :value="$settings['company_email']->value ?? ''" placeholder="billing@ids.net.id" />
                            </div>

                            <div>
                                <x-input-label for="company_phone" :value="__('Contact Number')" />
                                <x-text-input id="company_phone" name="company_phone" type="text" class="mt-1 block w-full" :value="$settings['company_phone']->value ?? ''" placeholder="+62..." />
                            </div>

                            <div>
                                <x-input-label for="company_website" :value="__('Website URL')" />
                                <x-text-input id="company_website" name="company_website" type="text" class="mt-1 block w-full" :value="$settings['company_website']->value ?? ''" placeholder="https://..." />
                            </div>

                            <div>
                                <x-input-label for="company_domain" :value="__('User Domain Suffix')" />
                                <div class="flex mt-1">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-500 text-sm">@</span>
                                    <x-text-input id="company_domain" name="company_domain" type="text" class="block w-full rounded-none rounded-r-md" :value="$settings['company_domain']->value ?? ''" placeholder="ids.net.id" />
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="company_address" :value="__('Office Address')" />
                                <textarea id="company_address" name="company_address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $settings['company_address']->value ?? '' }}</textarea>
                            </div>

                            <div class="md:col-span-2 mt-8 pt-8 border-t border-gray-100 dark:border-gray-700">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                    Ticket Numbering Prefixes
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                    <div>
                                        <x-input-label for="ticket_prefix_gangguan" :value="__('Gangguan (TT)')" />
                                        <x-text-input id="ticket_prefix_gangguan" name="ticket_prefix_gangguan" type="text" class="mt-1 block w-full" :value="$settings['ticket_prefix_gangguan']->value ?? 'TT'" />
                                    </div>
                                    <div>
                                        <x-input-label for="ticket_prefix_aktivasi" :value="__('Aktivasi (AO)')" />
                                        <x-text-input id="ticket_prefix_aktivasi" name="ticket_prefix_aktivasi" type="text" class="mt-1 block w-full" :value="$settings['ticket_prefix_aktivasi']->value ?? 'AO'" />
                                    </div>
                                    <div>
                                        <x-input-label for="ticket_prefix_dismantle" :value="__('Dismantle (DO)')" />
                                        <x-text-input id="ticket_prefix_dismantle" name="ticket_prefix_dismantle" type="text" class="mt-1 block w-full" :value="$settings['ticket_prefix_dismantle']->value ?? 'DO'" />
                                    </div>
                                    <div>
                                        <x-input-label for="ticket_prefix_relokasi" :value="__('Relokasi (RL)')" />
                                        <x-text-input id="ticket_prefix_relokasi" name="ticket_prefix_relokasi" type="text" class="mt-1 block w-full" :value="$settings['ticket_prefix_relokasi']->value ?? 'RL'" />
                                    </div>
                                    <div>
                                        <x-input-label for="ticket_prefix_maintenance" :value="__('Maintenance (MT)')" />
                                        <x-text-input id="ticket_prefix_maintenance" name="ticket_prefix_maintenance" type="text" class="mt-1 block w-full" :value="$settings['ticket_prefix_maintenance']->value ?? 'MT'" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                        <x-primary-button>
                            {{ __('Save Settings') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
