<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Gangguan Massal</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Satu tiket induk per gangguan OLT / port PON / ODP, dengan pemberitahuan ke pelanggan terdampak.</p>
            </div>
            <a href="{{ route('noc.incidents.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4" /> Laporkan gangguan massal</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-2 text-sm">
            @foreach([null => 'Semua', 'open' => 'Berlangsung', 'resolved' => 'Pulih'] as $key => $label)
                <a href="{{ route('noc.incidents.index', array_filter(['status' => $key])) }}"
                   class="rounded-full px-3 py-1 {{ $status === $key ? 'bg-primary-600 text-white' : 'bg-surface-100 text-surface-700 hover:bg-surface-200 dark:bg-surface-800 dark:text-surface-300' }}">
                    {{ $label }}@if($key === 'open') <span class="tabular-nums">({{ $openCount }})</span>@endif
                </a>
            @endforeach
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 text-left text-xs font-medium uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                        <tr><th class="px-4 py-3">Gangguan</th><th class="px-4 py-3">Cakupan</th><th class="px-4 py-3 text-right">Pelanggan</th><th class="px-4 py-3 text-right">Tiket</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Durasi</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @forelse($items as $i)
                            <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('noc.incidents.show', $i) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $i->title }}</a>
                                    <span class="block font-mono text-[11px] text-surface-500">{{ $i->ticket?->ticket_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-surface-600 dark:text-surface-300">{{ $i->scope_label }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $i->customers_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ max(0, $i->tickets_count - 1) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-surface-600 dark:text-surface-300">{{ $i->started_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-surface-600 dark:text-surface-300">{{ $i->started_at->diffForHumans($i->resolved_at ?? now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}</td>
                                <td class="px-4 py-3">
                                    @if($i->isOpen())<span class="badge-danger">Berlangsung</span>@else<span class="badge-success">Pulih</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-14 text-center text-surface-500">Belum ada gangguan massal{{ $status ? ' di kelompok ini' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())<div class="border-t border-surface-100 px-4 py-3 dark:border-surface-800">{{ $items->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
