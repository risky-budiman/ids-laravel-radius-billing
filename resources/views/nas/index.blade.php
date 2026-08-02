<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Routers (NAS)') }}
            </h2>
            <a href="{{ route('nas.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Add NAS
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div x-data="{
        modalOpen: false,
        nasName: '',
        nasSecret: '',
        nasShortname: '',
        radiusIp: '{{ $serverIp }}',
        get script() {
            return `# --- MIKROTIK RADIUS CLIENT CONFIGURATION ---\n` +
                   `/radius add service=ppp,hotspot address=${this.radiusIp} secret=${this.nasSecret} authentication-port=1812 accounting-port=1813 comment=&quot;Radius Server&quot;\n` +
                   `/radius incoming set accept=yes port=3799\n` +
                   `/ppp aaa set use-radius=yes\n` +
                   `/ip hotspot profile set [find default=yes] use-radius=yes`;
        },
        openScriptModal(name, secret, shortname) {
            this.nasName = name;
            this.nasSecret = secret;
            this.nasShortname = shortname;
            this.modalOpen = true;
        },
        copyScript() {
            navigator.clipboard.writeText(this.script);
        }
    }">
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shortname</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Secret</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($routers as $router)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                    {{ $routers->firstItem() + $loop->index }}
                                </td>
                                <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400">{{ $router->nasname }}</td>
                                <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $router->shortname }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono tracking-widest">••••••••</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $router->description }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <button type="button" @click="openScriptModal('{{ $router->nasname }}', '{{ $router->secret }}', '{{ $router->shortname }}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-semibold transition-colors">Get Script</button>
                                        <a href="{{ route('nas.edit', $router->id) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 text-sm font-semibold transition-colors">Edit</a>
                                        <form action="{{ route('nas.destroy', $router->id) }}" method="POST" onsubmit="return confirm('Delete this NAS?');" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 text-sm font-semibold transition-colors">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No Routers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
                {{ $routers->links() }}
            </div>
        </div>

        <!-- Script Modal -->
        <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="modalOpen = false"></div>
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-100 dark:border-gray-700/60 glass">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700/50 flex justify-between items-center">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Mikrotik Configuration Script
                        </h3>
                        <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Copy and paste this script directly into Mikrotik Terminal (New Terminal) to connect your Mikrotik with the Radius Server.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-900/30 p-4 rounded-xl border border-slate-100 dark:border-slate-800/50">
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Router IP / NAS IP</span>
                                <span class="text-sm font-bold text-slate-850 dark:text-slate-200" x-text="nasName"></span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shared Secret</span>
                                <span class="text-sm font-mono font-bold text-slate-850 dark:text-slate-200" x-text="nasSecret"></span>
                            </div>
                        </div>
                        <div>
                            <x-input-label value="Radius Server IP Address (Active)" />
                            <x-text-input type="text" x-model="radiusIp" class="mt-1 w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed text-gray-500 dark:text-gray-400" readonly disabled />
                            <p class="text-[10px] text-gray-500 mt-1">IP server Radius aktif saat ini (dideteksi secara otomatis oleh sistem).</p>
                        </div>
                        <div>
                            <x-input-label value="Mikrotik Terminal Script" />
                            <div class="relative mt-1">
                                <textarea readonly x-text="script" rows="6" class="w-full font-mono text-xs bg-slate-950 text-emerald-400 border border-slate-800 rounded-xl p-4 focus:ring-0 focus:border-slate-800 cursor-text select-all"></textarea>
                                <div class="absolute right-3 bottom-3" x-data="{ copied: false }">
                                    <button type="button" @click="copyScript(); copied = true; setTimeout(() => copied = false, 2000)" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[10px] font-bold transition-all shadow-md hover:shadow-indigo-500/20 flex items-center">
                                        <span x-show="!copied">Copy Script</span>
                                        <span x-show="copied" class="flex items-center text-emerald-200">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Copied!
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50 flex justify-end">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-semibold transition-all">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
