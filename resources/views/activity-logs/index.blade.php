<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('System Activity Logs') }}
            </h2>
            <div class="flex space-x-3">
                <span class="px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-widest rounded-xl border border-indigo-100 dark:border-indigo-800">
                    Live Audit Trail
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-premium bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-[2.5rem] border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <!-- Filter Bar -->
                    <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800">
                        <form action="{{ route('activity-logs.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">User</label>
                                <select name="user_id" class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                    <option value="system" {{ request('user_id') === 'system' ? 'selected' : '' }}>System</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Action</label>
                                <select name="action" class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Actions</option>
                                    <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                    <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                    <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                    <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Login</option>
                                    <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Logout</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Date Start</label>
                                <input type="date" name="date_start" value="{{ request('date_start') }}" class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Date End</label>
                                <input type="date" name="date_end" value="{{ request('date_end') }}" class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="flex items-end space-x-2">
                                <div class="flex-grow">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <button type="submit" class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-200 dark:shadow-none transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                                <a href="{{ route('activity-logs.index') }}" class="p-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-xl transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">#</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">User</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Action</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Description</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">IP Address</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                @forelse($activities as $activity)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30 transition-all group">
                                        <td class="px-6 py-5 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                            {{ $activities->firstItem() + $loop->index }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs mr-3 border border-indigo-200 dark:border-indigo-800">
                                                    {{ substr($activity->user->name ?? 'S', 0, 1) }}
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $activity->user->name ?? 'System' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            @if($activity->action === 'created')
                                                <span class="px-3 py-1 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[10px] font-black uppercase tracking-widest rounded-full">Created</span>
                                            @elseif($activity->action === 'updated')
                                                <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest rounded-full">Updated</span>
                                            @elseif($activity->action === 'deleted')
                                                <span class="px-3 py-1 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-black uppercase tracking-widest rounded-full">Deleted</span>
                                            @else
                                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest rounded-full">{{ $activity->action }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-5">
                                            <p class="text-sm text-gray-900 dark:text-gray-100 font-bold mb-1">{{ $activity->description }}</p>
                                            
                                            <!-- Detail Perubahan (Diff) -->
                                            @if($activity->properties)
                                                <div class="mt-2 space-y-1">
                                                    @if(isset($activity->properties['old']) && $activity->action === 'updated')
                                                        @foreach($activity->properties['new'] as $key => $value)
                                                            @if(in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at'])) @continue @endif
                                                            <div class="text-[10px] flex items-center space-x-2">
                                                                <span class="font-mono text-gray-400 dark:text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}:</span>
                                                                <span class="px-1.5 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded border border-red-100 dark:border-red-900/30 line-through opacity-70">
                                                                    {{ is_array($activity->properties['old'][$key] ?? '') ? json_encode($activity->properties['old'][$key]) : ($activity->properties['old'][$key] ?? 'N/A') }}
                                                                </span>
                                                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                                                                <span class="px-1.5 py-0.5 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded border border-green-100 dark:border-green-800/30 font-bold">
                                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    @elseif(isset($activity->properties['old']) && $activity->action === 'deleted')
                                                        <div class="text-[10px] text-gray-500 dark:text-gray-400 italic bg-gray-50 dark:bg-gray-900/50 p-2 rounded-lg border border-gray-100 dark:border-gray-800">
                                                            Pernah memiliki data: 
                                                            @php 
                                                                $filteredOld = array_diff_key($activity->properties['old'], array_flip(['id', 'created_at', 'updated_at', 'deleted_at']));
                                                            @endphp
                                                            @foreach(array_slice($filteredOld, 0, 8) as $key => $val)
                                                                <span class="font-bold">{{ str_replace('_', ' ', $key) }}</span>: {{ is_array($val) ? '...' : $val }}@if(!$loop->last), @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                <svg class="w-3 h-3 mr-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                {{ $activity->ip_address }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $activity->created_at->format('M d, Y H:i:s') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <p>No activity logs found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8">
                        {{ $activities->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
