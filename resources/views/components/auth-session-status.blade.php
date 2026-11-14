@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-lg border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10 px-3.5 py-3 text-sm text-emerald-800 dark:text-emerald-300']) }} role="status">
        <x-icon name="check-circle" class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
        <span>{{ $status }}</span>
    </div>
@endif
