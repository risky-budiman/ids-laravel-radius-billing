@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $tone = ['matched' => 'badge-success', 'manual' => 'badge-info', 'unmatched' => 'badge-warning', 'ignored' => 'badge-gray'];
    $tabs = ['unmatched' => 'Belum cocok', 'matched' => 'Cocok otomatis', 'manual' => 'Manual', 'ignored' => 'Diabaikan', 'all' => 'Semua'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Mutasi Bank</h2>
            <p class="page-subtitle">Dana masuk dari Moota. Yang cocok dengan satu invoice dilunasi otomatis; yang ragu menunggu dicocokkan di sini.</p>
        </div>
    </x-slot>

    <div class="max-w-6xl space-y-6">
        @foreach(['success' => 'emerald', 'error' => 'rose'] as $key => $t)
            @if(session($key))
                <div class="rounded-xl border border-{{ $t }}-200 bg-{{ $t }}-50 px-4 py-3 text-sm text-{{ $t }}-800 dark:border-{{ $t }}-500/30 dark:bg-{{ $t }}-500/10 dark:text-{{ $t }}-300">{{ session($key) }}</div>
            @endif
        @endforeach
        @error('invoice_number')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Menunggu dicocokkan" :value="(string) ($counts['unmatched'] ?? 0)" icon="exclamation"
                         :tone="($counts['unmatched'] ?? 0) ? 'warning' : 'gray'" :hint="$rp($unmatchedTotal) . ' dana masuk belum teralokasi'" />
            <x-stat-card label="Cocok otomatis" :value="(string) ($counts['matched'] ?? 0)" icon="check-circle" tone="success" />
            <x-stat-card label="Dicocokkan manual" :value="(string) ($counts['manual'] ?? 0)" icon="user" tone="info" />
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('bank-mutations.index', ['status' => $key]) }}"
                   class="{{ $status === $key ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ $label }}@if($key !== 'all' && ($counts[$key] ?? 0)) ({{ $counts[$key] }})@endif</a>
            @endforeach
        </div>

        <div class="card">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th class="text-right">Nominal</th><th>Keterangan transfer</th><th>Status</th><th>Invoice / tindakan</th></tr></thead>
                    <tbody>
                        @forelse($mutations as $mu)
                            <tr class="align-top">
                                <td class="whitespace-nowrap text-xs">{{ ($mu->mutation_at ?? $mu->created_at)->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                <td class="whitespace-nowrap text-right font-mono">{{ $rp($mu->amount) }}</td>
                                <td class="text-xs">
                                    <div class="text-surface-800 dark:text-surface-200">{{ $mu->description ?: '—' }}</div>
                                    @if($mu->note)<div class="mt-1 text-surface-500">{{ $mu->note }}</div>@endif
                                </td>
                                <td><span class="{{ $tone[$mu->status] ?? 'badge-gray' }}">{{ \App\Models\BankMutation::STATUS_LABELS[$mu->status] ?? $mu->status }}</span></td>
                                <td class="text-xs">
                                    @if($mu->invoice)
                                        <a href="{{ route('invoices.show', $mu->invoice) }}" class="font-mono text-primary-600 hover:underline dark:text-primary-400">{{ $mu->invoice->invoice_number }}</a>
                                        <div class="text-surface-500">{{ $mu->invoice->customer?->name }}</div>
                                        @if($mu->handler)<div class="text-surface-400">oleh {{ $mu->handler->name }}</div>@endif
                                    @elseif($mu->status === 'unmatched')
                                        <form method="POST" action="{{ route('bank-mutations.match', $mu) }}" class="flex gap-2">
                                            @csrf
                                            <input name="invoice_number" class="input !h-8 font-mono text-xs w-40" placeholder="Nomor invoice" required>
                                            <button class="btn-primary btn-sm" onclick="return confirm('Lunasi invoice ini dengan mutasi {{ $rp($mu->amount) }}?')">Lunasi</button>
                                        </form>
                                        <form method="POST" action="{{ route('bank-mutations.ignore', $mu) }}" class="mt-1">
                                            @csrf
                                            <button class="text-surface-400 hover:text-surface-700 dark:hover:text-surface-200" onclick="return confirm('Abaikan mutasi ini (bukan pembayaran invoice)?')">Abaikan</button>
                                        </form>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-sm text-surface-500">Tidak ada mutasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($mutations->hasPages())<div class="card-body">{{ $mutations->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
