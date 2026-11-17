@php
    $hdr = (int) ($map['header_row'] ?? 0);
    $headerCells = $rows[$hdr] ?? [];
    $colOptions = function ($selected, bool $optional = true) use ($width, $headerCells) {
        $html = $optional ? '<option value="">— tidak ada —</option>' : '';
        for ($i = 0; $i < $width; $i++) {
            $name = trim((string) ($headerCells[$i] ?? ''));
            $html .= '<option value="' . $i . '"' . ((string) $selected !== '' && $selected !== null && (int) $selected === $i ? ' selected' : '') . '>Kolom ' . ($i + 1) . ($name !== '' ? ': ' . e(\Illuminate\Support\Str::limit($name, 30)) : '') . '</option>';
        }

        return $html;
    };
    $mode = $map['amount_mode'] ?? 'single';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('bank-reconciliation.index', ['account' => $account->id]) }}" class="text-sm text-surface-500 hover:text-primary-600">← Rekonsiliasi Bank</a>
            <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Petakan kolom mutasi</h2>
            <p class="text-sm text-surface-500">{{ $file }} → {{ $account->bank_name }} · {{ $account->account_name }} · {{ number_format($totalRows) }} baris di file</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('bank-reconciliation.confirm') }}" class="space-y-4" x-data="{ mode: @js($mode) }">
        @csrf
        @if(session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
        @endif

        <div class="card">
            <div class="card-body grid gap-4 md:grid-cols-3">
                <div><label class="label" for="header_row">Baris judul kolom</label>
                    <input id="header_row" type="number" min="0" name="header_row" value="{{ $hdr }}" class="input">
                    <p class="help-text">Nomor baris (lihat kolom # di tabel bawah). Data dibaca mulai baris berikutnya.</p></div>
                <div><label class="label" for="date_col">Tanggal</label><select id="date_col" name="date_col" class="input">{!! $colOptions($map['date_col'] ?? 0, false) !!}</select></div>
                <div><label class="label" for="desc_col">Keterangan</label><select id="desc_col" name="desc_col" class="input">{!! $colOptions($map['desc_col'] ?? null) !!}</select></div>

                <div class="md:col-span-3">
                    <span class="label">Nominal</span>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2"><input type="radio" name="amount_mode" value="single" x-model="mode"> Satu kolom (bertanda +/−, atau CR/DB)</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" name="amount_mode" value="split" x-model="mode"> Kolom Debit & Kredit terpisah</label>
                    </div>
                </div>
                <template x-if="mode === 'single'">
                    <div class="contents">
                        <div><label class="label" for="amount_col">Kolom nominal</label><select id="amount_col" name="amount_col" class="input">{!! $colOptions($map['amount_col'] ?? null) !!}</select></div>
                        <div><label class="label" for="type_col">Kolom jenis CR/DB (opsional)</label><select id="type_col" name="type_col" class="input">{!! $colOptions($map['type_col'] ?? null) !!}</select>
                            <p class="help-text">Bila "CR"/"DB" ada di kolom nominal (format BCA), biarkan kosong.</p></div>
                        <div></div>
                    </div>
                </template>
                <template x-if="mode === 'split'">
                    <div class="contents">
                        <div><label class="label" for="debit_col">Kolom debit (keluar)</label><select id="debit_col" name="debit_col" class="input">{!! $colOptions($map['debit_col'] ?? null) !!}</select></div>
                        <div><label class="label" for="credit_col">Kolom kredit (masuk)</label><select id="credit_col" name="credit_col" class="input">{!! $colOptions($map['credit_col'] ?? null) !!}</select></div>
                        <div></div>
                    </div>
                </template>

                <div><label class="label" for="balance_col">Saldo (opsional)</label><select id="balance_col" name="balance_col" class="input">{!! $colOptions($map['balance_col'] ?? null) !!}</select>
                    <p class="help-text">Dipakai untuk membandingkan saldo bank vs aplikasi.</p></div>
                <div><label class="label" for="date_format">Format tanggal</label>
                    <select id="date_format" name="date_format" class="input">
                        @foreach(['auto' => 'Otomatis (hari/bulan/tahun)', 'd/m/Y' => 'Hari/bulan/tahun', 'm/d/Y' => 'Bulan/hari/tahun'] as $k => $v)
                            <option value="{{ $k }}" @selected(($map['date_format'] ?? 'auto') === $k)>{{ $v }}</option>
                        @endforeach
                    </select></div>
                <div><label class="label" for="year">Tahun (bila tanggal tanpa tahun)</label>
                    <input id="year" type="number" name="year" value="{{ $map['year'] ?? date('Y') }}" class="input"></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-surface-200 px-6 py-4 dark:border-surface-800">
                <p class="text-sm text-surface-600 dark:text-surface-300"><strong class="tabular-nums">{{ $previewCount }}</strong> mutasi terbaca{{ $skipped ? ", {$skipped} baris dilewati (judul, saldo awal, total)" : '' }}.</p>
                <div class="flex gap-2">
                    <button name="action" value="preview" class="btn-secondary">Pratinjau ulang</button>
                    <button name="action" value="import" class="btn-primary" @disabled(! $previewCount)>Import {{ $previewCount }} mutasi</button>
                </div>
            </div>
        </div>

        @if($preview)
            <div class="card overflow-hidden">
                <div class="card-header"><h3 class="card-title">Hasil baca (contoh)</h3></div>
                <div class="overflow-x-auto"><table class="w-full text-sm">
                    <thead class="bg-surface-50 text-left text-xs uppercase tracking-wide text-surface-500 dark:bg-surface-900/60"><tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Keterangan</th><th class="px-4 py-2 text-right">Mutasi</th><th class="px-4 py-2 text-right">Saldo</th></tr></thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($preview as $l)
                            <tr><td class="px-4 py-2 tabular-nums">{{ $l['date']->format('d/m/Y') }}</td><td class="px-4 py-2">{{ \Illuminate\Support\Str::limit($l['description'], 80) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums {{ $l['amount'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ ($l['amount'] > 0 ? '+' : '−') . ' ' . number_format(abs($l['amount']), 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $l['balance'] !== null ? number_format($l['balance'], 2, ',', '.') : '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="card-header"><h3 class="card-title">Isi file (40 baris pertama)</h3></div>
            <div class="overflow-x-auto"><table class="w-full text-xs">
                <thead class="bg-surface-50 text-left text-surface-500 dark:bg-surface-900/60"><tr><th class="px-3 py-2">#</th>@for($i = 0; $i < $width; $i++)<th class="px-3 py-2">Kolom {{ $i + 1 }}</th>@endfor</tr></thead>
                <tbody class="divide-y divide-surface-100 font-mono dark:divide-surface-800">
                    @foreach($rows as $ri => $r)
                        <tr class="{{ $ri === $hdr ? 'bg-primary-50 font-semibold dark:bg-primary-500/10' : '' }}"><td class="px-3 py-1.5 text-surface-400">{{ $ri }}</td>
                            @for($i = 0; $i < $width; $i++)<td class="max-w-[16rem] truncate px-3 py-1.5" title="{{ $r[$i] ?? '' }}">{{ $r[$i] ?? '' }}</td>@endfor</tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    </form>
</x-app-layout>
