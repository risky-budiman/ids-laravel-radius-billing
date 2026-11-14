@props([
    'label',
    'value',
    'icon' => 'chart-bar',
    'tone' => 'primary',   // primary | success | warning | danger | info | gray
    'hint' => null,
    'href' => null,
])
@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'danger'  => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        'info'    => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
        'gray'    => 'bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-300',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card p-5 block' . ($href ? ' transition-colors hover:border-surface-300 dark:hover:border-surface-700' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-surface-500 dark:text-surface-400">{{ $label }}</p>
        <span class="flex items-center justify-center w-9 h-9 rounded-lg shrink-0 {{ $tones[$tone] ?? $tones['primary'] }}">
            <x-icon :name="$icon" class="w-[18px] h-[18px]" />
        </span>
    </div>
    <p class="mt-1 text-2xl font-semibold tracking-tight text-surface-900 dark:text-white tabular-nums">{{ $value }}</p>
    @if($hint || isset($footer))
        <div class="mt-2 text-xs text-surface-500 dark:text-surface-400">
            {{ $footer ?? $hint }}
        </div>
    @endif
</{{ $tag }}>
