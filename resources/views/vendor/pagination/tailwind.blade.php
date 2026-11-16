{{--
    Pagination aplikasi (menggantikan bawaan Laravel). Dipakai otomatis oleh semua ->links().
    Opsional: ->links(null, ['label' => 'pelanggan']) untuk satuan pada teks ringkasan.
    Nomor halaman diringkas: 1 … (sekitar halaman aktif) … terakhir; di ponsel hanya "Hal X / Y".
--}}
@php
    $label = $label ?? 'data';
    $btn = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
    $idle = 'border-surface-200 bg-white text-surface-700 hover:bg-surface-50 hover:text-primary-700 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300 dark:hover:bg-surface-800 dark:hover:text-white';
    $off = 'border-surface-200 bg-surface-50 text-surface-300 cursor-not-allowed dark:border-surface-800 dark:bg-surface-900/50 dark:text-surface-600';
    $active = 'border-primary-600 bg-primary-600 text-white shadow-sm';
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last, $current - 1, $current, $current + 1])
            ->merge($current <= 3 ? [2, 3, 4] : [])->merge($current >= $last - 2 ? [$last - 3, $last - 2, $last - 1] : [])
            ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
    @endphp
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col-reverse items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-surface-500 dark:text-surface-400">
            @if ($paginator->firstItem())
                Menampilkan
                <span class="font-semibold text-surface-900 dark:text-white">{{ $fmt($paginator->firstItem()) }}–{{ $fmt($paginator->lastItem()) }}</span>
                dari <span class="font-semibold text-surface-900 dark:text-white">{{ $fmt($paginator->total()) }}</span> {{ $label }}
            @else
                {{-- Nomor halaman di luar jangkauan (mis. setelah filter) --}}
                Halaman {{ $current }} kosong · total <span class="font-semibold text-surface-900 dark:text-white">{{ $fmt($paginator->total()) }}</span> {{ $label }}
                · <a href="{{ $paginator->url(1) }}" class="font-medium text-primary-600 hover:underline">ke halaman 1</a>
            @endif
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="{{ $btn }} {{ $off }}" aria-disabled="true" aria-label="Sebelumnya">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.7 5.3a1 1 0 0 1 0 1.4L9.42 10l3.3 3.3a1 1 0 0 1-1.42 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.42 0Z" clip-rule="evenodd"/></svg>
                    <span class="ml-1 hidden md:inline">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} {{ $idle }}" aria-label="Sebelumnya">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.7 5.3a1 1 0 0 1 0 1.4L9.42 10l3.3 3.3a1 1 0 0 1-1.42 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.42 0Z" clip-rule="evenodd"/></svg>
                    <span class="ml-1 hidden md:inline">Sebelumnya</span>
                </a>
            @endif

            {{-- Ponsel: ringkas --}}
            <span class="px-3 text-sm text-surface-600 dark:text-surface-300 sm:hidden">Hal {{ $current }} / {{ $last }}</span>

            {{-- Layar lebar: nomor halaman --}}
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($pages as $i => $page)
                    @if ($i > 0 && $page - $pages[$i - 1] > 1)
                        <span class="inline-flex h-9 min-w-6 items-center justify-center text-sm text-surface-400" aria-hidden="true">…</span>
                    @endif
                    @if ($page === $current)
                        <span class="{{ $btn }} {{ $active }}" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="{{ $btn }} {{ $idle }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} {{ $idle }}" aria-label="Berikutnya">
                    <span class="mr-1 hidden md:inline">Berikutnya</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.3 14.7a1 1 0 0 1 0-1.4L10.58 10 7.3 6.7a1 1 0 0 1 1.42-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.42 0Z" clip-rule="evenodd"/></svg>
                </a>
            @else
                <span class="{{ $btn }} {{ $off }}" aria-disabled="true" aria-label="Berikutnya">
                    <span class="mr-1 hidden md:inline">Berikutnya</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.3 14.7a1 1 0 0 1 0-1.4L10.58 10 7.3 6.7a1 1 0 0 1 1.42-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.42 0Z" clip-rule="evenodd"/></svg>
                </span>
            @endif
        </div>
    </nav>
@elseif ($paginator->total() > 0)
    {{-- Satu halaman: cukup ringkasan agar wadah tidak kosong --}}
    <p class="text-sm text-surface-500 dark:text-surface-400">
        Menampilkan <span class="font-semibold text-surface-900 dark:text-white">{{ $fmt($paginator->total()) }}</span> {{ $label }}
    </p>
@endif
