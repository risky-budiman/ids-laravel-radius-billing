<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Integration: WhatsApp Gateways') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 sm:px-6 lg:px-8">
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

                <!-- Wablas -->
                @php $w = $gateways->get('wablas'); @endphp
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-blue-50/50 dark:bg-blue-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.082 19.163c-1.81 0-3.534-.446-5.112-1.267l-5.666 1.48 1.508-5.494c-.908-1.625-1.401-3.475-1.401-5.359 0-6.178 5.039-11.206 11.234-11.206 5.864 0 10.952 4.675 10.952 11.215 0 6.182-5.043 11.21-11.235 11.231z"></path></svg>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Wablas WhatsApp API</h3>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ ($w->is_active ?? false) ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ ($w->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                    </div>
                    <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="provider" value="wablas">
                        <input type="hidden" name="type" value="whatsapp">
                        
                        <div>
                            <x-input-label value="Wablas Domain" />
                            <x-text-input name="credentials[domain]" value="{{ $w->credentials['domain'] ?? 'https://console.wablas.com' }}" class="mt-1 w-full" placeholder="https://console.wablas.com" />
                        </div>
                        <div>
                            <x-input-label value="API Security Token" />
                            <x-text-input name="credentials[token]" value="{{ $w->credentials['token'] ?? '' }}" class="mt-1 w-full" type="password" />
                        </div>
                        <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="is_active" value="1" {{ ($w->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active WhatsApp Gateway</span>
                            </label>
                            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                        </div>
                    </form>
                </div>

                <!-- Starsender -->
                @php $s = $gateways->get('starsender'); @endphp
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-amber-50/50 dark:bg-amber-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Starsender API</h3>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ ($s->is_active ?? false) ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ ($s->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                    </div>
                    <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="provider" value="starsender">
                        <input type="hidden" name="type" value="whatsapp">
                        
                        <div>
                            <x-input-label value="API Key / Token" />
                            <x-text-input name="credentials[token]" value="{{ $s->credentials['token'] ?? '' }}" class="mt-1 w-full" type="password" />
                        </div>
                        <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="is_active" value="1" {{ ($s->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active WhatsApp Gateway</span>
                            </label>
                            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                        </div>
                    </form>
                </div>

                <!-- Mekari Qontak -->
                @php $m = $gateways->get('mekari'); @endphp
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">Mekari Qontak (Enterprise)</h3>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ ($m->is_active ?? false) ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ ($m->is_active ?? false) ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                    </div>
                    <form action="{{ route('integrations.update') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="provider" value="mekari">
                        <input type="hidden" name="type" value="whatsapp">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Access Token (Bearer)" />
                                <x-text-input name="credentials[token]" value="{{ $m->credentials['token'] ?? '' }}" class="mt-1 w-full" type="password" />
                            </div>
                            <div>
                                <x-input-label value="Channel Integration ID" />
                                <x-text-input name="credentials[channel_id]" value="{{ $m->credentials['channel_id'] ?? '' }}" class="mt-1 w-full" placeholder="UUID Channel Mekari" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label value="Default Template ID (Optional)" />
                                <x-text-input name="credentials[template_id]" value="{{ $m->credentials['template_id'] ?? '' }}" class="mt-1 w-full" placeholder="Template ID for simple notifications" />
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="is_active" value="1" {{ ($m->is_active ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-gray-600 dark:text-gray-400">Set as Active WhatsApp Gateway</span>
                            </label>
                            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm font-semibold">Save Settings</button>
                        </div>
                    </form>
                </div>

                <div class="mt-8 bg-indigo-50 dark:bg-indigo-900/20 rounded-3xl p-8 border border-indigo-100 dark:border-indigo-800/50">
                    <h4 class="font-bold text-indigo-900 dark:text-indigo-300 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Panduan Penggunaan Multi-Gateway & Failover
                    </h4>
                    <div class="space-y-4 text-sm text-indigo-800 dark:text-indigo-400">
                        <div class="flex items-start">
                            <span class="bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 w-6 h-6 rounded-full flex items-center justify-center mr-3 shrink-0">1</span>
                            <p><b>Sistem Failover Otomatis:</b> Jika Anda mengaktifkan lebih dari satu provider, sistem akan mencoba mengirim pesan lewat provider pertama. Jika gagal, sistem otomatis mencoba lewat provider kedua, dst.</p>
                        </div>
                        <div class="flex items-start">
                            <span class="bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 w-6 h-6 rounded-full flex items-center justify-center mr-3 shrink-0">2</span>
                            <p><b>Urutan Prioritas:</b> Provider yang baru saja Anda update/simpan pengaturannya akan menjadi prioritas terakhir. Untuk mengatur ulang, cukup simpan kembali provider yang ingin dijadikan utama.</p>
                        </div>
                        <div class="flex items-start">
                            <span class="bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 w-6 h-6 rounded-full flex items-center justify-center mr-3 shrink-0">3</span>
                            <p><b>Monitoring:</b> Anda dapat melihat provider mana yang berhasil mengirim pesan melalui menu <b>Billing > Riwayat Pesan</b>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
