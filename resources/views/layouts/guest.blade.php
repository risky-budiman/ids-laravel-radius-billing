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
            $companyName = get_setting('company_name', config('app.name', 'Radius ISP'));
        @endphp
        @if($appIcon)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . $appIcon) }}">
        @elseif($companyLogo)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . $companyLogo) }}">
        @endif

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
            .admin-left-bg { 
                background-color: #020617; 
                background-image: radial-gradient(at 0% 0%, hsla(220, 100%, 10%, 1) 0, transparent 50%);
            }
        </style>
    </head>
    <body class="antialiased min-h-screen bg-white">
        <div class="flex flex-col lg:flex-row min-h-screen">
            
            <!-- Left Side: Branding (Visible on Desktop) -->
            <div class="hidden lg:flex lg:w-[45%] admin-left-bg relative flex-col items-center justify-center p-12 text-center border-r border-white/5">
                <!-- Decorative Elements -->
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 40px 40px;"></div>
                
                <div class="relative z-10 flex flex-col items-center max-w-md">
                    <!-- Logo Badge -->
                    <div class="relative mb-12">
                        <div class="absolute -inset-8 bg-blue-600/20 blur-3xl rounded-full"></div>
                        <div class="relative p-6 bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-[2.5rem] shadow-2xl">
                            <div class="bg-white rounded-2xl p-4 w-28 h-28 flex items-center justify-center shadow-inner">
                                @if($companyLogo)
                                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" class="w-full h-full object-contain">
                                @else
                                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                @endif
                            </div>
                        </div>
                    </div>

                    <h1 class="text-4xl font-black text-white tracking-tighter mb-4">{{ $companyName }}</h1>
                    <div class="w-16 h-1 bg-indigo-600 rounded-full mb-8"></div>
                    
                    <p class="text-slate-400 text-lg font-medium leading-relaxed">
                        Powerful automation for modern ISP management. Simple, clean, and efficient.
                    </p>
                </div>

                <!-- Left Footer -->
                <div class="absolute bottom-10 left-0 right-0 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.3em]">
                        &copy; {{ date('Y') }} {{ $companyName }} &bull; Next-Gen Network Control
                    </p>
                </div>
            </div>

            <!-- Right Side: Auth Form -->
            <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-12 bg-white relative overflow-hidden">
                <!-- Mobile Logo Header (Hidden on Desktop) -->
                <div class="lg:hidden flex flex-col items-center mb-12">
                    <div class="w-20 h-20 bg-slate-900 rounded-3xl flex items-center justify-center p-3 shadow-xl mb-4">
                        <div class="bg-white rounded-2xl w-full h-full flex items-center justify-center p-2">
                             @if($companyLogo)
                                <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" class="w-full h-full object-contain">
                            @else
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            @endif
                        </div>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tighter">{{ $companyName }}</h2>
                </div>

                <!-- Main Form Container -->
                <div class="w-full max-w-md relative z-10">
                    {{ $slot }}
                </div>

                <!-- Decorative Background (Right) -->
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-blue-50 rounded-full blur-3xl opacity-50"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>
            </div>
        </div>
    </body>
</html>
