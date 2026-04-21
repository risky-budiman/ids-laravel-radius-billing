<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Integration: WhatsApp Gateways') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if(session('success'))
            <div class="px-4 py-3 bg-green-100 border border-green-200 text-green-700 rounded-xl font-medium">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-xl">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6">

            <!-- Fonnte -->
            @php $f = $gateways->get('fonnte'); @endphp
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-emerald-50/50 dark:bg-emerald-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.082 19.163c-1.81 0-3.534-.446-5.112-1.267l-5.666 1.48 1.508-5.494c-.908-1.625-1.401-3.475-1.401-5.359 0-6.178 5.039-11.206 11.234-11.206 5.864 0 10.952 4.675 10.952 11.215 0 6.182-5.043 11.21-11.235 11.231z"></path></svg>
                        <h3 class="font-bold text-gray-900 dark:text-gray-100">Fonnte WhatsApp API</h3>
                    </div>
                    <span class="px-2 py-1 rounded text-xs font-bold {{ ($f->is_active ?? false) ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ ($f->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="provider" value="fonnte">
                    <input type="hidden" name="type" value="whatsapp">
                    
                    <div>
                        <x-input-label value="API Security Token" />
                        <x-text-input name="credentials[token]" value="{{ $f->credentials['token'] ?? '' }}" class="mt-1 w-full" type="password" />
                        <p class="text-xs text-gray-500 mt-1">Found in your Fonnte Dashboard -> API Tokens.</p>
                    </div>
                    <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" name="is_active" value="1" {{ ($f->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active WhatsApp Gateway</span>
                        </label>
                        <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
