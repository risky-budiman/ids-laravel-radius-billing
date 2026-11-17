<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('noc.incidents.index') }}" class="text-sm text-surface-500 hover:text-primary-600">← Gangguan Massal</a>
                <h2 class="flex flex-wrap items-center gap-2 font-bold text-2xl text-surface-900 dark:text-white leading-tight">
                    {{ $incident->title }}
                    @if($incident->isOpen())<span class="badge-danger">Berlangsung</span>@else<span class="badge-success">Pulih</span>@endif
                </h2>
                <p class="text-sm text-surface-500">{{ $incident->scope_label }} · mulai {{ $incident->started_at->translatedFormat('d M Y H:i') }}
                    @if($incident->resolved_at) · pulih {{ $incident->resolved_at->translatedFormat('d M Y H:i') }} ({{ $incident->started_at->diffForHumans($incident->resolved_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }})@endif</p>
            </div>
            @if($incident->ticket)
                <a href="{{ route('tickets.show', $incident->ticket) }}" class="btn-secondary">Tiket induk {{ $incident->ticket->ticket_number }}</a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
        @endif

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="card p-4"><p class="text-xs text-surface-500">Pelanggan terdampak</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $customers->total() }}</p></div>
            <div class="card p-4"><p class="text-xs text-surface-500">Tiket pelanggan tertaut</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $tickets->count() }}</p></div>
            <div class="card p-4"><p class="text-xs text-surface-500">WA pemberitahuan</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $incident->notified_count }}</p></div>
            <div class="card p-4"><p class="text-xs text-surface-500">WA pemulihan</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $incident->resolved_notified_count }}</p></div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-4">
                @if($incident->description || $incident->estimate || $incident->resolution_notes)
                    <div class="card"><div class="card-body space-y-2 text-sm">
                        @if($incident->description)<p class="whitespace-pre-line text-surface-700 dark:text-surface-300">{{ $incident->description }}</p>@endif
                        @if($incident->estimate)<p><span class="text-surface-500">Estimasi perbaikan:</span> {{ $incident->estimate }}</p>@endif
                        @if($incident->resolution_notes)<p><span class="text-surface-500">Penyelesaian:</span> {{ $incident->resolution_notes }}</p>@endif
                        <p class="text-xs text-surface-500">Dibuat oleh {{ $incident->creator?->name ?? 'sistem' }}@if($incident->resolver) · dipulihkan oleh {{ $incident->resolver->name }}@endif</p>
                    </div></div>
                @endif

                @if($tickets->isNotEmpty())
                    <div class="card overflow-hidden">
                        <div class="card-header"><h3 class="card-title">Tiket pelanggan tertaut</h3></div>
                        <ul class="divide-y divide-surface-100 text-sm dark:divide-surface-800">
                            @foreach($tickets as $t)
                                <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                    <a href="{{ route('tickets.show', $t) }}" class="hover:underline"><span class="font-mono text-xs text-surface-500">{{ $t->ticket_number }}</span> {{ $t->customer?->name }}</a>
                                    <span class="{{ in_array($t->status, \App\Models\Ticket::DONE_STATUSES, true) ? 'badge-success' : 'badge-warning' }}">{{ $t->status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card overflow-hidden">
                    <div class="card-header"><h3 class="card-title">Pelanggan terdampak</h3>
                        <p class="text-xs text-surface-500">Pelanggan aktif di {{ $incident->scope_label }} saat gangguan dibuat. Tiket gangguan baru dari pelanggan ini otomatis tertaut selama gangguan berlangsung.</p></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-surface-50 text-left text-xs uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                                <tr><th class="px-4 py-2">Pelanggan</th><th class="px-4 py-2">Paket</th><th class="px-4 py-2">ONU (sinkron terakhir)</th><th class="px-4 py-2">No. HP</th></tr>
                            </thead>
                            <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                                @forelse($customers as $c)
                                    <tr>
                                        <td class="px-4 py-2"><a href="{{ route('customers.show', $c->id) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $c->name }}</a>
                                            <span class="block font-mono text-[11px] text-surface-500">{{ $c->customer_code }}</span></td>
                                        <td class="px-4 py-2 text-surface-600 dark:text-surface-300">{{ $c->package?->name ?? '—' }}</td>
                                        <td class="px-4 py-2 text-surface-600 dark:text-surface-300">
                                            @if($c->signalCache){{ $c->signalCache->status }}@if($c->signalCache->rx_power !== null) · {{ number_format((float) $c->signalCache->rx_power, 1) }} dBm @endif @else — @endif
                                        </td>
                                        <td class="px-4 py-2 text-surface-600 dark:text-surface-300">{{ $c->phone ? 'ada' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-10 text-center text-surface-500">Tidak ada pelanggan aktif tercatat di cakupan ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($customers->hasPages())<div class="border-t border-surface-100 px-4 py-3 dark:border-surface-800">{{ $customers->links() }}</div>@endif
                </div>
            </div>

            @if($incident->isOpen())
                <aside class="space-y-4">
                    <form method="POST" action="{{ route('noc.incidents.resolve', $incident) }}" class="card"
                          onsubmit="return confirm('Tandai gangguan ini pulih? Tiket induk dan semua tiket pelanggan yang tertaut akan diselesaikan.')">
                        @csrf
                        <div class="card-body space-y-3">
                            <h3 class="card-title">Tandai pulih</h3>
                            <div>
                                <label class="label" for="resolution_notes">Catatan penyelesaian</label>
                                <textarea id="resolution_notes" name="resolution_notes" rows="3" class="input" placeholder="mis. Kabel feeder disambung ulang, sinyal normal"></textarea>
                            </div>
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="notify" value="1" class="mt-0.5 rounded" checked>
                                <span>Kirim WhatsApp "layanan sudah normal" ke pelanggan terdampak <span class="block text-xs text-surface-500">Template <em>Gangguan Teratasi</em></span></span>
                            </label>
                            <button class="btn-primary w-full justify-center">Tandai pulih</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('noc.incidents.notify', $incident) }}" class="card"
                          onsubmit="return confirm('Kirim WhatsApp pemberitahuan gangguan ke pelanggan terdampak?')">
                        @csrf
                        <div class="card-body space-y-2 text-sm">
                            <h3 class="card-title">Pemberitahuan gangguan</h3>
                            <p class="text-surface-500">{{ $incident->notified_count ? "Sudah dikirim ke {$incident->notified_count} pelanggan. Kirim lagi bila ada informasi baru (ubah estimasi lewat template)." : 'Belum dikirim.' }}</p>
                            <button class="btn-secondary w-full justify-center">{{ $incident->notified_count ? 'Kirim ulang WhatsApp' : 'Kirim WhatsApp pemberitahuan' }}</button>
                        </div>
                    </form>
                </aside>
            @endif
        </div>
    </div>
</x-app-layout>
