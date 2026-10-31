<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Riwayat Pesan WhatsApp
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Pantau seluruh antrean (waiting), pesan terkirim (sent), dan pesan gagal (failed).
                </p>
            </div>
            <div>
                <a href="{{ route('whatsapp-broadcast.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-emerald-500/20">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Kirim Broadcast Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="whatsappLogsManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl dark:bg-emerald-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl dark:bg-red-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Bulk Action Floating Bar -->
            <div x-show="selected.length > 0" x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="flex items-center justify-between p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-rose-600 text-white text-xs font-bold" x-text="selected.length"></span>
                    <span class="text-sm font-semibold text-rose-900 dark:text-rose-200">Riwayat pesan dipilih</span>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" @click="selected = []" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Batal
                    </button>
                    <form action="{{ route('whatsapp-logs.bulk-destroy') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ' + selected.length + ' riwayat pesan yang dipilih?');">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-rose-500/20">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Hapus Terpilih
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <form action="{{ route('whatsapp-logs.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 w-full">
                        <div class="flex-1">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor telepon atau isi pesan..." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div class="w-full sm:w-48">
                            <select name="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Waiting (Antrean)</option>
                                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Terkirim</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-medium transition-colors border border-gray-200 dark:border-gray-600">
                                Cari
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th scope="col" class="w-10 px-4 py-4 text-center">
                                    <input type="checkbox" 
                                           @click="toggleSelectAll()" 
                                           :checked="allIds.length > 0 && selected.length === allIds.length"
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer">
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tujuan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/3">
                                    Isi Pesan 
                                    <span class="text-[10px] lowercase font-normal text-emerald-600 dark:text-emerald-400">(klik untuk baca detail)</span>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/50">
                            @forelse($logs as $log)
                            @php
                                $logData = [
                                    'id' => $log->id,
                                    'target_phone' => $log->target_phone,
                                    'message' => $log->message,
                                    'status' => $log->status,
                                    'error_reason' => $log->error_reason,
                                    'date' => $log->created_at->format('d M Y'),
                                    'time' => $log->created_at->format('H:i:s'),
                                    'full_date' => $log->created_at->format('d M Y, H:i:s'),
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/25 transition-colors">
                                <td class="w-10 px-4 py-4 text-center">
                                    <input type="checkbox" 
                                           value="{{ $log->id }}" 
                                           x-model.number="selected"
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white font-mono font-medium">{{ $log->target_phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div @click="openDetail({{ json_encode($logData) }})" 
                                         class="cursor-pointer group flex items-start justify-between gap-2 p-2 -m-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                                         title="Klik untuk membaca pesan lengkap">
                                        <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed">
                                            {{ $log->message }}
                                        </div>
                                        <span class="flex-shrink-0 text-gray-400 group-hover:text-emerald-600 transition-colors pt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </span>
                                    </div>
                                    @if($log->status === 'failed' && $log->error_reason)
                                        <div class="text-xs text-red-500 mt-1 font-mono">Error: {{ \Illuminate\Support\Str::limit($log->error_reason, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                            Sent
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800/50" title="{{ $log->error_reason }}">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                            Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                            Waiting
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Tombol Baca Detail -->
                                        <button type="button" @click="openDetail({{ json_encode($logData) }})" class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" title="Baca Pesan Lengkap">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Tombol Resend jika Gagal -->
                                        @if($log->status === 'failed')
                                        <form action="{{ route('whatsapp-logs.resend', $log->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg transition-colors" title="Kirim Ulang Pesan" onclick="return confirm('Kirim ulang pesan ini?');">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                        </form>
                                        @endif

                                        <!-- Tombol Hapus Single -->
                                        <form action="{{ route('whatsapp-logs.destroy', $log->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat pesan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Hapus Riwayat">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    Belum ada log pesan yang tersimpan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>

        </div>

        <!-- Detail Modal Pop-up -->
        <div x-show="showModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 transition-opacity bg-gray-900/70 backdrop-blur-sm" @click="closeModal()"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-gray-100 dark:border-gray-700 z-10"
                     x-show="showModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-emerald-600 flex justify-between items-center text-white">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            <h3 class="font-bold text-base">Detail Pesan WhatsApp</h3>
                        </div>
                        <button type="button" @click="closeModal()" class="text-white/80 hover:text-white rounded-lg p-1 transition-colors text-xl font-bold leading-none">&times;</button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4" x-if="selectedLog">
                        <!-- Info Meta Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                                <span class="text-[10px] uppercase font-bold text-gray-400 block mb-0.5">Nomor Tujuan</span>
                                <span class="text-sm font-bold font-mono text-gray-900 dark:text-white" x-text="selectedLog?.target_phone"></span>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                                <span class="text-[10px] uppercase font-bold text-gray-400 block mb-0.5">Waktu Kirim</span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200" x-text="selectedLog?.full_date"></span>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                                <span class="text-[10px] uppercase font-bold text-gray-400 block mb-0.5">Status</span>
                                <template x-if="selectedLog?.status === 'sent'">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Terkirim
                                    </span>
                                </template>
                                <template x-if="selectedLog?.status === 'failed'">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        Gagal
                                    </span>
                                </template>
                                <template x-if="selectedLog?.status === 'pending'">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Antrean
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Error Box if failed -->
                        <template x-if="selectedLog?.status === 'failed' && selectedLog?.error_reason">
                            <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl">
                                <span class="text-[10px] uppercase font-bold text-rose-600 dark:text-rose-400 block mb-1">Alasan Kegagalan</span>
                                <p class="text-xs text-rose-700 dark:text-rose-300 font-mono break-words" x-text="selectedLog?.error_reason"></p>
                            </div>
                        </template>

                        <!-- Message Content Box -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400 tracking-wider">Isi Lengkap Pesan</span>
                                <button type="button" @click="copyMessage()" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    <span x-text="copied ? 'Tersalin!' : 'Salin Pesan'">Salin Pesan</span>
                                </button>
                            </div>
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/70 rounded-2xl border border-gray-100 dark:border-gray-700 text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap font-sans max-h-72 overflow-y-auto leading-relaxed select-text" 
                                 x-text="selectedLog?.message">
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700 flex justify-end space-x-3">
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function whatsappLogsManager() {
            return {
                selected: [],
                allIds: {!! json_encode($logs->pluck('id')->all()) !!},
                selectedLog: null,
                showModal: false,
                copied: false,
                toggleSelectAll() {
                    if (this.selected.length === this.allIds.length) {
                        this.selected = [];
                    } else {
                        this.selected = [...this.allIds];
                    }
                },
                openDetail(log) {
                    this.selectedLog = log;
                    this.showModal = true;
                    this.copied = false;
                },
                closeModal() {
                    this.showModal = false;
                    this.selectedLog = null;
                },
                copyMessage() {
                    if (this.selectedLog && this.selectedLog.message) {
                        navigator.clipboard.writeText(this.selectedLog.message);
                        this.copied = true;
                        setTimeout(() => {
                            this.copied = false;
                        }, 2000);
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
