<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5 font-semibold">
                    <a href="{{ route('acs-servers.devices') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold">ONT Management</a>
                    <span>/</span>
                    <span class="text-indigo-500 dark:text-indigo-400">ACS Servers</span>
                </div>
                <h2 class="font-black text-3xl text-gray-800 dark:text-gray-100 tracking-tight leading-none">
                    {{ __('GenieACS Servers') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Manage server endpoints and credentials for automatic remote management protocol connections</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900 text-green-700 dark:text-green-400 rounded-2xl flex items-center gap-3 text-sm font-semibold">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900 text-rose-700 dark:text-rose-400 rounded-2xl flex flex-col gap-1 text-sm font-semibold">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Please correct the following errors:</span>
                </div>
                <ul class="list-disc list-inside pl-7 space-y-0.5 font-medium">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Server Form -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none overflow-hidden">
                    <div class="bg-gray-50/50 dark:bg-gray-900/30 px-6 py-4 border-b border-gray-100 dark:border-gray-700/50">
                        <h3 class="font-extrabold text-sm text-gray-800 dark:text-gray-100 uppercase tracking-wide">Add New Server</h3>
                    </div>
                    <form action="{{ route('acs-servers.store') }}" method="POST" class="p-6 space-y-5">
                        @csrf
                        <div>
                            <x-input-label for="name" value="Server Name" class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2" />
                            <input id="name" name="name" type="text" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200" placeholder="e.g. GenieACS Pusat" required />
                        </div>

                        <div>
                            <x-input-label for="url" value="API URL Endpoint" class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2" />
                            <input id="url" name="url" type="url" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200" placeholder="http://10.0.0.1:7557" required />
                            <p class="mt-1.5 text-[10px] text-gray-400 dark:text-gray-500 font-medium">Include port if necessary (default GenieACS is 7557)</p>
                        </div>

                        <div>
                            <x-input-label for="description" value="Description / Notes" class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2" />
                            <textarea id="description" name="description" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-2xl text-sm outline-none transition-all dark:text-gray-200" placeholder="Location or notes..." rows="3"></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input id="is_active" name="is_active" type="checkbox" value="1" checked class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-0">
                            <label for="is_active" class="text-sm text-gray-600 dark:text-gray-400 font-bold select-none cursor-pointer">Set as Active Server</label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-xs transition-all shadow-md shadow-indigo-500/10 active:scale-95 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Save Server Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Server List Table -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/80 shadow-md shadow-gray-100/10 dark:shadow-none overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30 border-b border-gray-100 dark:border-gray-700/50">
                                    <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Server Name</th>
                                    <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">URL Endpoint</th>
                                    <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4.5 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                @forelse($servers as $server)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all duration-200">
                                        <td class="px-6 py-4.5">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-extrabold text-gray-800 dark:text-gray-200">{{ $server->name }}</span>
                                                @if($server->description)
                                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $server->description }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4.5">
                                            <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ $server->url }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4.5">
                                            @if($server->is_active)
                                                <span class="px-2.5 py-1 bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 rounded-lg text-[10px] font-extrabold uppercase border border-green-100/20 flex items-center gap-1.5 w-fit">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 bg-gray-50 dark:bg-gray-900 text-gray-400 rounded-lg text-[10px] font-extrabold uppercase border border-gray-100/30 flex items-center gap-1.5 w-fit">
                                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4.5">
                                            <div class="flex justify-center items-center">
                                                <form action="{{ route('acs-servers.destroy', $server->id) }}" method="POST" onsubmit="return confirm('Delete this server connection details?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-4 py-2 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 font-bold bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-950/40 rounded-xl border border-rose-100/30 dark:border-rose-900/30 transition-all active:scale-95">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-16 text-center">
                                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">No Servers Found</h4>
                                            <p class="text-xs text-gray-400">Please add your first GenieACS server endpoint connection.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
