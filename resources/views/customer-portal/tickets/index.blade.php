<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Riwayat Laporan Gangguan
            </h2>
            <a href="{{ route('customer.tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-white hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg shadow-indigo-500/30">
                + Buat Laporan
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-start">
                <svg class="w-5 h-5 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            @if($tickets->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-800 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Belum Ada Tiket</h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Anda belum pernah membuat laporan gangguan. Jika internet Anda bermasalah, silakan klik tombol "Buat Laporan".</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($tickets as $ticket)
                        <a href="{{ route('customer.tickets.show', $ticket) }}" class="block p-5 rounded-2xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-100 dark:hover:border-indigo-800 transition-all duration-200 group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $ticket->status === 'resolved' || $ticket->status === 'closed' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                        @if($ticket->status === 'resolved' || $ticket->status === 'closed')
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-mono font-bold text-gray-400">{{ $ticket->ticket_number }}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                @if($ticket->status === 'open') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                                @elseif($ticket->status === 'in_progress') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                                                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                                @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 @endif
                                            ">
                                                {{ str_replace('_', ' ', $ticket->status) }}
                                            </span>
                                        </div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ $ticket->subject }}
                                        </h4>
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-1">{{ $ticket->description }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                                    <div class="mt-2 text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-sm font-semibold">Lihat Detail &rarr;</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
