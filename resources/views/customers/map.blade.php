@php
    // Lapisan status pelanggan (urut prioritas) — warna + label, tidak hanya warna
    $layers = [
        'trouble' => ['Gangguan', '#dc2626', 'Tiket gangguan terbuka, ONU putus, atau sinyal kritis'],
        'suspended' => ['Terisolir', '#7c3aed', 'Diisolir karena tagihan'],
        'overdue' => ['Menunggak', '#d97706', 'Ada invoice lewat jatuh tempo'],
        'normal' => ['Normal', '#16a34a', 'Aktif tanpa masalah'],
        'pending' => ['Belum aktif', '#0284c7', 'Baru / menunggu aktivasi'],
        'inactive' => ['Berhenti', '#94a3b8', 'Dicabut / batal / menunggu dismantle'],
    ];
    $hasInfra = ! empty($infrastructure);
    $canEdit = \App\Support\RouteAccess::allows(auth()->user(), 'customers.map.routes.save');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Peta Pelanggan</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Sebaran pelanggan per status dan jalur kabel jaringan.</p>
            </div>
            <div class="relative w-full lg:w-72">
                <input id="map-search" type="search" class="input" placeholder="Cari nama / kode pelanggan…" autocomplete="off" aria-label="Cari pelanggan di peta">
                <ul id="map-search-results" class="absolute z-[1000] mt-1 hidden max-h-64 w-full overflow-auto rounded-lg border border-surface-200 bg-white py-1 text-sm shadow-lg dark:border-surface-700 dark:bg-surface-900"></ul>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ session('error') }}</div>
        @endif
        @if($canEdit && $linkable === 0)
            {{-- Garis kabel butuh induk berkoordinat: tanpa ODP tidak ada yang bisa digambar --}}
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                <strong>Belum ada garis kabel untuk digambar.</strong> Garis kabel menghubungkan pelanggan ke <strong>ODP</strong>-nya (lalu ODP → ODC → STB → STO).
                Saat ini {{ $odpWithCoords }} ODP berkoordinat dan {{ $customersWithOdp }} pelanggan berkoordinat yang terhubung ke ODP.
                Tambahkan ODP beserta koordinatnya di @canroute('locations.odps')<a href="{{ route('locations.odps') }}" class="font-semibold underline">Lokasi › ODP</a>@else menu Lokasi › ODP @endcanroute, lalu isi ODP di data pelanggan.
            </div>
        @endif

        {{-- Lapisan --}}
        <div class="card p-3">
            <div class="flex flex-wrap items-center gap-2" id="layer-toggles">
                @foreach($layers as $key => [$label, $color, $hint])
                    <label class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border border-surface-200 px-3 py-1.5 text-sm hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800" title="{{ $hint }}">
                        <input type="checkbox" class="rounded" data-layer="{{ $key }}" @checked($key !== 'inactive')>
                        <span class="h-3 w-3 rounded-full ring-2 ring-white dark:ring-surface-900" style="background: {{ $color }}"></span>
                        <span class="font-medium text-surface-800 dark:text-surface-200">{{ $label }}</span>
                        <span class="tabular-nums text-surface-500">{{ number_format($counts[$key] ?? 0) }}</span>
                    </label>
                @endforeach
                @if($hasInfra)
                    <span class="mx-1 hidden h-6 w-px bg-surface-200 dark:bg-surface-700 sm:block"></span>
                    <label class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border border-surface-200 px-3 py-1.5 text-sm hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800">
                        <input type="checkbox" class="rounded" data-layer="infra" checked>
                        <span class="h-3 w-3 rotate-45 bg-surface-700 dark:bg-surface-300"></span>
                        <span class="font-medium text-surface-800 dark:text-surface-200">Infrastruktur</span>
                        <span class="text-surface-500">OLT · ODC · ODP</span>
                    </label>
                @endif
                <label class="inline-flex cursor-pointer select-none items-center gap-2 rounded-lg border border-surface-200 px-3 py-1.5 text-sm hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800" title="Garis dari pelanggan / perangkat ke induknya">
                    <input type="checkbox" class="rounded" id="toggle-cables" checked>
                    <span class="h-0.5 w-4 bg-surface-500"></span>
                    <span class="font-medium text-surface-800 dark:text-surface-200">Kabel</span>
                </label>
                <button type="button" id="only-problems" class="btn-ghost btn-sm ml-auto">Hanya masalah</button>
            </div>
        </div>

        {{-- Panel mode gambar jalur --}}
        @if($canEdit)
            <div id="route-editor" class="card hidden border-primary-300 p-3 ring-2 ring-primary-500/30 dark:border-primary-500/40">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="text-sm">
                        <p class="font-semibold text-surface-900 dark:text-white">Menggambar jalur kabel: <span id="re-title"></span></p>
                        <p class="text-surface-500">Klik peta di sepanjang jalan/tiang untuk menambah titik belok · seret titik untuk memindah · klik kanan titik untuk menghapus.
                            Panjang: <strong id="re-length" class="tabular-nums text-surface-800 dark:text-surface-200">0 m</strong> <span id="re-mode" class="text-xs"></span></p>
                        <p id="re-msg" class="mt-1 hidden text-sm"></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="re-road" class="btn-secondary btn-sm" @disabled(! $routing['enabled'])
                                title="{{ $routing['enabled'] ? 'Bentuk jalur mengikuti jalan melewati titik-titik yang diklik' : 'Server routing jalan belum diatur administrator' }}">Ikuti jalan</button>
                        <button type="button" id="re-undo" class="btn-ghost btn-sm">Hapus titik terakhir</button>
                        <button type="button" id="re-straight" class="btn-ghost btn-sm text-rose-600">Kembalikan garis lurus</button>
                        <button type="button" id="re-cancel" class="btn-ghost btn-sm">Batal</button>
                        <button type="button" id="re-save" class="btn-primary btn-sm">Simpan jalur</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="card overflow-hidden p-0">
            <div id="customer-map" class="z-0 h-[65vh] min-h-[420px] w-full bg-surface-100 dark:bg-surface-800"></div>
        </div>
        <div class="space-y-1 text-xs text-surface-500">
            <p>{{ number_format(count($points)) }} pelanggan berkoordinat ditampilkan. Gangguan dihitung dari tiket gangguan yang masih terbuka dan hasil <strong>Sinkron ONU</strong> terakhir (ONU putus / sinyal di bawah -27 dBm). Pelanggan tanpa koordinat tidak muncul di peta.</p>
            <p>Garis <strong>utuh</strong> = jalur kabel yang sudah digambar ({{ number_format($cableTotal / 1000, 2, ',', '.') }} km tercatat); garis <strong>putus-putus</strong> = belum digambar (garis lurus ke induk).
                @if($canEdit) Klik pelanggan / ODP / ODC lalu pilih <strong>Gambar jalur kabel</strong>.@endif</p>
        </div>

        @if(auth()->user()->isAdministrator())
            <details class="card p-4">
                <summary class="cursor-pointer text-sm font-semibold text-surface-800 dark:text-surface-200">Pengaturan jalur — server routing jalan ({{ $routing['enabled'] ? 'aktif' : 'nonaktif' }})</summary>
                <form method="POST" action="{{ route('customers.map.routing-settings') }}" class="mt-3 grid gap-3 sm:grid-cols-[1fr_12rem_auto] sm:items-end">
                    @csrf
                    <div>
                        <label class="label" for="routing_url">Alamat server OSRM</label>
                        <input id="routing_url" name="routing_url" type="url" class="input" value="{{ old('routing_url', $routing['url']) }}" placeholder="mis. http://10.90.10.20:5000">
                    </div>
                    <div>
                        <label class="label" for="routing_profile">Profil</label>
                        <select id="routing_profile" name="routing_profile" class="input">
                            @foreach(['driving' => 'Jalan kendaraan', 'foot' => 'Jalan kaki (gang)', 'bike' => 'Sepeda'] as $k => $v)
                                <option value="{{ $k }}" @selected($routing['profile'] === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn-primary">Simpan</button>
                </form>
                <p class="mt-2 text-xs text-surface-500">Tombol <strong>Ikuti jalan</strong> mengirim koordinat titik jalur (tanpa nama/data pelanggan) ke server ini. Kosongkan untuk menonaktifkan — gambar manual tetap bisa.
                    Paling aman memakai server OSRM sendiri (data peta Indonesia dari OpenStreetMap). Server demo publik <code>https://router.project-osrm.org</code> hanya mendukung profil kendaraan, tanpa jaminan layanan, dan koordinat dikirim ke pihak luar.</p>
                @error('routing_url')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </details>
        @endif
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .leaflet-popup-content { margin: 12px 14px; min-width: 210px; }
        .map-pop-title { font-weight: 700; font-size: 14px; color: #0f172a; }
        .map-pop-meta { font-size: 12px; color: #64748b; }
        .map-pop-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; margin: 6px 0; }
        .map-pop-dot { width: 9px; height: 9px; border-radius: 9999px; }
        .map-pop-link { display: inline-block; margin-top: 8px; margin-right: 10px; font-size: 12px; font-weight: 600; color: #2563eb; background: none; border: 0; padding: 0; cursor: pointer; }
        .route-vertex { width: 12px; height: 12px; border-radius: 9999px; background: #fff; border: 3px solid #2563eb; box-shadow: 0 1px 3px rgba(0,0,0,.4); }
        .editing-map .leaflet-container { cursor: crosshair; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const points = @json($points);
            const infra = @json($infrastructure);
            const layerInfo = @json(collect($layers)->map(fn ($l) => ['label' => $l[0], 'color' => $l[1]]));
            const canEdit = @json($canEdit);
            const urls = { snap: @js(route('customers.map.routes.snap', [], false)), save: @js(route('customers.map.routes.save', [], false)), destroy: @js(route('customers.map.routes.destroy', [], false)) };
            const infraColors = { olt: '#b91c1c', region: '#ca8a04', sto: '#059669', stb: '#059669', odc: '#ea580c', odp: '#7c3aed' };
            const infraLabels = { olt: 'OLT', region: 'Wilayah', sto: 'STO', stb: 'STB', odc: 'ODC', odp: 'ODP' };
            const parentLabel = { customer: 'ODP', odp: 'ODC', odc: 'STB', stb: 'STO', sto: 'Wilayah' };

            const map = L.map('customer-map', { preferCanvas: true }).setView([-6.2, 106.816666], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
            const renderer = L.canvas({ padding: 0.5 });

            // DOM + textContent: nama pelanggan tidak pernah ditafsirkan sebagai HTML
            const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
            const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const meters = (m) => m >= 1000 ? (m / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' km' : Math.round(m) + ' m';
            const lengthOf = (path) => { let t = 0; for (let i = 1; i < path.length; i++) t += map.distance(path[i - 1], path[i]); return t; };

            const groups = {};
            Object.keys(layerInfo).forEach(k => groups[k] = L.layerGroup());
            groups.infra = L.layerGroup();
            const cables = L.layerGroup();
            const cableOf = {};   // "type:id" => polyline
            const byId = {};
            const bounds = [];

            // Satu sambungan titik → induk: jalur tersimpan (utuh) atau garis lurus (putus-putus)
            const drawCable = (node) => {
                const key = node.type + ':' + node.id;
                if (cableOf[key]) cables.removeLayer(cableOf[key]);
                if (!node.parent) return;
                const routed = node.route && node.route.path && node.route.path.length > 1;
                const line = L.polyline(routed ? node.route.path : [node.parent, [node.lat, node.lng]], {
                    renderer, bubblingMouseEvents: false, color: node.color, weight: routed ? 2.5 : 1.2, opacity: routed ? 0.75 : 0.4, dashArray: routed ? null : '4 7',
                });
                line.bindTooltip(parentLabel[node.type] + ' → ' + node.name + (routed ? ' · ' + meters(node.route.length) : ' · belum digambar'), { sticky: true });
                if (canEdit) line.on('click', () => startEdit(node));
                cableOf[key] = line.addTo(cables);
            };

            const popup = (node, p) => {
                const box = el('div');
                box.appendChild(el('div', 'map-pop-title', node.name));
                if (p) {
                    box.appendChild(el('div', 'map-pop-meta', [p.code, p.package].filter(Boolean).join(' · ')));
                    const badge = el('div', 'map-pop-badge');
                    const dot = el('span', 'map-pop-dot'); dot.style.background = layerInfo[p.layer].color;
                    badge.appendChild(dot); badge.appendChild(el('span', null, layerInfo[p.layer].label + (p.problem ? ' — ' + p.problem : '')));
                    box.appendChild(badge);
                    if (p.overdue > 0) box.appendChild(el('div', 'map-pop-meta', 'Tunggakan lewat jatuh tempo: ' + rupiah(p.overdue)));
                } else {
                    box.appendChild(el('div', 'map-pop-meta', infraLabels[node.type] || node.type));
                }
                if (node.parent) box.appendChild(el('div', 'map-pop-meta', 'Kabel ke ' + parentLabel[node.type] + ': ' + (node.route ? meters(node.route.length) : 'belum digambar')));
                if (p) { const a = el('a', 'map-pop-link', 'Buka data pelanggan →'); a.href = p.url; box.appendChild(a); }
                if (canEdit && node.parent && node.editable) {
                    const b = el('button', 'map-pop-link', node.route ? 'Ubah jalur kabel' : 'Gambar jalur kabel');
                    b.type = 'button'; b.addEventListener('click', () => { map.closePopup(); startEdit(node); });
                    box.appendChild(b);
                }
                return box;
            };

            points.forEach(p => {
                const node = { type: 'customer', id: String(p.id), name: p.name, lat: p.lat, lng: p.lng, parent: p.odp, route: p.route, color: layerInfo[p.layer].color, editable: true };
                const m = L.circleMarker([p.lat, p.lng], { renderer, bubblingMouseEvents: false, radius: p.layer === 'trouble' ? 8 : 6, color: '#ffffff', weight: 2, fillColor: node.color, fillOpacity: 0.95 })
                    .bindPopup(() => popup(node, p)).bindTooltip(p.name, { direction: 'top', offset: [0, -6] });
                m.addTo(groups[p.layer]);
                node.layer = p.layer;
                drawCable(node);
                byId[p.id] = { marker: m, point: p, node };
                bounds.push([p.lat, p.lng]);
            });

            Object.entries(infra || {}).forEach(([type, rows]) => rows.forEach(r => {
                const node = { type, id: r.id, name: r.name, lat: r.lat, lng: r.lng, parent: r.parent, route: r.route, color: infraColors[type], editable: ['odp', 'odc', 'stb', 'sto'].includes(type) };
                drawCable(node);
                L.circleMarker([r.lat, r.lng], { renderer, bubblingMouseEvents: false, radius: type === 'olt' ? 9 : 5, color: '#0f172a', weight: 2, fillColor: infraColors[type], fillOpacity: 1 })
                    .bindPopup(() => popup(node)).bindTooltip(infraLabels[type] + ': ' + r.name, { direction: 'top' }).addTo(groups.infra);
                if (!points.length) bounds.push([r.lat, r.lng]);
            }));

            const toggles = document.querySelectorAll('#layer-toggles input[data-layer]');
            const cableToggle = document.getElementById('toggle-cables');
            const apply = () => {
                toggles.forEach(t => { const g = groups[t.dataset.layer]; if (g) t.checked ? g.addTo(map) : map.removeLayer(g); });
                cableToggle.checked ? cables.addTo(map) : map.removeLayer(cables);
                groups.trouble.eachLayer(l => l.bringToFront && l.bringToFront());
            };
            toggles.forEach(t => t.addEventListener('change', apply));
            cableToggle.addEventListener('change', apply);
            cables.addTo(map);   // kabel di bawah titik
            apply();

            document.getElementById('only-problems').addEventListener('click', () => {
                toggles.forEach(t => { if (t.dataset.layer !== 'infra') t.checked = ['trouble', 'suspended', 'overdue'].includes(t.dataset.layer); });
                apply();
            });

            if (bounds.length) map.fitBounds(L.latLngBounds(bounds).pad(0.08), { maxZoom: 16 });

            // ---------- Mode gambar jalur kabel ----------
            let edit = null;   // { node, waypoints: [[lat,lng]], road: null|[[lat,lng]], line, handles }
            const panel = document.getElementById('route-editor');
            const $ = (id) => document.getElementById(id);
            const csrf = () => document.querySelector('meta[name=csrf-token]').content;
            const say = (text, ok) => { const m = $('re-msg'); m.textContent = text; m.className = 'mt-1 text-sm ' + (ok ? 'text-emerald-600' : 'text-rose-600'); };
            const draftPath = () => edit.road || [edit.node.parent, ...edit.waypoints, [edit.node.lat, edit.node.lng]];

            const redraw = () => {
                const path = draftPath();
                edit.line.setLatLngs(path);
                edit.handles.clearLayers();
                edit.waypoints.forEach((wp, i) => {
                    const h = L.marker(wp, { draggable: true, icon: L.divIcon({ className: '', html: '<div class="route-vertex"></div>', iconSize: [12, 12], iconAnchor: [6, 6] }) });
                    h.on('dragend', () => { edit.waypoints[i] = [h.getLatLng().lat, h.getLatLng().lng]; edit.road = null; redraw(); });
                    h.on('contextmenu', (e) => { L.DomEvent.preventDefault(e); edit.waypoints.splice(i, 1); edit.road = null; redraw(); });
                    h.addTo(edit.handles);
                });
                $('re-length').textContent = meters(lengthOf(path));
                $('re-mode').textContent = edit.road ? '(mengikuti jalan)' : '(manual)';
                $('re-straight').classList.toggle('hidden', !edit.node.route);
            };

            // Titik baru disisipkan di ruas terdekat agar urutan jalur tetap rapi
            const insertWaypoint = (ll) => {
                const pts = [edit.node.parent, ...edit.waypoints, [edit.node.lat, edit.node.lng]].map(p => map.latLngToLayerPoint(p));
                const q = map.latLngToLayerPoint(ll);
                let best = 0, bestD = Infinity;
                for (let i = 1; i < pts.length; i++) {
                    const d = L.LineUtil.pointToSegmentDistance(q, pts[i - 1], pts[i]);
                    if (d < bestD) { bestD = d; best = i - 1; }
                }
                edit.waypoints.splice(best, 0, [ll.lat, ll.lng]);
            };

            const onMapClick = (e) => { if (!edit) return; insertWaypoint(e.latlng); edit.road = null; redraw(); };

            function startEdit(node) {
                if (!canEdit || !node.parent) return;
                if (edit) stopEdit();
                const existing = node.route && node.route.path || null;
                edit = {
                    node,
                    // Jalur manual: titik beloknya bisa diubah; jalur jalan: tampil apa adanya sampai titik ditambah
                    waypoints: existing && node.route.mode !== 'road' ? existing.slice(1, -1) : [],
                    road: existing && node.route.mode === 'road' ? existing : null,
                    line: L.polyline([], { color: '#2563eb', weight: 4, opacity: 0.9 }).addTo(map),
                    handles: L.layerGroup().addTo(map),
                };
                if (cableOf[node.type + ':' + node.id]) cableOf[node.type + ':' + node.id].setStyle({ opacity: 0 });
                $('re-title').textContent = parentLabel[node.type] + ' → ' + node.name;
                $('re-msg').classList.add('hidden');
                panel.classList.remove('hidden');
                document.body.classList.add('editing-map');
                map.on('click', onMapClick);
                redraw();
                map.fitBounds(L.latLngBounds(draftPath()).pad(0.3), { maxZoom: 18 });
            }

            function stopEdit() {
                if (!edit) return;
                map.removeLayer(edit.line); map.removeLayer(edit.handles);
                drawCable(edit.node);
                map.off('click', onMapClick);
                edit = null;
                panel.classList.add('hidden');
                document.body.classList.remove('editing-map');
            }

            const send = (url, method, body) => fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) })
                .then(r => r.json().catch(() => ({})).then(d => ({ ok: r.ok && d.success !== false, d, status: r.status })));

            if (canEdit) {
                $('re-cancel').addEventListener('click', stopEdit);
                $('re-undo').addEventListener('click', () => { if (edit && edit.waypoints.length) { edit.waypoints.pop(); edit.road = null; redraw(); } });
                $('re-road').addEventListener('click', () => {
                    if (!edit) return;
                    const btn = $('re-road'); btn.disabled = true; btn.textContent = 'Mencari jalan…';
                    send(urls.snap, 'POST', { points: [edit.node.parent, ...edit.waypoints, [edit.node.lat, edit.node.lng]] })
                        .then(({ ok, d }) => {
                            if (!ok) return say(d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Gagal mencari jalan.'), false);
                            edit.road = d.path; redraw(); say('Jalur mengikuti jalan. Tambah titik bila jalurnya kurang tepat, lalu klik Ikuti jalan lagi.', true);
                        })
                        .catch(() => say('Gagal menghubungi server.', false))
                        .finally(() => { btn.disabled = false; btn.textContent = 'Ikuti jalan'; });
                });
                $('re-save').addEventListener('click', () => {
                    if (!edit) return;
                    const path = draftPath();
                    send(urls.save, 'POST', { node_type: edit.node.type, node_id: edit.node.id, path, mode: edit.road ? 'road' : 'manual' })
                        .then(({ ok, d }) => {
                            if (!ok) return say(d.message || 'Gagal menyimpan jalur.', false);
                            edit.node.route = { path, length: d.length, mode: edit.road ? 'road' : 'manual' };
                            stopEdit();
                            window.showGlobalToast ? window.showGlobalToast(d.message) : null;
                        })
                        .catch(() => say('Gagal menghubungi server.', false));
                });
                $('re-straight').addEventListener('click', () => {
                    if (!edit || !confirm('Hapus jalur ini dan kembali ke garis lurus?')) return;
                    send(urls.destroy, 'DELETE', { node_type: edit.node.type, node_id: edit.node.id }).then(({ ok, d }) => {
                        if (!ok) return say(d.message || 'Gagal menghapus jalur.', false);
                        edit.node.route = null; stopEdit();
                    });
                });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && edit) stopEdit(); });
            }

            // ---------- Pencarian pelanggan ----------
            const input = $('map-search');
            const list = $('map-search-results');
            input.addEventListener('input', () => {
                const q = input.value.trim().toLowerCase();
                list.replaceChildren();
                if (q.length < 2) { list.classList.add('hidden'); return; }
                const hits = points.filter(p => (p.name || '').toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q)).slice(0, 12);
                hits.forEach(p => {
                    const li = el('li');
                    const b = el('button', 'block w-full px-3 py-1.5 text-left hover:bg-surface-50 dark:hover:bg-surface-800');
                    b.type = 'button';
                    b.appendChild(el('span', 'font-medium text-surface-900 dark:text-white', p.name));
                    b.appendChild(el('span', 'block text-xs text-surface-500', [p.code, layerInfo[p.layer].label].join(' · ')));
                    b.addEventListener('click', () => {
                        const t = document.querySelector(`#layer-toggles input[data-layer="${p.layer}"]`);
                        if (t && !t.checked) { t.checked = true; apply(); }
                        map.setView([p.lat, p.lng], 18);
                        byId[p.id].marker.openPopup();
                        list.classList.add('hidden');
                    });
                    li.appendChild(b); list.appendChild(li);
                });
                if (!hits.length) list.appendChild(el('li', 'px-3 py-2 text-xs text-surface-500', 'Tidak ditemukan di peta (mungkin tanpa koordinat).'));
                list.classList.remove('hidden');
            });
            document.addEventListener('click', e => { if (!list.contains(e.target) && e.target !== input) list.classList.add('hidden'); });
        });
    </script>
    @endpush
</x-app-layout>
