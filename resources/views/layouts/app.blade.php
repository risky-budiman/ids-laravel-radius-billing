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

        @stack('modals')

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
    </body>
</html>
