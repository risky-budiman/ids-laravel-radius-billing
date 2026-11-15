@php
    $companyName = get_setting('company_name', 'Radius ISP');
    $companyLogo = get_setting('company_logo');
    $companyPhone = get_setting('company_phone');
    $waNumber = $companyPhone ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $companyPhone)) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Masuk · Portal Pelanggan {{ $companyName }}</title>
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
            {{-- Brand --}}
            <div class="text-center mb-8">
                @if($companyLogo)
                    <img src="{{ asset('storage/' . $companyLogo) }}" alt="{{ $companyName }}" class="mx-auto h-14 w-auto object-contain">
                @else
                    <div class="mx-auto w-12 h-12 rounded-xl bg-primary-600 flex items-center justify-center text-white text-lg font-semibold">
                        {{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}
                    </div>
                @endif
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">Masuk ke Portal Pelanggan</h1>
                <p class="mt-1.5 text-sm text-surface-500 dark:text-surface-400">Cek tagihan, bayar online, dan laporkan gangguan.</p>
            </div>

            {{-- Form --}}
            <div class="card p-6 sm:p-8">
                @if(session('error') || $errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 px-3.5 py-3" role="alert">
                        <x-icon name="exclamation" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <p class="text-sm text-rose-700 dark:text-rose-300">{{ session('error') ?: $errors->first() }}</p>
                    </div>
                @endif

                <form action="{{ route('customer.login.post') }}" method="POST" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div>
                        <label for="customer_id" class="label">ID Pelanggan</label>
                        <input id="customer_id" type="text" name="customer_id" value="{{ old('customer_id') }}" required autofocus
                               autocomplete="username" placeholder="Contoh: 001001001435" class="input">
                        <p class="help-text">Tertera pada invoice atau pesan WhatsApp dari kami.</p>
                    </div>

                    <div>
                        <label for="phone" class="label">Nomor HP terdaftar</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required inputmode="tel"
                               autocomplete="tel" placeholder="08xxxxxxxxxx" class="input">
                        @if(\App\Services\PortalOtpService::enabled())
                            <p class="help-text">Kode verifikasi akan dikirim ke WhatsApp nomor ini.</p>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary btn-lg w-full" :disabled="loading">
                        <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Memproses…' : 'Masuk'">Masuk</span>
                    </button>
                </form>
            </div>

            {{-- Bantuan --}}
            <p class="mt-6 text-center text-sm text-surface-500 dark:text-surface-400">
                Butuh bantuan?
                @if($waNumber)
                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Hubungi kami via WhatsApp</a>
                @elseif(get_setting('company_email'))
                    <a href="mailto:{{ get_setting('company_email') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Kirim email ke kami</a>
                @else
                    Hubungi kantor {{ $companyName }}.
                @endif
            </p>
        </div>
    </main>

    <footer class="py-6 text-center text-xs text-surface-400">
        &copy; {{ date('Y') }} {{ $companyName }}
        @if(Route::has('login'))
            · <a href="{{ route('login') }}" class="hover:text-surface-600 dark:hover:text-surface-300">Login staf</a>
        @endif
    </footer>
</body>
</html>
