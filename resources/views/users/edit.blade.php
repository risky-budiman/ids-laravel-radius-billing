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
                            <select id="role" name="role" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed bg-gray-50' : '' }} focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
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
                            <a href="{{ route('users.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mr-6 transition-colors">
                                Cancel
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
