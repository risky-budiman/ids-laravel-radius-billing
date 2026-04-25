<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Subscribers') }}
            </h2>
            <a href="{{ route('customers.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Add Subscriber
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer ID</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Password</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subscriber Name</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">
                                {{ $customer->customer_code ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-gray-600 dark:text-gray-400 text-xs">
                                {{ $customer->username }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-[10px] font-black text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded border border-gray-100 dark:border-gray-700/50 inline-block">
                                    {{ $customer->password }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->name }}</div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $customer->phone ?: 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                @if($customer->package)
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-full text-xs font-medium">{{ $customer->package->name }}</span>
                                @else
                                    <span class="text-gray-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'waiting_activation' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'suspended' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                                        'waiting_dismantle' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        'dismantled' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                        'canceled' => 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColors[$customer->status] ?? 'bg-gray-100' }}">
                                    {{ str_replace('_', ' ', $customer->status ?? ($customer->is_active ? 'active' : 'inactive')) }}
                                </span>
                            </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end space-x-3 group">
                                            @if($customer->status === 'waiting_activation' || (!$customer->is_active && $customer->status === 'new'))
                                                @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi())
                                                <a href="{{ route('customers.activate', $customer) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-xl text-[10px] font-bold transition-all shadow-lg shadow-indigo-600/20">
                                                    ACTIVATE
                                                </a>
                                                @endif
                                            @endif

                                            @if($customer->status === 'active' || ($customer->is_active && $customer->status !== 'waiting_dismantle'))
                                                @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi())
                                                <form action="{{ route('customers.request-dismantle', $customer) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan dismantle untuk pelanggan ini?')">
                                                    @csrf
                                                    <button type="submit" class="bg-rose-100 hover:bg-rose-200 text-rose-700 px-3 py-1.5 rounded-xl text-[10px] font-bold transition-all">
                                                        DISMANTLE
                                                    </button>
                                                </form>
                                                @endif
                                            @endif

                                            @if($customer->status === 'waiting_dismantle')
                                                <a href="{{ route('customers.dismantle', $customer) }}" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-xl text-[10px] font-bold transition-all shadow-lg shadow-amber-600/20">
                                                    COLLECT GEAR
                                                </a>
                                            @endif

                                            <a href="{{ route('customers.show', $customer) }}" class="text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 text-xs font-bold transition-colors">Details</a>
                                            <a href="{{ route('customers.edit', $customer) }}" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 text-xs font-bold transition-colors">Edit</a>
                                            
                                            @if(auth()->user()->isAdmin())
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Hapus pelanggan ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                No subscribers found. Click "Add Subscriber" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
