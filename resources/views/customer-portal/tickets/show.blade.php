@php
    [$badge, $statusLabel] = [
        'open' => ['badge-info', 'Diterima'],
        'in_progress' => ['badge-warning', 'Sedang ditangani'],
        'resolved' => ['badge-success', 'Selesai'],
        'closed' => ['badge-gray', 'Ditutup'],
    ][$ticket->status] ?? ['badge-gray', \Illuminate\Support\Str::headline((string) $ticket->status)];
    $fmt = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y, H:i');
@endphp

<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-4">
        {{-- Judul --}}
        <div class="flex items-start gap-2">
            <a href="{{ route('customer.tickets.index') }}" class="btn-ghost btn-icon -ml-2 shrink-0" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-semibold tracking-tight text-surface-900 dark:text-white">{{ $ticket->subject }}</h1>
                    <span class="{{ $badge }}">{{ $statusLabel }}</span>
                </div>
                <p class="text-xs text-surface-400 font-mono mt-0.5">{{ $ticket->ticket_number }}</p>
            </div>
        </div>

        {{-- Info ringkas --}}
        <div class="card grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-surface-200 dark:divide-surface-800">
            <div class="p-4">
                <p class="text-xs text-surface-500 dark:text-surface-400">Dibuat</p>
                <p class="mt-0.5 text-sm font-medium text-surface-900 dark:text-white">{{ $fmt($ticket->created_at) }}</p>
            </div>
            <div class="p-4">
                <p class="text-xs text-surface-500 dark:text-surface-400">Ditangani oleh</p>
                <p class="mt-0.5 text-sm font-medium {{ $ticket->assignee ? 'text-surface-900 dark:text-white' : 'text-surface-400' }}">{{ $ticket->assignee->name ?? 'Menunggu teknisi' }}</p>
            </div>
            <div class="p-4">
                <p class="text-xs text-surface-500 dark:text-surface-400">Estimasi selesai</p>
                <p class="mt-0.5 text-sm font-medium {{ $ticket->estimated_completion_date ? 'text-surface-900 dark:text-white' : 'text-surface-400' }}">
                    {{ $ticket->estimated_completion_date ? $fmt($ticket->estimated_completion_date) : 'Belum ditentukan' }}
                </p>
            </div>
        </div>

        @if(in_array($ticket->status, ['resolved', 'closed']))
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10 p-4">
                <div class="flex items-start gap-2.5">
                    <x-icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <div class="text-sm text-emerald-800 dark:text-emerald-200">
                        <p class="font-medium">Laporan telah {{ $ticket->status === 'closed' ? 'ditutup' : 'diselesaikan' }}</p>
                        @if($ticket->status === 'resolved')
                            <p class="mt-0.5">Masih bermasalah? Kirim balasan di bawah untuk membuka kembali laporan ini.</p>
                        @endif
                        @if($ticket->resolution_notes)
                            <p class="mt-2"><span class="font-medium">Catatan teknisi:</span> {{ $ticket->resolution_notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Percakapan --}}
        <div class="space-y-3">
            {{-- Laporan awal --}}
            <div class="flex justify-end">
                <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-primary-600 text-white px-4 py-3">
                    <p class="text-sm whitespace-pre-wrap break-words">{{ $ticket->description }}</p>
                    @if($ticket->attachment)
                        <a href="{{ asset('storage/' . $ticket->attachment) }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-primary-100 hover:text-white underline">Lihat foto lampiran</a>
                    @endif
                    <p class="mt-1.5 text-[11px] text-primary-200 text-right">Anda · {{ $fmt($ticket->created_at) }}</p>
                </div>
            </div>

            @foreach($ticket->replies as $reply)
                @if($reply->user_id)
                    <div class="flex justify-start">
                        <div class="max-w-[85%] rounded-2xl rounded-tl-md card px-4 py-3">
                            <p class="text-xs font-medium text-surface-900 dark:text-white mb-1">{{ $reply->user->name ?? 'Tim Support' }} <span class="font-normal text-surface-400">· Tim {{ get_setting('company_name', 'Support') }}</span></p>
                            <p class="text-sm text-surface-700 dark:text-surface-200 whitespace-pre-wrap break-words">{{ $reply->message }}</p>
                            @if($reply->attachment)
                                <a href="{{ asset('storage/' . $reply->attachment) }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline">Lihat lampiran</a>
                            @endif
                            <p class="mt-1.5 text-[11px] text-surface-400">{{ $fmt($reply->created_at) }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-primary-600 text-white px-4 py-3">
                            <p class="text-sm whitespace-pre-wrap break-words">{{ $reply->message }}</p>
                            @if($reply->attachment)
                                <a href="{{ asset('storage/' . $reply->attachment) }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-medium text-primary-100 hover:text-white underline">Lihat lampiran</a>
                            @endif
                            <p class="mt-1.5 text-[11px] text-primary-200 text-right">Anda · {{ $fmt($reply->created_at) }}</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Balas --}}
        @if($ticket->status !== 'closed')
            <form action="{{ route('customer.tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="card p-4 space-y-3"
                  x-data="{ sending: false, fileName: '' }" @submit="sending = true">
                @csrf
                <label for="message" class="sr-only">Balasan</label>
                <textarea id="message" name="message" rows="3" required class="input" placeholder="{{ $ticket->status === 'resolved' ? 'Jelaskan kendala yang masih terjadi…' : 'Tulis pesan tambahan…' }}">{{ old('message') }}</textarea>
                <div class="flex items-center justify-between gap-3">
                    <label class="btn-ghost btn-sm cursor-pointer">
                        <input type="file" name="attachment" accept="image/*" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="truncate max-w-[10rem]" x-text="fileName || 'Lampirkan foto'">Lampirkan foto</span>
                    </label>
                    <button type="submit" class="btn-primary" :disabled="sending">
                        <span x-text="sending ? 'Mengirim…' : 'Kirim'">Kirim</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
