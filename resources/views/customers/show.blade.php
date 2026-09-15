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
                @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi())
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
                    
                    <div class="mt-3 flex justify-center space-x-2">
                        @php
                            $typeColors = [
                                'personal' => 'bg-blue-50 text-blue-600 border-blue-100',
                                'corporate' => 'bg-purple-50 text-purple-600 border-purple-100',
                                'vip' => 'bg-amber-50 text-amber-600 border-amber-100',
                            ];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $typeColors[$customer->customer_type] ?? 'bg-gray-50' }}">
                            {{ $customer->customer_type }}
                        </span>
                    </div>

                    @if($customer->ktp)
                        <p class="text-[10px] text-gray-400 mt-1 font-mono">ID: {{ $customer->ktp }}</p>
                    @endif
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
                    @if(!empty($customer->discount_value) && $customer->discount_value > 0)
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Diskon Pelanggan:</span>
                        <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold text-xs rounded-lg">
                            {{ $customer->discount_type === 'percentage' ? rtrim(rtrim(number_format($customer->discount_value, 2), '0'), '.') . '%' : 'Rp ' . number_format($customer->discount_value, 0, ',', '.') }}
                        </span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center text-sm pt-4 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500">Password:</span>
                        @php 
                            $radCheck = \App\Models\Radius\RadCheck::where('username', $customer->username)->first();
                        @endphp
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $radCheck ? $radCheck->value : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
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
                        <svg class="w-4 h-4 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"></path></svg>
                        <p class="text-xs text-gray-700 dark:text-gray-300 font-mono">{{ $customer->ktp ?: 'No KTP registered' }}</p>
                    </div>
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

            <!-- FUP Usage Card (Optional) -->
            @if(get_setting('enable_fup_module') == '1' && $customer->package && $customer->package->enable_fup)
            <div class="glass bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">FUP Statistics</h4>
                    @php 
                        $percentage = ($customer->package->fup_limit_gb > 0) ? ($customer->current_month_usage_gb / $customer->package->fup_limit_gb) * 100 : 0;
                        $colorClass = $percentage >= 100 ? 'bg-rose-500' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <span class="text-[10px] font-bold {{ $percentage >= 100 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ round($percentage, 1) }}% Used
                    </span>
                </div>
                
                <div class="space-y-4">
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="{{ $colorClass }} h-2 rounded-full transition-all duration-1000" style="width: {{ min(100, $percentage) }}%"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl">
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Usage</p>
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ $customer->current_month_usage_gb }} GB</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl">
                            <p class="text-[9px] text-gray-400 uppercase font-bold">Limit</p>
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ $customer->package->fup_limit_gb }} GB</p>
                        </div>
                    </div>

                    <p class="text-[10px] text-gray-400 italic text-center">Last sync: {{ $customer->last_usage_sync ? $customer->last_usage_sync->diffForHumans() : 'Never' }}</p>

                    @if(auth()->user()->isAdmin())
                    <form action="{{ route('customers.reset-fup', $customer) }}" method="POST" onsubmit="return confirm('Reset usage to 0 and restore normal speed?')">
                        @csrf
                        <button type="submit" class="w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase tracking-widest rounded-xl hover:opacity-90 transition-opacity">
                            Reset FUP Manually
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content: Tabs -->
        <div class="lg:col-span-2 space-y-8" x-data="{ tab: 'assets' }">
            
            <!-- Tab Navigation -->
            <div class="flex space-x-6 border-b border-gray-100 dark:border-gray-800">
                <button @click="tab = 'assets'" :class="tab === 'assets' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Installed Assets</button>
                <button @click="tab = 'enterprise'" :class="tab === 'enterprise' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Enterprise & KYC</button>
                <button @click="tab = 'billing'" :class="tab === 'billing' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Billing History</button>
                <button @click="tab = 'sessions'" :class="tab === 'sessions' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'" class="pb-4 px-2 border-b-2 font-bold text-sm transition-all">Session History</button>
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
                                    @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi())
                                        <a href="{{ route('customers.dismantle', $customer) }}" class="text-[10px] font-bold text-rose-500 hover:text-rose-700 uppercase tracking-widest">Dismantle →</a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6 border-2 border-dashed border-gray-50 dark:border-gray-800 rounded-3xl">
                                <p class="text-xs text-gray-400 italic">No equipment recorded for this customer.</p>
                                @if(!auth()->user()->isSales())
                                    @if(!$customer->is_active)
                                        <a href="{{ route('customers.activate', $customer) }}" class="inline-block mt-3 text-[10px] font-bold text-indigo-600 uppercase">Run Activation Wizard →</a>
                                    @endif
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

            <!-- Tab Content: Enterprise & KYC -->
            <div x-show="tab === 'enterprise'" x-transition class="space-y-8">
                <!-- Physical Infrastructure Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-8 py-4 bg-emerald-50/50 dark:bg-emerald-900/10 border-b border-gray-100 dark:border-gray-700">
                            <h4 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Physical Infrastructure</h4>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">ODC Cabinet:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->odc_id ?? 'Not Mapped' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">ODP Box:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->odp_id ?? 'Not Mapped' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">ODP Port:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->odp_port ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Drop Core:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->cable_length ? $customer->cable_length . ' Meters' : '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-8 py-4 bg-indigo-50/50 dark:bg-indigo-900/10 border-b border-gray-100 dark:border-gray-700">
                            <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">CPE & Logical Configuration</h4>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Service VLAN:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->vlan_id ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Static IP:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->static_ip ?? 'Dynamic' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">CPE Brand:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $customer->cpe_brand ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">CPE MAC:</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white font-mono">{{ $customer->cpe_mac ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KYC Documents / Photos -->
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-8 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">KYC Documents & Verification Photos</h4>
                    </div>
                    <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                        @foreach(['identity_photo' => 'Identity (KTP)', 'house_photo' => 'House/Location', 'cpe_photo' => 'Device (CPE)'] as $field => $label)
                            <div class="space-y-3">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block text-center">{{ $label }}</span>
                                <div class="aspect-video rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 overflow-hidden group relative">
                                    @if($customer->$field)
                                        <img src="{{ asset('storage/' . $customer->$field) }}" class="w-full h-full object-cover transition-transform group-hover:scale-110 cursor-pointer" onclick="window.open(this.src)">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-white text-[10px] font-bold uppercase tracking-widest">Click to Zoom</span>
                                        </div>
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002-2z"></path></svg>
                                            <span class="text-[9px] font-bold uppercase">No Image</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Special Notes -->
                @if($customer->description)
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-8">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Subscriber Notes</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 italic leading-relaxed whitespace-pre-wrap">{{ $customer->description }}</p>
                </div>
                @endif
            </div>

            <!-- Tab Content: Billing -->
            <div x-show="tab === 'billing'" x-transition class="glass bg-white dark:bg-gray-800 p-12 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-sm text-gray-400 italic">Integrate with Invoice module to see detailed billing history here.</p>
                <a href="{{ route('invoices.index') }}" class="inline-block mt-4 text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Manage Invoices →</a>
            </div>

            <!-- Tab Content: Session History -->
            <div x-show="tab === 'sessions'" x-transition class="space-y-6">
                <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">#</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Start Time</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">End Time</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Duration</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">IP Address</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">MAC Address</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">NAS/Router</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Cause</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Traffic</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($sessions as $session)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-[10px] font-medium">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $session->acctstarttime->format('d/m/Y') }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $session->acctstarttime->format('H:i:s') }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($session->acctstoptime)
                                                <div class="text-xs font-bold text-gray-900 dark:text-white">{{ $session->acctstoptime->format('d/m/Y') }}</div>
                                                <div class="text-[10px] text-gray-400">{{ $session->acctstoptime->format('H:i:s') }}</div>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 animate-pulse">Active</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                            {{ gmdate("H:i:s", $session->acctsessiontime) }}
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-700 dark:text-gray-300 font-mono">
                                            {{ $session->framedipaddress ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-gray-500 font-mono uppercase">
                                            {{ $session->callingstationid ?: '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-gray-600 dark:text-gray-400">
                                            {{ $session->nasipaddress }}
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-gray-500 italic">
                                            {{ $session->acctterminatecause ?: '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="text-[10px] font-bold text-emerald-600">↓ {{ number_format($session->acctoutputoctets / 1048576, 2) }} MB</div>
                                            <div class="text-[10px] font-bold text-indigo-600">↑ {{ number_format($session->acctinputoctets / 1048576, 2) }} MB</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-400 text-xs italic">No session history found for this subscriber.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
