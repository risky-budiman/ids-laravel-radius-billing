<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit NAS: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $router->nasname }}</span>
        </h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{
        nasSecret: '{{ $router->secret }}',
        radiusIp: '{{ $serverIp }}',
        get script() {
            return `# --- MIKROTIK RADIUS CLIENT CONFIGURATION ---\n` +
                   `/radius add service=ppp,hotspot address=${this.radiusIp} secret=${this.nasSecret} authentication-port=1812 accounting-port=1813 comment=&quot;Radius Server&quot;\n` +
                   `/radius incoming set accept=yes port=3799\n` +
                   `/ppp aaa set use-radius=yes\n` +
                   `/ip hotspot profile set [find default=yes] use-radius=yes`;
        },
        copyScript() {
            navigator.clipboard.writeText(this.script);
        }
    }">
        <!-- Edit Form -->
        <div class="glass lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden h-fit">
            <form action="{{ route('nas.update', $router->id) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <x-input-label for="nasname" :value="__('NAS IP Address (nasname)')" />
                        <x-text-input id="nasname" name="nasname" type="text" class="mt-1 block w-full" :value="old('nasname', $router->nasname)" required />
                    </div>
                    <div>
                        <x-input-label for="shortname" :value="__('Shortname')" />
                        <x-text-input id="shortname" name="shortname" type="text" class="mt-1 block w-full" :value="old('shortname', $router->shortname)" />
                    </div>
                    <div>
                        <x-input-label for="secret" :value="__('RADIUS Secret')" />
                        <x-text-input id="secret" name="secret" type="text" class="mt-1 block w-full" :value="old('secret', $router->secret)" required x-model="nasSecret" />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $router->description)" required />
                    </div>
                </div>
                <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('nas.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                    <x-primary-button>Update NAS</x-primary-button>
                </div>
            </form>
        </div>

        <!-- Mikrotik Script Generator Panel -->
        <div class="glass lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between h-fit">
            <div>
                <h3 class="text-base font-black text-slate-800 dark:text-slate-100 flex items-center mb-2">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Mikrotik Script
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Secara otomatis menyesuaikan dengan secret yang Anda masukkan pada form.</p>

                <div class="mb-4">
                    <x-input-label value="Radius Server IP Address (Active)" />
                    <x-text-input type="text" x-model="radiusIp" class="mt-1 w-full text-xs bg-gray-100 dark:bg-gray-700 cursor-not-allowed text-gray-500 dark:text-gray-400" readonly disabled />
                </div>

                <div class="relative">
                    <textarea readonly x-text="script" rows="8" class="w-full font-mono text-[10px] bg-slate-950 text-emerald-400 border border-slate-800 rounded-xl p-3 focus:ring-0 focus:border-slate-800 cursor-text select-all"></textarea>
                    <div class="absolute right-2 bottom-2" x-data="{ copied: false }">
                        <button type="button" @click="copyScript(); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[9px] font-bold transition-all shadow-md flex items-center">
                            <span x-show="!copied">Copy Script</span>
                            <span x-show="copied" class="flex items-center text-emerald-200">
                                <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Copied!
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
