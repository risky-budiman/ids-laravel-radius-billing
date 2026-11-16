@php
    $levels = [
        'full' => ['Penuh', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300'],
        'view' => ['Lihat', 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300'],
        'partial' => ['Sebagian', 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'],
        'none' => ['—', 'text-surface-400'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Hak Akses per Role</h2>
            <p class="text-sm text-surface-500 dark:text-surface-400">Dibaca langsung dari aturan yang ditegakkan aplikasi (pembatasan role pada setiap halaman & aksi). Menu dan tombol mengikuti aturan yang sama.</p>
        </div>
    </x-slot>

    <div class="space-y-4" x-data="{ q: '' }">
        <div class="card p-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-xs">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                <input type="search" x-model="q" class="input pl-9" placeholder="Cari fitur…" aria-label="Cari fitur">
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-surface-600 dark:text-surface-300">
                @foreach($levels as $key => [$text, $cls])
                    <span class="inline-flex items-center gap-1.5"><span class="rounded px-1.5 py-0.5 font-medium {{ $cls }}">{{ $text }}</span>
                        {{ ['full' => 'semua halaman & aksi', 'view' => 'hanya melihat', 'partial' => 'sebagian (klik fitur untuk rincian)', 'none' => 'tidak ada akses'][$key] }}</span>
                @endforeach
            </div>
        </div>

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
                            <tr class="cursor-pointer hover:bg-surface-50 dark:hover:bg-surface-800/40" @click="open = !open">
                                <td class="px-4 py-2.5 font-medium text-surface-900 dark:text-white sticky left-0 bg-white dark:bg-surface-900">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-icon name="chevron-right" class="w-3.5 h-3.5 text-surface-400 transition-transform" ::class="open && 'rotate-90'" />
                                        {{ $f['label'] }}
                                    </span>
                                    <span class="ml-1 text-xs font-normal text-surface-400">{{ count($f['routes']) }}</span>
                                </td>
                                @foreach($roles as $role => $label)
                                    @php [$text, $cls] = $levels[$f['level'][$role]]; @endphp
                                    <td class="px-3 py-2.5 text-center"><span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $text }}</span></td>
                                @endforeach
                            </tr>
                            @foreach($f['routes'] as $r)
                                <tr x-show="open" x-cloak class="bg-surface-50/60 dark:bg-surface-900/40 text-xs">
                                    <td class="py-1.5 pl-10 pr-4 sticky left-0 bg-surface-50 dark:bg-surface-900">
                                        <span class="text-surface-700 dark:text-surface-200">{{ ucfirst($r['action']) }}</span>
                                        <span class="ml-1 font-mono text-[11px] text-surface-400" title="{{ $r['uri'] }}">{{ $r['read'] ? 'GET' : $r['method'] }} {{ $r['name'] }}</span>
                                    </td>
                                    @foreach($roles as $role => $label)
                                        <td class="px-3 py-1.5 text-center">
                                            @if($r['allowed'][$role])
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
            <p>Staf dengan penanda <strong>"juga sales"</strong> mendapat tambahan akses sales di atas akses role-nya.</p>
            <p>Beberapa aksi punya pemeriksaan tambahan di dalam halaman (mis. kolom form pelanggan yang hanya bisa diubah administrator, atau hapus OLT yang masih dipakai pelanggan); tabel ini menunjukkan siapa yang boleh membuka halaman/aksinya.</p>
            <p>Untuk mengubah akses role, hubungi pengembang: aturan ini sengaja tidak bisa diubah dari layar agar tidak ada celah akses tidak sengaja.</p>
        </div>
    </div>
</x-app-layout>
