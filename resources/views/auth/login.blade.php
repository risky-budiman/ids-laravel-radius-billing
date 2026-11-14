<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">Masuk ke panel admin</h2>
        <p class="mt-1.5 text-sm text-surface-500 dark:text-surface-400">Gunakan akun staf yang diberikan administrator.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if($errors->any())
        <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 px-3.5 py-3" role="alert">
            <x-icon name="exclamation" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
            <p class="text-sm text-rose-700 dark:text-rose-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false, loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="login" class="label">Email atau username</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                   class="input @error('login') input-error @enderror" placeholder="nama@perusahaan.com">
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="text-sm font-medium text-surface-700 dark:text-surface-300">Kata sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                       class="input pr-10 @error('password') input-error @enderror" placeholder="••••••••">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-3 text-surface-400 hover:text-surface-600 dark:hover:text-surface-200"
                        :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="!show" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
        </div>

        <label for="remember_me" class="flex items-center gap-2.5 cursor-pointer select-none">
            <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-surface-300 dark:border-surface-600 dark:bg-surface-800 text-primary-600 focus:ring-primary-500">
            <span class="text-sm text-surface-600 dark:text-surface-300">Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn-primary btn-lg w-full" :disabled="loading">
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span x-text="loading ? 'Memproses…' : 'Masuk'">Masuk</span>
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-surface-500 dark:text-surface-400">
        Pelanggan? <a href="{{ route('customer.login') }}" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Masuk ke portal pelanggan</a>
    </p>
</x-guest-layout>
