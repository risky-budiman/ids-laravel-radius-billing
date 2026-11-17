@php
    use App\Services\Reports\ReceivablesService as R;
    $rp = fn ($v) => rupiah($v, 0);
    $cell = fn ($v) => $v ? rupiah($v, 0, true, false) : '';
    $statusLabel = ['new' => 'Baru', 'waiting_activation' => 'Menunggu aktivasi', 'active' => 'Aktif', 'suspended' => 'Terisolir',
        'waiting_dismantle' => 'Menunggu dismantle', 'dismantled' => 'Dicabut', 'canceled' => 'Batal'];
    $statusBadge = ['active' => 'badge-success', 'suspended' => 'badge-warning', 'waiting_dismantle' => 'badge-danger', 'dismantled' => 'badge-gray', 'canceled' => 'badge-gray'];
    $ageClass = fn ($d) => $d <= 0 ? 'text-surface-500' : ($d <= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400 font-medium');
    $isToday = $asOf->isToday();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="Piutang Pelanggan" :subtitle="'Umur tagihan per ' . $asOf->locale('id')->translatedFormat('d F Y') . ' · ' . $customerCount . ' pelanggan · ' . $totalCount . ' invoice belum dibayar'">
            <x-slot name="filters">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="date" class="label !mb-1 text-xs">Per tanggal</label>
                        <input id="date" type="date" name="date" value="{{ $asOf->toDateString() }}" class="input !h-9">
                    </div>
                    <button type="submit" class="btn-primary">Tampilkan</button>
                </form>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('receivables.export', request()->query()) }}" class="btn-secondary">
                    <x-icon name="arrow-down" class="w-4 h-4" /> Ekspor Excel
                </a>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300 no-print">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 no-print">{{ session('error') }}</div>
        @endif

        <div class="no-print grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total piutang" :value="$rp($total)" icon="receipt" tone="primary" :hint="$totalCount . ' invoice'" />
            <x-stat-card label="Sudah lewat jatuh tempo" :value="$rp($overdueAmount)" icon="clock" tone="warning" />
            <x-stat-card label="Terlambat > 30 hari" :value="$rp($over30Amount)" icon="exclamation" tone="danger" />
            <x-stat-card label="Akan diisolir" :value="number_format($toBeSuspended->count(), 0, ',', '.')" icon="shield-check" tone="gray"
                         :hint="'Run billing ' . $asOf->addDay()->locale('id')->translatedFormat('d M') . ' 00:01'" />
        </div>

        {{-- Akan diisolir --}}
        <section class="card overflow-hidden">
            <div class="card-header">
                <h3 class="card-title">Akan diisolir pada run billing berikutnya ({{ $toBeSuspended->count() }})</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">
                    Pelanggan aktif yang tagihannya jatuh tempo paling lambat {{ $asOf->locale('id')->translatedFormat('d F Y') }}, atau masa aktif renewal-nya habis.
                    @if(\App\Services\AutoSuspension::enabled())
                        Diisolir otomatis {{ $asOf->addDay()->locale('id')->translatedFormat('d F Y') }} pukul 00:01 bila belum dibayar.
                    @else
                        <span class="font-semibold text-amber-700 dark:text-amber-300">Isolir otomatis sedang nonaktif</span> — pelanggan ini tidak akan diisolir sampai diaktifkan di Pengaturan Isolir.
                    @endif
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr><th>Pelanggan</th><th>No. HP</th><th>Alasan</th><th>Invoice</th><th class="text-right">Total</th><th class="no-print"></th></tr>
                    </thead>
                    <tbody>
                        @forelse($toBeSuspended as $s)
                            <tr>
                                <td>
                                    @canroute('customers.show')<a href="{{ route('customers.show', $s['customer']) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $s['customer']->name }}</a>@else<span class="font-medium text-surface-900 dark:text-white">{{ $s['customer']->name }}</span>@endcanroute
                                    <span class="block text-xs text-surface-500">{{ $s['customer']->customer_code }}</span>
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $s['customer']->phone ?: '—' }}</td>
                                <td class="text-xs">{{ $s['reason'] }}</td>
                                <td class="text-xs">
                                    @foreach($s['invoices'] as $inv)
                                        <a href="{{ route('invoices.show', $inv) }}" class="font-mono hover:underline">{{ $inv->invoice_number }}</a><span class="text-surface-400"> · {{ $inv->due_date?->format('d/m') }}</span>@if(! $loop->last)<br>@endif
                                    @endforeach
                                </td>
                                <td class="amount font-medium">{{ $s['total'] ? $rp($s['total']) : '—' }}</td>
                                <td class="text-right no-print">
                                    @if($s['invoices']->isNotEmpty() && $s['customer']->phone)
                                        <form method="POST" action="{{ route('invoices.whatsapp', $s['invoices']->first()) }}">
                                            @csrf
                                            <button type="submit" class="btn-ghost btn-sm">Kirim WA</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-surface-500 py-6">Tidak ada pelanggan yang akan diisolir pada run berikutnya.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Umur piutang --}}
        <div class="report-doc">
            <table class="report-table">
                <thead><tr><th>Umur piutang</th><th class="amount">Invoice</th><th class="amount">Jumlah</th><th class="amount">Porsi</th></tr></thead>
                <tbody>
                    @foreach($summary as $key => $b)
                        <tr class="report-row">
                            <td>{{ $b['label'] }}</td>
                            <td class="amount">{{ number_format($b['count'], 0, ',', '.') }}</td>
                            <td class="amount {{ in_array($key, ['d60', 'd90', 'd90plus']) && $b['amount'] ? 'amount-neg' : '' }}">{{ rupiah($b['amount'], 0, true, false) }}</td>
                            <td class="amount text-surface-500">{{ $total > 0 ? number_format($b['amount'] / $total * 100, 1, ',', '.') . '%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="report-total">
                        <td>Total piutang</td>
                        <td class="amount">{{ number_format($totalCount, 0, ',', '.') }}</td>
                        <td class="amount">{{ rupiah($total, 0, true, false) }}</td>
                        <td class="amount">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Per pelanggan --}}
        <section class="card overflow-hidden">
            <div class="card-header">
                <h3 class="card-title">Per pelanggan</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">Diurutkan dari yang paling lama menunggak.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th class="text-right">Terlambat</th>
                            @foreach(R::BUCKETS as [$label])
                                <th class="text-right font-normal normal-case">{{ $label }}</th>
                            @endforeach
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $c)
                            <tr>
                                <td class="min-w-[12rem]">
                                    @if($c['customer'])
                                        @canroute('customers.show')<a href="{{ route('customers.show', $c['customer']) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $c['customer']->name }}</a>@else<span class="font-medium text-surface-900 dark:text-white">{{ $c['customer']->name }}</span>@endcanroute
                                        <span class="block text-xs text-surface-500">{{ $c['customer']->customer_code }} · {{ $c['invoice_count'] }} invoice</span>
                                    @else
                                        <span class="text-surface-400">Pelanggan dihapus</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c['customer'])
                                        <span class="{{ $statusBadge[$c['customer']->status] ?? 'badge-gray' }}">{{ $statusLabel[$c['customer']->status] ?? $c['customer']->status }}</span>
                                    @endif
                                </td>
                                <td class="amount whitespace-nowrap {{ $ageClass($c['max_days']) }}">{{ $c['max_days'] > 0 ? $c['max_days'] . ' hari' : '—' }}</td>
                                @foreach(array_keys(R::BUCKETS) as $k)
                                    <td class="amount text-xs">{{ $cell($c['buckets'][$k]) }}</td>
                                @endforeach
                                <td class="amount font-semibold text-surface-900 dark:text-white">{{ $cell($c['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 4 + count(R::BUCKETS) }}" class="text-center text-surface-500 py-8">Tidak ada piutang. Semua invoice sudah dibayar.</td></tr>
                        @endforelse
                    </tbody>
                    @if($customers->isNotEmpty())
                        <tfoot>
                            <tr class="font-semibold text-surface-900 dark:text-white border-t-2 border-surface-300 dark:border-surface-700">
                                <td class="px-4 py-3" colspan="3">Total</td>
                                @foreach(array_keys(R::BUCKETS) as $k)
                                    <td class="px-4 py-3 amount text-xs">{{ $cell($summary[$k]['amount']) }}</td>
                                @endforeach
                                <td class="px-4 py-3 amount">{{ $cell($total) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <p class="text-xs text-surface-500 dark:text-surface-400">
            Piutang = invoice belum dibayar, termasuk milik pelanggan terisolir/dicabut. Pembelian booster yang belum dibayar tidak dihitung.
            @unless($isToday) Laporan per tanggal lampau memakai status invoice saat ini (invoice yang sudah dilunasi tidak muncul). @endunless
        </p>
    </div>
</x-app-layout>
