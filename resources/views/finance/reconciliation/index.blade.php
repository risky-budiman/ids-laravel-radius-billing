@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $signed = fn ($v) => ((float) $v >= 0 ? '+' : '−') . ' ' . number_format(abs((float) $v), 0, ',', '.');
    if ($account) {
        $s = $summary;
        $totalLines = $s['lines']->count();
        $done = $s['matched']->count() + $s['ignored']->count();
        $pct = $totalLines ? (int) round($done / $totalLines * 100) : 0;
        $tabs = [
            'bank' => ['Hanya di bank', $s['only_bank']->count(), 'bg-amber-500'],
            'app' => ['Hanya di aplikasi', $s['only_app']->count(), 'bg-rose-500'],
            'matched' => ['Cocok', $s['matched']->count(), null],
            'ignored' => ['Diabaikan', $s['ignored']->count(), null],
            'imports' => ['Riwayat import', $imports->count(), null],
        ];
        $firstTab = $s['only_bank']->isNotEmpty() ? 'bank' : ($s['only_app']->isNotEmpty() ? 'app' : 'matched');
        $neverImported = $imports->isEmpty();
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Rekonsiliasi Bank</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Cocokkan mutasi dari internet banking dengan catatan Kas & Bank.</p>
            </div>
            @if($account)
                <form method="GET" class="w-full lg:w-80">
                    <label for="account" class="sr-only">Rekening</label>
                    <select id="account" name="account" class="input" onchange="this.form.submit()">
                        @foreach($accounts as $a)<option value="{{ $a->id }}" @selected($a->id === $account->id)>{{ $a->bank_name }} · {{ $a->account_name }}{{ $a->account_number ? ' · ' . $a->account_number : '' }}</option>@endforeach
                    </select>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-5" x-data="recon(@js($account ? $firstTab : 'bank'), @js($account && ($neverImported || $errors->has('file'))))">
        @if(session('success'))
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"><x-icon name="exclamation" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('error') }}</div>
        @endif

        @if(! $account)
            <div class="card p-10 text-center">
                <p class="font-medium text-surface-800 dark:text-surface-200">Belum ada rekening bank aktif</p>
                <p class="mt-1 text-sm text-surface-500">Tambahkan rekening bank di <a href="{{ route('bank-accounts.index') }}" class="text-primary-600 hover:underline">Kas & Bank</a> terlebih dahulu.</p>
            </div>
        @else
            {{-- Toolbar --}}
            <div class="card">
                <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-end lg:justify-between">
                    <form method="GET" class="flex flex-wrap items-end gap-2">
                        <input type="hidden" name="account" value="{{ $account->id }}">
                        <div><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
                        <div><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
                        <button class="btn-secondary">Tampilkan</button>
                    </form>
                    <div class="flex flex-wrap gap-2">
                        @unless($neverImported)
                            <form method="POST" action="{{ route('bank-reconciliation.rematch') }}">@csrf
                                <input type="hidden" name="account" value="{{ $account->id }}"><input type="hidden" name="from" value="{{ $from->toDateString() }}"><input type="hidden" name="to" value="{{ $to->toDateString() }}">
                                <button class="btn-secondary" title="Jalankan lagi setelah mencatat transaksi yang kurang"><x-icon name="refresh" class="h-4 w-4" /> Cocokkan otomatis lagi</button>
                            </form>
                        @endunless
                        <button type="button" class="btn-primary" @click="upload = !upload"><x-icon name="plus" class="h-4 w-4" /> Import mutasi</button>
                    </div>
                </div>

                {{-- Unggah file --}}
                <form x-show="upload" x-cloak method="POST" action="{{ route('bank-reconciliation.upload') }}" enctype="multipart/form-data"
                      class="border-t border-surface-200 p-4 dark:border-surface-800">
                    @csrf
                    <input type="hidden" name="bank_account_id" value="{{ $account->id }}">
                    <label for="file" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-8 text-center transition-colors"
                           :class="fileName ? 'border-primary-400 bg-primary-50/60 dark:border-primary-500/60 dark:bg-primary-500/10' : 'border-surface-300 hover:border-primary-400 hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800/50'"
                           @dragover.prevent @drop.prevent="$refs.file.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name || ''">
                        <x-icon name="document" class="h-8 w-8 text-surface-400" />
                        <span class="text-sm font-medium text-surface-800 dark:text-surface-200" x-text="fileName || 'Pilih atau tarik file mutasi ke sini'"></span>
                        <span class="text-xs text-surface-500">CSV atau Excel (.xlsx / .xls) dari internet banking {{ $account->bank_name }} · maks. 10 MB</span>
                        <input id="file" x-ref="file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="sr-only" required @change="fileName = $event.target.files[0]?.name || ''">
                    </label>
                    @error('file')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-surface-500">Berikutnya Anda memetakan kolom (sekali per rekening, lalu diingat). Mutasi yang sudah pernah diimport dilewati.</p>
                        <button class="btn-primary" :disabled="!fileName">Unggah & petakan kolom</button>
                    </div>
                </form>
            </div>

            @if($neverImported)
                {{-- Panduan pertama kali --}}
                <div class="card p-6">
                    <h3 class="font-semibold text-surface-900 dark:text-white">Rekonsiliasi pertama untuk {{ $account->bank_name }} · {{ $account->account_name }}</h3>
                    <ol class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                        @foreach([['Unduh mutasi', 'Dari internet banking / aplikasi bisnis bank, pilih periode lalu unduh sebagai CSV atau Excel.'],
                                  ['Import & petakan', 'Tentukan kolom tanggal, keterangan, nominal & saldo. Pemetaan diingat untuk bulan berikutnya.'],
                                  ['Jelaskan selisih', 'Mutasi dicocokkan otomatis. Catat yang belum ada, periksa yang tercatat tapi tidak ada di bank.']] as $i => [$t, $d])
                            <li class="rounded-lg bg-surface-50 p-4 dark:bg-surface-800/50">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">{{ $i + 1 }}</span>
                                <p class="mt-2 font-medium text-surface-900 dark:text-white">{{ $t }}</p>
                                <p class="mt-1 text-surface-500">{{ $d }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @else
                {{-- Ringkasan saldo --}}
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="card p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-surface-500">Menurut bank</p>
                        @if($s['bank_closing'] !== null)
                            <p class="mt-2 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($s['bank_closing']) }}</p>
                            <p class="text-xs text-surface-500">saldo akhir · awal {{ $rp($s['bank_opening']) }}</p>
                        @else
                            <p class="mt-2 text-sm text-surface-500">File tanpa kolom saldo</p>
                        @endif
                        <dl class="mt-3 flex gap-4 border-t border-surface-100 pt-3 text-xs dark:border-surface-800">
                            <div><dt class="text-surface-500">Masuk</dt><dd class="font-medium tabular-nums text-emerald-600">{{ $rp($s['bank_in']) }}</dd></div>
                            <div><dt class="text-surface-500">Keluar</dt><dd class="font-medium tabular-nums text-rose-600">{{ $rp($s['bank_out']) }}</dd></div>
                        </dl>
                    </div>
                    <div class="card p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-surface-500">Menurut aplikasi</p>
                        <p class="mt-2 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($s['app_closing']) }}</p>
                        <p class="text-xs text-surface-500">saldo akhir · awal {{ $rp($s['app_opening']) }}</p>
                        <p class="mt-3 border-t border-surface-100 pt-3 text-xs text-surface-500 dark:border-surface-800">Dari transaksi Kas & Bank, {{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M Y') }}</p>
                    </div>
                    @php $diff = $s['closing_difference']; $ok = $diff !== null && abs($diff) < 1; @endphp
                    <div class="card p-5 {{ $diff === null ? '' : ($ok ? 'border-emerald-300 dark:border-emerald-500/40' : 'border-amber-300 dark:border-amber-500/40') }}">
                        <p class="text-xs font-medium uppercase tracking-wide text-surface-500">Selisih saldo akhir</p>
                        @if($diff === null)
                            <p class="mt-2 text-sm text-surface-500">Tidak bisa dihitung tanpa kolom saldo.</p>
                        @elseif($ok)
                            <p class="mt-2 flex items-center gap-2 text-2xl font-semibold text-emerald-600"><x-icon name="check-circle" class="h-6 w-6" /> Cocok</p>
                        @else
                            <p class="mt-2 text-2xl font-semibold tabular-nums text-amber-600">{{ $signed($diff) }}</p>
                            <p class="text-xs text-surface-500">bank − aplikasi. Jelaskan lewat tab di bawah; selisih saldo awal ikut terbawa bila periode sebelumnya belum direkonsiliasi.</p>
                        @endif
                        <div class="mt-3 border-t border-surface-100 pt-3 dark:border-surface-800">
                            <div class="flex justify-between text-xs text-surface-500"><span>{{ $done }} dari {{ $totalLines }} mutasi selesai</span><span class="tabular-nums">{{ $pct }}%</span></div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-100 dark:bg-surface-800" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-primary-500' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @unless($neverImported)
                {{-- Tab --}}
                <div class="card overflow-hidden">
                    <div class="flex gap-1 overflow-x-auto border-b border-surface-200 px-3 dark:border-surface-800" role="tablist">
                        @foreach($tabs as $key => [$label, $count, $dot])
                            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                    class="-mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium transition-colors"
                                    :class="tab === '{{ $key }}' ? 'border-primary-600 text-primary-700 dark:text-primary-400' : 'border-transparent text-surface-500 hover:text-surface-800 dark:hover:text-surface-200'">
                                @if($dot && $count)<span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>@endif
                                {{ $label }}
                                <span class="rounded-full bg-surface-100 px-1.5 text-xs tabular-nums text-surface-600 dark:bg-surface-800 dark:text-surface-300">{{ $count }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Hanya di bank --}}
                    <div x-show="tab === 'bank'">
                        <p class="border-b border-surface-100 px-5 py-3 text-xs text-surface-500 dark:border-surface-800">Ada di mutasi bank tetapi belum tercatat di aplikasi. <strong>Catat</strong> membuka form pemasukan/pengeluaran yang sudah terisi; <strong>Cocokkan</strong> bila transaksinya tercatat dengan tanggal/nominal berbeda; <strong>Abaikan</strong> untuk yang memang tidak dicatat per transaksi (mis. biaya admin).</p>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead><tr><th>Tanggal</th><th>Keterangan bank</th><th class="text-right">Mutasi</th><th class="text-right">Aksi</th></tr></thead>
                                <tbody>
                                    @forelse($s['only_bank'] as $l)
                                        <tr>
                                            <td class="whitespace-nowrap tabular-nums">{{ $l->line_date->format('d/m/Y') }}</td>
                                            <td class="min-w-[16rem]">
                                                {{ $l->description ?: '—' }}
                                                {{-- Panel cocokkan / abaikan di baris ini --}}
                                                <form x-show="open === 'm{{ $l->id }}'" x-cloak method="POST" :action="matchUrl" class="mt-2 flex flex-wrap items-center gap-2">
                                                    @csrf
                                                    <select name="bank_transaction_id" class="input h-9 min-w-[18rem] flex-1 py-1 text-xs" required>
                                                        <option value="" x-text="cands === null ? 'Memuat kandidat…' : (cands.length ? '— pilih transaksi Kas & Bank —' : 'Tidak ada kandidat dengan arah yang sama')"></option>
                                                        <template x-for="c in cands || []" :key="c.id"><option :value="c.id" x-text="c.label"></option></template>
                                                    </select>
                                                    <button class="btn-primary btn-sm">Cocokkan</button>
                                                    <button type="button" class="btn-ghost btn-sm" @click="open = null">Batal</button>
                                                </form>
                                                <form x-show="open === 'i{{ $l->id }}'" x-cloak method="POST" action="{{ route('bank-reconciliation.ignore', $l) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                                    @csrf
                                                    <input name="note" class="input h-9 min-w-[18rem] flex-1 py-1 text-xs" maxlength="300" required placeholder="Alasan, mis. biaya admin bank dicatat akhir bulan">
                                                    <button class="btn-secondary btn-sm">Abaikan</button>
                                                    <button type="button" class="btn-ghost btn-sm" @click="open = null">Batal</button>
                                                </form>
                                            </td>
                                            <td class="whitespace-nowrap text-right font-medium tabular-nums {{ $l->isCredit() ? '!text-emerald-600' : '!text-rose-600' }}">{{ $signed($l->amount) }}</td>
                                            <td class="whitespace-nowrap text-right">
                                                <div class="inline-flex items-center gap-1">
                                                    <a href="{{ route('bank-reconciliation.record', $l) }}" class="btn-secondary btn-sm" title="Catat di Kas & Bank">Catat</a>
                                                    <button type="button" class="btn-ghost btn-sm" :class="open === 'm{{ $l->id }}' && 'bg-surface-100 dark:bg-surface-800'"
                                                            @click="openMatch('m{{ $l->id }}', @js(route('bank-reconciliation.candidates', $l, false)), @js(route('bank-reconciliation.match', $l, false)))">Cocokkan</button>
                                                    <button type="button" class="btn-ghost btn-sm text-surface-500" :class="open === 'i{{ $l->id }}' && 'bg-surface-100 dark:bg-surface-800'" @click="open = open === 'i{{ $l->id }}' ? null : 'i{{ $l->id }}'">Abaikan</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="py-10 text-center text-surface-500"><x-icon name="check-circle" class="mx-auto mb-2 h-6 w-6 text-emerald-500" /> Semua mutasi bank pada periode ini sudah cocok atau diabaikan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Hanya di aplikasi --}}
                    <div x-show="tab === 'app'" x-cloak>
                        <p class="border-b border-surface-100 px-5 py-3 text-xs text-surface-500 dark:border-surface-800">Tercatat di Kas & Bank tetapi tidak ada di mutasi bank: kemungkinan salah rekening, salah nominal/tanggal (lebih dari 3 hari), tercatat dua kali, atau tidak pernah terjadi.</p>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Akun</th><th class="text-right">Nominal</th><th></th></tr></thead>
                                <tbody>
                                    @forelse($s['only_app'] as $t)
                                        <tr>
                                            <td class="whitespace-nowrap tabular-nums">{{ $t->transaction_date->format('d/m/Y') }}</td>
                                            <td class="min-w-[16rem]">{{ $t->description }} @if($t->reference_number)<span class="block font-mono text-[11px] text-surface-500">{{ $t->reference_number }}</span>@endif</td>
                                            <td class="text-surface-600 dark:text-surface-300">{{ $t->category ? $t->category->code . ' ' . $t->category->name : '—' }}</td>
                                            <td class="whitespace-nowrap text-right font-medium tabular-nums {{ $t->type === 'deposit' ? '!text-emerald-600' : '!text-rose-600' }}">{{ $signed($t->type === 'deposit' ? $t->amount : -$t->amount) }}</td>
                                            <td class="text-right">@canroute('bank-transactions.edit')<a href="{{ route('bank-transactions.edit', $t) }}" class="btn-ghost btn-sm">Periksa</a>@endcanroute</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-10 text-center text-surface-500"><x-icon name="check-circle" class="mx-auto mb-2 h-6 w-6 text-emerald-500" /> Semua transaksi aplikasi pada periode ini ada di mutasi bank.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Cocok & diabaikan --}}
                    @foreach(['matched' => $s['matched'], 'ignored' => $s['ignored']] as $key => $list)
                        <div x-show="tab === '{{ $key }}'" x-cloak class="overflow-x-auto">
                            <table class="table">
                                <thead><tr><th>Tanggal</th><th>Keterangan bank</th><th class="text-right">Mutasi</th><th>{{ $key === 'matched' ? 'Transaksi aplikasi' : 'Alasan' }}</th><th></th></tr></thead>
                                <tbody>
                                    @forelse($list as $l)
                                        <tr>
                                            <td class="whitespace-nowrap tabular-nums">{{ $l->line_date->format('d/m/Y') }}</td>
                                            <td class="min-w-[14rem]">{{ $l->description }}</td>
                                            <td class="whitespace-nowrap text-right tabular-nums {{ $l->isCredit() ? '!text-emerald-600' : '!text-rose-600' }}">{{ $signed($l->amount) }}</td>
                                            <td class="text-xs text-surface-600 dark:text-surface-300">
                                                @if($key === 'matched')
                                                    {{ $l->transaction?->transaction_date?->format('d/m/Y') }} · {{ \Illuminate\Support\Str::limit((string) $l->transaction?->description, 50) }}
                                                    <span class="badge-gray ml-1">{{ $l->match_type === 'manual' ? 'manual' : 'otomatis' }}</span>
                                                @else
                                                    {{ $l->note }}
                                                @endif
                                            </td>
                                            <td class="text-right"><form method="POST" action="{{ route('bank-reconciliation.unmatch', $l) }}">@csrf<button class="btn-ghost btn-sm">{{ $key === 'matched' ? 'Batalkan' : 'Pulihkan' }}</button></form></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-10 text-center text-surface-500">{{ $key === 'matched' ? 'Belum ada mutasi yang cocok pada periode ini.' : 'Tidak ada mutasi yang diabaikan.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    {{-- Riwayat import --}}
                    <div x-show="tab === 'imports'" x-cloak class="overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>File</th><th>Periode</th><th class="text-right">Baris baru</th><th>Oleh</th><th></th></tr></thead>
                            <tbody>
                                @foreach($imports as $im)
                                    <tr>
                                        <td class="font-medium">{{ $im->file_name }}</td>
                                        <td class="whitespace-nowrap tabular-nums">
                                            <a href="{{ route('bank-reconciliation.index', ['account' => $account->id, 'from' => $im->period_start?->toDateString(), 'to' => $im->period_end?->toDateString()]) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $im->period_start?->format('d/m/Y') }} – {{ $im->period_end?->format('d/m/Y') }}</a>
                                        </td>
                                        <td class="text-right tabular-nums">{{ $im->rows_new }} / {{ $im->rows_total }}</td>
                                        <td class="text-surface-600 dark:text-surface-300">{{ $im->creator?->name ?? '—' }} · {{ $im->created_at->diffForHumans() }}</td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('bank-reconciliation.import.destroy', $im) }}" onsubmit="return confirm('Hapus import ini beserta baris mutasinya? Transaksi Kas & Bank tidak ikut terhapus.')">
                                                @csrf @method('DELETE')<button class="btn-ghost btn-sm text-rose-600">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endunless
        @endif
    </div>

    @push('scripts')
    <script>
        function recon(tab, upload) {
            return {
                tab, upload, fileName: '', open: null, matchUrl: '', cands: null,
                openMatch(key, candUrl, matchUrl) {
                    if (this.open === key) { this.open = null; return; }
                    this.open = key; this.matchUrl = matchUrl; this.cands = null;
                    fetch(candUrl, { headers: { 'Accept': 'application/json' } }).then(r => r.json()).then(d => this.cands = d).catch(() => this.cands = []);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
