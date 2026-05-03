<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Master Data: STOs (Sentral Telepon Otomat)') }}
        </h2>
    </x-slot>

    <div 
        class="max-w-6xl mx-auto space-y-8"
        x-data="{ 
            editModalOpen: false, 
            editSto: {},
            mapPickerOpen: false,
            targetLatId: '',
            targetLonId: '',
            map: null,
            marker: null,
            
            initMap() {
                setTimeout(() => {
                    if (this.map) {
                        this.map.remove();
                    }
                    
                    const initialLat = document.getElementById(this.targetLatId).value || -6.200000;
                    const initialLon = document.getElementById(this.targetLonId).value || 106.816666;
                    
                    this.map = L.map('map-picker-container').setView([initialLat, initialLon], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                    
                    this.marker = L.marker([initialLat, initialLon], {draggable: true}).addTo(this.map);
                    
                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                    });
                }, 100);
            },
            
            confirmPick() {
                const pos = this.marker.getLatLng();
                document.getElementById(this.targetLatId).value = pos.lat.toFixed(6);
                document.getElementById(this.targetLonId).value = pos.lng.toFixed(6);
                document.getElementById(this.targetLatId).dispatchEvent(new Event('input'));
                document.getElementById(this.targetLonId).dispatchEvent(new Event('input'));
                this.mapPickerOpen = false;
            }
        }"
    >
        @if(session('success'))
            <div class="px-5 py-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl font-medium shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- Card Input Form -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-3xl shadow-lg border border-gray-100/50 dark:border-gray-700/50 p-8 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-fuchsia-50 dark:bg-fuchsia-900/10 rounded-full blur-3xl -mr-20 -mt-20 z-0"></div>
            
            <div class="relative z-10">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-rose-600 flex items-center justify-center shadow-inner mr-4 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 tracking-tight">Add STO (Branch Office)</h3>
                </div>

                <form action="{{ route('locations.sto.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-6 items-end">
                    @csrf
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Parent Region</label>
                        <select name="region_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" required>
                            <option value="">-- Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">STO Name</label>
                        <input type="text" name="name" placeholder="E.g. Jakarta Selatan" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" required />
                    </div>
                    <div class="md:col-span-1 relative">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Latitude</label>
                        <input type="text" name="latitude" id="create_lat" placeholder="-6.123" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" />
                        <button type="button" @click="targetLatId = 'create_lat'; targetLonId = 'create_lon'; mapPickerOpen = true; initMap()" class="absolute right-2 bottom-3 text-rose-600 hover:text-rose-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </button>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Longitude</label>
                        <input type="text" name="longitude" id="create_lon" placeholder="106.123" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 shadow-sm focus:border-fuchsia-500 focus:ring-4 focus:ring-fuchsia-500/10 transition-all px-4 py-3" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-fuchsia-600 to-rose-600 text-white px-6 py-3 rounded-xl hover:from-fuchsia-700 hover:to-rose-700 font-bold shadow-lg shadow-fuchsia-500/30 transform hover:-translate-y-0.5 transition-all outline-none focus:ring-4 focus:ring-fuchsia-500/30 whitespace-nowrap">
                            Save STO
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap align-middle">
                    <thead class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">#</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">STO Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Coordinates</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Region Master</th>
                            <th class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">Child STBs</th>
                            <th class="px-6 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($stos as $s)
                        <tr class="hover:bg-fuchsia-50/30 dark:hover:bg-fuchsia-900/10 transition-colors group">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $loop->iteration }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-mono font-bold px-3 py-1.5 rounded-lg text-sm border border-gray-200 dark:border-gray-600">
                                    {{ $s->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $s->name }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                @if($s->latitude)
                                    <span class="block">Lat: {{ $s->latitude }}</span>
                                    <span class="block">Lon: {{ $s->longitude }}</span>
                                @else
                                    <span class="italic text-gray-400 text-[10px]">No Coordinates</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($s->region)
                                    <span class="inline-flex items-center text-xs text-gray-400 font-mono mr-1">[{{ $s->region->code }}]</span> 
                                    {{ $s->region->name }}
                                @else
                                    <span class="text-red-400 italic">No Region</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                                    {{ $s->stbs->count() }} STBs
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-3">
                                    <button 
                                        @click='editSto = @json($s); editModalOpen = true'
                                        class="text-rose-600 hover:text-rose-900 font-bold text-sm"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('locations.sto.destroy', $s) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 font-bold text-sm" onclick="return confirm('Delete STO? Ensure no dependent STBs exist!');">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal -->
        <div x-show="editModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="editModalOpen = false"></div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg p-8 relative z-10 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit STO: <span x-text="editSto.code" class="text-rose-500"></span></h3>
                    
                    <form :action="`{{ url('admin/locations/stos') }}/${editSto.id}`" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Parent Region</label>
                            <select name="region_id" x-model="editSto.region_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-4 py-3" required>
                                @foreach($regions as $r)
                                    <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">STO Name</label>
                            <input type="text" name="name" x-model="editSto.name" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-4 py-3" required />
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Latitude</label>
                                <input type="text" name="latitude" id="edit_lat" x-model="editSto.latitude" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-4 py-3" />
                                <button type="button" @click="targetLatId = 'edit_lat'; targetLonId = 'edit_lon'; mapPickerOpen = true; initMap()" class="absolute right-2 bottom-3 text-rose-600 hover:text-rose-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Longitude</label>
                                <input type="text" name="longitude" id="edit_lon" x-model="editSto.longitude" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 px-4 py-3" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8">
                            <button type="button" @click="editModalOpen = false" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold">Cancel</button>
                            <button type="submit" class="px-10 py-3 bg-rose-600 text-white rounded-xl font-bold shadow-lg">Update STO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Map Picker Modal -->
        <div x-show="mapPickerOpen" class="fixed inset-0 z-[60] overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md" @click="mapPickerOpen = false"></div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-4xl h-[80vh] flex flex-col relative z-10 overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pick Location</h3>
                        <p class="text-sm text-gray-500">Click on map or drag marker to set position</p>
                    </div>
                    <div id="map-picker-container" class="flex-grow"></div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
                        <button type="button" @click="mapPickerOpen = false" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg font-bold">Cancel</button>
                        <button type="button" @click="confirmPick()" class="px-10 py-2 bg-rose-600 text-white rounded-lg font-bold shadow-lg">Confirm Location</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        [x-cloak] { display: none !important; }
        #map-picker-container { width: 100%; height: 100%; z-index: 1; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</x-app-layout>
