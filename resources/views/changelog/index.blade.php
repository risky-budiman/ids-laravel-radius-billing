@php
    $typeBadge = [
        'feature' => ['Fitur baru', 'badge-info'],
        'fix' => ['Perbaikan', 'badge-warning'],
        'security' => ['Keamanan', 'badge-danger'],
        'improvement' => ['Peningkatan', 'badge-gray'],
    ];
    $sectionTone = [
        'Fitur baru' => 'text-primary-600 dark:text-primary-400',
        'Perbaikan' => 'text-amber-700 dark:text-amber-400',
        'Keamanan' => 'text-rose-700 dark:text-rose-400',
        'Peningkatan' => 'text-surface-500 dark:text-surface-400',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>Riwayat Pembaruan</h2>
                <p class="page-subtitle">Versi terpasang <span class="font-mono font-semibold">{{ $currentVersion }}</span> · perubahan per versi, terbaru di atas.</p>
            </div>
            @canroute('changelog.store')
                <button type="button" x-data @click="$dispatch('open-modal', 'add-changelog')" class="btn-secondary btn-sm">Tambah catatan manual</button>
            @endcanroute
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-4">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        @forelse($changelogs as $log)
            @php
                [$typeLabel, $typeClass] = $typeBadge[$log->type] ?? $typeBadge['improvement'];
                $isCurrent = ltrim($log->version, 'v') === ltrim($currentVersion, 'v');
                // Baris "[Bagian] teks" (hasil sinkron) dikelompokkan; baris tanpa label (catatan manual) apa adanya
                $groups = [];
                foreach (preg_split('/\R/', (string) $log->description) as $line) {
                    $line = trim(ltrim(trim($line), '-+•* '));
                    if ($line === '') continue;
                    if (preg_match('/^\[([^\]]+)\]\s*(.+)$/', $line, $m)) {
                        $groups[$m[1]][] = $m[2];
                    } else {
                        $groups[''][] = $line;
                    }
                }
            @endphp
            <article class="card {{ $isCurrent ? 'ring-2 ring-primary-500/40' : '' }}">
                <div class="card-body">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-mono text-base font-semibold text-surface-900 dark:text-white">{{ $log->version }}</h3>
                        <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                        @if($isCurrent)<span class="badge-success">Versi terpasang</span>@endif
                        <span class="ml-auto text-xs text-surface-500 dark:text-surface-400">
                            {{ $log->release_date ? \Carbon\Carbon::parse($log->release_date)->locale('id')->translatedFormat('d F Y') : '' }}
                        </span>
                        @if(auth()->user()->isAdministrator())
                            <form action="{{ route('changelog.destroy', $log) }}" method="POST" onsubmit="return confirm('Hapus catatan versi {{ $log->version }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-surface-400 hover:text-rose-600" title="Hapus catatan">Hapus</button>
                            </form>
                        @endif
                    </div>
                    {{-- Judul otomatis ("2 fitur baru, 1 perbaikan") sudah tergambar dari badge & daftar --}}
                    @if($log->title && ! preg_match('/^\d+ /', $log->title) && ! str_starts_with($log->title, 'Release '))
                        <p class="mt-1 text-sm font-medium text-surface-800 dark:text-surface-200">{{ $log->title }}</p>
                    @endif

                    <div class="mt-2 space-y-3 text-sm">
                        @foreach($groups as $section => $items)
                            <div>
                                @if($section !== '' && count($groups) > 1)
                                    <p class="text-xs font-semibold uppercase tracking-wide {{ $sectionTone[$section] ?? 'text-surface-500' }}">{{ $section }}</p>
                                @endif
                                <ul class="mt-1 list-disc space-y-0.5 pl-5 text-surface-700 dark:text-surface-300">
                                    @foreach($items as $item)<li>{{ $item }}</li>@endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                </div>
            </article>
        @empty
            <div class="card"><div class="card-body text-center text-sm text-surface-500">Belum ada riwayat pembaruan.</div></div>
        @endforelse

        <div>{{ $changelogs->links() }}</div>
    </div>

    @canroute('changelog.store')
    <x-modal name="add-changelog" focusable>
        <form method="post" action="{{ route('changelog.store') }}" class="p-6 space-y-4">
            @csrf
            <h2 class="text-lg font-semibold text-surface-900 dark:text-white">Tambah catatan manual</h2>
            <p class="text-xs text-surface-500">Catatan otomatis disusun dari riwayat commit. Gunakan ini hanya untuk versi di luar riwayat git.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="version" class="label">Versi</label>
                    <input id="version" name="version" class="input font-mono" placeholder="v1.0.1" required>
                </div>
                <div>
                    <label for="release_date" class="label">Tanggal rilis</label>
                    <input id="release_date" name="release_date" type="date" class="input" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div>
                <label for="title" class="label">Judul</label>
                <input id="title" name="title" class="input" placeholder="Ringkasan rilis" required>
            </div>
            <div>
                <label for="type" class="label">Kategori</label>
                <select id="type" name="type" class="input">
                    <option value="feature">Fitur baru</option>
                    <option value="fix">Perbaikan</option>
                    <option value="improvement">Peningkatan</option>
                    <option value="security">Keamanan</option>
                </select>
            </div>
            <div>
                <label for="description" class="label">Rincian (satu per baris)</label>
                <textarea id="description" name="description" rows="5" class="input !h-auto" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close')" class="btn-ghost">Batal</button>
                <button class="btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>
    @endcanroute
</x-app-layout>
