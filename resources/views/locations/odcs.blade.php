<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Master Data: ODC (Optical Distribution Cabinet)') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-8">
        @if(session('success'))
            <div class="px-5 py-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl font-medium shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-xl">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <!-- Card Input Form -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-3xl shadow-lg border border-gray-100/50 dark:border-gray-700/50 p-8 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-3xl -mr-20 -mt-20 z-0"></div>
            
            <div class="relative z-10">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-inner mr-4 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 tracking-tight">Add ODC Cabinet</h3>
                </div>

                <form action="{{ route('locations.odc.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Parent STB</label>
                        <select name="stb_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all px-4 py-3" required>
                            <option value="">-- Select STB --</option>
                            @foreach($stbs as $stb)
                                <option value="{{ $stb->id }}">[{{ $stb->code }}] {{ $stb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ODC Code / ID</label>
                        <input type="text" name="id" placeholder="E.g. ODC-BDG-01" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all px-4 py-3 font-mono" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cabinet Name</label>
                        <input type="text" name="name" placeholder="E.g. ODC Pusat Kota" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all px-4 py-3" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Total Ports</label>
                        <input type="number" name="total_ports" placeholder="E.g. 288" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all px-4 py-3" />
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-xl hover:from-indigo-700 hover:to-purple-700 font-bold shadow-lg shadow-indigo-500/30 transform hover:-translate-y-0.5 transition-all outline-none focus:ring-4 focus:ring-indigo-500/30 whitespace-nowrap">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Save ODC
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap align-middle">
                    <thead class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">#</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">ID / Code</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Cabinet Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Parent STB</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Capacity</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Total ODPs</th>
                            <th class="px-6 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($odcs as $o)
                        <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors group">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono font-bold px-3 py-1.5 rounded-lg text-sm border border-indigo-100 dark:border-indigo-800">
                                    {{ $o->id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $o->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($o->stb)
                                    <span class="inline-flex items-center text-xs text-gray-400 font-mono mr-1">[{{ $o->stb->code }}]</span> 
                                    {{ $o->stb->name }}
                                @else
                                    <span class="text-red-400 italic">No STB</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $o->total_ports ?? '-' }} Ports
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2.5 py-1 rounded-md text-xs font-bold">
                                    {{ $o->odps_count ?? $o->odps->count() }} ODPs
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('locations.odc.destroy', $o) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm shadow-red-500/30 transform hover:-translate-y-0.5" onclick="return confirm('Delete ODC Cabinet?');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @if($odcs->isEmpty())
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <span class="block font-medium">No ODC Cabinets registered yet</span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
