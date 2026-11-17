@php
    $initials = mb_strtoupper(collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
    $isSelf = $user->id === auth()->id();
    $tfaRequired = in_array($user->role, \App\Services\TwoFactorService::requiredRoles(), true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="btn-ghost btn-icon" aria-label="Kembali ke Kelola Staf"><x-icon name="chevron-right" class="h-5 w-5 rotate-180" /></a>
            <div class="min-w-0">
                <h2 class="truncate font-bold text-2xl text-surface-900 dark:text-white leading-tight">Ubah staf</h2>
                <p class="truncate text-sm text-surface-500 dark:text-surface-400">{{ $user->name }} · {{ $user->email }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        @csrf
        @method('PUT')
        <div class="min-w-0">
            @if(session('error'))
                <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                    <x-icon name="exclamation" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('error') }}</div>
            @elseif($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                    <x-icon name="exclamation" class="mt-0.5 h-4 w-4 shrink-0" /> Periksa kembali isian yang ditandai.</div>
            @endif
            @include('users._form', ['user' => $user])
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
            {{-- Ringkasan akun --}}
            <div class="card overflow-hidden">
                <div class="flex items-center gap-3 border-b border-surface-200 p-5 dark:border-surface-800">
                    @if($user->profile_photo)
                        <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="" class="h-12 w-12 rounded-full object-cover">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-base font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ $initials }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 truncate font-semibold text-surface-900 dark:text-white">{{ $user->name }}
                            @if($isSelf)<span class="rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-surface-500 dark:bg-surface-800">Anda</span>@endif</p>
                        <p class="text-xs text-surface-500">{{ \App\Models\User::STAFF_ROLES[$user->role] ?? $user->role }}{{ $user->hasSalesFlag() ? ' + Sales' : '' }}</p>
                    </div>
                </div>
                <dl class="divide-y divide-surface-100 text-sm dark:divide-surface-800">
                    <div class="flex items-center justify-between px-5 py-3"><dt class="text-surface-500">Status</dt>
                        <dd class="inline-flex items-center gap-1.5 {{ $user->is_active ? 'text-emerald-700 dark:text-emerald-400' : 'text-surface-500' }}"><span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-surface-400' }}"></span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                    <div class="flex items-center justify-between px-5 py-3"><dt class="text-surface-500">Verifikasi 2 langkah</dt>
                        <dd class="{{ $user->hasTwoFactor() ? 'text-emerald-700 dark:text-emerald-400' : ($tfaRequired ? 'text-amber-700 dark:text-amber-400' : 'text-surface-500') }}">{{ $user->hasTwoFactor() ? 'Aktif' : ($tfaRequired ? 'Wajib, belum aktif' : 'Tidak aktif') }}</dd></div>
                    <div class="flex items-center justify-between px-5 py-3"><dt class="text-surface-500">Sesi aktif</dt>
                        <dd class="tabular-nums text-surface-800 dark:text-surface-200">{{ $user->sessions_count }} perangkat</dd></div>
                    <div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-surface-500">Masuk terakhir</dt>
                        <dd class="text-right text-surface-800 dark:text-surface-200">{{ $lastLogin ? \Illuminate\Support\Carbon::parse($lastLogin)->translatedFormat('d M Y H:i') : 'Belum pernah' }}</dd></div>
                    <div class="flex items-center justify-between px-5 py-3"><dt class="text-surface-500">Dibuat</dt>
                        <dd class="text-surface-800 dark:text-surface-200">{{ $user->created_at?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="card flex flex-col gap-2 p-4">
                <button class="btn-primary w-full justify-center"><x-icon name="check" class="h-4 w-4" /> Simpan perubahan</button>
                <a href="{{ route('users.index') }}" class="btn-ghost w-full justify-center">Batal</a>
            </div>
            <p class="px-1 text-xs text-surface-500">Nonaktifkan akun, keluarkan dari semua perangkat, reset verifikasi 2 langkah atau hapus akun lewat menu ⋮ di daftar Kelola Staf. Perubahan peran tercatat di Log Aktivitas.</p>
        </aside>
    </form>
</x-app-layout>
