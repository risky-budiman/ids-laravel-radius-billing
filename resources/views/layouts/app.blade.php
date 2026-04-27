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
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(229, 231, 235, 0.5);
            }
            .dark .glass {
                background: rgba(17, 24, 39, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            body { font-family: 'Inter', sans-serif; }
            
            /* Custom Scrollbar */
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.5); border-radius: 20px; }
            .dark ::-webkit-scrollbar-thumb { background-color: rgba(75, 85, 99, 0.5); }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 transition-colors duration-300"
          x-data="{ sidebarOpen: false }">
        
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar Component -->
            @include('components.sidebar')

            <!-- Main Content Area -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                <!-- Navbar Component -->
                @include('components.navbar')

                <!-- Page Header -->
                @isset($header)
                    <header class="glass mx-6 mt-6 rounded-2xl shadow-sm z-10">
                        <div class="py-5 px-6">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-grow p-6">
                    {{ $slot }}
                </main>
                
                <!-- Footer Component -->
                @include('components.footer')
            </div>
        </div>
        
        @stack('modals')

        @stack('scripts')
    </body>
</html>
