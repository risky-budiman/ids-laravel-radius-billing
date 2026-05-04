<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Staff Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" value="{{ __('Full Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="text" name="name" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" value="{{ __('Email Address') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="email" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="role" value="{{ __('Assigned Role') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                            <select id="role" name="role" onchange="toggleFields(this.value)" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all">
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            <p class="mt-2 text-xs text-gray-400 italic font-medium">*Each role has specific access limitations in the system.</p>
                        </div>

                        <!-- Secondary Capability: Sales -->
                        <div id="sales_toggle_container" class="p-4 bg-indigo-50/30 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100/50 dark:border-indigo-800/30">
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" name="is_sales" value="1" class="sr-only peer" {{ old('role') == 'sales' ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">Enable Sales Functionality</span>
                            </label>
                            <p class="mt-2 text-[10px] text-gray-400 leading-tight">If enabled, this user can be selected as a Sales Referral for new customers and earn commissions, regardless of their primary role.</p>
                        </div>

                        <!-- Partner Fields (Conditional) -->
                        <div id="partner_fields" class="hidden p-6 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-3xl border border-emerald-100 dark:border-emerald-800/30 space-y-4">
                            <div class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span class="text-xs font-black uppercase tracking-widest">Partner Configuration</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="commission_rate" value="{{ __('Default Commission Rate') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <x-text-input id="commission_rate" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="number" step="0.01" name="commission_rate" :value="old('commission_rate')" />
                                </div>

                                <div>
                                    <x-input-label for="commission_type" value="{{ __('Commission Type') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <select id="commission_type" name="commission_type" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:border-emerald-500 focus:ring-emerald-500 shadow-sm transition-all">
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount (Rp)</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <x-input-label for="bank_name" value="{{ __('Bank Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_name" :value="old('bank_name')" placeholder="BCA, Mandiri, etc" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_account_number" value="{{ __('Account Number') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_account_number" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_account_number" :value="old('bank_account_number')" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_account_name" value="{{ __('Account Holder Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_account_name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_account_name" :value="old('bank_account_name')" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function togglePartnerFields(role) {
                                const container = document.getElementById('partner_fields');
                                if (role === 'mitra') {
                                    container.classList.remove('hidden');
                                } else {
                                    container.classList.add('hidden');
                                }
                            }
                        </script>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="password" value="{{ __('Initial Password') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="password" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="password" name="password" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" value="{{ __('Confirm Password') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="password" name="password_confirmation" required autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-10 pt-6 border-t border-gray-50 dark:border-gray-700">
                            <a href="{{ route('users.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mr-6 transition-colors">
                                Cancel
                            </a>
                            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 px-8 py-3 rounded-2xl shadow-lg shadow-indigo-500/20 uppercase text-xs">
                                {{ __('Create Staff Account') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
