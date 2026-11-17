@php
    use App\Models\User;
    $tfaRequired = \App\Services\TwoFactorService::requiredRoles();
    $roleStyle = [
        'administrator' => ['bg-rose-500', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
        'admin' => ['bg-indigo-500', 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'],
        'teknisi' => ['bg-amber-500', 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'],
        'kasir' => ['bg-emerald-500', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
        'sales' => ['bg-sky-500', 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
        'cs' => ['bg-violet-500', 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300'],
        'mitra' => ['bg-slate-500', 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300'],
    ];
    $roleLabel = fn ($r) => ['cs' => 'CS', 'mitra' => 'Mitra'][$r] ?? (User::STAFF_ROLES[$r] ?? $r);
    $initials = fn ($name) => mb_strtoupper(collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
    $filtered = $role || $status || $q;
    $tfaPct = $stats['active'] ? (int) round($stats['two_factor'] / $stats['active'] * 100) : 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Kelola Staf</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Akun, peran, dan keamanan login seluruh staf.</p>
            </div>
            <div class="flex items-center gap-2">
                @canroute('access.matrix')
                    <a href="{{ route('access.matrix') }}" class="btn-secondary"><x-icon name="shield-check" class="h-4 w-4" /> Hak akses per role</a>
                @endcanroute
                <a href="{{ route('users.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Tambah staf</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('success'))
            <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert"><x-icon name="exclamation" class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('error') }}</div>
        @endif

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="text-xs font-medium uppercase tracking-wide text-surface-500">Total staf</p><x-icon name="users" class="h-5 w-5 text-surface-400" /></div>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-surface-500">{{ $stats['active'] }} aktif · {{ $stats['total'] - $stats['active'] }} nonaktif</p>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="text-xs font-medium uppercase tracking-wide text-surface-500">Online sekarang</p>
                    <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60 {{ $stats['online'] ? '' : 'hidden' }}"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $stats['online'] ? 'bg-emerald-500' : 'bg-surface-300 dark:bg-surface-600' }}"></span></span></div>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $stats['online'] }}</p>
                <p class="text-xs text-surface-500">punya sesi web aktif</p>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="text-xs font-medium uppercase tracking-wide text-surface-500">Verifikasi 2 langkah</p><x-icon name="lock-closed" class="h-5 w-5 text-surface-400" /></div>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $stats['two_factor'] }}<span class="text-base font-normal text-surface-400"> / {{ $stats['active'] }}</span></p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100 dark:bg-surface-800"><div class="h-full rounded-full {{ $tfaPct === 100 ? 'bg-emerald-500' : 'bg-primary-500' }}" style="width: {{ $tfaPct }}%"></div></div>
            </div>
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="text-xs font-medium uppercase tracking-wide text-surface-500">Wajib 2FA</p><x-icon name="shield-check" class="h-5 w-5 text-surface-400" /></div>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ count($tfaRequired) }}<span class="text-base font-normal text-surface-400"> role</span></p>
                <p class="text-xs text-surface-500">{{ $tfaRequired ? collect($tfaRequired)->map($roleLabel)->implode(', ') : 'Belum ada — atur di kebijakan keamanan' }}</p>
            </div>
        </div>

        {{-- Kebijakan keamanan login --}}
        <details class="card group" @if($errors->has('roles')) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300"><x-icon name="lock-closed" class="h-5 w-5" /></span>
                    <span><span class="block text-sm font-semibold text-surface-900 dark:text-white">Kebijakan keamanan login</span>
                        <span class="block text-xs text-surface-500">Wajibkan verifikasi dua langkah (aplikasi authenticator) untuk role tertentu.</span></span>
                </span>
                <x-icon name="chevron-down" class="h-5 w-5 text-surface-400 transition-transform group-open:rotate-180" />
            </summary>
            <form method="POST" action="{{ route('users.two-factor.required') }}" class="border-t border-surface-200 px-5 py-4 dark:border-surface-800"
                  onsubmit="return confirm('Simpan kebijakan? Staf dengan role yang dicentang dan belum mengaktifkan verifikasi dua langkah hanya bisa membuka Profil sampai mengaktifkannya.')">
                @csrf
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Services\TwoFactorService::STAFF_ROLES as $r)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $r }}" class="peer sr-only" @checked(in_array($r, $tfaRequired, true))>
                            <span class="inline-flex items-center gap-2 rounded-full border border-surface-200 px-3.5 py-1.5 text-sm text-surface-700 transition-colors hover:border-surface-300 peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:text-primary-800 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 dark:border-surface-700 dark:text-surface-300 dark:peer-checked:bg-primary-500/15 dark:peer-checked:text-primary-200">
                                <span class="h-2 w-2 rounded-full {{ $roleStyle[$r][0] }}"></span>{{ $roleLabel($r) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-surface-500">Staf mengaktifkan sendiri di Profil. Login aplikasi mobile staf juga meminta kode — perbarui aplikasi mobile sebelum mewajibkan role yang memakainya.</p>
                    <button class="btn-primary shrink-0">Simpan kebijakan</button>
                </div>
            </form>
        </details>

        {{-- Daftar staf --}}
        <div class="card">
            <form method="GET" class="flex flex-col gap-3 border-b border-surface-200 p-4 dark:border-surface-800 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('users.index', array_filter(['status' => $status, 'q' => $q])) }}"
                       class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ ! $role ? 'bg-surface-900 text-white dark:bg-white dark:text-surface-900' : 'text-surface-600 hover:bg-surface-100 dark:text-surface-300 dark:hover:bg-surface-800' }}">
                        Semua <span class="tabular-nums opacity-70">{{ $stats['total'] }}</span></a>
                    @foreach(User::STAFF_ROLES as $r => $label)
                        @continue(! ($roleCounts[$r] ?? 0))
                        <a href="{{ route('users.index', array_filter(['role' => $r, 'status' => $status, 'q' => $q])) }}"
                           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $role === $r ? 'bg-surface-900 text-white dark:bg-white dark:text-surface-900' : 'text-surface-600 hover:bg-surface-100 dark:text-surface-300 dark:hover:bg-surface-800' }}">
                            <span class="h-2 w-2 rounded-full {{ $roleStyle[$r][0] ?? 'bg-surface-400' }}"></span>{{ $roleLabel($r) }} <span class="tabular-nums opacity-70">{{ $roleCounts[$r] }}</span></a>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    @if($role)<input type="hidden" name="role" value="{{ $role }}">@endif
                    <label for="status" class="sr-only">Status</label>
                    <select id="status" name="status" class="input h-9 w-auto py-1 text-sm" onchange="this.form.submit()">
                        @foreach(['' => 'Semua status', 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'no2fa' => 'Tanpa 2FA'] as $k => $v)
                            <option value="{{ $k }}" @selected((string) $status === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-surface-400" />
                        <label for="q" class="sr-only">Cari staf</label>
                        <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email…" class="input h-9 w-56 py-1 pl-9 text-sm">
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto lg:overflow-visible">
                <table class="table">
                    <thead>
                        <tr><th>Staf</th><th>Peran</th><th>Status</th><th>Keamanan</th><th>Aktivitas</th><th class="text-right"><span class="sr-only">Aksi</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $isMe = $user->id === auth()->id();
                                $online = $user->sessions_count > 0;
                                $lastSeen = $user->last_seen ? \Illuminate\Support\Carbon::createFromTimestamp($user->last_seen) : null;
                                $lastLogin = $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at) : null;
                            @endphp
                            <tr class="{{ $user->is_active ? '' : 'opacity-60' }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($user->profile_photo)
                                            <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white dark:ring-surface-900">
                                        @else
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $roleStyle[$user->role][1] ?? 'bg-surface-100 text-surface-600' }}" aria-hidden="true">{{ $initials($user->name) }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-2 truncate font-medium text-surface-900 dark:text-white">{{ $user->name }}
                                                @if($isMe)<span class="rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-surface-500 dark:bg-surface-800">Anda</span>@endif</p>
                                            <p class="truncate text-xs text-surface-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $roleStyle[$user->role][1] ?? 'bg-surface-100 text-surface-600' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $roleStyle[$user->role][0] ?? 'bg-surface-400' }}"></span>{{ $roleLabel($user->role) }}</span>
                                    @if($user->hasSalesFlag())
                                        <span class="ml-1 inline-flex rounded-full bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30" title="Juga punya akses & komisi sales">+ Sales</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-sm {{ $user->is_active ? 'text-emerald-700 dark:text-emerald-400' : 'text-surface-500' }}">
                                        <span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-surface-400' }}"></span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($user->hasTwoFactor())
                                        <span class="inline-flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400" title="Aktif sejak {{ $user->two_factor_confirmed_at->translatedFormat('d M Y') }}"><x-icon name="shield-check" class="h-4 w-4" /> 2FA aktif</span>
                                    @elseif(in_array($user->role, $tfaRequired, true))
                                        <span class="inline-flex items-center gap-1.5 text-sm text-amber-700 dark:text-amber-400"><x-icon name="exclamation" class="h-4 w-4" /> Wajib, belum aktif</span>
                                    @else
                                        <span class="text-sm text-surface-400">Password saja</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($online)
                                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>Online · {{ $user->sessions_count }} perangkat</span>
                                    @elseif($lastSeen)
                                        <span class="text-sm text-surface-700 dark:text-surface-300" title="{{ $lastSeen->translatedFormat('d M Y H:i') }}">Aktif {{ $lastSeen->diffForHumans() }}</span>
                                    @else
                                        <span class="text-sm text-surface-400">Belum ada sesi</span>
                                    @endif
                                    <span class="block text-xs text-surface-500">{{ $lastLogin ? 'Masuk terakhir ' . $lastLogin->translatedFormat('d M Y H:i') : 'Belum pernah masuk' }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-1" x-data="{ open: false }" @keydown.escape.window="open = false">
                                        <a href="{{ route('users.edit', $user) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4" /> Ubah</a>
                                        <div class="relative">
                                            <button type="button" class="btn-ghost btn-sm px-2" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="Aksi lain untuk {{ $user->name }}">
                                                <x-icon name="dots-vertical" class="h-4 w-4" /></button>
                                            <div x-show="open" x-cloak x-transition.origin.top.right @click.outside="open = false" role="menu"
                                                 class="absolute right-0 z-30 mt-1 w-64 overflow-hidden whitespace-normal rounded-xl border border-surface-200 bg-white py-1 text-left shadow-lg dark:border-surface-700 dark:bg-surface-900">
                                                @if($user->role !== User::ROLE_ADMINISTRATOR)
                                                    <form action="{{ route('users.toggle-status', $user) }}" method="POST">@csrf
                                                        <button role="menuitem" class="flex w-full items-center text-left gap-2.5 px-3.5 py-2 text-sm text-surface-700 hover:bg-surface-50 dark:text-surface-200 dark:hover:bg-surface-800">
                                                            <x-icon name="{{ $user->is_active ? 'ban' : 'check-circle' }}" class="h-4 w-4 shrink-0 text-surface-400" />{{ $user->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('users.reset-sessions', $user) }}" method="POST" onsubmit="return confirm('Keluarkan {{ addslashes($user->name) }} dari semua perangkat?')">@csrf
                                                    <button role="menuitem" class="flex w-full items-center text-left gap-2.5 px-3.5 py-2 text-sm text-surface-700 hover:bg-surface-50 dark:text-surface-200 dark:hover:bg-surface-800">
                                                        <x-icon name="logout" class="h-4 w-4 shrink-0 text-surface-400" />Keluarkan dari semua perangkat</button>
                                                </form>
                                                @if($user->hasTwoFactor() && ! $isMe)
                                                    <form action="{{ route('users.two-factor.reset', $user) }}" method="POST" onsubmit="return confirm('Reset verifikasi dua langkah {{ addslashes($user->name) }}? Pakai bila HP & kode cadangannya hilang; ia harus mengaktifkan ulang.')">@csrf
                                                        <button role="menuitem" class="flex w-full items-center text-left gap-2.5 px-3.5 py-2 text-sm text-surface-700 hover:bg-surface-50 dark:text-surface-200 dark:hover:bg-surface-800">
                                                            <x-icon name="device-mobile" class="h-4 w-4 shrink-0 text-surface-400" />Reset verifikasi 2 langkah</button>
                                                    </form>
                                                @endif
                                                @if(auth()->user()->isAdministrator() && ! $isMe)
                                                    <div class="my-1 border-t border-surface-100 dark:border-surface-800"></div>
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }} secara permanen? Akun yang sudah punya riwayat (tiket, komisi, transaksi, log) tidak bisa dihapus — nonaktifkan saja.')">
                                                        @csrf @method('DELETE')
                                                        <button role="menuitem" class="flex w-full items-center text-left gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                                            <x-icon name="trash" class="h-4 w-4 shrink-0" />Hapus akun</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-14 text-center">
                                <x-icon name="users" class="mx-auto h-8 w-8 text-surface-300" />
                                <p class="mt-2 text-sm font-medium text-surface-700 dark:text-surface-300">{{ $filtered ? 'Tidak ada staf yang cocok dengan filter.' : 'Belum ada staf.' }}</p>
                                @if($filtered)<a href="{{ route('users.index') }}" class="mt-1 inline-block text-sm text-primary-600 hover:underline">Hapus filter</a>@endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="border-t border-surface-200 px-4 py-3 dark:border-surface-800">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
