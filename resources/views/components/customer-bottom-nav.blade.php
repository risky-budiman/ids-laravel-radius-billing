@php
    $navItems = [
        ['Beranda', 'customer.dashboard', 'home', ['customer.dashboard']],
        ['Tagihan', 'customer.invoices', 'document', ['customer.invoices', 'portal.invoice']],
        ['Booster', 'customer.boosters', 'bolt', ['customer.boosters']],
        ['Bantuan', 'customer.tickets.index', 'support', ['customer.tickets.*']],
    ];
@endphp
<nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-white/95 dark:bg-surface-900/95 backdrop-blur border-t border-surface-200 dark:border-surface-800"
     style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Navigasi portal">
    <div class="grid grid-cols-4 h-16">
        @foreach($navItems as [$label, $route, $icon, $active])
            @php $isActive = request()->routeIs(...$active); @endphp
            <a href="{{ route($route) }}" class="flex flex-col items-center justify-center gap-1 {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-surface-500 dark:text-surface-400 hover:text-surface-800 dark:hover:text-surface-200' }}"
               @if($isActive) aria-current="page" @endif>
                <x-icon :name="$icon" class="w-6 h-6" :stroke="$isActive ? '2' : '1.75'" />
                <span class="text-[11px] {{ $isActive ? 'font-semibold' : 'font-medium' }}">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</nav>
