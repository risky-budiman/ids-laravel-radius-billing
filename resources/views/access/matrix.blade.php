@php
    $levels = [
        'full' => ['Penuh', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'],
        'view' => ['Lihat', 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300'],
        'partial' => ['Sebagian', 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'],
        'none' => ['—', 'text-surface-400'],
    ];
    $editableRoles = array_diff(array_keys($roles), [\App\Models\User::ROLE_ADMINISTRATOR]);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Hak Akses per Role</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Atur apa yang boleh dibuka & dilakukan setiap role. Menu, tombol dan pemeriksaan server langsung mengikuti.</p>
            </div>
            @if($overrideCount)
                <form method="POST" action="{{ route('access.matrix.reset') }}" onsubmit="return confirm('Kembalikan SEMUA role ke hak akses bawaan? ({{ $overrideCount }} pengaturan dihapus)')">@csrf
                    <button class="btn-secondary"><x-icon name="refresh" class="w-4 h-4" /> Kembalikan semua ke bawaan</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4" x-data="accessMatrix()">
        @if(session('success'))
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">
                <x-icon name="check-circle" class="w-5 h-5 shrink-0" /> {{ session('success') }}
            </div>
        @endif

        <div class="card p-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-xs">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input type="search" x-model="q" class="input pl-9" placeholder="Cari fitur…" aria-label="Cari fitur">
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-surface-600 dark:text-surface-300">
                @foreach($levels as $key => [$text, $cls])
                    <span class="inline-flex items-center gap-1.5"><span class="rounded px-1.5 py-0.5 font-medium {{ $cls }}">{{ $text }}</span>
                        {{ ['full' => 'semua halaman & aksi', 'view' => 'hanya melihat', 'partial' => 'sebagian', 'none' => 'tidak ada akses'][$key] }}</span>
                @endforeach
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-primary-500"></span> diubah dari bawaan</span>
            </div>
        </div>

        <p x-show="saving" x-cloak class="text-sm text-surface-500">Menyimpan…</p>
        <p x-show="error" x-cloak x-text="error" class="rounded-lg bg-rose-50 px-4 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"></p>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-900/60 text-left text-xs font-medium uppercase tracking-wide text-surface-500">
                        <tr>
                            <th class="px-4 py-3 sticky left-0 bg-surface-50 dark:bg-surface-900">Fitur</th>
                            @foreach($roles as $role => $label)
                                <th class="px-3 py-3 text-center whitespace-nowrap">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    @foreach($features as $key => $f)
                        <tbody x-data="{ open: false }" x-show="!q || @js(mb_strtolower($f['label'] . ' ' . $key)).includes(q.toLowerCase())" class="border-t border-surface-100 dark:border-surface-800">
                            <tr class="hover:bg-surface-50/60 dark:hover:bg-surface-800/30">
                                <td class="px-4 py-2.5 font-medium text-surface-900 dark:text-white sticky left-0 bg-white dark:bg-surface-900">
                                    <button type="button" class="inline-flex items-center gap-1.5 whitespace-nowrap" @click="open = !open" :aria-expanded="open">
                                        <x-icon name="chevron-right" class="w-3.5 h-3.5 text-surface-400 transition-transform" ::class="open && 'rotate-90'" />
                                        {{ $f['label'] }}
                                        <span class="text-xs font-normal text-surface-400">{{ count($f['routes']) }}</span>
                                        @if($f['locked'])<span class="text-xs font-normal text-surface-400" title="Khusus administrator, tidak bisa diubah">🔒</span>@endif
                                    </button>
                                </td>
                                @foreach($roles as $role => $label)
                                    @php [$text, $cls] = $levels[$f['level'][$role]]; @endphp
                                    <td class="px-2 py-2 text-center">
                                        @if(in_array($role, $editableRoles, true) && ! $f['locked'])
                                            <span class="relative inline-flex items-center">
                                                <select class="appearance-none bg-none cursor-pointer rounded py-0.5 pl-1.5 pr-4 text-xs font-medium border-0 focus:ring-2 focus:ring-primary-500 {{ $cls }}" style="background-image:none"
                                                        aria-label="Akses {{ $label }} untuk {{ $f['label'] }}"
                                                        @change="saveFeature(@js($role), @js($key), $event.target.value)">
                                                    <option value="" selected disabled hidden>{{ $text }}</option>
                                                    <option value="full">Penuh</option>
                                                    <option value="view">Lihat saja</option>
                                                    <option value="none">Tidak ada</option>
                                                    @if($f['changed'][$role])<option value="default">Kembalikan ke bawaan</option>@endif
                                                </select>
                                                <span class="pointer-events-none absolute right-1 text-[8px] opacity-50">▼</span>
                                                @if($f['changed'][$role])<span class="absolute -right-2 -top-1 h-2 w-2 rounded-full bg-primary-500" title="Diubah dari bawaan"></span>@endif
                                            </span>
                                        @else
                                            <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $text }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @foreach($f['routes'] as $r)
                                <tr x-show="open" x-cloak class="bg-surface-50/60 dark:bg-surface-900/40 text-xs">
                                    <td class="py-1.5 pl-10 pr-4 sticky left-0 bg-surface-50 dark:bg-surface-900">
                                        <span class="text-surface-700 dark:text-surface-200">{{ ucfirst($r['action']) }}</span>
                                        <span class="ml-1 font-mono text-[11px] text-surface-400" title="{{ $r['uri'] }}">{{ $r['read'] ? 'GET' : $r['method'] }} {{ $r['name'] }}</span>
                                    </td>
                                    @foreach($roles as $role => $label)
                                        <td class="px-2 py-1.5 text-center">
                                            @if(in_array($role, $editableRoles, true) && ! $r['locked'])
                                                <select style="background-image:none" class="appearance-none cursor-pointer rounded border-0 bg-transparent px-1 py-0 text-xs focus:ring-2 focus:ring-primary-500 {{ $r['allowed'][$role] ? 'text-emerald-700 dark:text-emerald-400' : 'text-surface-400' }} {{ $r['override'][$role] !== null ? 'font-semibold underline decoration-primary-500 decoration-2' : '' }}"
                                                        aria-label="{{ $label }}: {{ $r['name'] }}"
                                                        @change="saveRoute(@js($role), @js($r['name']), $event.target.value)">
                                                    <option value="" selected disabled hidden>{{ $r['allowed'][$role] ? '✓ Boleh' : '· Tidak' }}</option>
                                                    <option value="allow">✓ Boleh</option>
                                                    <option value="deny">· Tidak</option>
                                                    @if($r['override'][$role] !== null)<option value="default">Bawaan ({{ $r['default'][$role] ? 'boleh' : 'tidak' }})</option>@endif
                                                </select>
                                            @elseif($r['allowed'][$role])
                                                <x-icon name="check" class="inline w-4 h-4 text-emerald-600" /><span class="sr-only">boleh</span>
                                            @else
                                                <span class="text-surface-300 dark:text-surface-600" aria-label="tidak">·</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="text-xs text-surface-500 dark:text-surface-400 space-y-1">
            <p><strong>Cara pakai:</strong> pilih level di sel fitur (Penuh / Lihat saja / Tidak ada), atau buka fitur (klik namanya) untuk mengatur per aksi. Perubahan langsung berlaku setelah halaman dimuat ulang oleh pengguna terkait.</p>
            <p>Role <strong>Administrator</strong> selalu penuh. Fitur bertanda 🔒 (Kelola Staf, Hak Akses, Backup, Pembaruan sistem) selalu khusus administrator.</p>
            <p>Staf bertanda <strong>"juga sales"</strong> mendapat akses role Sales di atas akses role-nya. Beberapa aksi punya pemeriksaan tambahan di dalam halaman (mis. kolom pelanggan yang hanya bisa diubah administrator); aplikasi mobile (API) memakai aturan bawaan.</p>
        </div>
    </div>

    @push('scripts')
    <script>
        function accessMatrix() {
            return {
                q: '', saving: false, error: '',
                send(body) {
                    this.saving = true; this.error = '';
                    fetch(@js(route('access.matrix.update', [], false)), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(body),
                    })
                        .then(async r => { const d = await r.json().catch(() => ({})); if (!r.ok) throw new Error(d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'HTTP ' + r.status)); return d; })
                        .then(() => window.location.reload())
                        .catch(e => { this.error = 'Gagal menyimpan: ' + e.message; this.saving = false; });
                },
                saveFeature(role, feature, level) {
                    if (level === 'full' && !confirm('Beri akses PENUH (semua halaman & aksi) untuk fitur ini?')) { window.location.reload(); return; }
                    this.send({ role, feature, level });
                },
                saveRoute(role, route, value) { this.send({ role, route, value }); },
            };
        }
    </script>
    @endpush
</x-app-layout>
