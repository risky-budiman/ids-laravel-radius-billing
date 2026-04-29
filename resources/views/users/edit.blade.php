<x-app-layout>
    <x-slot name="header">
        <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Staff Account') }}: {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" value="{{ __('Full Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" value="{{ __('Email Address') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                <x-text-input id="email" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="email" name="email" :value="old('email', $user->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="role" value="{{ __('Assigned Role') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                            <select id="role" name="role" onchange="togglePartnerFields(this.value)" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed bg-gray-50' : '' }} focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if($user->id === auth()->id())
                                <input type="hidden" name="role" value="{{ $user->role }}">
                                <p class="mt-2 text-[10px] text-amber-600 font-bold uppercase tracking-widest">You cannot change your own root role here.</p>
                            @endif
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <!-- Partner Fields (Conditional) -->
                        <div id="partner_fields" class="{{ $user->role === 'mitra' ? '' : 'hidden' }} p-6 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-3xl border border-emerald-100 dark:border-emerald-800/30 space-y-4">
                            <div class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span class="text-xs font-black uppercase tracking-widest">Partner Configuration</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="commission_rate" value="{{ __('Default Commission Rate') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <x-text-input id="commission_rate" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="number" step="0.01" name="commission_rate" :value="old('commission_rate', $user->commission_rate)" />
                                    <p class="mt-1 text-[10px] text-gray-400">Default rate for this partner.</p>
                                </div>

                                <div>
                                    <x-input-label for="commission_type" value="{{ __('Commission Type') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <select id="commission_type" name="commission_type" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:border-emerald-500 focus:ring-emerald-500 shadow-sm transition-all">
                                        <option value="percentage" {{ old('commission_type', $user->commission_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                        <option value="fixed" {{ old('commission_type', $user->commission_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount (Rp)</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <x-input-label for="bank_name" value="{{ __('Bank Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_name" :value="old('bank_name', $user->bank_name)" placeholder="BCA, Mandiri, etc" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_account_number" value="{{ __('Account Number') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_account_number" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_account_number" :value="old('bank_account_number', $user->bank_account_number)" />
                                    </div>
                                    <div>
                                        <x-input-label for="bank_account_name" value="{{ __('Account Holder Name') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                        <x-text-input id="bank_account_name" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-emerald-500" type="text" name="bank_account_name" :value="old('bank_account_name', $user->bank_account_name)" />
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

                        <div class="p-6 bg-gray-50/50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-700 space-y-4">
                            <div class="flex items-center space-x-2 text-indigo-600 dark:text-indigo-400 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span class="text-xs font-black uppercase tracking-widest">Password Reset (Optional)</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="password" value="{{ __('New Password') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <x-text-input id="password" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="password" name="password" autocomplete="new-password" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="password_confirmation" value="{{ __('Confirm New Password') }}" class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-2" />
                                    <x-text-input id="password_confirmation" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:ring-indigo-500" type="password" name="password_confirmation" autocomplete="new-password" />
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400">Leave blank if you don't want to change the password.</p>
                        </div>

                        <div class="flex items-center justify-end mt-10 pt-6 border-t border-gray-50 dark:border-gray-700">
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 border border-transparent rounded-2xl font-bold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-600 transition ease-in-out duration-150 mr-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Kembali
                            </a>
                            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 px-8 py-3 rounded-2xl shadow-lg shadow-indigo-500/20 uppercase text-xs">
                                {{ __('Update Account Information') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
