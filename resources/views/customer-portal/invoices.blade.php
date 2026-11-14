<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-surface-900 dark:text-white">Tagihan</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400">Riwayat dan status pembayaran Anda.</p>
        </div>

        @forelse($invoices as $invoice)
            @php
                $isPaid = $invoice->status === 'paid';
                $isOverdue = ! $isPaid && $invoice->due_date && $invoice->due_date->isPast();
                $link = \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $invoice->id);
            @endphp
            <div class="card p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-surface-900 dark:text-white">
                            Tagihan {{ ($invoice->period_start ?? $invoice->created_at)->locale('id')->translatedFormat('F Y') }}
                        </p>
                        <p class="text-xs text-surface-500 dark:text-surface-400 font-mono mt-0.5">{{ $invoice->invoice_number }}</p>
                    </div>
                    @if($isPaid)
                        <span class="badge-success">Lunas</span>
                    @elseif($isOverdue)
                        <span class="badge-danger">Terlambat</span>
                    @else
                        <span class="badge-warning">Belum bayar</span>
                    @endif
                </div>

                <div class="mt-4 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs text-surface-500 dark:text-surface-400">
                            {{ $isPaid ? 'Dibayar' : 'Jatuh tempo' }}
                            {{ ($isPaid && $invoice->paid_at ? $invoice->paid_at : $invoice->due_date)?->locale('id')->translatedFormat('d M Y') ?? '—' }}
                        </p>
                        <p class="text-lg font-semibold tabular-nums text-surface-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                    </div>
                    @if($isPaid)
                        <a href="{{ $link }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">Lihat detail</a>
                    @else
                        <a href="{{ $link }}" class="btn-primary btn-sm">Bayar sekarang</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card px-6 py-12 text-center">
                <div class="mx-auto w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 mb-3">
                    <x-icon name="document" class="w-5 h-5" />
                </div>
                <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Belum ada tagihan</p>
                <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Tagihan bulanan Anda akan muncul di sini.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
