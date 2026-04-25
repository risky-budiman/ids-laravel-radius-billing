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
                        <!-- Left: Logo Upload -->
                        <div class="space-y-4">
                            <x-input-label :value="__('Company Logo')" />
                            <div class="relative group">
                                <div class="w-full h-40 bg-gray-50 dark:bg-gray-900 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center overflow-hidden transition-all group-hover:border-indigo-300">
                                    @if(isset($settings['company_logo']))
                                        <img src="{{ asset('storage/' . $settings['company_logo']->value) }}" class="w-full h-full object-contain p-4" id="logo_preview">
                                    @else
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <p class="mt-2 text-[10px] text-gray-400 font-bold uppercase tracking-widest">Click to upload</p>
                                    @endif
                                </div>
                                <input type="file" name="company_logo" class="absolute inset-0 opacity-0 cursor-pointer" onchange="document.getElementById('logo_preview_new').src = window.URL.createObjectURL(this.files[0]); document.getElementById('logo_preview_new').classList.remove('hidden')">
                            </div>
                            <img id="logo_preview_new" class="hidden w-20 h-20 object-contain mx-auto mt-2 rounded-lg border border-indigo-100 shadow-sm">
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
