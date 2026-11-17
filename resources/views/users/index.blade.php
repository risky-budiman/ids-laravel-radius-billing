<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                Kelola Staf
            </h2>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white  hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Staf
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 px-4 py-3 bg-rose-100/80 border border-rose-200 text-rose-700 rounded-xl dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-400" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
            @endif

            {{-- Verifikasi dua langkah wajib per role --}}
            @php $tfaRequired = \App\Services\TwoFactorService::requiredRoles(); @endphp
            <form method="POST" action="{{ route('users.two-factor.required') }}" class="card mb-6 p-5"
                  onsubmit="return confirm('Simpan? Staf dengan role yang dicentang dan belum mengaktifkan verifikasi dua langkah akan diminta mengaktifkannya sebelum bisa memakai aplikasi.')">
                @csrf
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-surface-900 dark:text-white">Verifikasi dua langkah wajib untuk role</p>
                        <p class="text-xs text-surface-500">Staf mengaktifkan sendiri di Profil (aplikasi authenticator di HP). Yang belum aktif hanya bisa membuka Profil sampai mengaktifkannya. Login aplikasi mobile staf juga meminta kode.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @foreach(\App\Services\TwoFactorService::STAFF_ROLES as $role)
                            <label class="inline-flex items-center gap-1.5 text-sm"><input type="checkbox" name="roles[]" value="{{ $role }}" class="rounded" @checked(in_array($role, $tfaRequired, true))> {{ \App\Models\User::STAFF_ROLES[$role] ?? $role }}</label>
                        @endforeach
                        <button class="btn-secondary btn-sm">Simpan</button>
                    </div>
                </div>
            </form>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 ">#</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 ">Profil Pengguna</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400  text-center">Peran</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400  text-center">Status</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400  text-center">Perangkat Login</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400  text-center">Terakhir Aktif</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-400  text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach($users as $user)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/50 transition-all group">
                                    <td class="px-6 py-5 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                        {{ $users->firstItem() + $loop->index }}
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center">
                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover border-2 border-indigo-500 shadow-md">
                                             <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        @php
                                            $roleClasses = [
                                                'administrator' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                'admin' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                'teknisi' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'kasir' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                'sales' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'cs' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 text-xs rounded-full {{ $roleClasses[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $user->role === 'cs' ? 'CS' : $user->role }}
                                        </span>
                                        @if($user->hasSalesFlag())
                                            {{-- Dulu akses sales lewat penanda ini tidak terlihat di daftar --}}
                                            <span class="ml-1 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" title="Juga punya akses & komisi sales">+ Sales</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        @if($user->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 animate-pulse-slow">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                Nonaktif
                                            </span>
                                        @endif
                                        @if($user->hasTwoFactor())
                                            <span class="badge-success ml-1" title="Verifikasi dua langkah aktif">2FA</span>
                                        @elseif(in_array($user->role, $tfaRequired, true))
                                            <span class="badge-warning ml-1" title="Wajib, belum diaktifkan">2FA belum</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $user->sessions_count > 0 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500' }}">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            {{ $user->sessions_count }} perangkat
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            @if($user->role !== \App\Models\User::ROLE_ADMINISTRATOR)
                                            <form action="{{ route('users.toggle-status', $user) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-amber-50 hover:text-amber-600 transition-all shadow-sm border border-transparent hover:border-amber-200" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @if($user->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            @endif

                                            @if($user->hasTwoFactor() && $user->id !== auth()->id())
                                                <form action="{{ route('users.two-factor.reset', $user) }}" method="POST" onsubmit="return confirm('Reset verifikasi dua langkah {{ addslashes($user->name) }}? Pakai bila HP & kode cadangannya hilang. Ia harus mengaktifkan ulang.')">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl border border-transparent bg-gray-50 px-2 py-2 text-xs font-semibold text-gray-500 shadow-sm transition-all hover:border-amber-200 hover:bg-amber-50 hover:text-amber-600 dark:bg-gray-700 dark:text-gray-400" title="Reset verifikasi dua langkah">2FA</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('users.reset-sessions', $user) }}" method="POST" onsubmit="return confirm('Keluarkan pengguna ini dari semua perangkat?')">
                                                @csrf
                                                <button type="submit" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-transparent hover:border-indigo-200" title="Atur Ulang Semua Sesi">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                </button>
                                            </form>

                                            <a href="{{ route('users.edit', $user) }}" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-transparent hover:border-indigo-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>

                                            @if(auth()->user()->isAdministrator() && $user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus akun ini secara permanen? Akun yang sudah punya riwayat (tiket, komisi, transaksi, log) tidak bisa dihapus — nonaktifkan saja.')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all shadow-sm border border-transparent hover:border-red-200" title="Hapus Akun">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
