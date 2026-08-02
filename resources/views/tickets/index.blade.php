<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Ticket System') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('tickets.create', ['type' => 'aktivasi']) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Aktivasi Baru
                </a>
                <a href="{{ route('tickets.create', ['type' => 'gangguan']) }}" class="inline-flex items-center px-4 py-2 bg-rose-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-rose-700 focus:bg-rose-700 active:bg-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Buka Gangguan
                </a>
                <a href="{{ route('tickets.create', ['type' => 'dismantle']) }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Dismantle
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Rekap Kinerja Tim Dashboard -->
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-base font-black text-slate-800 dark:text-slate-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Rekap Kinerja Tim & Status Tiket
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Analisis pengerjaan tiket teknisi (Selesai vs Waiting) secara real-time.</p>
                    </div>
                    
                    <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-900/30 px-4 py-2.5 rounded-xl border border-slate-100 dark:border-slate-800/50">
                        <div class="relative w-12 h-12 flex items-center justify-center">
                            <svg class="absolute w-full h-full -rotate-90" viewBox="0 0 36 36">
                                <path class="text-gray-200 dark:text-gray-700" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-indigo-600 dark:text-indigo-400" stroke-width="3" stroke-dasharray="{{ $recap['overall']['percentage'] }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $recap['overall']['percentage'] }}%</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Efisiensi Tim</span>
                            <span class="block text-sm font-black text-slate-800 dark:text-slate-100 mt-0.5">
                                {{ $recap['overall']['resolved'] + $recap['overall']['closed'] }} dari {{ $recap['overall']['total'] }} Selesai
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50/20 dark:bg-slate-900/10">
                    <!-- Aktivasi -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <a href="{{ route('tickets.index', ['type' => 'aktivasi']) }}" class="flex items-center text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Aktivasi Layanan
                                </a>
                                <span class="text-xs font-bold bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded">
                                    {{ $recap['aktivasi']['percentage'] }}% Done
                                </span>
                            </div>
                            
                            <!-- Bagan/Bar Grafik (Stacked) -->
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3.5 mb-4 overflow-hidden flex">
                                @if($recap['aktivasi']['total'] > 0)
                                    <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ ($recap['aktivasi']['resolved'] + $recap['aktivasi']['closed']) / $recap['aktivasi']['total'] * 100 }}%" title="Selesai: {{ $recap['aktivasi']['resolved'] + $recap['aktivasi']['closed'] }}"></div>
                                    <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $recap['aktivasi']['open'] / $recap['aktivasi']['total'] * 100 }}%" title="Baru: {{ $recap['aktivasi']['open'] }}"></div>
                                    <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $recap['aktivasi']['in_progress'] / $recap['aktivasi']['total'] * 100 }}%" title="Proses: {{ $recap['aktivasi']['in_progress'] }}"></div>
                                    <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ $recap['aktivasi']['canceled'] / $recap['aktivasi']['total'] * 100 }}%" title="Batal: {{ $recap['aktivasi']['canceled'] }}"></div>
                                @else
                                    <div class="bg-gray-200 dark:bg-gray-700 w-full h-full"></div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="bg-emerald-50/50 dark:bg-emerald-950/20 p-2 rounded-xl border border-emerald-100/50 dark:border-emerald-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Selesai</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $recap['aktivasi']['resolved'] + $recap['aktivasi']['closed'] }}</span>
                                </div>
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 p-2 rounded-xl border border-blue-100/50 dark:border-blue-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Baru (Open)</span>
                                    <span class="text-xs font-bold text-blue-500 dark:text-blue-400">{{ $recap['aktivasi']['open'] }}</span>
                                </div>
                                <div class="bg-amber-50/50 dark:bg-amber-950/20 p-2 rounded-xl border border-amber-100/50 dark:border-amber-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Proses</span>
                                    <span class="text-xs font-bold text-amber-500 dark:text-amber-400">{{ $recap['aktivasi']['in_progress'] }}</span>
                                </div>
                                <div class="bg-rose-50/50 dark:bg-rose-950/20 p-2 rounded-xl border border-rose-100/50 dark:border-rose-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Batal</span>
                                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $recap['aktivasi']['canceled'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                            <span>Total Tiket: <b>{{ $recap['aktivasi']['total'] }}</b></span>
                            <a href="{{ route('tickets.index', ['type' => 'aktivasi']) }}" class="hover:underline flex items-center font-semibold">
                                Lihat Tiket
                                <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Gangguan -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <a href="{{ route('tickets.index', ['type' => 'gangguan']) }}" class="flex items-center text-sm font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Gangguan Layanan
                                </a>
                                <span class="text-xs font-bold bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 px-2 py-0.5 rounded">
                                    {{ $recap['gangguan']['percentage'] }}% Done
                                </span>
                            </div>
                            
                            <!-- Bagan/Bar Grafik (Stacked) -->
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3.5 mb-4 overflow-hidden flex">
                                @if($recap['gangguan']['total'] > 0)
                                    <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ ($recap['gangguan']['resolved'] + $recap['gangguan']['closed']) / $recap['gangguan']['total'] * 100 }}%" title="Selesai: {{ $recap['gangguan']['resolved'] + $recap['gangguan']['closed'] }}"></div>
                                    <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $recap['gangguan']['open'] / $recap['gangguan']['total'] * 100 }}%" title="Baru: {{ $recap['gangguan']['open'] }}"></div>
                                    <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $recap['gangguan']['in_progress'] / $recap['gangguan']['total'] * 100 }}%" title="Proses: {{ $recap['gangguan']['in_progress'] }}"></div>
                                    <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ $recap['gangguan']['canceled'] / $recap['gangguan']['total'] * 100 }}%" title="Batal: {{ $recap['gangguan']['canceled'] }}"></div>
                                @else
                                    <div class="bg-gray-200 dark:bg-gray-700 w-full h-full"></div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="bg-emerald-50/50 dark:bg-emerald-950/20 p-2 rounded-xl border border-emerald-100/50 dark:border-emerald-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Selesai</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $recap['gangguan']['resolved'] + $recap['gangguan']['closed'] }}</span>
                                </div>
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 p-2 rounded-xl border border-blue-100/50 dark:border-blue-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Baru (Open)</span>
                                    <span class="text-xs font-bold text-blue-500 dark:text-blue-400">{{ $recap['gangguan']['open'] }}</span>
                                </div>
                                <div class="bg-amber-50/50 dark:bg-amber-950/20 p-2 rounded-xl border border-amber-100/50 dark:border-amber-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Proses</span>
                                    <span class="text-xs font-bold text-amber-500 dark:text-amber-400">{{ $recap['gangguan']['in_progress'] }}</span>
                                </div>
                                <div class="bg-rose-50/50 dark:bg-rose-950/20 p-2 rounded-xl border border-rose-100/50 dark:border-rose-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Batal</span>
                                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $recap['gangguan']['canceled'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                            <span>Total Tiket: <b>{{ $recap['gangguan']['total'] }}</b></span>
                            <a href="{{ route('tickets.index', ['type' => 'gangguan']) }}" class="hover:underline flex items-center font-semibold">
                                Lihat Tiket
                                <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Dismantle -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <a href="{{ route('tickets.index', ['type' => 'dismantle']) }}" class="flex items-center text-sm font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Dismantle Layanan
                                </a>
                                <span class="text-xs font-bold bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded">
                                    {{ $recap['dismantle']['percentage'] }}% Done
                                </span>
                            </div>
                            
                            <!-- Bagan/Bar Grafik (Stacked) -->
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3.5 mb-4 overflow-hidden flex">
                                @if($recap['dismantle']['total'] > 0)
                                    <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ ($recap['dismantle']['resolved'] + $recap['dismantle']['closed']) / $recap['dismantle']['total'] * 100 }}%" title="Selesai: {{ $recap['dismantle']['resolved'] + $recap['dismantle']['closed'] }}"></div>
                                    <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $recap['dismantle']['open'] / $recap['dismantle']['total'] * 100 }}%" title="Baru: {{ $recap['dismantle']['open'] }}"></div>
                                    <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $recap['dismantle']['in_progress'] / $recap['dismantle']['total'] * 100 }}%" title="Proses: {{ $recap['dismantle']['in_progress'] }}"></div>
                                    <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ $recap['dismantle']['canceled'] / $recap['dismantle']['total'] * 100 }}%" title="Batal: {{ $recap['dismantle']['canceled'] }}"></div>
                                @else
                                    <div class="bg-gray-200 dark:bg-gray-700 w-full h-full"></div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="bg-emerald-50/50 dark:bg-emerald-950/20 p-2 rounded-xl border border-emerald-100/50 dark:border-emerald-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Selesai</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $recap['dismantle']['resolved'] + $recap['dismantle']['closed'] }}</span>
                                </div>
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 p-2 rounded-xl border border-blue-100/50 dark:border-blue-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Baru (Open)</span>
                                    <span class="text-xs font-bold text-blue-500 dark:text-blue-400">{{ $recap['dismantle']['open'] }}</span>
                                </div>
                                <div class="bg-amber-50/50 dark:bg-amber-950/20 p-2 rounded-xl border border-amber-100/50 dark:border-amber-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Proses</span>
                                    <span class="text-xs font-bold text-amber-500 dark:text-amber-400">{{ $recap['dismantle']['in_progress'] }}</span>
                                </div>
                                <div class="bg-rose-50/50 dark:bg-rose-950/20 p-2 rounded-xl border border-rose-100/50 dark:border-rose-900/30 flex justify-between items-center">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Batal</span>
                                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $recap['dismantle']['canceled'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                            <span>Total Tiket: <b>{{ $recap['dismantle']['total'] }}</b></span>
                            <a href="{{ route('tickets.index', ['type' => 'dismantle']) }}" class="hover:underline flex items-center font-semibold">
                                Lihat Tiket
                                <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 font-semibold">#</th>
                                    <th class="px-6 py-4 font-semibold">Number</th>
                                    <th class="px-6 py-4 font-semibold">Customer</th>
                                    <th class="px-6 py-4 font-semibold">Type</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Priority</th>
                                    <th class="px-6 py-4 font-semibold">Assigned To</th>
                                    <th class="px-6 py-4 font-semibold">Date</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-100 dark:divide-gray-700">
                                @forelse($tickets as $ticket)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                                        <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                            {{ $tickets->firstItem() + $loop->index }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $ticket->ticket_number }}
                                            </a>
                                            @if($ticket->isOverdue())
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black bg-rose-100 text-rose-700 animate-pulse border border-rose-200 uppercase tracking-tighter">
                                                    OVERDUE
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $ticket->customer->name ?? 'Internal/System' }}</span>
                                                <span class="text-xs text-gray-500">{{ $ticket->customer->username ?? '' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $typeColors = [
                                                    'aktivasi' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                                    'gangguan' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                                    'dismantle' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                ];
                                            @endphp
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $typeColors[$ticket->type] ?? 'bg-gray-100' }}">
                                                {{ ucfirst($ticket->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'open' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                    'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                    'resolved' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                    'closed' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                                ];
                                            @endphp
                                            <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $statusColors[$ticket->status] ?? 'bg-gray-100' }}">
                                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $priorityColors = [
                                                    'low' => 'text-gray-400',
                                                    'medium' => 'text-blue-500',
                                                    'high' => 'text-orange-500',
                                                    'urgent' => 'text-rose-600 font-black',
                                                ];
                                            @endphp
                                            <div class="flex items-center">
                                                <div class="w-2 h-2 rounded-full mr-2 {{ str_replace('text-', 'bg-', $priorityColors[$ticket->priority] ?? 'bg-gray-100') }}"></div>
                                                <span class="text-xs font-semibold {{ $priorityColors[$ticket->priority] ?? 'text-gray-500' }}">
                                                    {{ strtoupper($ticket->priority) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $ticket->assignee->name ?? 'Unassigned' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            {{ $ticket->created_at->format('d M Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex justify-center space-x-2">
                                                <a href="{{ route('tickets.show', $ticket) }}" class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </a>
                                                <a href="{{ route('tickets.edit', $ticket) }}" class="p-2 text-gray-400 hover:text-amber-600 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 italic">
                                            No tickets found for this segment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6">
                        {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
