<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('OLT Management') }}
            </h2>
            <a href="{{ route('olts.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Add OLT
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @foreach($olts as $olt)
            <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col h-full hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl mr-4 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $olt->name }}</h3>
                            <p class="text-sm text-gray-500 font-mono">{{ $olt->ip_address }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 {{ $olt->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700' }} rounded-lg text-xs font-bold uppercase tracking-wider">
                        {{ $olt->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="flex-grow">
                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl">
                            <span class="block text-gray-400 text-xs uppercase mb-1">Type</span>
                            <span class="font-semibold">{{ $olt->olt_type }}</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl">
                            <span class="block text-gray-400 text-xs uppercase mb-1">PON Ports</span>
                            <span class="font-semibold">{{ $olt->pon_ports_count }} Ports</span>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700">
                            <span>SNMP Port</span>
                            <span class="font-mono">{{ $olt->snmp_port }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700">
                            <span>Read Community</span>
                            <span class="font-mono text-xs">{{ Str::mask($olt->snmp_read_community, '*', 2, -2) }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>Telnet Port</span>
                            <span class="font-mono">{{ $olt->telnet_port }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button onclick="testConnection({{ $olt->id }})" id="test-btn-{{ $olt->id }}" class="flex items-center justify-center px-2 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition-all" title="Test SNMP">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </button>
                    <a href="{{ route('olts.show', $olt->id) }}" class="flex items-center justify-center px-2 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-xl hover:bg-indigo-100 transition-all">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Manage
                    </a>
                    <form action="{{ route('olts.destroy', $olt->id) }}" method="POST" class="flex" onsubmit="return confirm('WARNING: Are you sure you want to delete this OLT?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center px-2 py-2 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-xl hover:bg-rose-100 transition-all" title="Delete OLT">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach

        @if($olts->isEmpty())
            <div class="col-span-full glass bg-white dark:bg-gray-800 rounded-2xl p-12 text-center">
                <div class="inline-flex p-4 bg-gray-50 dark:bg-gray-700/50 rounded-2xl mb-4">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">No OLT devices found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Start by adding your first OLT device to the system.</p>
                <a href="{{ route('olts.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all">Add First OLT</a>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function testConnection(id) {
            const btn = document.getElementById(`test-btn-${id}`);
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Testing...`;
            
            fetch(`/olts/${id}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while testing connection.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }
    </script>
    @endpush
</x-app-layout>
