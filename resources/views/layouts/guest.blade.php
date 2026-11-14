@php
    $appIcon = get_setting('app_icon');
    $companyLogo = get_setting('company_logo');
    $companyName = get_setting('company_name', config('app.name', 'Radius ISP'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        <title>Masuk · {{ $companyName }}</title>
        @if($appIcon || $companyLogo)
            <link rel="icon" type="image/png" href="{{ asset('storage/' . ($appIcon ?: $companyLogo)) }}">
        @endif
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
    </head>
    <body class="min-h-screen bg-white dark:bg-surface-950">
        <div class="min-h-screen grid lg:grid-cols-2">

            {{-- Panel brand (desktop) --}}
            <aside class="hidden lg:flex flex-col justify-between bg-surface-950 text-white p-12 relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(rgb(255 255 255) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255) 1px, transparent 1px); background-size: 48px 48px;"></div>
                <div class="absolute -top-40 -right-40 w-[28rem] h-[28rem] rounded-full bg-primary-600/25 blur-3xl"></div>

                <div class="relative flex items-center gap-3">
                    @if($companyLogo)
                        <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-white p-1.5">
                            <img src="{{ asset('storage/' . $companyLogo) }}" alt="" class="max-w-full max-h-full object-contain">
                        </span>
                    @else
                        <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-600 font-semibold">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</span>
                    @endif
                    <span class="text-base font-semibold">{{ $companyName }}</span>
                </div>

                <div class="relative max-w-md">
                    <h1 class="text-3xl font-semibold tracking-tight leading-tight">Satu panel untuk jaringan, pelanggan, dan penagihan.</h1>
                    <ul class="mt-8 space-y-4 text-sm text-surface-300">
                        @foreach([
                            ['signal', 'Pantau sesi RADIUS, OLT, dan kualitas sinyal secara realtime'],
                            ['receipt', 'Tagihan otomatis, pembayaran online, dan pengingat WhatsApp'],
                            ['ticket', 'Tiket gangguan dan aktivasi terhubung langsung ke teknisi'],
                        ] as [$ico, $txt])
                            <li class="flex items-start gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 text-primary-300 shrink-0"><x-icon :name="$ico" class="w-4 h-4" /></span>
                                <span class="pt-1.5">{{ $txt }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-surface-500">&copy; {{ date('Y') }} {{ $companyName }} · v{{ app_version() }}</p>
            </aside>

            {{-- Form --}}
            <main class="flex flex-col items-center justify-center px-4 py-12 sm:px-8">
                <div class="w-full max-w-sm">
                    <div class="lg:hidden mb-8 flex items-center gap-3">
                        @if($companyLogo)
                            <img src="{{ asset('storage/' . $companyLogo) }}" alt="" class="h-9 w-auto max-w-[8rem] object-contain">
                        @else
                            <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-600 text-white font-semibold">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</span>
                        @endif
                        <span class="text-base font-semibold text-surface-900 dark:text-white">{{ $companyName }}</span>
                    </div>

                    {{ $slot }}
                </div>
                <p class="lg:hidden mt-10 text-xs text-surface-400">&copy; {{ date('Y') }} {{ $companyName }} · v{{ app_version() }}</p>
            </main>
        </div>
    </body>
</html>
