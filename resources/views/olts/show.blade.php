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
                @if(auth()->user()->isAdministrator())
                <a href="{{ route('olts.edit', $olt->id) }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Ubah OLT
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- OLT Info Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                <h3 class="text-sm font-bold text-gray-400  mb-4">Status Perangkat</h3>
                <div class="flex items-center mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                    <div class="w-3 h-3 rounded-full {{ $olt->is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }} mr-3"></div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $olt->is_active ? 'ACTIVE & OPERATIONAL' : 'INACTIVE' }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">IP Management</span>
                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $olt->ip_address }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">Model / Tipe</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $olt->olt_type }}</span>
                    </div>
                    <div>
                        {{-- Dulu community WRITE tampil terbuka di sini (juga untuk teknisi) --}}
                        <span class="block text-xs text-gray-400 uppercase">Versi SNMP</span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ (int) $olt->snmp_version === 3 ? 'v3' : ((int) $olt->snmp_version === 1 ? 'v1' : 'v2c') }}</span>
                    </div>
                </div>
            </div>

            <!-- Generate Ports Card -->
            <div class="glass bg-indigo-600 rounded-xl p-6 text-white shadow-sm">
                <h3 class="font-bold text-lg mb-2">Buat Port PON</h3>
                <p class="text-indigo-100 text-sm mb-6">Buat port PON untuk OLT ini agar pelanggan bisa dipantau.</p>
                
                <form action="{{ route('olts.auto-discover-ports', $olt->id) }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-white text-indigo-600 rounded-xl font-bold hover:bg-indigo-50 transition-all shadow-lg flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Deteksi Port Otomatis
                    </button>
                </form>

                <div class="flex items-center my-4">
                    <div class="flex-grow border-t border-indigo-400"></div>
                    <span class="mx-3 text-xs text-indigo-200 uppercase font-bold">Atau Manual</span>
                    <div class="flex-grow border-t border-indigo-400"></div>
                </div>
                
                <form action="{{ route('olts.generate-ports', $olt->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs text-indigo-200 uppercase mb-1">Board Slot</label>
                        <input type="number" name="slot" value="1" class="w-full bg-indigo-500 border-none rounded-xl text-white placeholder-indigo-300 focus:ring-white transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs text-indigo-200 uppercase mb-1">Jumlah Port</label>
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
            <div class="glass bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-white">Physical PON Ports ({{ $olt->ponPorts->count() }})</h3>
                    <div class="flex space-x-2">
                        <span class="flex items-center text-xs text-green-500"><span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span> Aktif</span>
                        <span class="flex items-center text-xs text-gray-400"><span class="w-2 h-2 bg-gray-300 rounded-full mr-1"></span> Kosong</span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-4">
                        @foreach($olt->ponPorts->sortBy(['slot', 'pon_port']) as $port)
                            <a href="{{ route('olts.show-port', [$olt->id, $port->id]) }}" class="group relative flex flex-col items-center p-4 {{ $port->status == 'active' ? 'bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-900/30' : 'bg-gray-50 dark:bg-gray-700/50' }} rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all cursor-pointer border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800">
                                @if($port->status == 'active')
                                    <div class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full shadow-sm shadow-green-500/50"></div>
                                @endif
                                
                                <div class="w-8 h-8 rounded-lg {{ $port->status == 'active' ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} flex items-center justify-center font-bold text-xs mb-2 shadow-sm group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ $port->pon_port }}
                                </div>
                                <span class="text-xs {{ $port->status == 'active' ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }} uppercase font-bold tracking-tighter">Slot {{ $port->slot }}</span>
                                
                                <!-- Tooltip/Hover effect -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-indigo-600/10 rounded-xl">
                                    <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-md font-bold shadow-lg">LIHAT ONU</span>
                                </div>
                            </a>
                        @endforeach

                        @if($olt->ponPorts->isEmpty())
                            <div class="col-span-full py-12 text-center">
                                <p class="text-gray-500 dark:text-gray-400 italic">Port untuk OLT ini belum dibuat.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mt-6 glass bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Diagnostic Tools</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl text-left hover:bg-gray-100 transition-all group">
                        <span class="block font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600">Scan Unconfigured ONUs</span>
                        <span class="text-xs text-gray-500">Deteksi modem baru lewat SNMP walk</span>
                    </button>
                    <form action="{{ route('olts.sync-all-ports', $olt->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full p-4 bg-gray-50 dark:bg-gray-900 rounded-xl text-left hover:bg-gray-100 transition-all group">
                            <span class="block font-bold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600">Sinkron Status Port</span>
                            <span class="text-xs text-gray-500">Perbarui metrik port fisik di latar belakang</span>
                        </button>
                    </form>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl md:col-span-2">
                        <span class="block font-bold text-gray-600 dark:text-gray-300">Akses OLT: SNMP saja</span>
                        <span class="text-xs text-gray-500">Registrasi, hapus &amp; ganti profil ONU dikerjakan langsung di OLT. Sistem mencatat pekerjaan yang perlu dilakukan di Log Aktivitas.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
