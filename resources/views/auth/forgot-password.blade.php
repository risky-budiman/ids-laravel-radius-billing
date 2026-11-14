<x-guest-layout>
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm text-surface-500 hover:text-surface-800 dark:text-surface-400 dark:hover:text-surface-200 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke halaman masuk
    </a>

    <div class="mb-8">
        <h2 class="text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">Lupa kata sandi?</h2>
        <p class="mt-1.5 text-sm text-surface-500 dark:text-surface-400">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="input @error('email') input-error @enderror" placeholder="nama@perusahaan.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <button type="submit" class="btn-primary btn-lg w-full" :disabled="loading">
            <span x-text="loading ? 'Mengirim…' : 'Kirim tautan reset'">Kirim tautan reset</span>
        </button>
    </form>

    <p class="mt-6 text-xs text-surface-400">Tidak menerima email? Periksa folder spam atau hubungi administrator sistem.</p>
</x-guest-layout>
