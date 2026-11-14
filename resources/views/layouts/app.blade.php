<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>{{ get_setting('company_name', config('app.name', 'Radius ISP')) }}</title>

        <!-- Dynamic Favicon -->
        @php 
            $appIcon = get_setting('app_icon'); 
            $companyLogo = get_setting('company_logo');
        @endphp
        @if($appIcon)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . $appIcon) }}">
        @elseif($companyLogo)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . $companyLogo) }}">
        @endif

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#4f46e5">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles')

        <!-- Theme Initialization Script (prevents FOUC) -->
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
        
        <style>
            /* Seragamkan judul di slot header halaman lama tanpa harus mengedit tiap file. */
            .page-header h1, .page-header h2 {
                font-size: 1.25rem; line-height: 1.75rem; font-weight: 600; letter-spacing: -0.015em;
                color: rgb(15 23 42);
            }
            .dark .page-header h1, .dark .page-header h2 { color: #fff; }
            .page-header p { font-size: 0.875rem; }
        </style>
    </head>
    @php
        $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
        $isPortal = request()->is('client*') ||
                    request()->is('login') ||
                    request()->is('portal*') ||
                    str_contains($currentRoute, 'customer.') ||
                    str_contains($currentRoute, 'portal.');
        $companyName = get_setting('company_name', config('app.name', 'Radius ISP'));
    @endphp
    <body class="font-sans antialiased bg-surface-50 dark:bg-surface-950 text-surface-900 dark:text-surface-100"
          x-data="{ sidebarOpen: false }">

        <div class="flex {{ $isPortal ? 'min-h-screen' : 'h-screen overflow-hidden' }}">
            {{-- Sidebar: off-canvas di mobile, statis di desktop (portal: hanya desktop) --}}
            @include('components.sidebar')

            {{-- Area konten --}}
            <div id="main-scroll" class="relative flex flex-col flex-1 min-w-0 {{ $isPortal ? '' : 'overflow-y-auto overflow-x-hidden' }}">
                @if(!$isPortal)
                    @include('components.navbar')
                @endif

                {{-- Header Portal Pelanggan (mobile-first) --}}
                @if($isPortal)
                    @php
                        $user = auth('customer')->user() ?: auth('web')->user();
                        $portalLogo = get_setting('app_icon') ?: get_setting('company_logo');
                        $portalInitials = collect(preg_split('/\s+/', trim($user->name ?? 'U')))->filter()->take(2)
                            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    @endphp
                    <header class="sticky top-0 z-20 h-16 flex items-center justify-between px-4 sm:px-6 bg-white/85 dark:bg-surface-900/85 backdrop-blur-md border-b border-surface-200 dark:border-surface-800">
                        <div class="flex items-center gap-3 min-w-0 lg:invisible">
                            @if($portalLogo)
                                <img src="{{ asset('storage/' . $portalLogo) }}" alt="" class="w-8 h-8 rounded-lg object-contain bg-white ring-1 ring-surface-200 dark:ring-surface-700">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</div>
                            @endif
                            <p class="text-sm font-semibold text-surface-900 dark:text-white truncate">{{ $companyName }}</p>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors" aria-label="Menu akun">
                                <span class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-semibold">{{ $portalInitials ?: 'U' }}</span>
                                <span class="hidden sm:block text-sm font-medium text-surface-700 dark:text-surface-200 max-w-[10rem] truncate">{{ $user->name ?? 'User' }}</span>
                                <x-icon name="chevron-down" class="hidden sm:block w-4 h-4 text-surface-400" />
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 mt-2 w-56 card shadow-elevated overflow-hidden z-50">
                                <div class="px-4 py-3 border-b border-surface-200 dark:border-surface-800">
                                    <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $user->name ?? 'User' }}</p>
                                    <p class="text-xs text-surface-500 dark:text-surface-400 truncate">{{ $user->customer_code ?? $user->email ?? '' }}</p>
                                </div>
                                <form method="POST" action="{{ str_starts_with($currentRoute, 'customer.') ? route('customer.logout') : route('logout') }}" class="py-1">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                                        <x-icon name="logout" class="w-4 h-4" /> Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </header>
                @endif

                {{-- Header halaman --}}
                @isset($header)
                    @if(!$isPortal)
                        <div class="page-header bg-white dark:bg-surface-900 border-b border-surface-200 dark:border-surface-800">
                            <div class="px-4 sm:px-6 lg:px-8 py-5">
                                {{ $header }}
                            </div>
                        </div>
                    @endif
                @endisset

                {{-- Konten halaman --}}
                <main class="flex-grow {{ $isPortal ? 'p-4 pb-32 sm:p-6 lg:pb-8' : 'p-4 sm:p-6 lg:p-8' }}">
                    {{ $slot }}
                </main>
                
                <!-- Footer Component -->
                @if(!$isPortal)
                    @include('components.footer')
                @endif
            </div>
        </div>
        
        @if($isPortal)
            @include('components.customer-bottom-nav')
        @endif

        @stack('scripts')
        
        <!-- PWA Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => {
                            console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        })
                        .catch(err => {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                });
            }
        </script>

        <!-- Floating Browser Permission Prompt (Meminta Izin Browser 1 Kali) -->
        <div id="notif-permission-banner" class="hidden fixed bottom-4 left-4 lg:left-[17rem] z-[10000] max-w-sm w-[calc(100vw-2rem)] card shadow-elevated p-4 pointer-events-auto {{ $isPortal ? '!bottom-24 lg:!bottom-4' : '' }}">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0">
                    <x-icon name="bell" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-surface-900 dark:text-white">Aktifkan notifikasi</p>
                    <p class="mt-0.5 text-xs text-surface-500 dark:text-surface-400 leading-snug">Dapatkan bunyi dan pemberitahuan saat ada tiket baru.</p>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" onclick="window.requestDesktopNotificationPermission()" class="btn-primary btn-sm">Izinkan</button>
                        <button type="button" onclick="window.dismissPermissionBanner()" class="btn-ghost btn-sm">Nanti saja</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTML5 Audio Element for Realtime Notification Chime -->
        <audio id="notif-audio" src="{{ asset('sounds/notification.wav') }}" preload="auto"></audio>

        <!-- Global Floating Toast Notification (Pojok Kanan Bawah, 100% Reliable & Non-clipping) -->
        <div id="global-toast-container" class="hidden fixed bottom-6 right-6 z-[9999] max-w-sm w-[calc(100vw-3rem)] sm:w-96 transition-all duration-300 transform opacity-0 translate-y-4 pointer-events-auto">
            <div class="bg-surface-900 dark:bg-surface-800 text-white p-4 rounded-xl shadow-elevated ring-1 ring-white/10 flex items-start gap-3">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-primary-500/20 text-primary-300 flex items-center justify-center">
                    <x-icon name="bell" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-surface-400 mb-0.5">Notifikasi baru</p>
                    <p id="global-toast-text" class="text-sm text-surface-100 leading-snug break-words"></p>
                </div>
                <button type="button" onclick="window.closeGlobalToast()" class="shrink-0 p-1 rounded-md text-surface-400 hover:text-white hover:bg-white/10 transition-colors" aria-label="Tutup">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        </div>

        <script>
            let _audioUnlocked = false;

            // Buka gembok audio autoplay secara instan pada interaksi pertama apapun di halaman
            window.unlockAudio = function() {
                if (_audioUnlocked) return;
                const audio = document.getElementById('notif-audio');
                if (audio) {
                    const origVol = audio.volume;
                    audio.volume = 0.001;
                    const p = audio.play();
                    if (p !== undefined) {
                        p.then(() => {
                            audio.pause();
                            audio.currentTime = 0;
                            audio.volume = origVol || 0.95;
                            _audioUnlocked = true;
                        }).catch(() => {
                            audio.volume = origVol || 0.95;
                        });
                    }
                }
            };

            ['click', 'keydown', 'touchstart'].forEach(evt => {
                document.addEventListener(evt, window.unlockAudio, { capture: true, once: true });
            });

            window.playNotificationChime = function() {
                try {
                    const audio = document.getElementById('notif-audio');
                    if (audio) {
                        audio.currentTime = 0;
                        audio.volume = 0.95;
                        const p = audio.play();
                        if (p !== undefined) {
                            p.catch(e => {
                                // Silent: jika audio halaman belum di-klik,
                                // notifikasi desktop Windows / Service Worker tetap berbunyi via sistem OS.
                            });
                        }
                    }
                } catch (e) {}
            };

            window.requestDesktopNotificationPermission = function() {
                // 1. Unlock audio & bunyikan chime
                window.unlockAudio();
                window.playNotificationChime();

                // 2. Minta permission native browser
                if ('Notification' in window) {
                    Notification.requestPermission().then(permission => {
                        console.log('[Notification] Permission result:', permission);
                        window.dismissPermissionBanner();
                        if (permission === 'granted') {
                            try {
                                new Notification('Radius Billing IDS', {
                                    body: 'Suara & notifikasi desktop aktif untuk tiket realtime!',
                                    icon: '{{ asset('favicon.png') }}'
                                });
                            } catch(e) {}
                        }
                    });
                } else {
                    window.dismissPermissionBanner();
                }
            };

            window.dismissPermissionBanner = function() {
                const banner = document.getElementById('notif-permission-banner');
                if (banner) banner.classList.add('hidden');
                sessionStorage.setItem('dismissed_notif_banner', 'true');
            };

            // Tampilkan banner jika browser belum memiliki izin (permission === 'default')
            document.addEventListener('DOMContentLoaded', () => {
                if ('Notification' in window && Notification.permission === 'default') {
                    if (!sessionStorage.getItem('dismissed_notif_banner')) {
                        setTimeout(() => {
                            const banner = document.getElementById('notif-permission-banner');
                            if (banner) banner.classList.remove('hidden');
                        }, 800);
                    }
                }
            });

            window.showGlobalToast = function(msg) {
                const el = document.getElementById('global-toast-container');
                const textEl = document.getElementById('global-toast-text');
                if (!el || !textEl) return;

                textEl.innerText = msg;
                el.classList.remove('hidden');

                // Trigger reflow for smooth animation
                void el.offsetWidth;

                el.classList.remove('opacity-0', 'translate-y-4');
                el.classList.add('opacity-100', 'translate-y-0');

                // Play the authentic chime sound
                window.playNotificationChime();

                clearTimeout(window._toastTimer);
                window._toastTimer = setTimeout(() => {
                    window.closeGlobalToast();
                }, 6000);
            };

            window.closeGlobalToast = function() {
                const el = document.getElementById('global-toast-container');
                if (!el) return;
                el.classList.remove('opacity-100', 'translate-y-0');
                el.classList.add('opacity-0', 'translate-y-4');
                clearTimeout(window._toastTimer);
                setTimeout(() => {
                    el.classList.add('hidden');
                }, 300);
            };
        </script>

        @stack('modals')
    </body>
</html>
