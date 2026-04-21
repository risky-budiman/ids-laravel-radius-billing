<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Integration: Payment Gateways') }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        @if(session('success'))
            <div class="px-4 py-3 bg-green-100 border border-green-200 text-green-700 rounded-xl font-medium">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-xl">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Midtrans -->
            @php $m = $gateways->get('midtrans'); @endphp
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Midtrans</h3>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ ($m->is_active ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ ($m->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="midtrans">
                    <input type="hidden" name="type" value="payment">
                    
                    <div>
                        <x-input-label value="Environment" />
                        <select name="credentials[environment]" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-md shadow-sm">
                            <option value="sandbox" {{ ($m->credentials['environment'] ?? '') == 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                            <option value="production" {{ ($m->credentials['environment'] ?? '') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Merchant ID" />
                        <x-text-input name="credentials[merchant_id]" value="{{ $m->credentials['merchant_id'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label value="Server Key" />
                        <x-text-input name="credentials[server_key]" value="{{ $m->credentials['server_key'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label value="Client Key" />
                        <x-text-input name="credentials[client_key]" value="{{ $m->credentials['client_key'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ ($m->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active Gateway</span>
                        </label>
                        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                    </div>
                </form>
            </div>

            <!-- Xendit -->
            @php $x = $gateways->get('xendit'); @endphp
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-blue-50/50 dark:bg-blue-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Xendit</h3>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ ($x->is_active ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ ($x->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="xendit">
                    <input type="hidden" name="type" value="payment">
                    
                    <div>
                        <x-input-label value="API Key (Secret)" />
                        <x-text-input name="credentials[secret_key]" value="{{ $x->credentials['secret_key'] ?? '' }}" class="mt-1 w-full" type="password" />
                    </div>
                    <div>
                        <x-input-label value="Callback Token (Verification)" />
                        <x-text-input name="credentials[callback_token]" value="{{ $x->credentials['callback_token'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ ($x->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active Gateway</span>
                        </label>
                        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                    </div>
                </form>
            </div>

            <!-- Duitku -->
            @php $d = $gateways->get('duitku'); @endphp
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-rose-50/50 dark:bg-rose-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Duitku</h3>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ ($d->is_active ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ ($d->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="duitku">
                    <input type="hidden" name="type" value="payment">
                    
                    <div>
                        <x-input-label value="Environment" />
                        <select name="credentials[environment]" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-md shadow-sm">
                            <option value="sandbox" {{ ($d->credentials['environment'] ?? '') == 'sandbox' ? 'selected' : '' }}>Sandbox (Test)</option>
                            <option value="production" {{ ($d->credentials['environment'] ?? '') == 'production' ? 'selected' : '' }}>Production (Live)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Merchant Code" />
                        <x-text-input name="credentials[merchant_code]" value="{{ $d->credentials['merchant_code'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label value="API Key" />
                        <x-text-input name="credentials[api_key]" value="{{ $d->credentials['api_key'] ?? '' }}" class="mt-1 w-full" type="password" />
                    </div>
                    <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ ($d->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active Gateway</span>
                        </label>
                        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                    </div>
                </form>
            </div>

            <!-- Moota -->
            @php $mo = $gateways->get('moota'); @endphp
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-teal-50/50 dark:bg-teal-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">Moota (Mutation Check)</h3>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ ($mo->is_active ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ ($mo->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="moota">
                    <input type="hidden" name="type" value="payment">
                    
                    <div>
                        <x-input-label value="Personal Access Token (API Token)" />
                        <x-text-input name="credentials[api_token]" value="{{ $mo->credentials['api_token'] ?? '' }}" class="mt-1 w-full" type="password" />
                    </div>
                    <div>
                        <x-input-label value="Bank ID (Optional/Default)" />
                        <x-text-input name="credentials[bank_id]" value="{{ $mo->credentials['bank_id'] ?? '' }}" class="mt-1 w-full" />
                    </div>
                    <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ ($mo->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active Gateway</span>
                        </label>
                        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
