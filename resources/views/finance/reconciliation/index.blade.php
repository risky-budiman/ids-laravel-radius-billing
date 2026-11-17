@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $signed = fn ($v) => ((float) $v >= 0 ? '+' : '−') . ' ' . number_format(abs((float) $v), 0, ',', '.');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Rekonsiliasi Bank</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Import mutasi dari internet banking lalu cocokkan dengan catatan Kas & Bank.</p>
            </div>
            @if($account)
                <form method="GET" class="flex items-center gap-2">
                    <select name="account" class="input w-auto" onchange="this.form.submit()" aria-label="Rekening">
                        @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($a->id === $account->id)>{{ $a->bank_name }} · {{ $a->account_name }}{{ $a->account_number ? ' · ' . $a->account_number : '' }}</option>@endforeach
                    </select>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4" x-data="recon()">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
        @endif

        @if(! $account)
            <div class="card p-8 text-center text-sm text-surface-500">Belum ada rekening bank aktif di Kas & Bank.</div>
        @else
            {{-- Import --}}
            <form method="POST" action="{{ route('bank-reconciliation.upload') }}" enctype="multipart/form-data" class="card">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $account->id }}">
                <div class="card-body flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="label" for="file">Import mutasi {{ $account->bank_name }} · {{ $account->account_name }}</label>
                        <input id="file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="input" required>
                        <p class="help-text">CSV atau Excel hasil unduh mutasi dari internet banking / aplikasi bisnis bank. Baris yang sudah pernah diimport dilewati.</p>
                        @error('file')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="btn-primary">Unggah & petakan kolom</button>
                </div>
            </form>

            {{-- Periode --}}
            <div class="flex flex-wrap items-end gap-2">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="account" value="{{ $account->id }}">
                    <div><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
                    <div><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
                    <button class="btn-secondary">Tampilkan</button>
                </form>
                <form method="POST" action="{{ route('bank-reconciliation.rematch') }}">@csrf
                    <input type="hidden" name="account" value="{{ $account->id }}"><input type="hidden" name="from" value="{{ $from->toDateString() }}"><input type="hidden" name="to" value="{{ $to->toDateString() }}">
                    <button class="btn-ghost" title="Jalankan lagi setelah mencatat transaksi yang kurang">Cocokkan otomatis lagi</button>
                </form>
            </div>

            {{-- Ringkasan --}}
            @php $s = $summary; @endphp
            <div class="grid gap-4 md:grid-cols-3">
                <div class="card p-4">
                    <p class="text-xs font-medium text-surface-500">Menurut bank (mutasi)</p>
                    @if($s['bank_closing'] !== null)
                        <p class="mt-1 text-sm text-surface-600 dark:text-surface-300">Saldo awal {{ $rp($s['bank_opening']) }}</p>
                        <p class="text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($s['bank_closing']) }}</p>
                    @else
                        <p class="mt-1 text-sm text-surface-500">File tanpa kolom saldo</p>
                    @endif
                    <p class="mt-1 text-xs text-surface-500">Masuk {{ $rp($s['bank_in']) }} · keluar {{ $rp($s['bank_out']) }}</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs font-medium text-surface-500">Menurut aplikasi (Kas & Bank)</p>
                    <p class="mt-1 text-sm text-surface-600 dark:text-surface-300">Saldo awal {{ $rp($s['app_opening']) }}</p>
                    <p class="text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($s['app_closing']) }}</p>
                    <p class="mt-1 text-xs text-surface-500">dari transaksi tercatat, {{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M Y') }}</p>
                </div>
                <div class="card p-4 {{ $s['closing_difference'] !== null && abs($s['closing_difference']) >= 1 ? 'border-amber-300 dark:border-amber-500/40' : '' }}">
                    <p class="text-xs font-medium text-surface-500">Selisih saldo akhir (bank − aplikasi)</p>
                    @if($s['closing_difference'] === null)
                        <p class="mt-1 text-sm text-surface-500">Tidak bisa dihitung tanpa kolom saldo.</p>
                    @elseif(abs($s['closing_difference']) < 1)
                        <p class="mt-1 text-2xl font-semibold text-emerald-600">Cocok</p>
                    @else
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-600">{{ $signed($s['closing_difference']) }}</p>
                        <p class="text-xs text-surface-500">Jelaskan lewat daftar di bawah. Selisih saldo awal juga terbawa bila periode sebelumnya belum direkonsiliasi.</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="badge-success">{{ $s['matched']->count() }} cocok</span>
                <span class="badge-warning">{{ $s['only_bank']->count() }} hanya di bank</span>
                <span class="badge-danger">{{ $s['only_app']->count() }} hanya di aplikasi</span>
                <span class="badge-gray">{{ $s['ignored']->count() }} diabaikan</span>
            </div>

            {{-- Hanya di bank --}}
            <div class="card overflow-hidden">
                <div class="card-header"><h3 class="card-title">Ada di bank, belum tercatat di aplikasi ({{ $s['only_bank']->count() }})</h3>
                    <p class="text-xs text-surface-500">Catat sebagai pemasukan/pengeluaran (form terisi otomatis), cocokkan manual dengan transaksi yang tanggal/nominalnya berbeda, atau abaikan (mis. biaya admin yang dicatat bulanan).</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-50 text-left text-xs uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                            <tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Keterangan bank</th><th class="px-4 py-2 text-right">Mutasi</th><th class="px-4 py-2 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @forelse($s['only_bank'] as $l)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 tabular-nums">{{ $l->line_date->format('d/m/Y') }}</td>
                                    <td class="max-w-md px-4 py-2 text-surface-700 dark:text-surface-300">{{ $l->description ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right font-medium tabular-nums {{ $l->isCredit() ? 'text-emerald-600' : 'text-rose-600' }}">{{ $signed($l->amount) }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('bank-reconciliation.record', $l) }}" class="btn-secondary btn-sm">Catat</a>
                                            <button type="button" class="btn-ghost btn-sm" @click="openMatch({{ $l->id }}, @js(route('bank-reconciliation.candidates', $l, false)), @js(route('bank-reconciliation.match', $l, false)))">Cocokkan</button>
                                            <form method="POST" action="{{ route('bank-reconciliation.ignore', $l) }}" onsubmit="const n = prompt('Alasan diabaikan (mis. biaya admin bank, bunga):'); if (!n) return false; this.note.value = n;">
                                                @csrf<input type="hidden" name="note"><button class="btn-ghost btn-sm text-surface-500">Abaikan</button>
                                            </form>
                                        </div>
                                        <template x-if="matchFor === {{ $l->id }}">
                                            <form method="POST" :action="matchUrl" class="mt-2 flex items-center justify-end gap-2">
                                                @csrf
                                                <select name="bank_transaction_id" class="input py-1 text-xs" required>
                                                    <option value="" x-text="cands === null ? 'Memuat…' : (cands.length ? '— pilih transaksi —' : 'Tidak ada kandidat')"></option>
                                                    <template x-for="c in cands || []" :key="c.id"><option :value="c.id" x-text="c.label"></option></template>
                                                </select>
                                                <button class="btn-primary btn-sm">Cocokkan</button>
                                            </form>
                                        </template>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-8 text-center text-surface-500">Semua mutasi bank pada periode ini sudah cocok atau diabaikan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Hanya di aplikasi --}}
            <div class="card overflow-hidden">
                <div class="card-header"><h3 class="card-title">Tercatat di aplikasi, tidak ada di mutasi bank ({{ $s['only_app']->count() }})</h3>
                    <p class="text-xs text-surface-500">Kemungkinan salah rekening, salah nominal/tanggal, tercatat dua kali, atau transaksi yang tidak pernah terjadi di bank. Periksa & perbaiki di Kas & Bank.</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-50 text-left text-xs uppercase tracking-wide text-surface-500 dark:bg-surface-900/60">
                            <tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Keterangan</th><th class="px-4 py-2">Akun</th><th class="px-4 py-2 text-right">Nominal</th><th class="px-4 py-2"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @forelse($s['only_app'] as $t)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 tabular-nums">{{ $t->transaction_date->format('d/m/Y') }}</td>
                                    <td class="max-w-md px-4 py-2 text-surface-700 dark:text-surface-300">{{ $t->description }} @if($t->reference_number)<span class="font-mono text-[11px] text-surface-500">{{ $t->reference_number }}</span>@endif</td>
                                    <td class="px-4 py-2 text-surface-600 dark:text-surface-300">{{ $t->category ? $t->category->code . ' ' . $t->category->name : '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right font-medium tabular-nums {{ $t->type === 'deposit' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $signed($t->type === 'deposit' ? $t->amount : -$t->amount) }}</td>
                                    <td class="px-4 py-2 text-right">@canroute('bank-transactions.edit')<a href="{{ route('bank-transactions.edit', $t) }}" class="btn-ghost btn-sm">Periksa</a>@endcanroute</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-surface-500">Semua transaksi aplikasi pada periode ini ada di mutasi bank.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cocok & diabaikan --}}
            <details class="card">
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-surface-800 dark:text-surface-200">Sudah cocok ({{ $s['matched']->count() }}) & diabaikan ({{ $s['ignored']->count() }})</summary>
                <div class="overflow-x-auto border-t border-surface-100 dark:border-surface-800">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                            @foreach($s['matched']->concat($s['ignored']) as $l)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 tabular-nums">{{ $l->line_date->format('d/m/Y') }}</td>
                                    <td class="max-w-sm px-4 py-2 text-surface-700 dark:text-surface-300">{{ $l->description }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums">{{ $signed($l->amount) }}</td>
                                    <td class="px-4 py-2 text-xs text-surface-500">
                                        @if($l->status === 'matched')
                                            ↔ {{ $l->transaction?->transaction_date?->format('d/m/Y') }} · {{ \Illuminate\Support\Str::limit((string) $l->transaction?->description, 50) }} <span class="badge-gray">{{ $l->match_type === 'manual' ? 'manual' : 'otomatis' }}</span>
                                        @else
                                            Diabaikan: {{ $l->note }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('bank-reconciliation.unmatch', $l) }}">@csrf<button class="btn-ghost btn-sm">{{ $l->status === 'matched' ? 'Batalkan' : 'Pulihkan' }}</button></form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>

            @if($imports->isNotEmpty())
                <details class="card">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-surface-800 dark:text-surface-200">Riwayat import ({{ $imports->count() }})</summary>
                    <ul class="divide-y divide-surface-100 border-t border-surface-100 text-sm dark:divide-surface-800 dark:border-surface-800">
                        @foreach($imports as $im)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                                <span><span class="font-medium">{{ $im->file_name }}</span>
                                    <span class="text-surface-500">· {{ $im->period_start?->format('d/m/Y') }} – {{ $im->period_end?->format('d/m/Y') }} · {{ $im->rows_new }}/{{ $im->rows_total }} baris baru · {{ $im->creator?->name }} {{ $im->created_at->diffForHumans() }}</span></span>
                                <form method="POST" action="{{ route('bank-reconciliation.import.destroy', $im) }}" onsubmit="return confirm('Hapus import ini beserta baris mutasinya? Transaksi Kas & Bank tidak ikut terhapus.')">
                                    @csrf @method('DELETE')<button class="btn-ghost btn-sm text-rose-600">Hapus</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endif
    </div>

    @push('scripts')
    <script>
        function recon() {
            return {
                matchFor: null, matchUrl: '', cands: null,
                openMatch(id, candUrl, matchUrl) {
                    if (this.matchFor === id) { this.matchFor = null; return; }
                    this.matchFor = id; this.matchUrl = matchUrl; this.cands = null;
                    fetch(candUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json()).then(d => this.cands = d).catch(() => this.cands = []);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
