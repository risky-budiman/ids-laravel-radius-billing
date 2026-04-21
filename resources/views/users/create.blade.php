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
                            <select id="role" name="role" class="block mt-1 w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all">
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            <p class="mt-2 text-xs text-gray-400 italic font-medium">*Each role has specific access limitations in the system.</p>
                        </div>

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
