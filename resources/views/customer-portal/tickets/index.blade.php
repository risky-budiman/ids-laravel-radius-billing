<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-surface-900 dark:text-white">Bantuan & Gangguan</h1>
                <p class="text-sm text-surface-500 dark:text-surface-400">Laporkan gangguan dan pantau penanganannya.</p>
            </div>
            <a href="{{ route('customer.tickets.create') }}" class="btn-primary shrink-0">
                <x-icon name="plus" class="w-4 h-4" /> <span class="hidden sm:inline">Laporan baru</span><span class="sm:hidden">Lapor</span>
            </a>
        </div>

        <div class="space-y-3">
            @forelse($tickets as $ticket)
                @php
                    [$badge, $label] = [
                        'open' => ['badge-info', 'Diterima'],
                        'in_progress' => ['badge-warning', 'Sedang ditangani'],
                        'resolved' => ['badge-success', 'Selesai'],
                        'closed' => ['badge-gray', 'Ditutup'],
                    ][$ticket->status] ?? ['badge-gray', \Illuminate\Support\Str::headline((string) $ticket->status)];
                @endphp
                <a href="{{ route('customer.tickets.show', $ticket) }}" class="card block p-4 sm:p-5 hover:border-surface-300 dark:hover:border-surface-700 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $ticket->subject }}</p>
                            <p class="text-xs text-surface-400 font-mono mt-0.5">{{ $ticket->ticket_number }}</p>
                        </div>
                        <span class="{{ $badge }} shrink-0">{{ $label }}</span>
                    </div>
                    @if($ticket->description)
                        <p class="mt-2 text-sm text-surface-600 dark:text-surface-300 line-clamp-2">{{ $ticket->description }}</p>
                    @endif
                    <div class="mt-3 flex items-center justify-between text-xs text-surface-400">
                        <span>{{ $ticket->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                        <span class="inline-flex items-center gap-1 font-medium text-primary-600 dark:text-primary-400">Detail <x-icon name="chevron-right" class="w-3.5 h-3.5" /></span>
                    </div>
                </a>
            @empty
                <div class="card px-6 py-12 text-center">
                    <div class="mx-auto w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 mb-3">
                        <x-icon name="support" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Belum ada laporan</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Koneksi bermasalah? Buat laporan agar tim kami segera membantu.</p>
                    <a href="{{ route('customer.tickets.create') }}" class="btn-primary btn-sm mt-4">Buat laporan</a>
                </div>
            @endforelse
        </div>

        @if($tickets->hasPages())
            <div>{{ $tickets->links() }}</div>
        @endif
    </div>
</x-app-layout>
