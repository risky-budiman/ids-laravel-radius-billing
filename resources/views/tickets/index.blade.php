<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2>Tiket</h2>
                <p class="page-subtitle">Aktivasi, gangguan, dan dismantle pelanggan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('tickets.recap') }}" class="btn-ghost">
                        <x-icon name="chart-bar" class="w-4 h-4" /> Rekap Bulanan / Tahunan
                    </a>
                @endif
                <a href="{{ route('tickets.create', ['type' => 'dismantle']) }}" class="btn-secondary">
                    <x-icon name="plus" class="w-4 h-4" /> Dismantle
                </a>
                <a href="{{ route('tickets.create', ['type' => 'gangguan']) }}" class="btn-secondary">
                    <x-icon name="plus" class="w-4 h-4" /> Laporan Gangguan
                </a>
                <a href="{{ route('tickets.create', ['type' => 'aktivasi']) }}" class="btn-primary">
                    <x-icon name="plus" class="w-4 h-4" /> Aktivasi Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div>
        <div class="space-y-6">
            <!-- Rekap Kinerja Tim Dashboard -->
            <div class="card overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100 flex items-center">
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
                            <span class="block text-xs font-bold text-gray-500 dark:text-gray-400">Total Efisiensi Tim</span>
                            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100 mt-0.5">
                                {{ $recap['overall']['resolved'] + $recap['overall']['closed'] }} dari {{ $recap['overall']['total'] }} Selesai
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50/20 dark:bg-slate-900/10">
                    <!-- Aktivasi -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
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
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
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
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
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
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
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
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
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
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
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
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>Nomor</th>
                                <th>Pelanggan</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Prioritas</th>
                                <th>Ditangani</th>
                                <th>Dibuat</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                @php
                                    $typeBadge = ['aktivasi' => 'badge-primary', 'gangguan' => 'badge-danger', 'dismantle' => 'badge-warning'][$ticket->type] ?? 'badge-gray';
                                    $statusBadge = [
                                        'open' => ['badge-info', 'Open'],
                                        'in_progress' => ['badge-warning', 'Diproses'],
                                        'resolved' => ['badge-success', 'Selesai'],
                                        'closed' => ['badge-gray', 'Ditutup'],
                                    ][$ticket->status] ?? ['badge-gray', \Illuminate\Support\Str::headline((string) $ticket->status)];
                                    $prio = [
                                        'low' => ['bg-surface-300', 'Rendah', 'text-surface-500'],
                                        'medium' => ['bg-sky-500', 'Sedang', 'text-surface-700 dark:text-surface-300'],
                                        'high' => ['bg-orange-500', 'Tinggi', 'text-orange-600 dark:text-orange-400'],
                                        'urgent' => ['bg-rose-600', 'Mendesak', 'text-rose-600 dark:text-rose-400 font-medium'],
                                    ][$ticket->priority] ?? ['bg-surface-300', ucfirst((string) $ticket->priority), 'text-surface-500'];
                                @endphp
                                <tr>
                                    <td class="text-surface-400 tabular-nums">{{ $tickets->firstItem() + $loop->index }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-surface-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 font-mono text-[13px]">{{ $ticket->ticket_number }}</a>
                                        @if($ticket->isOverdue())
                                            <span class="badge-danger ml-1.5">Lewat SLA</span>
                                        @endif
                                    </td>
                                    <td>
                                        <p class="font-medium text-surface-900 dark:text-white">{{ $ticket->customer->name ?? 'Internal / Sistem' }}</p>
                                        <p class="text-xs text-surface-400 font-mono">{{ $ticket->customer->username ?? '' }}</p>
                                    </td>
                                    <td><span class="{{ $typeBadge }}">{{ ucfirst($ticket->type) }}</span></td>
                                    <td><span class="{{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span></td>
                                    <td>
                                        <span class="inline-flex items-center gap-2 text-sm {{ $prio[2] }}">
                                            <span class="w-2 h-2 rounded-full {{ $prio[0] }}"></span>{{ $prio[1] }}
                                        </span>
                                    </td>
                                    <td class="{{ $ticket->assignee ? '' : 'text-surface-400' }}">{{ $ticket->assignee->name ?? 'Belum ada' }}</td>
                                    <td class="text-surface-500 dark:text-surface-400 text-[13px]">{{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-0.5">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="btn-ghost btn-icon !h-8 !w-8" title="Lihat detail" aria-label="Lihat detail">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn-ghost btn-icon !h-8 !w-8" title="Edit tiket" aria-label="Edit tiket">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="!py-16 text-center">
                                        <div class="mx-auto w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 mb-3">
                                            <x-icon name="ticket" class="w-5 h-5" />
                                        </div>
                                        <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Tidak ada tiket</p>
                                        <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Belum ada tiket pada segmen ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tickets->hasPages())
                    <div class="px-5 py-3 border-t border-surface-200 dark:border-surface-800">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
