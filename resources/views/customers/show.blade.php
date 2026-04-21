<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('customers.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    Subscriber Profile: {{ $customer->name }}
                </h2>
            </div>
            <div class="flex space-x-3">
                @php 
                    $hasInstalled = \App\Models\InventoryStock::where('customer_id', $customer->id)->where('status', 'installed')->exists();
                @endphp
                @if($customer->is_active && $hasInstalled)
                    <a href="{{ route('customers.dismantle', $customer) }}" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-6 py-2 rounded-xl text-sm font-bold transition-all border border-rose-100 shadow-sm shadow-rose-500/10">
                        Dismantle
                    </a>
                @endif
                @if(!$customer->is_active)
                    <a href="{{ route('customers.activate', $customer) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-600/20">
                        Activate Now
                    </a>
                @endif
                <a href="{{ route('customers.edit', $customer) }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl text-sm font-bold transition-all hover:bg-gray-200">
                    Edit Profile
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: Subscriber Info -->
        <div class="space-y-6">
            <div class="glass bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 mx-auto font-black text-2xl shadow-inner">
                        {{ substr($customer->name, 0, 1) }}
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ $customer->name }}</h3>
                    <p class="text-xs text-gray-400 font-mono">@ {{ $customer->username }}</p>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Status:</span>
                        @if($customer->is_active)
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-black rounded uppercase">Active</span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-400 text-[10px] font-black rounded uppercase">Inactive</span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Package:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $customer->package ? $customer->package->name : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500">Joined Ate:</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $customer->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Contact Card -->
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Contact Details</h4>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $customer->phone ?: 'Not provided' }}</p>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $customer->email ?: 'Not provided' }}</p>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <p class="text-xs text-gray-700 dark:text-gray-300">{{ $customer->address ?: 'No address registered' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Tabs -->
        <div class="lg:col-span-2 space-y-8" x-data="{ tab: 'assets' }">
            
            <!-- Tab Navigation -->
            <div class="flex space-x-6 border-b border-gray-100 dark:border-gray-800">
                <button @click="tab = 'assets'" :class="tab === 'assets' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Installed Assets</button>
                <button @click="tab = 'billing'" :class="tab === 'billing' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Billing History</button>
            </div>

            <!-- Tab Content: Assets -->
            <div x-show="tab === 'assets'" x-transition class="space-y-6">
                <!-- Serialized Assets (Modem etc) -->
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-widest">Main Equipment</h4>
                    </div>
                    <div class="p-8">
                        @php 
                            $modem = \App\Models\InventoryStock::where('customer_id', $customer->id)
                                ->where('status', 'installed')
                                ->with('item')
                                ->first();
                        @endphp

                        @if($modem)
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <div class="ml-6">
                                    <h5 class="text-sm font-bold text-gray-900 dark:text-white">{{ $modem->item->name }}</h5>
                                    <p class="text-xs text-gray-400 font-mono uppercase">SN: {{ $modem->serial_number }}</p>
                                </div>
                                <div class="ml-auto flex items-center space-x-4">
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 text-[10px] font-black rounded uppercase tracking-widest">Installed</span>
                                    <a href="{{ route('customers.dismantle', $customer) }}" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 uppercase tracking-widest">Dismantle →</a>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6 border-2 border-dashed border-gray-50 dark:border-gray-800 rounded-3xl">
                                <p class="text-xs text-gray-400 italic">No equipment recorded for this customer.</p>
                                @if(!$customer->is_active)
                                    <a href="{{ route('customers.activate', $customer) }}" class="inline-block mt-3 text-[10px] font-bold text-indigo-600 uppercase">Run Activation Wizard →</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Consumable Usage (Cables etc) -->
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                        <h4 class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-widest">Calculated Materials (Cables/Con)</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @php 
                                    $materials = \App\Models\InventoryMovement::where('customer_id', $customer->id)
                                        ->where('type', 'out')
                                        ->with('item')
                                        ->get();
                                @endphp
                                @forelse($materials as $material)
                                    @continue($modem && $material->inventory_item_id == $modem->inventory_item_id) {{-- Skip modem movement in material list if already shown --}}
                                    <tr>
                                        <td class="px-8 py-4">
                                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $material->item->name }}</p>
                                            <p class="text-[10px] text-gray-400 uppercase tracking-widest">{{ $material->reference }}</p>
                                        </td>
                                        <td class="px-8 py-4 text-xs font-black text-indigo-600 text-right">
                                            {{ number_format($material->quantity, 1) }} {{ $material->item->unit }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-8 py-10 text-center text-gray-400 text-xs italic">No materials consumption logged.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Billing (Simple placeholder for now) -->
            <div x-show="tab === 'billing'" x-transition class="glass bg-white dark:bg-gray-800 p-12 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-sm text-gray-400 italic">Integrate with Invoice module to see detailed billing history here.</p>
                <a href="{{ route('invoices.index') }}" class="inline-block mt-4 text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Manage Invoices →</a>
            </div>

        </div>
    </div>
</x-app-layout>
