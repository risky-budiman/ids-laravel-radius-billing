<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New OLT') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="card overflow-hidden">
            <form action="{{ route('olts.store') }}" method="POST" class="p-8" x-data="{ ver: '{{ old('snmp_version', '2') }}' }">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info Section -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Informasi Dasar</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nama OLT</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="mis. OLT STO JKT-01" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="olt_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Tipe OLT</label>
                        <select name="olt_type" id="olt_type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="HSGQ_EPON" {{ old('olt_type') == 'HSGQ_EPON' ? 'selected' : '' }}>HSGQ EPON (E04ID / E08L / Generic)</option>
                            <option value="ZTE_C320" {{ old('olt_type') == 'ZTE_C320' ? 'selected' : '' }}>ZTE ZXA10 C320</option>
                            <option value="ZTE_C300" {{ old('olt_type') == 'ZTE_C300' ? 'selected' : '' }}>ZTE ZXA10 C300</option>
                            <option value="HIOSO" {{ old('olt_type') == 'HIOSO' ? 'selected' : '' }}>Hioso (Generic)</option>
                            <option value="OTHER" {{ old('olt_type') == 'OTHER' ? 'selected' : '' }}>Lainnya / Umum</option>
                        </select>
                        @error('olt_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="ip_address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Alamat IP Manajemen</label>
                        <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="10.10.10.2" required>
                        @error('ip_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Deskripsi / Lokasi</label>
                        <input type="text" name="description" id="description" value="{{ old('description') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Catatan opsional">
                    </div>

                    <div class="space-y-2">
                        <label for="latitude" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Latitude</label>
                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="-6.123456">
                    </div>

                    <div class="space-y-2">
                        <label for="longitude" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Longitude</label>
                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="106.123456">
                    </div>

                    <!-- SNMP Configuration Section -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-sm font-bold text-amber-600 dark:text-amber-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">SNMP Configuration</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_port" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Port</label>
                        <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', 161) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_version" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Version</label>
                        <select name="snmp_version" id="snmp_version" x-model="ver" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="1" {{ old('snmp_version') == '1' ? 'selected' : '' }}>Version 1</option>
                            <option value="2" {{ old('snmp_version', '2') == '2' ? 'selected' : '' }}>Version 2c</option>
                            <option value="3" {{ old('snmp_version', '2') == '3' ? 'selected' : '' }}>Version 3 (aman: user + enkripsi)</option>
                        </select>
                        @error('snmp_version') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- SNMPv3 (USM): tampil bila versi 3 -->
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 rounded-xl border border-indigo-100 dark:border-indigo-900/40 p-4" x-show="ver === '3'" x-cloak>
                        <p class="md:col-span-2 text-xs text-gray-500 dark:text-gray-400">SNMPv3 memakai user + autentikasi + enkripsi, sehingga kredensial tidak dikirim terbuka seperti community v1/v2c. Buat user yang sama di OLT. Password minimal 8 karakter.</p>
                        <div class="space-y-2">
                            <label for="snmp_v3_user" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">User SNMPv3</label>
                            <input type="text" name="snmp_v3_user" id="snmp_v3_user" value="{{ old('snmp_v3_user', '') }}" autocomplete="off" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                            @error('snmp_v3_user') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_level" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Tingkat keamanan</label>
                            <select name="snmp_v3_level" id="snmp_v3_level" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['authPriv' => 'authPriv — autentikasi + enkripsi (disarankan)', 'authNoPriv' => 'authNoPriv — autentikasi saja', 'noAuthNoPriv' => 'noAuthNoPriv — tanpa keamanan'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_level', 'authPriv') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_auth_protocol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Protokol autentikasi</label>
                            <select name="snmp_v3_auth_protocol" id="snmp_v3_auth_protocol" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['SHA' => 'SHA', 'SHA256' => 'SHA-256', 'MD5' => 'MD5'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_auth_protocol', 'SHA') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_auth_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Password autentikasi</label>
                            <input type="password" name="snmp_v3_auth_password" id="snmp_v3_auth_password" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" placeholder="Minimal 8 karakter">
                            @error('snmp_v3_auth_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_priv_protocol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Protokol enkripsi</label>
                            <select name="snmp_v3_priv_protocol" id="snmp_v3_priv_protocol" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['AES' => 'AES-128', 'DES' => 'DES'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_priv_protocol', 'AES') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_priv_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Password enkripsi</label>
                            <input type="password" name="snmp_v3_priv_password" id="snmp_v3_priv_password" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" placeholder="Minimal 8 karakter">
                            @error('snmp_v3_priv_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_read_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Read Community</label>
                        <input type="text" name="snmp_read_community" id="snmp_read_community" value="{{ old('snmp_read_community', 'public') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_read_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_write_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Write Community (RW)</label>
                        <input type="text" name="snmp_write_community" id="snmp_write_community" value="{{ old('snmp_write_community', 'private') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                        @error('snmp_write_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Metode akses -->
                    <div class="md:col-span-2 space-y-2">
                        <label for="access_method" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Metode Akses</label>
                        <select name="access_method" id="access_method" class="w-full md:w-1/2 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="snmp_telnet" @selected(old('access_method', 'snmp_telnet') === 'snmp_telnet')>SNMP + Telnet (pemantauan + registrasi/hapus/isolir ONU otomatis)</option>
                            <option value="snmp" @selected(old('access_method', 'snmp_telnet') === 'snmp')>SNMP saja (tanpa telnet)</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400">SNMP saja: pemantauan, sinyal & daftar ONU (ZTE) tetap jalan; registrasi, hapus & ganti profil ONU <strong>tidak dijalankan otomatis</strong> (dicatat di Log Aktivitas untuk dikerjakan manual di OLT), isolir cukup lewat RADIUS. Daftar ONU OLT HSGQ masih membutuhkan telnet. Kolom telnet di bawah boleh dikosongkan.</p>
                    </div>

                    <!-- CLI / Telnet Section (Optional) -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Konfigurasi CLI / Telnet (Opsional, hanya untuk provisioning)</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="telnet_port" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Port Telnet (Opsional)</label>
                        <input type="number" name="telnet_port" id="telnet_port" value="{{ old('telnet_port', 23) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                        @error('telnet_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Login Password</label>
                        <input type="password" name="password" id="password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label for="enable_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Enable Password</label>
                        <input type="password" name="enable_password" id="enable_password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Usually same as login password">
                    </div>

                    <div class="flex items-center space-x-3 mt-4">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Aktifkan Perangkat</label>
                    </div>
                </div>

                <div class="mt-10 flex justify-end space-x-4 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('olts.index') }}" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 transition-all">Batal</a>
                    <button type="submit" class="px-10 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-sm transition-all">Simpan Perangkat OLT</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
