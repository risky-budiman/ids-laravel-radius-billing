<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="btn-ghost btn-icon" aria-label="Kembali ke Kelola Staf"><x-icon name="chevron-right" class="h-5 w-5 rotate-180" /></a>
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Tambah staf</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Buat akun untuk anggota tim baru.</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('users.store') }}" class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        @csrf
        <div class="min-w-0">
            @if($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                    <x-icon name="exclamation" class="mt-0.5 h-4 w-4 shrink-0" /> Periksa kembali isian yang ditandai.</div>
            @endif
            @include('users._form', ['user' => null])
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
            <div class="card p-5">
                <h3 class="text-sm font-semibold text-surface-900 dark:text-white">Sebelum membuat akun</h3>
                <ul class="mt-3 space-y-2.5 text-sm text-surface-600 dark:text-surface-300">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Satu orang satu akun — jangan berbagi akun, agar setiap perubahan bisa ditelusuri.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Pilih peran paling kecil yang cukup untuk pekerjaannya.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" /> Sampaikan kata sandi awal secara pribadi; staf menggantinya di Profil.</li>
                    @if(\App\Services\TwoFactorService::requiredRoles())
                        <li class="flex gap-2"><x-icon name="lock-closed" class="mt-0.5 h-4 w-4 shrink-0 text-primary-500" /> Role {{ collect(\App\Services\TwoFactorService::requiredRoles())->map(fn ($r) => \App\Models\User::STAFF_ROLES[$r] ?? $r)->implode(', ') }} wajib mengaktifkan verifikasi dua langkah saat pertama masuk.</li>
                    @endif
                </ul>
            </div>
            <div class="card flex flex-col gap-2 p-4">
                <button class="btn-primary w-full justify-center"><x-icon name="user-plus" class="h-4 w-4" /> Buat akun staf</button>
                <a href="{{ route('users.index') }}" class="btn-ghost w-full justify-center">Batal</a>
            </div>
        </aside>
    </form>
</x-app-layout>
