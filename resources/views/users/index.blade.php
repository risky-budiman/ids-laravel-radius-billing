<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-black text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('System User Management') }}
            </h2>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-2xl font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg shadow-indigo-500/30">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add New Staff
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">User Profile</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Role</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Last Active</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach($users as $user)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/50 transition-all group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-md">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
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
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-tighter rounded-full {{ $roleClasses[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        @if($user->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 animate-pulse-slow">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-center text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <div class="flex justify-end items-center space-x-2">
                                            <form action="{{ route('users.toggle-status', $user) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-amber-50 hover:text-amber-600 transition-all shadow-sm border border-transparent hover:border-amber-200" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                                    @if($user->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            <a href="{{ route('users.edit', $user) }}" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all shadow-sm border border-transparent hover:border-indigo-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>

                                            @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all shadow-sm border border-transparent hover:border-red-200">
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
