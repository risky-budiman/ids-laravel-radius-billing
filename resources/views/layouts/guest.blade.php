<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ get_setting('company_name', config('app.name', 'Radius ISP')) }} - Login</title>

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

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
        <style>
            body { font-family: 'Inter', sans-serif; }
            .bg-premium-solid {
                background-color: #0f172a;
            }
            .glass-premium {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }
            .dark .glass-premium {
                background: rgba(15, 23, 42, 0.8);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .animate-float {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-15px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body class="antialiased bg-gray-50 dark:bg-gray-950 overflow-y-auto">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left Side: Visual/Branding (Hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-premium-solid relative overflow-hidden items-center justify-center p-12 min-h-screen">
                <!-- Clean Grid Pattern Background -->
                <div class="absolute inset-0 opacity-[0.05]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>
                
                <!-- Large Subtle Decoration -->
                <div class="absolute top-1/4 -left-20 w-96 h-96 bg-indigo-500/10 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-blue-500/10 rounded-full blur-[120px]"></div>
                
                <div class="relative z-10 text-center">
                    <div class="mb-10 inline-block p-5 bg-white/5 rounded-3xl backdrop-blur-xl border border-white/10 shadow-2xl animate-float">
                        @if(get_setting('company_logo'))
                            <img src="{{ asset('storage/' . get_setting('company_logo')) }}" alt="Logo" class="w-24 h-24 object-contain">
                        @else
                            <div class="w-24 h-24 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-inner">
                                <x-application-logo class="w-14 h-14 fill-current text-white" />
                            </div>
                        @endif
                    </div>
                    <h1 class="text-5xl font-black text-white tracking-tight mb-6">
                        {{ get_setting('company_name', config('app.name', 'Radius ISP')) }}
                    </h1>
                    <div class="w-20 h-1.5 bg-indigo-500 mx-auto rounded-full mb-8"></div>
                    <p class="text-gray-400 text-lg max-w-sm mx-auto leading-relaxed font-medium">
                        Powerful automation for modern ISP management. Simple, clean, and efficient.
                    </p>
                </div>

                <!-- Footer branding -->
                <div class="absolute bottom-10 left-0 right-0 text-center">
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-[0.3em]">
                        &copy; {{ date('Y') }} {{ get_setting('company_name', 'Radius') }} &bull; Next-Gen Network Control
                    </p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 md:p-16 relative bg-white dark:bg-gray-950 min-h-screen">
                <div class="lg:hidden absolute -top-24 -left-24 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl"></div>
                <div class="lg:hidden absolute -bottom-24 -right-24 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl"></div>
                
                <div class="w-full max-w-md space-y-8 relative z-10">
                    <div class="text-center lg:text-left">
                        <!-- Mobile-only logo -->
                        <div class="lg:hidden flex justify-center mb-8">
                            <div class="p-4 bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800">
                                @if(get_setting('company_logo'))
                                    <img src="{{ asset('storage/' . get_setting('company_logo')) }}" alt="Logo" class="w-16 h-16 object-contain">
                                @else
                                    <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center">
                                        <x-application-logo class="w-10 h-10 fill-current text-white" />
                                    </div>
                                @endif
                            </div>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-tight">
                            Access Dashboard <br class="hidden lg:block"> <span class="text-indigo-600 dark:text-indigo-400">Management ISP</span>
                        </h2>
                        <p class="mt-2 text-sm sm:text-base text-gray-500 dark:text-gray-400 font-medium">Control your network infrastructure and customers with precision.</p>
                    </div>

                    <div class="glass-premium rounded-[2.5rem] p-8 sm:p-10 shadow-2xl shadow-indigo-500/10 border border-gray-100 dark:border-gray-800">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
