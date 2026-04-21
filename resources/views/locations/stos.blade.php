<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Master Data: STOs (Sentral Telepon Otomat)') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-8">
        @if(session('success'))
            <div class="px-5 py-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl font-medium shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- Card Input Form -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-3xl shadow-lg border border-gray-100/50 dark:border-gray-700/50 p-8 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-fuchsia-50 dark:bg-fuchsia-900/10 rounded-full blur-3xl -mr-20 -mt-20 z-0"></div>
            
            <div class="relative z-10">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-rose-600 flex items-center justify-center shadow-inner mr-4 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 tracking-tight">Add STO (Branch Office)</h3>
                </div>

                <form action="{{ route('locations.sto.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Parent Region</label>
                        <select name="region_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" required>
                            <option value="">-- Select Master Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">STO Name</label>
                        <input type="text" name="name" placeholder="E.g. Jakarta Selatan STO" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" required />
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-gradient-to-r from-fuchsia-600 to-rose-600 text-white px-6 py-3 rounded-xl hover:from-fuchsia-700 hover:to-rose-700 font-bold shadow-lg shadow-fuchsia-500/30 transform hover:-translate-y-0.5 transition-all outline-none focus:ring-4 focus:ring-fuchsia-500/30 whitespace-nowrap">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Save STO
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
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">STO Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Region Master</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Child STBs</th>
                            <th class="px-6 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($stos as $s)
                        <tr class="hover:bg-fuchsia-50/30 dark:hover:bg-fuchsia-900/10 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono font-bold px-3 py-1.5 rounded-lg text-sm border border-gray-200 dark:border-gray-600">
                                    {{ $s->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $s->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($s->region)
                                    <span class="inline-flex items-center text-xs text-gray-400 font-mono mr-1">[{{ $s->region->code }}]</span> 
                                    {{ $s->region->name }}
                                @else
                                    <span class="text-red-400 italic">No Region</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                    {{ $s->stbs->count() }} STBs
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('locations.sto.destroy', $s) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-sm shadow-red-500/30 transform hover:-translate-y-0.5 active:translate-y-0" onclick="return confirm('Delete STO? Ensure no dependent STBs exist!');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @if($stos->isEmpty())
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <span class="block font-medium">No STOs registered yet</span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
