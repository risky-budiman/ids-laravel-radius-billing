@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-surface-700 dark:text-surface-300']) }}>
    {{ $value ?? $slot }}
</label>
