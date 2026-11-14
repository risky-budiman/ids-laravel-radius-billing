@php
    $isPortal = request()->is('client*') || request()->routeIs('customer.*');
    $sidebarUser = $isPortal ? (auth('customer')->user() ?: auth('web')->user()) : auth('web')->user();
    $menu = $isPortal ? \App\View\SidebarMenu::customer() : \App\View\SidebarMenu::admin(auth('web')->user());
    $logo = get_setting('app_icon') ?: get_setting('company_logo');
    $companyName = get_setting('company_name', 'Radius ISP');
    $roleLabels = [
        'administrator' => 'Administrator', 'admin' => 'Admin', 'teknisi' => 'Teknisi',
        'kasir' => 'Kasir', 'sales' => 'Sales', 'mitra' => 'Mitra', 'customer' => 'Pelanggan',
    ];
    $roleKey = $sidebarUser->role ?? 'customer';
    $roleLabel = $roleLabels[$roleKey] ?? ucfirst($roleKey);
    $initials = collect(preg_split('/\s+/', trim($sidebarUser->name ?? 'U')))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

{{-- Backdrop (mobile) --}}
<div x-show="sidebarOpen" x-cloak
     x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-30 bg-surface-950/40 backdrop-blur-sm lg:hidden"
     @click="sidebarOpen = false"></div>

<aside class="{{ $isPortal ? 'hidden lg:flex' : 'flex' }} fixed inset-y-0 left-0 z-40 w-64 flex-col h-screen shrink-0
              bg-white dark:bg-surface-900 border-r border-surface-200 dark:border-surface-800
              -translate-x-full transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:translate-x-0"
       :class="sidebarOpen ? '!translate-x-0' : ''"
       @keydown.escape.window="sidebarOpen = false"
       aria-label="Navigasi utama">

    {{-- Brand --}}
    <div class="flex items-center justify-between h-16 px-4 shrink-0 border-b border-surface-200 dark:border-surface-800">
        <a href="{{ $isPortal ? route('customer.dashboard') : route('dashboard') }}" class="flex items-center gap-3 min-w-0">
            @if($logo)
                <img src="{{ asset('storage/' . $logo) }}" alt="" class="w-8 h-8 rounded-lg object-contain bg-white ring-1 ring-surface-200 dark:ring-surface-700">
            @else
                <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0 leading-tight">
                <p class="text-sm font-semibold text-surface-900 dark:text-white truncate">{{ $companyName }}</p>
                <p class="text-[11px] text-surface-500 dark:text-surface-400">{{ $isPortal ? 'Portal Pelanggan' : 'ISP Management' }}</p>
            </div>
        </a>
        <button type="button" class="btn-ghost btn-icon lg:hidden" @click="sidebarOpen = false" aria-label="Tutup menu">
            <x-icon name="x" class="w-5 h-5" />
        </button>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 pb-4">
        @foreach($menu as $section)
            @if($section['label'])
                <p class="nav-section">{{ $section['label'] }}</p>
            @else
                <div class="pt-3"></div>
            @endif
            <ul class="space-y-0.5">
                @foreach($section['items'] as $item)
                    @if($item['type'] === 'group')
                        <li x-data="{ open: {{ $item['isActive'] ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open" class="nav-item {{ $item['isActive'] ? '!text-surface-900 dark:!text-white' : '' }}" :aria-expanded="open.toString()">
                                <x-icon :name="$item['icon']" class="nav-icon {{ $item['isActive'] ? '!text-primary-600 dark:!text-primary-400' : '' }}" />
                                <span class="flex-1 text-left truncate">{{ $item['label'] }}</span>
                                <x-icon name="chevron-down" class="w-4 h-4 text-surface-400 transition-transform duration-200" ::class="open ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open" @if(!$item['isActive']) x-cloak @endif
                                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-0.5 mb-1 ml-[1.15rem] pl-3 border-l border-surface-200 dark:border-surface-800 space-y-0.5">
                                @foreach($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" class="nav-subitem {{ $child['isActive'] ? 'nav-subitem-active' : '' }}"
                                       @if($child['isActive']) aria-current="page" @endif>
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @else
                        <li>
                            <a href="{{ $item['url'] }}" class="nav-item {{ $item['isActive'] ? 'nav-item-active' : '' }}"
                               @if($item['isActive']) aria-current="page" @endif>
                                <x-icon :name="$item['icon'] ?? 'cube'" class="nav-icon" />
                                <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                @if($item['badge'])
                                    <span class="badge-primary !text-[10px] !px-1.5 !py-0">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endforeach
    </nav>

    {{-- Pengguna aktif --}}
    @if($sidebarUser)
        <div class="shrink-0 p-3 border-t border-surface-200 dark:border-surface-800">
            <div class="flex items-center gap-3 px-2 py-1.5">
                <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 flex items-center justify-center text-xs font-semibold shrink-0">
                    {{ $initials ?: 'U' }}
                </div>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $sidebarUser->name }}</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400 truncate">{{ $roleLabel }}</p>
                </div>
            </div>
        </div>
    @endif
</aside>
