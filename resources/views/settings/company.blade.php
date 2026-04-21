<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Company Profile & Settings') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400 font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data" class="p-8">
                @csrf

                <!-- Section: Visual Identity -->
                <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Visual Identity</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <div class="flex flex-col items-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center w-full">Current Logo</p>
                                <div class="w-32 h-32 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900/50">
                                    @if(get_setting('company_logo'))
                                        <img src="{{ asset('storage/' . get_setting('company_logo')) }}" alt="Logo" class="max-w-full max-h-full object-contain p-2">
                                    @else
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="company_logo" :value="__('Upload New Logo')" />
                            <input id="company_logo" name="company_logo" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300" />
                            <p class="mt-2 text-xs text-gray-500 italic">Recommended: Transparent PNG or SVG. Max 2MB.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('company_logo')" />
                        </div>
                    </div>
                </div>

                <!-- Section: Company Basics -->
                <div class="pb-6 mb-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-input-label for="company_name" :value="__('Company Name')" />
                            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', get_setting('company_name', config('app.name')))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="company_email" :value="__('Company Email')" />
                                <x-text-input id="company_email" name="company_email" type="email" class="mt-1 block w-full" :value="old('company_email', get_setting('company_email'))" />
                                <x-input-error class="mt-2" :messages="$errors->get('company_email')" />
                            </div>
                            <div>
                                <x-input-label for="company_phone" :value="__('Company Phone')" />
                                <x-text-input id="company_phone" name="company_phone" type="text" class="mt-1 block w-full" :value="old('company_phone', get_setting('company_phone'))" />
                                <x-input-error class="mt-2" :messages="$errors->get('company_phone')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="company_address" :value="__('Company Address')" />
                            <textarea id="company_address" name="company_address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">{{ old('company_address', get_setting('company_address')) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('company_address')" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4">
                    <x-primary-button>
                        {{ __('Save Changes') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
