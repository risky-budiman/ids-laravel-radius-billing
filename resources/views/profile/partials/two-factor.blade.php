@php
    $u = auth()->user();
    $setupSecret = session('two_factor_setup');
    $required = \App\Services\TwoFactorService::requiredFor($u);
    $recovery = session('two_factor_recovery');
    $left = $u->hasTwoFactor() ? count((array) $u->two_factor_recovery_codes) : 0;
@endphp
<section id="verifikasi-dua-langkah" class="space-y-4">
    <header>
        <h2 class="flex flex-wrap items-center gap-2 text-lg font-medium text-surface-900 dark:text-white">Verifikasi dua langkah
            <span class="{{ $u->hasTwoFactor() ? 'badge-success' : ($required ? 'badge-danger' : 'badge-gray') }}">{{ $u->hasTwoFactor() ? 'Aktif' : ($required ? 'Wajib — belum aktif' : 'Nonaktif') }}</span>
        </h2>
        <p class="mt-1 text-sm text-surface-600 dark:text-surface-400">Selain password, masuk juga memerlukan kode 6 digit dari aplikasi authenticator di HP Anda (Google Authenticator, Authy, Microsoft Authenticator). Akun tetap aman walau password bocor.</p>
    </header>

    @if(session('warning'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">{{ session('warning') }}</div>
    @endif

    @if($recovery)
        {{-- Ditampilkan sekali --}}
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10" x-data="{ copied: false }">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Kode cadangan — simpan sekarang, tidak akan ditampilkan lagi</p>
            <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">Pakai salah satu bila HP hilang. Setiap kode hanya berlaku sekali. Simpan di pengelola password atau cetak & simpan di tempat aman.</p>
            <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                @foreach($recovery as $c)<span class="rounded bg-white px-2 py-1.5 text-center tracking-wider text-surface-900 dark:bg-surface-900 dark:text-white">{{ $c }}</span>@endforeach
            </div>
            <button type="button" class="btn-secondary btn-sm mt-3" @click="navigator.clipboard.writeText(@js(implode("\n", $recovery))); copied = true" x-text="copied ? 'Tersalin' : 'Salin semua kode'"></button>
        </div>
    @endif

    @if($u->hasTwoFactor())
        <p class="text-sm text-surface-600 dark:text-surface-400">Aktif sejak {{ $u->two_factor_confirmed_at->translatedFormat('d M Y H:i') }} · sisa <strong>{{ $left }}</strong> kode cadangan.</p>
        <div class="grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('two-factor.recovery') }}" class="space-y-2 rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                @csrf
                <p class="text-sm font-medium text-surface-900 dark:text-white">Buat kode cadangan baru</p>
                <input type="password" name="two_factor_password" class="input" placeholder="Password Anda" autocomplete="current-password" required>
                <button class="btn-secondary btn-sm">Buat ulang kode cadangan</button>
            </form>
            @unless($required)
                <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-2 rounded-lg border border-surface-200 p-4 dark:border-surface-700"
                      onsubmit="return confirm('Nonaktifkan verifikasi dua langkah?')">
                    @csrf
                    <p class="text-sm font-medium text-surface-900 dark:text-white">Nonaktifkan</p>
                    <input type="password" name="two_factor_password" class="input" placeholder="Password Anda" autocomplete="current-password" required>
                    <input type="text" name="two_factor_code" class="input" placeholder="Kode 6 digit" inputmode="numeric" autocomplete="one-time-code" required>
                    <button class="btn-ghost btn-sm text-rose-600">Nonaktifkan verifikasi</button>
                </form>
            @else
                <p class="rounded-lg border border-surface-200 p-4 text-sm text-surface-500 dark:border-surface-700">Verifikasi dua langkah wajib untuk role Anda, sehingga tidak bisa dinonaktifkan. Ganti HP: minta administrator mereset, lalu aktifkan lagi.</p>
            @endunless
        </div>
        @error('two_factor_password')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('two_factor_code')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
    @elseif($setupSecret)
        @php $uri = \App\Support\Totp::uri($setupSecret, $u->email, \App\Services\TwoFactorService::issuer()); @endphp
        <div class="grid gap-5 rounded-lg border border-primary-200 p-4 dark:border-primary-500/30 md:grid-cols-[auto_1fr]">
            <div class="flex justify-center">
                <div id="tfa-qr" class="rounded-lg bg-white p-3" aria-label="QR code verifikasi dua langkah" data-uri="{{ $uri }}"></div>
            </div>
            <div class="space-y-3 text-sm">
                <ol class="list-decimal space-y-1 pl-5 text-surface-700 dark:text-surface-300">
                    <li>Pasang aplikasi authenticator di HP (Google Authenticator, Authy, atau Microsoft Authenticator).</li>
                    <li>Tambah akun → <strong>pindai QR code</strong> ini. Tidak bisa memindai? Masukkan kode berikut secara manual:</li>
                </ol>
                <p class="select-all rounded bg-surface-100 px-3 py-2 font-mono text-sm tracking-wider text-surface-900 dark:bg-surface-800 dark:text-white">{{ trim(chunk_split($setupSecret, 4, ' ')) }}</p>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label class="label" for="two_factor_code">3. Masukkan kode 6 digit dari aplikasi</label>
                        <input id="two_factor_code" name="two_factor_code" class="input w-40 text-center font-semibold tracking-[0.3em] tabular-nums" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus>
                    </div>
                    <button class="btn-primary">Aktifkan</button>
                </form>
                @error('two_factor_code')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                <form method="POST" action="{{ route('two-factor.cancel') }}">@csrf<button class="text-xs text-surface-500 hover:underline">Batal</button></form>
            </div>
        </div>
        <script src="{{ asset('js/vendor/qrcode-generator-1.4.4.js') }}"></script>
        <script>
            (function () {
                const el = document.getElementById('tfa-qr');
                if (!el || typeof qrcode === 'undefined') return;
                const qr = qrcode(0, 'M');
                qr.addData(el.dataset.uri);
                qr.make();
                el.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 0, scalable: false });
            })();
        </script>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}">
            @csrf
            <button class="btn-primary">Aktifkan verifikasi dua langkah</button>
        </form>
    @endif
</section>
