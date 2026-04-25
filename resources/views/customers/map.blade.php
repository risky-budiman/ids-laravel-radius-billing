<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-2xl rounded-3xl border border-gray-200 dark:border-gray-800">
                <div class="p-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                        <div>
                            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                Customer Location Map
                            </h2>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">
                                Visualizing all active customer locations across the network.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2 rounded-xl border border-indigo-100 dark:border-indigo-900/30">
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Total Database:</span>
                                <span class="text-sm font-black text-indigo-700 dark:text-indigo-300">{{ $customers->count() }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-xl border border-emerald-100 dark:border-emerald-900/30">
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Markers Rendered:</span>
                                <span id="marker-count" class="text-sm font-black text-emerald-700 dark:text-emerald-300">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div class="relative group">
                        <div id="customer-map" style="height: 600px; width: 100%;" class="rounded-2xl border-4 border-white dark:border-gray-800 shadow-xl overflow-hidden z-0 bg-gray-100 dark:bg-gray-800"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .leaflet-popup-content-wrapper { border-radius: 12px; padding: 0; overflow: hidden; }
        .leaflet-popup-content { margin: 0; width: 250px !important; }
        
        /* Legend styling for Leaflet Control */
        .map-legend-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            border: 1px solid rgba(0,0,0,0.1);
            min-width: 150px;
        }
        .dark .map-legend-box {
            background: rgba(17, 24, 39, 0.95);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customers = @json($customers);
            console.log('Mapped Customers:', customers);

            let center = [-6.200000, 106.816666]; // Default Jakarta
            
            if (customers.length > 0 && customers[0].latitude && customers[0].longitude) {
                center = [parseFloat(customers[0].latitude), parseFloat(customers[0].longitude)];
            }

            const map = L.map('customer-map').setView(center, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            // Create Legend Control
            const legend = L.control({position: 'topright'});
            legend.onAdd = function (map) {
                const div = L.DomUtil.create('div', 'map-legend-box');
                div.innerHTML = `
                    <h4 style="margin: 0 0 10px 0; font-size: 10px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em;">Map Legend</h4>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: #6366f1; box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);"></div>
                        <span style="font-size: 13px; font-weight: 600;">Active Customer</span>
                    </div>
                `;
                return div;
            };
            legend.addTo(map);

            const markers = [];
            let renderedCount = 0;

            customers.forEach(customer => {
                if (customer.latitude && customer.longitude) {
                    const lat = parseFloat(customer.latitude);
                    const lng = parseFloat(customer.longitude);
                    
                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        renderedCount++;
                        const marker = L.marker([lat, lng]).addTo(map);
                        
                        const popupContent = `
                            <div class="bg-white dark:bg-gray-900 overflow-hidden rounded-lg shadow-lg" style="min-width: 200px;">
                                <div class="h-1 bg-indigo-500"></div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base mb-2">${customer.name}</h3>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] uppercase font-bold text-gray-400">Package</span>
                                            <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">${customer.package ? customer.package.name : 'N/A'}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] uppercase font-bold text-gray-400">Status</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${customer.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">
                                                ${customer.is_active ? 'ACTIVE' : 'INACTIVE'}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                                        <a href="/customers/${customer.id}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-500 transition-colors">
                                            View Profile
                                            <svg class="ml-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        marker.bindPopup(popupContent);
                        markers.push(marker);
                    }
                }
            });

            document.getElementById('marker-count').textContent = renderedCount;

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            } else {
                console.warn('No valid markers found to display on map.');
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        map.setView([position.coords.latitude, position.coords.longitude], 13);
                    });
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
