<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
            NOC Bot Integration
        </h2>
        <p class="text-sm text-gray-500">Hubungkan sistem monitoring ke Telegram & WhatsApp untuk peringatan dini.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <form action="{{ route('integrations.noc-bot.update') }}" method="POST">
                    @csrf
                    
                    <div class="p-8 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex items-center gap-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl text-blue-600">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.69-.52.35-.97.52-1.33.51-.4-.01-1.18-.23-1.75-.42-.71-.23-1.28-.35-1.23-.74.03-.2.31-.4.83-.61 3.23-1.4 5.39-2.33 6.47-2.78 3.07-1.28 3.71-1.5 4.13-1.51.09 0 .3.02.44.13.11.09.15.22.16.31.01.05.02.16 0 .25z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Telegram Bot Settings</h3>
                            <p class="text-xs text-gray-500">Gunakan BotFather untuk mendapatkan Token dan cari Chat ID grup Anda.</p>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Telegram Bot Token" />
                                <x-text-input name="telegram_bot_token" :value="get_setting('telegram_bot_token')" class="mt-1 w-full" placeholder="123456789:ABCDE..." />
                            </div>
                            <div>
                                <x-input-label value="Telegram NOC Chat ID" />
                                <x-text-input name="telegram_noc_chat_id" :value="get_setting('telegram_noc_chat_id')" class="mt-1 w-full" placeholder="-100123456789" />
                            </div>
                        </div>

                        <hr class="border-gray-100 dark:border-gray-700">

                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl text-emerald-600">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">WhatsApp NOC Alert</h3>
                                <p class="text-xs text-gray-500">Nomor tujuan pengiriman alert via WhatsApp (Gunakan format 628xxx).</p>
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Target Alert WhatsApp" />
                            <div class="mt-2 flex gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="whatsapp_noc_target_type" value="personal" {{ get_setting('whatsapp_noc_target_type', 'personal') == 'personal' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Nomor Pribadi</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="whatsapp_noc_target_type" value="group" {{ get_setting('whatsapp_noc_target_type') == 'group' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Grup WhatsApp</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Nomor WA / Nama Grup / ID Grup" />
                            <x-text-input name="whatsapp_noc_number" :value="get_setting('whatsapp_noc_number')" class="mt-1 w-full md:w-1/2" placeholder="628xxx atau Nama Grup" />
                            <div class="mt-2 space-y-1">
                                <p class="text-[10px] text-gray-400 italic">• Jika Pribadi: Gunakan format 628123456xxx</p>
                                <p class="text-[10px] text-gray-400 italic">• Jika Grup: Gunakan Nama Grup (contoh: NOC TEAM) atau ID Grup Fonnte.</p>
                                <p class="text-[10px] text-amber-500 font-bold italic">• Penting: Nomor pengirim (Fonnte) harus sudah bergabung di grup tersebut.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 flex justify-end items-center border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" class="bg-indigo-600 text-white px-10 py-3 rounded-2xl text-sm font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                            Simpan Pengaturan Bot
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-8 p-6 bg-amber-50 dark:bg-amber-900/20 rounded-3xl border border-amber-100 dark:border-amber-800/50">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-amber-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h4 class="font-bold text-amber-900 dark:text-amber-300">Cara Mencari Chat ID Telegram</h4>
                        <p class="text-sm text-amber-700 dark:text-amber-400/80 leading-relaxed mt-1">
                            1. Buat grup Telegram dan tambahkan Bot Anda.<br>
                            2. Jadikan Bot sebagai Admin.<br>
                            3. Tambahkan bot <b>@get_id_bot</b> ke grup tersebut, atau kirim pesan apapun ke grup lalu akses: <code class="bg-amber-100 px-1 rounded text-amber-900 font-mono">https://api.telegram.org/bot<TOKEN_ANDA>/getUpdates</code>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
