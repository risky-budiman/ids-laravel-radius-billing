{{-- Pagination sederhana (simplePaginate): Sebelumnya / Berikutnya dengan gaya aplikasi. --}}
@php
    $btn = 'inline-flex h-9 items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
    $idle = 'border-surface-200 bg-white text-surface-700 hover:bg-surface-50 hover:text-primary-700 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300 dark:hover:bg-surface-800 dark:hover:text-white';
    $off = 'border-surface-200 bg-surface-50 text-surface-300 cursor-not-allowed dark:border-surface-800 dark:bg-surface-900/50 dark:text-surface-600';
@endphp
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="{{ $btn }} {{ $off }}" aria-disabled="true">‹ Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} {{ $idle }}">‹ Sebelumnya</a>
        @endif
        <span class="text-sm text-surface-500 dark:text-surface-400">Halaman {{ $paginator->currentPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} {{ $idle }}">Berikutnya ›</a>
        @else
            <span class="{{ $btn }} {{ $off }}" aria-disabled="true">Berikutnya ›</span>
        @endif
    </nav>
@endif
