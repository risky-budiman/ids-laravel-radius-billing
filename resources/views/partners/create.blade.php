<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New Partner') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl py-6">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <form action="{{ route('partners.store') }}" method="POST" class="p-8">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Account Info -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Partner Account
                        </h3>
                        
                        <div>
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            </div>
                        </div>
                    </div>

                    <!-- Commission & Bank -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Commission & Payout
                        </h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="commission_rate" :value="__('Commission Rate')" />
                                <x-text-input id="commission_rate" name="commission_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('commission_rate', get_setting('default_commission_rate', 10))" required />
                            </div>
                            <div>
                                <x-input-label for="commission_type" :value="__('Rate Type')" />
                                <select id="commission_type" name="commission_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="percentage" {{ old('commission_type', get_setting('default_commission_type')) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="bank_name" :value="__('Bank Name')" />
                            <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name')" placeholder="e.g. BCA, Mandiri" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <x-input-label for="bank_account_number" :value="__('Account Number')" />
                                <x-text-input id="bank_account_number" name="bank_account_number" type="text" class="mt-1 block w-full" :value="old('bank_account_number')" />
                            </div>
                            <div class="col-span-2">
                                <x-input-label for="bank_account_name" :value="__('Account Holder Name')" />
                                <x-text-input id="bank_account_name" name="bank_account_name" type="text" class="mt-1 block w-full" :value="old('bank_account_name')" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-12 pt-8 border-t border-gray-100 dark:border-gray-700">
                    <x-secondary-button type="button" onclick="window.history.back()" class="mr-3">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-primary-button>
                        {{ __('Create Partner Account') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
