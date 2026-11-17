{{-- Form akun staf bersama (Tambah & Ubah). $user = null saat menambah. --}}
@php
    use App\Models\User;
    $editing = $user !== null;
    $isSelf = $editing && $user->id === auth()->id();
    $currentRole = old('role', $user?->role ?? User::ROLE_TEKNISI);
    $roleInfo = [
        'administrator' => ['Akses penuh: pengaturan aplikasi, akun staf, hak akses, backup & pembaruan sistem.', 'bg-rose-500'],
        'admin' => ['Operasional & keuangan: pelanggan, tagihan, kas & bank, impor/ekspor, laporan.', 'bg-indigo-500'],
        'teknisi' => ['Lapangan & jaringan: tiket, aktivasi & dismantle, OLT/NOC, inventaris.', 'bg-amber-500'],
        'kasir' => ['Penerimaan uang: pembayaran invoice, kas & setoran kasir.', 'bg-emerald-500'],
        'sales' => ['Penjualan: mendaftarkan pelanggan & memantau komisi referal.', 'bg-sky-500'],
        'cs' => ['Layanan pelanggan: tiket, kontak & status; ganti paket lewat pengajuan.', 'bg-violet-500'],
        'mitra' => ['Pihak luar (reseller): hanya pelanggan miliknya & komisi mitra.', 'bg-slate-500'],
    ];
    $internal = [User::ROLE_ADMINISTRATOR, User::ROLE_ADMIN, User::ROLE_TEKNISI, User::ROLE_KASIR, User::ROLE_CS];
    $salesOn = (bool) old('is_sales', $user?->is_sales ?? false);
    $section = 'rounded-xl border border-surface-200 bg-white dark:border-surface-800 dark:bg-surface-900';
@endphp

<div x-data="{ role: @js($currentRole), internal: @js($internal), showPw: false, pw: '', pw2: '',
              strength() { const p = this.pw; let s = 0; if (p.length >= 8) s++; if (p.length >= 12) s++; if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++; if (/\d/.test(p)) s++; if (/[^A-Za-z0-9]/.test(p)) s++; return p ? Math.min(4, Math.max(1, s - 1)) : 0; },
              generate() { const c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#%*?'; const a = new Uint32Array(14); crypto.getRandomValues(a); this.pw = this.pw2 = Array.from(a, x => c[x % c.length]).join(''); this.showPw = true; } }"
     class="space-y-5">

    {{-- Identitas --}}
    <section class="{{ $section }}">
        <header class="border-b border-surface-200 px-5 py-4 dark:border-surface-800">
            <h3 class="text-sm font-semibold text-surface-900 dark:text-white">Identitas</h3>
            <p class="text-xs text-surface-500">Email dipakai untuk masuk ke panel admin dan aplikasi mobile staf.</p>
        </header>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Nama lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required maxlength="255" autocomplete="name" @unless($editing) autofocus @endunless
                       class="input @error('name') input-error @enderror" placeholder="mis. Budi Santoso">
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required maxlength="255" autocomplete="email"
                       class="input @error('email') input-error @enderror" placeholder="nama@perusahaan.com">
                @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Peran --}}
    <section class="{{ $section }}">
        <header class="flex flex-col gap-1 border-b border-surface-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-surface-800">
            <div>
                <h3 class="text-sm font-semibold text-surface-900 dark:text-white">Peran</h3>
                <p class="text-xs text-surface-500">Menentukan menu & aksi yang bisa dipakai. Rinciannya bisa diatur di Hak akses per role.</p>
            </div>
            @canroute('access.matrix')<a href="{{ route('access.matrix') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400" target="_blank">Lihat hak akses per role ↗</a>@endcanroute
        </header>
        <div class="p-5">
            @if($isSelf)
                <input type="hidden" name="role" value="{{ $user->role }}">
                <p class="mb-3 flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><x-icon name="exclamation" class="h-4 w-4 shrink-0" /> Peran akun Anda sendiri tidak bisa diubah. Minta administrator lain bila perlu.</p>
            @endif
            <fieldset class="grid gap-2.5 sm:grid-cols-2" @if($isSelf) disabled @endif>
                <legend class="sr-only">Pilih peran</legend>
                @foreach($roles as $value => $label)
                    <label class="relative flex cursor-pointer gap-3 rounded-lg border p-3.5 transition-colors {{ $isSelf ? 'cursor-not-allowed opacity-60' : '' }}"
                           :class="role === '{{ $value }}' ? 'border-primary-500 bg-primary-50/60 ring-1 ring-primary-500 dark:bg-primary-500/10' : 'border-surface-200 hover:border-surface-300 dark:border-surface-700 dark:hover:border-surface-600'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" class="mt-0.5 shrink-0 text-primary-600 focus:ring-primary-500" @checked($currentRole === $value)>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-medium text-surface-900 dark:text-white"><span class="h-2 w-2 rounded-full {{ $roleInfo[$value][1] ?? 'bg-surface-400' }}"></span>{{ $label }}</span>
                            <span class="mt-0.5 block text-xs leading-snug text-surface-500">{{ $roleInfo[$value][0] ?? '' }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>
            @error('role')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

            {{-- Juga sales: hanya staf internal --}}
            <label x-show="internal.includes(role)" x-cloak class="mt-4 flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-surface-200 p-3.5 dark:border-surface-700">
                <span>
                    <span class="block text-sm font-medium text-surface-900 dark:text-white">Juga sebagai sales</span>
                    <span class="block text-xs text-surface-500">Mendapat akses sales (dasbor sales, daftarkan pelanggan) dan bisa dipilih sebagai referal yang menerima komisi. Tampil sebagai "+ Sales" di daftar staf.</span>
                </span>
                <span class="relative mt-0.5 inline-flex shrink-0">
                    <input type="checkbox" name="is_sales" value="1" class="peer sr-only" @checked($salesOn)>
                    <span class="h-6 w-11 rounded-full bg-surface-200 transition-colors peer-checked:bg-primary-600 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 peer-focus-visible:ring-offset-2 dark:bg-surface-700"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                </span>
            </label>
        </div>
    </section>

    {{-- Mitra --}}
    <section x-show="role === 'mitra'" x-cloak class="{{ $section }}">
        <header class="border-b border-surface-200 px-5 py-4 dark:border-surface-800">
            <h3 class="text-sm font-semibold text-surface-900 dark:text-white">Komisi &amp; rekening mitra</h3>
            <p class="text-xs text-surface-500">Tarif bawaan komisi mitra dan rekening tujuan pencairan.</p>
        </header>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <label for="commission_type" class="label">Jenis komisi</label>
                <select id="commission_type" name="commission_type" class="input">
                    <option value="percentage" @selected(old('commission_type', $user?->commission_type ?? 'percentage') === 'percentage')>Persentase dari tagihan (%)</option>
                    <option value="fixed" @selected(old('commission_type', $user?->commission_type) === 'fixed')>Nominal tetap per tagihan (Rp)</option>
                </select>
            </div>
            <div>
                <label for="commission_rate" class="label">Besaran komisi</label>
                <input id="commission_rate" name="commission_rate" type="number" step="0.01" min="0" value="{{ old('commission_rate', $user?->commission_rate) }}" class="input @error('commission_rate') input-error @enderror" placeholder="mis. 10">
                @error('commission_rate')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bank_name" class="label">Bank</label>
                <input id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $user?->bank_name) }}" class="input" placeholder="BCA, Mandiri, BRI…">
            </div>
            <div>
                <label for="bank_account_number" class="label">Nomor rekening</label>
                <input id="bank_account_number" name="bank_account_number" type="text" inputmode="numeric" value="{{ old('bank_account_number', $user?->bank_account_number) }}" class="input">
            </div>
            <div class="sm:col-span-2">
                <label for="bank_account_name" class="label">Nama pemilik rekening</label>
                <input id="bank_account_name" name="bank_account_name" type="text" value="{{ old('bank_account_name', $user?->bank_account_name) }}" class="input">
            </div>
        </div>
    </section>

    {{-- Kata sandi --}}
    <section class="{{ $section }}">
        <header class="flex flex-col gap-1 border-b border-surface-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-surface-800">
            <div>
                <h3 class="text-sm font-semibold text-surface-900 dark:text-white">{{ $editing ? 'Ganti kata sandi' : 'Kata sandi awal' }}</h3>
                <p class="text-xs text-surface-500">{{ $editing ? 'Kosongkan bila tidak diganti. Mengganti kata sandi tidak mengeluarkan sesi yang sedang aktif.' : 'Minimal 8 karakter. Sampaikan secara pribadi; staf bisa menggantinya sendiri di Profil.' }}</p>
            </div>
            <button type="button" class="btn-ghost btn-sm shrink-0" @click="generate()"><x-icon name="refresh" class="h-4 w-4" /> Buat sandi acak</button>
        </header>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">{{ $editing ? 'Kata sandi baru' : 'Kata sandi' }}</label>
                <div class="relative">
                    <input id="password" name="password" :type="showPw ? 'text' : 'password'" x-model="pw" minlength="8" autocomplete="new-password" @unless($editing) required @endunless
                           class="input pr-20 @error('password') input-error @enderror">
                    <button type="button" class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-surface-500 hover:text-surface-800 dark:hover:text-surface-200" @click="showPw = !showPw" x-text="showPw ? 'Sembunyikan' : 'Tampilkan'"></button>
                </div>
                <div class="mt-2 flex items-center gap-2" x-show="pw" x-cloak>
                    <div class="flex flex-1 gap-1">
                        <template x-for="i in 4"><span class="h-1 flex-1 rounded-full" :class="i <= strength() ? ['', 'bg-rose-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-500'][strength()] : 'bg-surface-200 dark:bg-surface-700'"></span></template>
                    </div>
                    <span class="w-14 text-right text-xs text-surface-500" x-text="['', 'Lemah', 'Cukup', 'Baik', 'Kuat'][strength()]"></span>
                </div>
                @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" :type="showPw ? 'text' : 'password'" x-model="pw2" autocomplete="new-password" @unless($editing) required @endunless class="input">
                <p class="mt-1 text-xs text-rose-600" x-show="pw2 && pw !== pw2" x-cloak>Belum sama dengan kata sandi.</p>
            </div>
        </div>
    </section>
</div>
