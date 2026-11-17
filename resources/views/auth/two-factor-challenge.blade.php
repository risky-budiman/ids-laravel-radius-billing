<x-guest-layout>
    <div class="mb-8" x-data>
        <h2 class="text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">Verifikasi dua langkah</h2>
        <p class="mt-1.5 text-sm text-surface-500 dark:text-surface-400">Buka aplikasi authenticator (Google Authenticator, Authy, Microsoft Authenticator) dan masukkan kode 6 digit untuk akun ini.</p>
    </div>

    @if($errors->any())
        <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-3 dark:border-rose-500/20 dark:bg-rose-500/10" role="alert">
            <x-icon name="exclamation" class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" />
            <p class="text-sm text-rose-700 dark:text-rose-300">{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5" x-data="{ recovery: false, loading: false }" @submit="loading = true">
        @csrf
        <div x-show="!recovery">
            <label for="code" class="label">Kode 6 digit</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" autofocus
                   class="input text-center text-2xl font-semibold tracking-[0.4em] tabular-nums" placeholder="000000" :disabled="recovery" :required="!recovery">
        </div>
        <div x-show="recovery" x-cloak>
            <label for="recovery_code" class="label">Kode cadangan</label>
            <input id="recovery_code" type="text" name="code" autocomplete="off" class="input text-center font-mono uppercase tracking-widest"
                   placeholder="XXXX-XXXX" :disabled="!recovery" :required="recovery">
            <p class="help-text">Salah satu kode cadangan yang disimpan saat mengaktifkan verifikasi. Setiap kode hanya berlaku sekali.</p>
        </div>
        <button class="btn-primary w-full justify-center" :disabled="loading"><span x-text="loading ? 'Memeriksa…' : 'Masuk'"></span></button>
        <div class="flex items-center justify-between text-sm">
            <button type="button" class="font-medium text-primary-600 hover:underline dark:text-primary-400" @click="recovery = !recovery; $nextTick(() => (recovery ? $refs.rc : $refs.c)?.focus())"
                    x-text="recovery ? 'Pakai kode dari aplikasi' : 'HP hilang? Pakai kode cadangan'"></button>
            <a href="{{ route('login') }}" class="text-surface-500 hover:text-surface-700 dark:hover:text-surface-300">Batal</a>
        </div>
    </form>
    <p class="mt-6 text-xs text-surface-500">Tidak punya akses ke aplikasi maupun kode cadangan? Hubungi administrator untuk mereset verifikasi dua langkah akun Anda.</p>
</x-guest-layout>
