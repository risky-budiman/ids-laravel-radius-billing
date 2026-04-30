<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('olts.index') }}" class="mr-4 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $olt->name }}
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('olts.edit', $olt->id) }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Edit OLT
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- OLT Info Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Device Status</h3>
                <div class="flex items-center mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl">
                    <div class="w-3 h-3 rounded-full {{ $olt->is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }} mr-3"></div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $olt->is_active ? 'ACTIVE & OPERATIONAL' : 'INACTIVE' }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">IP Management</span>
                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $olt->ip_address }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">Model / Type</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $olt->olt_type }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">SNMP Write Community</span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ $olt->snmp_write_community }}</span>
                    </div>
                </div>
            </div>

            <!-- Generate Ports Card -->
            <div class="glass bg-indigo-600 rounded-2xl p-6 text-white shadow-lg shadow-indigo-500/30">
                <h3 class="font-bold text-lg mb-2">Initialize PON Ports</h3>
                <p class="text-indigo-100 text-sm mb-6">Generate PON ports for this OLT to start monitoring subscribers.</p>
                
                <form action="{{ route('olts.auto-discover-ports', $olt->id) }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-white text-indigo-600 rounded-xl font-bold hover:bg-indigo-50 transition-all shadow-lg flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Auto Discover Ports
                    </button>
                </form>

                <div class="flex items-center my-4">
                    <div class="flex-grow border-t border-indigo-400"></div>
                    <span class="mx-3 text-xs text-indigo-200 uppercase font-bold">Or Manual</span>
                    <div class="flex-grow border-t border-indigo-400"></div>
                </div>
                
                <form action="{{ route('olts.generate-ports', $olt->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs text-indigo-200 uppercase mb-1">Board Slot</label>
                        <input type="number" name="slot" value="1" class="w-full bg-indigo-500 border-none rounded-xl text-white placeholder-indigo-300 focus:ring-white transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs text-indigo-200 uppercase mb-1">Port Count</label>
                        <select name="port_count" class="w-full bg-indigo-500 border-none rounded-xl text-white focus:ring-white transition-all">
                            <option value="8">8 Ports</option>
                            <option value="16" selected>16 Ports</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-700 text-white rounded-xl font-bold hover:bg-indigo-800 transition-all shadow-lg">
                        Generate Manual
                    </button>
                </form>
            </div>
        </div>

        <!-- PON Ports List -->
        <div class="lg:col-span-2">
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-white">Physical PON Ports ({{ $olt->ponPorts->count() }})</h3>
                    <div class="flex space-x-2">
                        <span class="flex items-center text-xs text-green-500"><span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span> Active</span>
                        <span class="flex items-center text-xs text-gray-400"><span class="w-2 h-2 bg-gray-300 rounded-full mr-1"></span> Empty</span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-4">
                        @foreach($olt->ponPorts->sortBy(['slot', 'pon_port']) as $port)
                            <a href="{{ route('olts.show-port', [$olt->id, $port->id]) }}" class="group relative flex flex-col items-center p-4 {{ $port->status == 'active' ? 'bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-900/30' : 'bg-gray-50 dark:bg-gray-700/50' }} rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all cursor-pointer border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800">
                                @if($port->status == 'active')
                                    <div class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full shadow-sm shadow-green-500/50"></div>
                                @endif
                                
                                <div class="w-8 h-8 rounded-lg {{ $port->status == 'active' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} flex items-center justify-center font-bold text-xs mb-2 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ $port->pon_port }}
                                </div>
                                <span class="text-[10px] {{ $port->status == 'active' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }} uppercase font-bold tracking-tighter">Slot {{ $port->slot }}</span>
                                
                                <!-- Tooltip/Hover effect -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-indigo-600/10 rounded-2xl">
                                    <span class="bg-indigo-600 text-white text-[10px] px-2 py-1 rounded-md font-bold shadow-lg">VIEW ONU</span>
                                </div>
                            </a>
                        @endforeach

                        @if($olt->ponPorts->isEmpty())
                            <div class="col-span-full py-12 text-center">
                                <p class="text-gray-500 dark:text-gray-400 italic">No ports generated for this OLT yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mt-6 glass bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Diagnostic Tools</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl text-left hover:bg-gray-100 transition-all group">
                        <span class="block font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600">Scan Unconfigured ONUs</span>
                        <span class="text-xs text-gray-500">Detect new modems via SNMP walk</span>
                    </button>
                    <form action="{{ route('olts.sync-all-ports', $olt->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl text-left hover:bg-gray-100 transition-all group">
                            <span class="block font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600">Sync Port Status</span>
                            <span class="text-xs text-gray-500">Refresh physical port metrics in background</span>
                        </button>
                    </form>
                    <button onclick="document.getElementById('manual-delete-modal').classList.remove('hidden')" class="p-4 bg-rose-50 dark:bg-rose-900/20 rounded-2xl text-left hover:bg-rose-100 transition-all group md:col-span-2">
                        <span class="block font-bold text-rose-700 dark:text-rose-400 group-hover:text-rose-800">Manual Delete ONU</span>
                        <span class="text-xs text-rose-500">Force delete an ONU configuration directly from OLT</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Delete Modal -->
    <div id="manual-delete-modal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Force Delete ONU</h3>
                    <p class="text-sm text-gray-500 mt-1">WARNING: This action bypasses billing data and deletes the ONU directly from the OLT.</p>
                </div>
                <button onclick="document.getElementById('manual-delete-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="{{ route('olts.manual-delete-onu', $olt->id) }}" method="POST">
                @csrf
                <div class="space-y-4 mb-8">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ONU Index (Position)</label>
                        <input type="text" name="onu_index" placeholder="e.g. .1.1.1.1" required class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-rose-500 focus:border-rose-500 font-mono">
                        <p class="text-xs text-gray-500 mt-1">Format: .shelf.slot.port.onu_id</p>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="document.getElementById('manual-delete-modal').classList.add('hidden')" class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 transition-colors">Cancel</button>
                    <button type="submit" onclick="return confirm('Are you absolutely sure you want to delete this ONU from the OLT?');" class="flex-1 py-3 bg-rose-600 text-white rounded-xl font-bold hover:bg-rose-700 transition-colors shadow-lg shadow-rose-500/30">Delete ONU</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
