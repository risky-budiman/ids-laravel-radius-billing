<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

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
            /* Premium utilities */
            .glass {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.4);
            }
            .dark .glass {
                background: rgba(17, 24, 39, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            body { font-family: 'Inter', sans-serif; }
            
            /* Custom Scrollbar */
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.5); border-radius: 20px; }
            .dark ::-webkit-scrollbar-thumb { background-color: rgba(75, 85, 99, 0.5); }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-[#F8FAFC] dark:bg-gray-950 transition-colors duration-300 relative"
          x-data="{ 
            sidebarOpen: false, 
            isPortal: {{ (request()->is('client*') || request()->is('login') || request()->is('portal*')) ? 'true' : 'false' }}
          }"
          x-init="if(isPortal) sidebarOpen = false">
        <div class="absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-50 pointer-events-none"></div>
        
        @php 
            $currentRoute = request()->route() ? request()->route()->getName() : '';
            $isPortal = request()->is('client*') || 
                        request()->is('login') || 
                        request()->is('portal*') || 
                        str_contains($currentRoute, 'customer.') ||
                        str_contains($currentRoute, 'portal.');
        @endphp

        <div class="flex {{ $isPortal ? 'min-h-screen' : 'h-screen overflow-hidden' }}">
            <!-- Sidebar Component -->
            @if(!$isPortal)
                <!-- Default Admin Sidebar, hidden on mobile by default -->
                <div class="hidden lg:block">
                    @include('components.sidebar')
                </div>
                <!-- Mobile Admin Sidebar Backdrop -->
                <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>
                <!-- Mobile Admin Sidebar -->
                <div x-show="sidebarOpen" class="fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 lg:hidden">
                    @include('components.sidebar')
                </div>
            @else
                <!-- Customer Sidebar: Hidden entirely on mobile, visible on desktop -->
                <div class="hidden lg:block">
                    @include('components.sidebar')
                </div>
            @endif

            <!-- Main Content Area -->
            <div class="relative flex flex-col flex-1 {{ $isPortal ? '' : 'overflow-y-auto overflow-x-hidden' }}">
                <!-- Navbar Component (Admin Only) -->
                @if(!$isPortal)
                    @include('components.navbar')
                @endif

                <!-- Portal Header (Mobile Native) -->
                @if($isPortal)
                    <header class="flex items-center justify-between px-6 py-5 z-20 sticky top-0 glass border-b border-white/20 dark:border-white/5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-violet-700 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <h1 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Radius<span class="text-indigo-600">.</span></h1>
                        </div>
                        
                        <div class="flex items-center gap-3" x-data="{ open: false }">
                            <!-- Notification Trigger -->
                            <button class="w-10 h-10 rounded-xl glass flex items-center justify-center text-gray-500 dark:text-gray-400 active:scale-90 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            </button>
                            
                            <!-- Profile Dropdown -->
                            <div class="relative">
                                @php $user = auth('customer')->user() ?: auth('web')->user(); @endphp
                                <button @click="open = !open" class="focus:outline-none active:scale-95 transition-transform">
                                    <img class="w-10 h-10 rounded-xl border-2 border-indigo-500/20 shadow-md" src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&color=4F46E5&background=EEF2FF" alt="Avatar">
                                </button>
                                
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     class="absolute right-0 mt-3 w-48 glass rounded-2xl shadow-2xl border border-white/20 z-50 py-2 overflow-hidden">
                                    <div class="px-4 py-2 border-b border-white/10 mb-1">
                                        <p class="text-xs font-black text-gray-900 dark:text-white truncate">{{ $user->name ?? 'User' }}</p>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $user->email ?? $user->customer_code ?? '' }}</p>
                                    </div>
                                    
                                    <form method="POST" action="{{ str_starts_with(request()->route()->getName() ?? '', 'customer.') ? route('customer.logout') : route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 dark:text-rose-400 font-black hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </header>
                @endif

                <!-- Page Header (Standard) -->
                @isset($header)
                    @if(!$isPortal)
                        <header class="glass mx-6 mt-6 rounded-2xl shadow-sm z-10">
                            <div class="py-5 px-6">
                                {{ $header }}
                            </div>
                        </header>
                    @endif
                @endisset

                <!-- Page Content -->
                <main class="flex-grow {{ $isPortal ? 'p-5 pb-40 lg:p-6 lg:pb-6' : 'p-6' }}">
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
        <div id="notif-permission-banner" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-[10000] max-w-md w-[calc(100vw-2rem)] bg-white dark:bg-gray-900 border border-indigo-200 dark:border-indigo-800/80 rounded-2xl shadow-2xl p-4 transition-all duration-300 pointer-events-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Izin Suara & Notifikasi</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-snug">Aktifkan agar bel berdering & notif muncul saat ada tiket baru.</p>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <button type="button" onclick="window.requestDesktopNotificationPermission()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 active:scale-95 transition-all">
                        Izinkan
                    </button>
                    <button type="button" onclick="window.dismissPermissionBanner()" title="Tutup" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Floating Audio Activator Pill (Solusi Chrome Autoplay: 1x Klik Membuka Suara Permanen) -->
        <div id="audio-activator-pill" onclick="window.activateAudioDirectly()" class="fixed bottom-6 left-6 z-[9998] cursor-pointer bg-slate-900/90 hover:bg-slate-900 text-white px-4 py-2.5 rounded-2xl shadow-2xl flex items-center gap-2.5 border border-indigo-500/40 backdrop-blur-md transition-all duration-300 animate-pulse hover:scale-105 active:scale-95">
            <span class="text-base">🔊</span>
            <span id="audio-activator-text" class="text-xs font-bold text-slate-100">Klik di sini untuk aktifkan audio tiket</span>
        </div>

        <!-- HTML5 Audio Element for Realtime Notification Chime -->
        <audio id="notif-audio" src="{{ asset('sounds/notification.wav') }}" preload="auto"></audio>

        <!-- Global Floating Toast Notification (Pojok Kanan Bawah, 100% Reliable & Non-clipping) -->
        <div id="global-toast-container" class="hidden fixed bottom-6 right-6 z-[9999] max-w-sm w-[calc(100vw-3rem)] sm:w-96 transition-all duration-300 transform opacity-0 translate-y-4 pointer-events-auto">
            <div class="bg-slate-900/95 dark:bg-gray-800/95 text-white p-4 rounded-2xl shadow-2xl border border-slate-700/80 backdrop-blur-xl flex items-start space-x-3.5">
                <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <div class="flex-1 min-w-0 pr-1">
                    <p class="text-[11px] font-bold text-indigo-400 uppercase tracking-wider mb-0.5">Notifikasi Realtime</p>
                    <p id="global-toast-text" class="text-sm font-medium text-slate-100 leading-snug break-words"></p>
                </div>
                <button type="button" onclick="window.closeGlobalToast()" class="flex-shrink-0 text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <script>
            let _audioUnlocked = false;

            // Buka blokir audio autoplay secara instan pada interaksi pertama apapun di halaman
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
                            console.log('🔊 [Audio] Autoplay browser berhasil di-unlock.');
                        }).catch(e => {
                            audio.volume = origVol || 0.95;
                        });
                    }
                }
            };

            window.activateAudioDirectly = function() {
                window.unlockAudio();
                window.playNotificationChime();
                const pill = document.getElementById('audio-activator-pill');
                const text = document.getElementById('audio-activator-text');
                if (pill && text) {
                    text.innerText = 'Audio tiket aktif!';
                    pill.classList.remove('animate-pulse', 'border-indigo-500/40');
                    pill.classList.add('bg-emerald-600', 'border-emerald-400');
                    setTimeout(() => {
                        pill.style.opacity = '0';
                        setTimeout(() => pill.remove(), 400);
                    }, 1200);
                }
            };

            // Hilangkan pill dan buka audio begitu user klik/interaksi pertama di mana saja
            const dismissPillOnAnyInteraction = () => {
                window.unlockAudio();
                const pill = document.getElementById('audio-activator-pill');
                if (pill) {
                    pill.style.opacity = '0';
                    setTimeout(() => pill.remove(), 300);
                }
            };

            ['click', 'keydown', 'touchstart'].forEach(evt => {
                document.addEventListener(evt, dismissPillOnAnyInteraction, { capture: true, once: true });
            });

            document.addEventListener('DOMContentLoaded', () => {
                if (navigator.userActivation && navigator.userActivation.hasBeenActive) {
                    const pill = document.getElementById('audio-activator-pill');
                    if (pill) pill.remove();
                }
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
                                console.warn('[Audio] Autoplay blocked by browser policy:', e);
                                const pill = document.getElementById('audio-activator-pill');
                                const text = document.getElementById('audio-activator-text');
                                if (pill && text) {
                                    text.innerText = 'Tiket masuk! Klik untuk bunyikan';
                                    pill.classList.add('animate-bounce');
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.warn('[Audio] Play error:', e);
                }
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
