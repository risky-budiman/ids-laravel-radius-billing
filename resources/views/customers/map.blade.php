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
            const infrastructure = @json($infrastructure);
            
            console.log('Mapped Customers:', customers);
            console.log('Infrastructure:', infrastructure);

            let center = [-6.200000, 106.816666]; // Default Jakarta
            
            if (customers.length > 0 && customers[0].latitude && customers[0].longitude) {
                center = [parseFloat(customers[0].latitude), parseFloat(customers[0].longitude)];
            } else if (infrastructure.olts && infrastructure.olts.length > 0) {
                center = [parseFloat(infrastructure.olts[0].latitude), parseFloat(infrastructure.olts[0].longitude)];
            }

            const map = L.map('customer-map').setView(center, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            // Icon Definitions
            const createIcon = (color) => {
                return new L.Icon({
                    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });
            };

            const icons = {
                customer: createIcon('blue'),
                olt: createIcon('red'),
                region: createIcon('gold'),
                sto: createIcon('green'),
                stb: createIcon('green'),
                odc: createIcon('orange'),
                odp: createIcon('violet')
            };

            // Create Legend Control
            const legend = L.control({position: 'topright'});
            legend.onAdd = function (map) {
                const div = L.DomUtil.create('div', 'map-legend-box');
                let html = `<h4 style="margin: 0 0 10px 0; font-size: 10px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em;">Map Legend</h4>`;
                
                const items = [
                    { label: 'Region', color: '#ffd700' },
                    { label: 'STO/STB', color: '#10b981' },
                    { label: 'ODC', color: '#f59e0b' },
                    { label: 'ODP', color: '#8b5cf6' },
                    { label: 'Customer', color: '#3b82f6' },
                    { label: 'Network Path', color: '#94a3b8', isLine: true }
                ];

                items.forEach(item => {
                    html += `
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            ${item.isLine 
                                ? `<div style="width: 20px; height: 2px; background: ${item.color};"></div>`
                                : `<div style="width: 10px; height: 10px; border-radius: 50%; background: ${item.color};"></div>`
                            }
                            <span style="font-size: 11px; font-weight: 600;">${item.label}</span>
                        </div>
                    `;
                });
                div.innerHTML = html;
                return div;
            };
            legend.addTo(map);

            const markers = [];
            let renderedCount = 0;

            // Helper to draw connecting line
            const drawLine = (pLat, pLng, cLat, cLng, color = '#94a3b8') => {
                if (pLat && pLng && cLat && cLng) {
                    L.polyline([
                        [parseFloat(pLat), parseFloat(pLng)],
                        [parseFloat(cLat), parseFloat(cLng)]
                    ], {
                        color: color,
                        weight: 2,
                        opacity: 0.6,
                        dashArray: '5, 10'
                    }).addTo(map);
                }
            };

            // Render Infrastructure & Lines
            if (infrastructure) {
                // 1. Draw Regions
                if (infrastructure.regions) {
                    infrastructure.regions.forEach(r => {
                        const marker = L.marker([r.latitude, r.longitude], {icon: icons.region}).addTo(map);
                        marker.bindPopup(`<b>REGION: ${r.name}</b>`);
                        markers.push(marker);
                    });
                }

                // 2. Draw STOs & Lines to Region
                if (infrastructure.stos) {
                    infrastructure.stos.forEach(s => {
                        const marker = L.marker([s.latitude, s.longitude], {icon: icons.sto}).addTo(map);
                        marker.bindPopup(`<b>STO: ${s.name}</b>`);
                        markers.push(marker);
                        if (s.region) drawLine(s.region.latitude, s.region.longitude, s.latitude, s.longitude, '#10b981');
                    });
                }

                // 3. Draw STBs & Lines to STO
                if (infrastructure.stbs) {
                    infrastructure.stbs.forEach(b => {
                        const marker = L.marker([b.latitude, b.longitude], {icon: icons.stb}).addTo(map);
                        marker.bindPopup(`<b>STB: ${b.name}</b>`);
                        markers.push(marker);
                        if (b.sto) drawLine(b.sto.latitude, b.sto.longitude, b.latitude, b.longitude, '#059669');
                    });
                }

                // 4. Draw ODCs & Lines to STB
                if (infrastructure.odcs) {
                    infrastructure.odcs.forEach(o => {
                        const marker = L.marker([o.latitude, o.longitude], {icon: icons.odc}).addTo(map);
                        marker.bindPopup(`<b>ODC: ${o.name}</b><br>${o.id}`);
                        markers.push(marker);
                        if (o.stb) drawLine(o.stb.latitude, o.stb.longitude, o.latitude, o.longitude, '#f59e0b');
                    });
                }

                // 5. Draw ODPs & Lines to ODC
                if (infrastructure.odps) {
                    infrastructure.odps.forEach(p => {
                        const marker = L.marker([p.latitude, p.longitude], {icon: icons.odp}).addTo(map);
                        marker.bindPopup(`<b>ODP: ${p.name}</b><br>${p.id}`);
                        markers.push(marker);
                        if (p.odc) drawLine(p.odc.latitude, p.odc.longitude, p.latitude, p.longitude, '#8b5cf6');
                    });
                }
            }

            // Render Customers
            customers.forEach(customer => {
                if (customer.latitude && customer.longitude) {
                    const lat = parseFloat(customer.latitude);
                    const lng = parseFloat(customer.longitude);
                    
                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        renderedCount++;
                        const marker = L.marker([lat, lng], {icon: icons.customer}).addTo(map);
                        
                        const popupContent = `
                            <div class="bg-white dark:bg-gray-900 overflow-hidden rounded-lg shadow-lg" style="min-width: 200px;">
                                <div class="h-1 bg-blue-500"></div>
                                <div class="p-4">
                                    <div class="text-[10px] uppercase font-bold text-blue-500 mb-1">Subscriber</div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base mb-2">${customer.name}</h3>
                                    <div class="space-y-1">
                                        <div class="text-xs text-gray-500">User: ${customer.username}</div>
                                        <div class="text-xs text-gray-500">Package: ${customer.package ? customer.package.name : 'N/A'}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                        markers.push(marker);

                        // Draw line to ODP parent
                        if (customer.odp) {
                            drawLine(customer.odp.latitude, customer.odp.longitude, lat, lng, '#3b82f6');
                        }
                    }
                }
            });

            document.getElementById('marker-count').textContent = markers.length;

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        });
    </script>
    @endpush
</x-app-layout>
