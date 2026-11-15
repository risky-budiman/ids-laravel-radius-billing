@php
    $companyName = get_setting('company_name', 'Radius ISP');
    $companyLogo = get_setting('company_logo');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Kode verifikasi · Portal Pelanggan {{ $companyName }}</title>
    @if(get_setting('app_icon') || $companyLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . (get_setting('app_icon') ?: $companyLogo)) }}">
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
<body class="min-h-screen bg-surface-50 dark:bg-surface-950 flex flex-col">
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                @if($companyLogo)
                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="{{ $companyName }}" class="mx-auto h-14 w-auto object-contain">
                @endif
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">Masukkan kode verifikasi</h1>
                <p class="mt-1.5 text-sm text-surface-500 dark:text-surface-400">Kode 6 digit dikirim ke WhatsApp <span class="font-mono">{{ $maskedPhone }}</span>.</p>
            </div>

            <div class="card p-6 sm:p-8">
                @if(session('error') || $errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 px-3.5 py-3" role="alert">
                        <x-icon name="exclamation" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <p class="text-sm text-rose-700 dark:text-rose-300">{{ session('error') ?: $errors->first() }}</p>
                    </div>
                @elseif(session('success'))
                    <div class="mb-5 rounded-lg border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10 px-3.5 py-3 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ session('success') }}</div>
                @endif

                <form action="{{ route('customer.login.verify.post') }}" method="POST" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div>
                        <label for="code" class="label">Kode verifikasi</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"
                               required autofocus placeholder="••••••" class="input text-center font-mono text-2xl tracking-[0.5em]">
                    </div>
                    <button type="submit" class="btn-primary btn-lg w-full" :disabled="loading">
                        <span x-text="loading ? 'Memeriksa…' : 'Masuk'">Masuk</span>
                    </button>
                </form>

                <div class="mt-5 flex items-center justify-between text-sm">
                    <a href="{{ route('customer.login') }}" class="text-surface-500 hover:text-surface-700 dark:hover:text-surface-300">Ganti akun</a>
                    <form action="{{ route('customer.login.resend') }}" method="POST">
                        @csrf
                        <button class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Kirim ulang kode</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
