{{--
    Form OLT bersama untuk tambah & ubah. $olt = null saat tambah.
    Kredensial (community, password SNMPv3, password web) tidak pernah ditampilkan; kosong saat ubah = tetap.
--}}
@php
    $editing = (bool) $olt;
    $keepHint = 'Tersimpan — kosongkan bila tidak diubah';
    $label = 'block text-sm font-medium text-surface-700 dark:text-surface-300';
    $help = 'mt-1 text-xs text-surface-500 dark:text-surface-400';
@endphp
<div class="space-y-6" x-data="{ type: '{{ old('olt_type', $olt->olt_type ?? 'HSGQ_EPON') }}', ver: '{{ old('snmp_version', $olt->snmp_version ?? '2') }}' }">
    @if($errors->any())
        <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
            <x-icon name="exclamation" class="w-5 h-5 shrink-0" />
            <div><p class="font-medium">Periksa kembali isian berikut:</p>
                <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        </div>
    @endif

    {{-- Identitas --}}
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">Identitas OLT</h3>
            <p class="text-xs text-surface-500">Nama, tipe perangkat dan lokasinya.</p>
        </div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="name" class="{{ $label }}">Nama OLT</label>
                <input type="text" name="name" id="name" value="{{ old('name', $olt->name ?? '') }}" class="input mt-1" placeholder="mis. OLT POP Gotong Royong" required>
            </div>
            <div>
                <label for="olt_type" class="{{ $label }}">Tipe OLT</label>
                <select name="olt_type" id="olt_type" x-model="type" class="input mt-1">
                    @foreach(\App\Models\Olt::TYPES as $value => $text)
                        <option value="{{ $value }}" @selected(old('olt_type', $olt->olt_type ?? 'HSGQ_EPON') === $value)>{{ $text }}{{ $value === 'HSGQ_EPON' ? ' (E04ID / E08L)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ip_address" class="{{ $label }}">IP manajemen</label>
                <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $olt->ip_address ?? '') }}" class="input mt-1 font-mono" placeholder="10.10.10.2" required>
                <p class="{{ $help }}">Harus bisa dijangkau server billing (UDP 161 untuk SNMP).</p>
            </div>
            <div>
                <label for="description" class="{{ $label }}">Lokasi / keterangan</label>
                <input type="text" name="description" id="description" value="{{ old('description', $olt->description ?? '') }}" class="input mt-1" placeholder="mis. POP Gotong Royong">
            </div>
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                <div>
                    <label for="latitude" class="{{ $label }}">Latitude</label>
                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $olt->latitude ?? '') }}" class="input mt-1 font-mono" placeholder="-6.123456">
                </div>
                <div>
                    <label for="longitude" class="{{ $label }}">Longitude</label>
                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $olt->longitude ?? '') }}" class="input mt-1 font-mono" placeholder="106.123456">
                </div>
                <a href="#" class="btn-secondary" title="Buka koordinat di Google Maps"
                   onclick="const la=document.getElementById('latitude').value, lo=document.getElementById('longitude').value; if(!la||!lo){alert('Isi latitude & longitude dulu.');return false;} window.open('https://www.google.com/maps?q='+encodeURIComponent(la+','+lo),'_blank'); return false;">
                    <x-icon name="map" class="w-4 h-4" /> Lihat di peta
                </a>
            </div>
            <label class="md:col-span-2 flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $olt->is_active ?? true)) class="rounded border-surface-300 text-primary-600">
                OLT aktif <span class="text-xs text-surface-500">(nonaktif = tidak dipantau & tidak ditampilkan di NOC)</span>
            </label>
        </div>
    </section>

    {{-- SNMP --}}
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">SNMP</h3>
            <p class="text-xs text-surface-500">Semua pemantauan & data ONU dibaca lewat SNMP. Disarankan v3, atau batasi akses SNMP OLT ke IP server billing.</p>
        </div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="snmp_version" class="{{ $label }}">Versi</label>
                <select name="snmp_version" id="snmp_version" x-model="ver" class="input mt-1">
                    <option value="1" @selected((string) old('snmp_version', $olt->snmp_version ?? '2') === '1')>Version 1</option>
                    <option value="2" @selected((string) old('snmp_version', $olt->snmp_version ?? '2') === '2')>Version 2c</option>
                    <option value="3" @selected((string) old('snmp_version', $olt->snmp_version ?? '2') === '3')>Version 3 (aman: user + enkripsi)</option>
                </select>
            </div>
            <div>
                <label for="snmp_port" class="{{ $label }}">Port</label>
                <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', $olt->snmp_port ?? 161) }}" class="input mt-1" required>
            </div>

            {{-- v1/v2c --}}
            <div x-show="ver !== '3'">
                <label for="snmp_read_community" class="{{ $label }}">Community baca</label>
                <input type="password" name="snmp_read_community" id="snmp_read_community" autocomplete="new-password" class="input mt-1"
                       @if($editing) placeholder="{{ $keepHint }}" @else value="{{ old('snmp_read_community', 'public') }}" @endif>
            </div>
            <div x-show="ver !== '3'">
                <label for="snmp_write_community" class="{{ $label }}">Community tulis</label>
                <input type="password" name="snmp_write_community" id="snmp_write_community" autocomplete="new-password" class="input mt-1"
                       placeholder="{{ $editing ? ($olt->snmp_write_community ? $keepHint : 'Belum diisi') : 'Opsional' }}">
                <p class="{{ $help }}">Dipakai untuk perintah tulis (mis. reboot ONU ZTE).</p>
            </div>

            {{-- v3 --}}
            <template x-if="ver === '3'">
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5 rounded-lg bg-surface-50 dark:bg-surface-800/50 p-4">
                    <p class="md:col-span-2 text-xs text-surface-500">Buat user SNMPv3 yang sama di OLT. Password minimal 8 karakter{{ $editing ? '; kosongkan untuk tetap memakai yang tersimpan' : '' }}.</p>
                    <div>
                        <label for="snmp_v3_user" class="{{ $label }}">User SNMPv3</label>
                        <input type="text" name="snmp_v3_user" id="snmp_v3_user" value="{{ old('snmp_v3_user', $olt->snmp_v3_user ?? '') }}" autocomplete="off" class="input mt-1">
                    </div>
                    <div>
                        <label for="snmp_v3_level" class="{{ $label }}">Tingkat keamanan</label>
                        <select name="snmp_v3_level" id="snmp_v3_level" class="input mt-1">
                            @foreach(['authPriv' => 'authPriv — autentikasi + enkripsi (disarankan)', 'authNoPriv' => 'authNoPriv — autentikasi saja', 'noAuthNoPriv' => 'noAuthNoPriv — tanpa keamanan'] as $v => $t)
                                <option value="{{ $v }}" @selected(old('snmp_v3_level', $olt->snmp_v3_level ?? 'authPriv') === $v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="snmp_v3_auth_protocol" class="{{ $label }}">Autentikasi</label>
                        <select name="snmp_v3_auth_protocol" id="snmp_v3_auth_protocol" class="input mt-1">
                            @foreach(['SHA' => 'SHA', 'SHA256' => 'SHA-256', 'MD5' => 'MD5'] as $v => $t)
                                <option value="{{ $v }}" @selected(old('snmp_v3_auth_protocol', $olt->snmp_v3_auth_protocol ?? 'SHA') === $v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="snmp_v3_auth_password" class="{{ $label }}">Password autentikasi</label>
                        <input type="password" name="snmp_v3_auth_password" id="snmp_v3_auth_password" autocomplete="new-password" class="input mt-1"
                               placeholder="{{ $editing && $olt->snmp_v3_auth_password ? $keepHint : 'Minimal 8 karakter' }}">
                    </div>
                    <div>
                        <label for="snmp_v3_priv_protocol" class="{{ $label }}">Enkripsi</label>
                        <select name="snmp_v3_priv_protocol" id="snmp_v3_priv_protocol" class="input mt-1">
                            @foreach(['AES' => 'AES-128', 'DES' => 'DES'] as $v => $t)
                                <option value="{{ $v }}" @selected(old('snmp_v3_priv_protocol', $olt->snmp_v3_priv_protocol ?? 'AES') === $v)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="snmp_v3_priv_password" class="{{ $label }}">Password enkripsi</label>
                        <input type="password" name="snmp_v3_priv_password" id="snmp_v3_priv_password" autocomplete="new-password" class="input mt-1"
                               placeholder="{{ $editing && $olt->snmp_v3_priv_password ? $keepHint : 'Minimal 8 karakter' }}">
                    </div>
                </div>
            </template>
        </div>
    </section>

    {{-- Akses web OLT (HSGQ) --}}
    <section class="card" x-show="type.startsWith('HSGQ')" x-cloak>
        <div class="card-header">
            <h3 class="card-title">Akses web OLT <span class="text-xs font-normal text-surface-500">(opsional)</span></h3>
            <p class="text-xs text-surface-500">Login halaman web OLT HSGQ: menampilkan deskripsi, status pasti, alasan putus & data optik ONU, serta model/firmware OLT. Password disimpan terenkripsi.</p>
        </div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="web_username" class="{{ $label }}">Username</label>
                <input type="text" name="web_username" id="web_username" value="{{ old('web_username', $olt->web_username ?? '') }}" autocomplete="off" class="input mt-1">
            </div>
            <div>
                <label for="web_password" class="{{ $label }}">Password</label>
                <input type="password" name="web_password" id="web_password" autocomplete="new-password" class="input mt-1"
                       placeholder="{{ $editing ? ($olt->web_password ? $keepHint : 'Belum diisi') : 'Password login web OLT' }}">
                <p class="{{ $help }}">Salah password berulang bisa mengunci akun web OLT.</p>
            </div>
            <div>
                <label for="web_port" class="{{ $label }}">Port</label>
                <input type="number" name="web_port" id="web_port" value="{{ old('web_port', $olt->web_port ?? 80) }}" class="input mt-1">
            </div>
            <div class="flex items-end gap-4">
                <label class="flex items-center gap-2 pb-2 text-sm text-surface-700 dark:text-surface-300">
                    <input type="checkbox" name="web_https" value="1" @checked(old('web_https', $olt->web_https ?? false)) class="rounded border-surface-300 text-primary-600"> Pakai HTTPS
                </label>
                @if($editing)
                    <div x-data="{ t: false, msg: '', ok: false }" class="pb-1">
                        <button type="button" class="btn-secondary btn-sm" :disabled="t"
                                @click="t = true; msg = ''; fetch(@js(route('olts.test-web-login', $olt, false)), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(d => { ok = d.success; msg = d.message; }).catch(() => { ok = false; msg = 'Gagal menghubungi server'; }).finally(() => t = false)">
                            <span x-text="t ? 'Menguji…' : 'Tes login web'"></span>
                        </button>
                        <p x-show="msg" x-text="msg" class="mt-1 text-xs" :class="ok ? 'text-emerald-600' : 'text-rose-600'"></p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
