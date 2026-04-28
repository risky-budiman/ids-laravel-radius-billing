<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('customer.tickets.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Tiket {{ $ticket->ticket_number }}
            </h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                @if($ticket->status === 'open') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                @elseif($ticket->status === 'in_progress') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                @elseif($ticket->status === 'resolved' || $ticket->status === 'closed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                @else bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 @endif
            ">
                {{ str_replace('_', ' ', $ticket->status) }}
            </span>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-start">
                <svg class="w-5 h-5 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Ticket Conversation -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Original Ticket Description -->
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold shrink-0">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <h4 class="font-bold text-gray-900 dark:text-white">Anda (Pelanggan)</h4>
                                <span class="text-xs text-gray-500">{{ $ticket->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $ticket->subject }}</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->description }}</div>
                            
                            @if($ticket->attachment)
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <a href="{{ asset('storage/' . $ticket->attachment) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Lihat Lampiran Foto
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Replies -->
                @foreach($ticket->replies as $reply)
                    <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border {{ $reply->user_id ? 'border-emerald-100 dark:border-emerald-800' : 'border-gray-100 dark:border-gray-700' }}">
                        <div class="flex items-start">
                            @if($reply->user_id)
                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold shrink-0">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            @endif
                            <div class="ml-4 flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-bold text-gray-900 dark:text-white">
                                        @if($reply->user_id)
                                            {{ $reply->user->name }} <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full ml-1">{{ ucfirst($reply->user->role) }}</span>
                                        @else
                                            Anda (Pelanggan)
                                        @endif
                                    </h4>
                                    <span class="text-xs text-gray-500">{{ $reply->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="p-4 {{ $reply->user_id ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-gray-50 dark:bg-gray-900/50' }} rounded-xl text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->message }}</div>
                                
                                @if($reply->attachment)
                                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <a href="{{ asset('storage/' . $reply->attachment) }}" target="_blank" class="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                            Lihat Lampiran
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Reply Form -->
                @if($ticket->status !== 'closed' && $ticket->status !== 'resolved')
                    <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <form action="{{ route('customer.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">Balas Laporan</h4>
                            <textarea name="message" rows="3" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm mb-4" placeholder="Ketik pesan tambahan di sini..." required></textarea>
                            
                            <div class="flex items-center justify-between">
                                <div class="relative">
                                    <input type="file" name="attachment" id="attachment" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                    <label for="attachment" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Lampirkan Foto
                                    </label>
                                </div>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">
                                    Kirim Balasan
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Right: Ticket Info -->
            <div class="space-y-6">
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Info Tiket</h3>
                    
                    <ul class="space-y-4">
                        <li>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
                            <p class="font-semibold text-gray-900 dark:text-white uppercase">{{ str_replace('_', ' ', $ticket->status) }}</p>
                        </li>
                        <li>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Dibuat Pada</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
                        </li>
                        <li>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Estimasi Penyelesaian</p>
                            @if($ticket->estimated_completion_date)
                                <p class="font-semibold text-amber-600">{{ \Carbon\Carbon::parse($ticket->estimated_completion_date)->format('d M Y, H:i') }}</p>
                            @else
                                <p class="text-gray-500 italic">Belum ditentukan</p>
                            @endif
                        </li>
                        <li>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ditangani Oleh</p>
                            @if($ticket->assignee)
                                <div class="flex items-center mt-1">
                                    <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-xs mr-2">
                                        {{ substr($ticket->assignee->name, 0, 1) }}
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $ticket->assignee->name }}</p>
                                </div>
                            @else
                                <p class="text-gray-500 italic">Menunggu teknisi</p>
                            @endif
                        </li>
                    </ul>
                </div>

                @if($ticket->status === 'resolved' || $ticket->status === 'closed')
                    <div class="p-6 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl">
                        <div class="flex items-center mb-3">
                            <svg class="w-6 h-6 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h3 class="text-lg font-bold text-emerald-800 dark:text-emerald-400">Masalah Selesai</h3>
                        </div>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300 mb-4">Tim teknisi kami telah menandai tiket ini sebagai selesai. Jika koneksi Anda masih bermasalah, Anda dapat mengirim balasan pada kolom chat untuk membuka kembali tiket ini.</p>
                        @if($ticket->resolution_notes)
                            <div class="p-3 bg-white/50 dark:bg-black/20 rounded-xl border border-emerald-100 dark:border-emerald-800 text-sm text-gray-700 dark:text-gray-300">
                                <strong>Catatan Teknisi:</strong><br>
                                {{ $ticket->resolution_notes }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
