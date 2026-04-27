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
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Segment Count Cards -->
                <a href="{{ route('tickets.index', ['type' => 'aktivasi']) }}" class="p-6 rounded-2xl border border-indigo-100 dark:border-indigo-900/50 transition-all duration-300 {{ $currentType == 'aktivasi' ? 'bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-500 shadow-lg' : 'bg-white dark:bg-gray-800 hover:shadow-md' }}">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktivasi</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">New Tickets</h3>
                        </div>
                    </div>
                </a>

                <a href="{{ route('tickets.index', ['type' => 'gangguan']) }}" class="p-6 rounded-2xl border border-rose-100 dark:border-rose-900/50 transition-all duration-300 {{ $currentType == 'gangguan' ? 'bg-rose-50 dark:bg-rose-900/30 ring-2 ring-rose-500 shadow-lg' : 'bg-white dark:bg-gray-800 hover:shadow-md' }}">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gangguan</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Issues</h3>
                        </div>
                    </div>
                </a>

                <a href="{{ route('tickets.index', ['type' => 'dismantle']) }}" class="p-6 rounded-2xl border border-amber-100 dark:border-amber-900/50 transition-all duration-300 {{ $currentType == 'dismantle' ? 'bg-amber-50 dark:bg-amber-900/30 ring-2 ring-amber-500 shadow-lg' : 'bg-white dark:bg-gray-800 hover:shadow-md' }}">
                    <div class="flex items-center">
                        <div class="p-3 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dismantle</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Returns</h3>
                        </div>
                    </div>
                </a>
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
