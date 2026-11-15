<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            Aktivasi Pelanggan
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="card overflow-hidden">
            <div class="bg-indigo-600 px-8 py-6 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg">Proses Aktivasi: {{ $customer->name }}</h3>
                    <p class="text-indigo-100 text-xs mt-1">Lengkapi data pemasangan perangkat untuk mengaktifkan pelanggan.</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold tracking-widest opacity-70">ID Pelanggan</p>
                    <p class="font-mono font-bold">{{ $customer->customer_code }}</p>
                </div>
            </div>

            <form action="{{ route('customers.activate.store', $customer) }}" method="POST" class="p-8 space-y-8" x-data="{ consumables: [] }">
                @csrf
                
                <!-- Section: OLT Provisioning (Zero Touch) -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">OLT Provisioning (Zero Touch)</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-surface-50/60 dark:bg-surface-800/30 p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                        <div>
                            <x-input-label for="olt_id" :value="__('Source OLT')" />
                            <select id="olt_id" name="olt_id" class="mt-1 input">
                                <option value="">-- Konfigurasi Manual (Tanpa OLT) --</option>
                                @foreach($olts as $olt)
                                    <option value="{{ $olt->id }}" {{ old('olt_id', $customer->olt_id) == $olt->id ? 'selected' : '' }}>{{ $olt->name }} ({{ $olt->ip_address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="activated_at" :value="__('Activation Date')" />
                            <x-text-input id="activated_at" name="activated_at" type="date" class="mt-1 block w-full" :value="old('activated_at', date('Y-m-d'))" />
                            <p class="text-xs text-indigo-500 mt-1 italic">Tanggal layanan mulai aktif (Prorata akan dihitung dari sini).</p>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <x-input-label for="onu_sn" :value="__('ONU Serial Number')" />
                                <div class="flex items-center space-x-2">
                                    <span id="sn-sync-badge" class="hidden text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                        Tersinkron dengan Inventaris
                                    </span>
                                    <x-barcode-scanner targetInputId="onu_sn" />
                                </div>
                            </div>
                            <x-text-input id="onu_sn" name="onu_sn" type="text" class="mt-1 block w-full font-mono uppercase" :value="old('onu_sn', $customer->onu_sn)" placeholder="mis. ZTEGC000..." />
                            <p class="text-xs text-gray-400 mt-1 italic">Dapat diisi manual atau otomatis dari pilihan stok di bawah.</p>
                        </div>

                        <div>
                            <x-input-label for="onu_index" :value="__('ONU Index (Position)')" />
                            <x-text-input id="onu_index" name="onu_index" type="text" class="mt-1 block w-full font-mono" :value="old('onu_index', $customer->onu_index)" placeholder=".shelf.slot.port.id" />
                            <p class="text-xs text-gray-400 mt-1 italic">Example: .1.1.1.1</p>
                        </div>

                        <div>
                            <x-input-label for="onu_type" :value="__('ONU Type/Model')" />
                            <select id="onu_type" name="onu_type" class="mt-1 input">
                                <option value="ZTE F660" {{ old('onu_type', $customer->onu_type) == 'ZTE F660' ? 'selected' : '' }}>ZTE F660</option>
                                <option value="ZTE F609" {{ old('onu_type', $customer->onu_type) == 'ZTE F609' ? 'selected' : '' }}>ZTE F609</option>
                                <option value="HG6243C" {{ old('onu_type', $customer->onu_type) == 'HG6243C' ? 'selected' : '' }}>HG6243C (FiberHome)</option>
                                <option value="HG6145D" {{ old('onu_type', $customer->onu_type) == 'HG6145D' ? 'selected' : '' }}>HG6145D (FiberHome)</option>
                                <option value="Other" {{ old('onu_type', $customer->onu_type) == 'Other' ? 'selected' : '' }}>Lainnya / Umum</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Main Equipment Selection -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-slate-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Inventory Mapping (ONU/Modem)</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label value="Pilih Perangkat (Opsional)" />
                            <select name="modem_stock_id" class="mt-1 input">
                                <option value="">-- Lewati / Tanpa Perangkat --</option>
                                @foreach($serialItems as $item)
                                    <optgroup label="{{ $item->name }}">
                                        @foreach($item->stocks as $stock)
                                            <option value="{{ $stock->id }}" data-sn="{{ $stock->serial_number }}" data-type="{{ $item->model }}" data-brand="{{ $item->brand }}">
                                                SN: {{ $stock->serial_number }} ({{ strtoupper($stock->condition) }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-xs text-surface-400 mt-2 italic">Hanya menampilkan modem dengan status 'Ready' di gudang.</p>
                        </div>
                    </div>
                </div>

                <!-- Consumables / Other Materials -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Material Tambahan (Kabel/Konektor)</h4>
                        </div>
                        <button type="button" @click="consumables.push({ id: '', qty: 0 })" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg hover:bg-indigo-100 transition-colors">
                            + Tambah Material
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(consumable, index) in consumables" :key="index">
                            <div class="grid grid-cols-12 gap-4 items-end bg-surface-50/60 dark:bg-surface-800/30 p-4 rounded-xl border border-surface-200 dark:border-surface-800">
                                <div class="col-span-12 md:col-span-7">
                                    <x-input-label value="Pilih Material" />
                                    <select :name="'consumables['+index+'][item_id]'" required class="mt-1 input">
                                        <option value="">-- Pilih --</option>
                                        @foreach($consumableItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }} (Stok: {{ $item->stock_count }} {{ $item->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-8 md:col-span-4">
                                    <x-input-label value="Jumlah Terpakai" />
                                    <x-text-input type="number" step="0.1" ::name="'consumables['+index+'][quantity]'" class="mt-1 w-full bg-white dark:bg-gray-800" placeholder="Qty..." />
                                </div>
                                <div class="col-span-4 md:col-span-1 flex justify-center">
                                    <button type="button" @click="consumables.splice(index, 1)" class="text-red-500 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="consumables.length === 0" class="text-center py-8 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-xl">
                            <p class="text-xs text-gray-400 italic font-medium">Klik tombol tambah material untuk mencatat kabel atau konektor yang terpakai.</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Physical Infrastructure -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Pemetaan Infrastruktur Fisik (ODC/ODP)</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-surface-50/60 dark:bg-surface-800/30 p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                        <div>
                            <x-input-label for="odc_id" :value="__('ODC Cabinet')" />
                            <select id="odc_id" name="odc_id" class="mt-1 input">
                                <option value="">-- Pilih ODC --</option>
                                @foreach($odcs as $odc)
                                    <option value="{{ $odc->id }}" {{ old('odc_id', $customer->odc_id) == $odc->id ? 'selected' : '' }}>{{ $odc->id }} - {{ $odc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="odp_id" :value="__('ODP Box')" />
                            <select id="odp_id" name="odp_id" class="mt-1 input">
                                <option value="">-- Pilih ODP --</option>
                                @foreach($odps as $odp)
                                    <option value="{{ $odp->id }}" data-odc-id="{{ $odp->odc_id }}" {{ old('odp_id', $customer->odp_id) == $odp->id ? 'selected' : '' }}>{{ $odp->id }} - {{ $odp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="odp_port" :value="__('ODP Port')" />
                                <x-text-input id="odp_port" name="odp_port" type="number" class="mt-1 block w-full" :value="old('odp_port', $customer->odp_port)" placeholder="1-16" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="vlan_id" :value="__('Service VLAN ID')" />
                                <x-text-input id="vlan_id" name="vlan_id" type="number" class="mt-1 block w-full" :value="old('vlan_id', $customer->vlan_id)" placeholder="100" />
                            </div>
                            <div>
                                <x-input-label for="static_ip" :value="__('Static IP (Optional)')" />
                                <x-text-input id="static_ip" name="static_ip" type="text" class="mt-1 block w-full" :value="old('static_ip', $customer->static_ip)" placeholder="10.x.x.x" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: KYC & CPE Photos -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-amber-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Dokumentasi KYC & Foto Perangkat</h4>
                    </div>
                    
                    <div class="bg-surface-50/60 dark:bg-surface-800/30 p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <x-input-label for="identity_photo" value="Foto KTP/Identitas (opsional)" />
                                <input type="file" id="identity_photo" name="identity_photo" data-compress class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-amber-100 file:text-amber-700" accept="image/*">
                            </div>
                            <div>
                                <x-input-label for="house_photo" value="Foto Rumah/Lokasi (opsional)" />
                                <input type="file" id="house_photo" name="house_photo" data-compress class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-amber-100 file:text-amber-700" accept="image/*">
                            </div>
                            <div class="space-y-3">
                                <x-input-label for="cpe_photo" value="Foto Label Modem (opsional)" />
                                <div class="flex items-center space-x-4">
                                    <input type="file" id="cpe_photo" name="cpe_photo" data-compress class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-amber-100 file:text-amber-700" accept="image/*">
                                    <div id="cpe-preview-container" class="hidden">
                                        <img id="cpe-preview" class="h-16 w-16 object-cover rounded-lg border-2 border-amber-200 shadow-sm" alt="">
                                    </div>
                                </div>
                                <p class="help-text !mt-1">Bila dilampirkan, SN di label dibaca otomatis (OCR). Foto dekat & tegak lurus ke label, tanpa pantulan cahaya.</p>
                                <div id="ocr-box" class="hidden mt-2 p-2 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/30 space-y-2">
                                    <p id="ocr-status-text" class="text-xs text-amber-800 dark:text-amber-300"></p>
                                    <div id="ocr-result-container" class="hidden text-xs flex flex-wrap items-center gap-2">
                                        <span class="text-gray-600 dark:text-gray-300">Terbaca:</span>
                                        <span id="ocr-detected-sn" class="font-mono text-indigo-600 dark:text-indigo-300 bg-white/70 dark:bg-white/10 px-1.5 py-0.5 rounded tracking-wider"></span>
                                        <button type="button" id="ocr-use-sn" class="hidden btn-secondary btn-sm">Pakai sebagai SN ONU</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="region_code" :value="__('Region')" />
                                <select id="region_code" name="region_code" class="mt-1 input" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($regions as $r)
                                        <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code', $customer->region_code) == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="sto_code" :value="__('STO')" />
                                <select id="sto_code" name="sto_code" class="mt-1 input" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($stos as $s)
                                        <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code', $customer->sto_code) == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="stb_code" :value="__('STB')" />
                                <select id="stb_code" name="stb_code" class="mt-1 input" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($stbs as $t)
                                        <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code', $customer->stb_code) == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Geolocation -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-1.5 h-6 bg-rose-500 rounded-full"></div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">Koordinat Pemasangan (GPS)</h4>
                        </div>
                        <button type="button" id="locate-me" class="hidden text-xs bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-colors">
                            Deteksi Lokasi Saya
                        </button>
                    </div>

                    <div class="bg-surface-50/60 dark:bg-surface-800/30 p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                        <div class="border-2 border-white dark:border-gray-800 rounded-xl overflow-hidden shadow-sm mb-4">
                            <div id="map-picker" style="height: 200px; width: 100%;"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="latitude" :value="__('Latitude')" />
                                <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full bg-white dark:bg-gray-900 font-mono" :value="old('latitude', $customer->latitude)" required />
                            </div>
                            <div>
                                <x-input-label for="longitude" :value="__('Longitude')" />
                                <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full bg-white dark:bg-gray-900 font-mono" :value="old('longitude', $customer->longitude)" required />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Installation Payment -->
                <div class="space-y-4 pt-6 border-t border-gray-100 dark:border-gray-700" x-data="{ payment_method: 'cash' }">
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                        <h4 class="font-bold text-gray-900 dark:text-gray-100">Pembayaran Instalasi</h4>
                    </div>
                    
                    <div class="bg-surface-50/60 dark:bg-surface-800/30 p-6 rounded-xl border border-surface-200 dark:border-surface-800">
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-sm font-medium text-indigo-800 dark:text-indigo-300">Biaya Instalasi Terutang:</span>
                            <span class="text-2xl font-semibold text-indigo-900 dark:text-indigo-100">Rp {{ number_format($customer->installation_fee, 2, ',', '.') }}</span>
                        </div>

                        @if($customer->installation_fee > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Metode Pembayaran" />
                                <select name="payment_method" x-model="payment_method" class="mt-1 input">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="transfer">Transfer Manual</option>
                                    <option value="pg">Payment Gateway</option>
                                </select>
                            </div>

                            <div x-show="payment_method === 'transfer' || payment_method === 'cash'">
                                <x-input-label value="Pilih Rekening Tujuan (Bank/Kas)" />
                                <select name="bank_account_id" class="mt-1 input">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach(\App\Models\BankAccount::where('type', '!=', 'payment_gateway')->where('is_active', true)->get() as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div x-show="payment_method === 'pg'" class="col-span-full">
                                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-indigo-200 dark:border-indigo-900/50 flex items-start gap-3">
                                    <svg class="w-5 h-5 text-indigo-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-[11px] text-indigo-800 dark:text-indigo-300 leading-relaxed italic">
                                        Pilih ini jika pelanggan ingin membayar melalui Link Pembayaran (Midtrans/Xendit). Akun akan otomatis aktif setalah pembayaran diverifikasi oleh Gateway.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-2">
                            <p class="text-sm text-gray-500 italic">Tidak ada biaya instalasi untuk pelanggan ini.</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end pt-6 space-x-3">
                    <a href="{{ route('customers.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Batal</a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-3 rounded-xl text-sm font-bold shadow-sm transition-all transform hover:-translate-y-0.5">
                        Aktifkan Pelanggan & Simpan Inventaris
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>
    <script>
        // ---------------------------------------------------------------- Foto & OCR SN modem
        // Foto kamera ponsel 3–8 MB dikecilkan di perangkat (maks. 1600 px, JPEG) sebelum dikirim:
        // unggahan lebih cepat & tidak ditolak batas ukuran server.
        async function compressImage(file, maxSide = 1600, quality = 0.85) {
            if (!file || !file.type.startsWith('image/') || file.size < 400 * 1024) return file;
            const bmp = await createImageBitmap(file).catch(() => null);
            if (!bmp) return file;
            const scale = Math.min(1, maxSide / Math.max(bmp.width, bmp.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', quality));
            if (!blob || blob.size >= file.size) return file;
            return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
        }

        document.querySelectorAll('input[type=file][data-compress]').forEach(input => {
            input.addEventListener('change', async () => {
                const original = input.files && input.files[0];
                if (!original) return;
                if (input.id === 'cpe_photo') {
                    const url = URL.createObjectURL(original);
                    document.getElementById('cpe-preview').src = url;
                    document.getElementById('cpe-preview-container').classList.remove('hidden');
                    runOcr(original);   // OCR memakai foto asli (lebih tajam)
                }
                try {
                    const small = await compressImage(original);
                    if (small !== original) {
                        const dt = new DataTransfer();
                        dt.items.add(small);
                        input.files = dt.files;
                    }
                } catch (e) { console.warn('Kompres foto gagal, dikirim apa adanya', e); }
            });
        });

        // Huruf/angka yang sering tertukar oleh OCR disamakan sebelum membandingkan SN
        const OCR_CONFUSABLE = { O: '0', Q: '0', D: '0', I: '1', L: '1', T: '1', Z: '2', S: '5', B: '8', G: '6' };
        const normSn = s => (s || '').toUpperCase().replace(/[^A-Z0-9]/g, '').replace(/[OQDILTZSBG]/g, c => OCR_CONFUSABLE[c]);

        function editDistance(a, b) {
            const dp = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
            for (let j = 1; j <= b.length; j++) dp[0][j] = j;
            for (let i = 1; i <= a.length; i++)
                for (let j = 1; j <= b.length; j++)
                    dp[i][j] = Math.min(dp[i - 1][j] + 1, dp[i][j - 1] + 1, dp[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
            return dp[a.length][b.length];
        }

        // Kandidat SN dari teks OCR: utamakan yang setelah tulisan S/N, SN, Serial; lalu pola SN GPON umum
        function snCandidates(text) {
            const up = text.toUpperCase();
            const out = [];
            const push = v => { v = (v || '').replace(/[^A-Z0-9]/g, ''); if (v.length >= 8 && v.length <= 20 && !out.includes(v)) out.push(v); };
            for (const m of up.matchAll(/(?:G?PON\s*)?(?:S\s*\/\s*N|SN|SERIAL(?:\s*(?:NO|NUMBER))?)\s*[:.#\-]?\s*([A-Z0-9][A-Z0-9\-]{6,22})/g)) push(m[1]);
            for (const m of up.matchAll(/\b(?:ZTEG|HWTC|FHTT|ALCL|ZNTS|HALN|GPON|CDTC|TPLG|YHTC|ZXIC)[A-Z0-9]{8}\b/g)) push(m[0]);
            for (const m of up.matchAll(/\b[0-9A-F]{16}\b/g)) push(m[0]);
            for (const m of up.matchAll(/\b(?=[A-Z0-9]*\d)(?=[A-Z0-9]*[A-Z])[A-Z0-9]{10,16}\b/g)) push(m[0]);
            return out;
        }

        // Gambar untuk OCR: diperbesar/diperkecil ke ±2000 px, abu-abu & kontras ditarik
        async function ocrCanvas(file) {
            const bmp = await createImageBitmap(file);
            const scale = Math.min(2.5, 2000 / Math.max(bmp.width, bmp.height));
            const c = document.createElement('canvas');
            c.width = Math.round(bmp.width * scale); c.height = Math.round(bmp.height * scale);
            const ctx = c.getContext('2d');
            ctx.drawImage(bmp, 0, 0, c.width, c.height);
            const img = ctx.getImageData(0, 0, c.width, c.height), d = img.data;
            let min = 255, max = 0;
            for (let i = 0; i < d.length; i += 4) {
                const g = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
                d[i] = g; if (g < min) min = g; if (g > max) max = g;
            }
            const range = Math.max(1, max - min);
            for (let i = 0; i < d.length; i += 4) { const v = (d[i] - min) * 255 / range; d[i] = d[i + 1] = d[i + 2] = v; }
            ctx.putImageData(img, 0, 0);
            return c;
        }

        let ocrRun = 0;
        async function runOcr(file) {
            const run = ++ocrRun;
            const box = document.getElementById('ocr-box');
            const statusText = document.getElementById('ocr-status-text');
            const resultContainer = document.getElementById('ocr-result-container');
            const detected = document.getElementById('ocr-detected-sn');
            const useBtn = document.getElementById('ocr-use-sn');
            const snInput = document.getElementById('onu_sn');
            const setStatus = (msg, tone) => {
                statusText.textContent = msg;
                statusText.className = 'text-xs ' + ({ ok: 'text-emerald-700 dark:text-emerald-300 font-semibold', warn: 'text-rose-700 dark:text-rose-300 font-semibold', info: 'text-amber-800 dark:text-amber-300' }[tone] || '');
            };
            box.classList.remove('hidden');
            resultContainer.classList.add('hidden');
            useBtn.classList.add('hidden');
            setStatus('Membaca SN dari foto… (bisa 10–30 detik di ponsel)', 'info');

            try {
                if (typeof Tesseract === 'undefined') throw new Error('Pustaka OCR tidak termuat (cek koneksi internet).');
                const worker = await Tesseract.createWorker('eng');
                await worker.setParameters({ tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789:/-. ' });
                const { data: { text } } = await worker.recognize(await ocrCanvas(file));
                await worker.terminate();
                if (run !== ocrRun) return;   // ada foto yang lebih baru
                console.log('OCR:', text);

                const candidates = snCandidates(text);
                const expected = (snInput.value || '').trim();
                if (!candidates.length) {
                    setStatus('SN tidak terbaca dari foto. Ulangi foto lebih dekat ke label, atau isi SN ONU manual / pakai pemindai barcode.', 'warn');
                    return;
                }

                resultContainer.classList.remove('hidden');
                if (expected.length >= 6) {
                    // Bandingkan dengan SN yang diisi / dipilih dari gudang (toleran 1 karakter salah baca)
                    const target = normSn(expected);
                    const best = candidates.map(c => ({ c, d: editDistance(normSn(c), target) })).sort((a, b) => a.d - b.d)[0];
                    detected.textContent = best.c;
                    if (best.d <= 1) {
                        setStatus('Cocok: SN di foto sesuai dengan SN ONU ' + expected.toUpperCase() + '.', 'ok');
                    } else {
                        setStatus('SN di foto berbeda dengan SN ONU yang diisi (' + expected.toUpperCase() + '). Periksa kembali perangkatnya.', 'warn');
                        useBtn.classList.remove('hidden');
                    }
                } else {
                    // Belum ada SN (tanpa modem dari gudang): isi otomatis dari foto
                    detected.textContent = candidates[0];
                    snInput.value = candidates[0];
                    snInput.dispatchEvent(new Event('input'));
                    setStatus('SN ONU diisi dari foto. Periksa kembali — OCR bisa salah baca huruf/angka yang mirip (8/B, 0/O, 5/S).', 'info');
                }
                useBtn.onclick = () => { snInput.value = detected.textContent; snInput.dispatchEvent(new Event('input')); useBtn.classList.add('hidden'); setStatus('SN ONU diganti dengan hasil baca foto.', 'info'); };
            } catch (err) {
                console.error('OCR Error:', err);
                if (run === ocrRun) setStatus('OCR gagal: ' + (err.message || err) + ' Isi SN ONU manual.', 'warn');
            }
        }

        (function() {
            // Map Logic
            var latInput = document.getElementById('latitude');
            var lngInput = document.getElementById('longitude');

            try {
                var initialLat = parseFloat(@json($customer->latitude)) || -6.200000;
                var initialLng = parseFloat(@json($customer->longitude)) || 106.816666;
                
                var map = L.map('map-picker').setView([initialLat, initialLng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                var marker = L.marker([initialLat, initialLng], {draggable: true}).addTo(map);

                function updateInputs(lat, lng) {
                    latInput.value = lat.toFixed(8);
                    lngInput.value = lng.toFixed(8);
                }

                marker.on('dragend', function(e) {
                    var position = marker.getLatLng();
                    updateInputs(position.lat, position.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateInputs(e.latlng.lat, e.latlng.lng);
                });

                var locateBtn = document.getElementById('locate-me');
                if (locateBtn && navigator.geolocation) {
                    locateBtn.classList.remove('hidden');
                    locateBtn.addEventListener('click', function() {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            var userLat = position.coords.latitude;
                            var userLng = position.coords.longitude;
                            marker.setLatLng([userLat, userLng]);
                            map.setView([userLat, userLng], 16);
                            updateInputs(userLat, userLng);
                        });
                    });
                }
            } catch(e) { console.warn(e); }

            // Cascading Logic
            var regionSelect = document.getElementById('region_code');
            var stoSelect = document.getElementById('sto_code');
            var stbSelect = document.getElementById('stb_code');
            var odcSelect = document.getElementById('odc_id');
            var odpSelect = document.getElementById('odp_id');

            regionSelect.addEventListener('change', function() {
                var regionId = this.options[this.selectedIndex].getAttribute('data-id');
                Array.from(stoSelect.options).forEach(opt => {
                    if (opt.value === "") return;
                    opt.classList.toggle('hidden', opt.getAttribute('data-region-id') !== regionId);
                });
                stoSelect.value = "";
                stbSelect.value = "";
            });

            stoSelect.addEventListener('change', function() {
                var stoId = this.options[this.selectedIndex].getAttribute('data-id');
                Array.from(stbSelect.options).forEach(opt => {
                    if (opt.value === "") return;
                    opt.classList.toggle('hidden', opt.getAttribute('data-sto-id') !== stoId);
                });
                stbSelect.value = "";
            });

            odcSelect.addEventListener('change', function() {
                var odcId = this.value;
                Array.from(odpSelect.options).forEach(opt => {
                    if (opt.value === "") return;
                    opt.classList.toggle('hidden', opt.getAttribute('data-odc-id') !== odcId);
                });
                odpSelect.value = "";
            });

            // Inventory SN Sync Logic
            var modemSelect = document.querySelector('select[name="modem_stock_id"]');
            var onuSnInput = document.getElementById('onu_sn');
            var onuTypeSelect = document.getElementById('onu_type');

            modemSelect.addEventListener('change', function() {
                var selected = this.options[this.selectedIndex];
                var badge = document.getElementById('sn-sync-badge');
                
                if (!selected || selected.value === "") {
                    if (badge) badge.classList.add('hidden');
                    return;
                }

                var sn = selected.getAttribute('data-sn');
                var type = selected.getAttribute('data-type');
                
                if (sn) {
                    onuSnInput.value = sn.toUpperCase();
                    if (badge) badge.classList.remove('hidden');
                }
                
                // Try to match onu_type select or add as custom
                if (type) {
                    let matched = false;
                    Array.from(onuTypeSelect.options).forEach(opt => {
                        if (opt.value.toLowerCase().includes(type.toLowerCase())) {
                            onuTypeSelect.value = opt.value;
                            matched = true;
                        }
                    });
                    if (!matched) {
                        onuTypeSelect.value = "Other";
                    }
                }
            });

            // If user manually edits, hide the sync badge to indicate custom value
            onuSnInput.addEventListener('input', function() {
                var badge = document.getElementById('sn-sync-badge');
                if (badge) badge.classList.add('hidden');
            });
        })();
    </script>
    @endpush
</x-app-layout>
