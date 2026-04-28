<x-app-layout>
    <div class="space-y-4 animate-fade-in pb-60">
        <div class="flex items-center justify-between px-2">
            <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Bantuan</h2>
            <a href="{{ route('customer.tickets.create') }}" class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-md active:scale-90 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-start animate-fade-in">
                <svg class="w-4 h-4 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-[10px] font-bold">{{ session('success') }}</p>
            </div>
        @endif

        <div class="space-y-3">
            @forelse($tickets as $ticket)
                <a href="{{ route('customer.tickets.show', $ticket) }}" class="block bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm active:scale-[0.98] transition-all group">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ in_array($ticket->status, ['resolved', 'closed']) ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">
                                @if(in_array($ticket->status, ['resolved', 'closed']))
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">{{ $ticket->ticket_number }}</p>
                                <h4 class="font-black text-slate-950 dark:text-white text-sm mt-0.5 group-hover:text-indigo-600 transition-colors">{{ $ticket->subject }}</h4>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[7px] font-black uppercase tracking-widest
                            @if($ticket->status === 'open') bg-blue-100 text-blue-700
                            @elseif($ticket->status === 'in_progress') bg-amber-100 text-amber-700
                            @elseif(in_array($ticket->status, ['resolved', 'closed'])) bg-emerald-100 text-emerald-700
                            @else bg-gray-100 text-gray-700 @endif
                        ">
                            {{ str_replace('_', ' ', $ticket->status) }}
                        </span>
                    </div>
                    
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">{{ $ticket->description }}</p>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50 dark:border-gray-700/50">
                        <span class="text-[8px] font-bold text-gray-400">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                        <span class="text-[8px] font-black text-indigo-600 uppercase tracking-widest">Detail &rarr;</span>
                    </div>
                </a>
            @empty
                <div class="bg-white dark:bg-gray-800 p-10 rounded-2xl text-center border border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 font-bold">Tidak ada tiket bantuan.</p>
                </div>
            @endforelse
        </div>

        @if($tickets->hasPages())
            <div class="pt-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
