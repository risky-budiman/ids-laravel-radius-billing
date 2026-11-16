<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit OLT Device') }}: {{ $olt->name }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-rose-100/80 border border-rose-200 text-rose-700 rounded-xl dark:bg-rose-900/30 dark:border-rose-800 dark:text-rose-400" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card overflow-hidden">
            <form action="{{ route('olts.update', $olt->id) }}" method="POST" class="p-8" x-data="{ ver: '{{ old('snmp_version', $olt->snmp_version ?? '2') }}' }">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info Section -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Informasi Dasar</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nama OLT</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $olt->name) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="mis. OLT STO JKT-01" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="olt_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Tipe OLT</label>
                        <select name="olt_type" id="olt_type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="HSGQ_EPON" {{ $olt->olt_type == 'HSGQ_EPON' ? 'selected' : '' }}>HSGQ EPON (E04ID / E08L / Generic)</option>
                            <option value="ZTE_C320" {{ $olt->olt_type == 'ZTE_C320' ? 'selected' : '' }}>ZTE ZXA10 C320</option>
                            <option value="ZTE_C300" {{ $olt->olt_type == 'ZTE_C300' ? 'selected' : '' }}>ZTE ZXA10 C300</option>
                            <option value="HIOSO" {{ $olt->olt_type == 'HIOSO' ? 'selected' : '' }}>Hioso (Generic)</option>
                            <option value="OTHER" {{ $olt->olt_type == 'OTHER' ? 'selected' : '' }}>Lainnya / Umum</option>
                        </select>
                        @error('olt_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="ip_address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Alamat IP Manajemen</label>
                        <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address', $olt->ip_address) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="10.10.10.2" required>
                        @error('ip_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Deskripsi / Lokasi</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $olt->description) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Catatan opsional">
                    </div>

                    <div class="space-y-2">
                        <label for="latitude" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Latitude</label>
                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $olt->latitude) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="-6.123456">
                    </div>

                    <div class="space-y-2">
                        <label for="longitude" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Longitude</label>
                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $olt->longitude) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="106.123456">
                    </div>

                    <!-- SNMP Configuration Section -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-sm font-bold text-amber-600 dark:text-amber-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">SNMP Configuration</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_port" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Port</label>
                        <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', $olt->snmp_port) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_version" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Version</label>
                        <select name="snmp_version" id="snmp_version" x-model="ver" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="1" {{ old('snmp_version', $olt->snmp_version) == '1' ? 'selected' : '' }}>Version 1</option>
                            <option value="2" {{ old('snmp_version', $olt->snmp_version ?? '2') == '2' ? 'selected' : '' }}>Version 2c</option>
                            <option value="3" {{ old('snmp_version', $olt->snmp_version ?? '2') == '3' ? 'selected' : '' }}>Version 3 (aman: user + enkripsi)</option>
                        </select>
                        @error('snmp_version') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- SNMPv3 (USM): tampil bila versi 3 -->
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 rounded-xl border border-indigo-100 dark:border-indigo-900/40 p-4" x-show="ver === '3'" x-cloak>
                        <p class="md:col-span-2 text-xs text-gray-500 dark:text-gray-400">SNMPv3 memakai user + autentikasi + enkripsi, sehingga kredensial tidak dikirim terbuka seperti community v1/v2c. Buat user yang sama di OLT. Password minimal 8 karakter; kosongkan password untuk tetap memakai yang tersimpan.</p>
                        <div class="space-y-2">
                            <label for="snmp_v3_user" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">User SNMPv3</label>
                            <input type="text" name="snmp_v3_user" id="snmp_v3_user" value="{{ old('snmp_v3_user', $olt->snmp_v3_user) }}" autocomplete="off" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                            @error('snmp_v3_user') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_level" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Tingkat keamanan</label>
                            <select name="snmp_v3_level" id="snmp_v3_level" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['authPriv' => 'authPriv — autentikasi + enkripsi (disarankan)', 'authNoPriv' => 'authNoPriv — autentikasi saja', 'noAuthNoPriv' => 'noAuthNoPriv — tanpa keamanan'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_level', $olt->snmp_v3_level ?: 'authPriv') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_auth_protocol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Protokol autentikasi</label>
                            <select name="snmp_v3_auth_protocol" id="snmp_v3_auth_protocol" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['SHA' => 'SHA', 'SHA256' => 'SHA-256', 'MD5' => 'MD5'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_auth_protocol', $olt->snmp_v3_auth_protocol ?: 'SHA') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_auth_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Password autentikasi</label>
                            <input type="password" name="snmp_v3_auth_password" id="snmp_v3_auth_password" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" placeholder="Tersimpan — kosongkan untuk tetap memakai yang lama">
                            @error('snmp_v3_auth_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_priv_protocol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Protokol enkripsi</label>
                            <select name="snmp_v3_priv_protocol" id="snmp_v3_priv_protocol" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500">
                                @foreach(['AES' => 'AES-128', 'DES' => 'DES'] as $v => $label)
                                    <option value="{{ $v }}" @selected(old('snmp_v3_priv_protocol', $olt->snmp_v3_priv_protocol ?: 'AES') === $v)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="snmp_v3_priv_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Password enkripsi</label>
                            <input type="password" name="snmp_v3_priv_password" id="snmp_v3_priv_password" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" placeholder="Tersimpan — kosongkan untuk tetap memakai yang lama">
                            @error('snmp_v3_priv_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_read_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Read Community</label>
                        <input type="password" name="snmp_read_community" id="snmp_read_community" value="" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Tersimpan — kosongkan untuk tetap memakai yang lama">
                        @error('snmp_read_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_write_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Write Community (RW)</label>
                        <input type="password" name="snmp_write_community" id="snmp_write_community" value="" autocomplete="new-password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="{{ $olt->snmp_write_community ? 'Tersimpan — kosongkan untuk tetap memakai yang lama' : 'Belum diisi' }}">
                        @error('snmp_write_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center space-x-3 mt-4">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $olt->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Aktifkan Perangkat</label>
                    </div>
                </div>

                <div class="mt-10 flex justify-end space-x-4 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('olts.index') }}" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 transition-all">Batal</a>
                    <button type="submit" class="px-10 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-sm transition-all">Perbarui Perangkat OLT</button>
                </div>
            </form>

            <div class="px-8 pb-8 flex justify-start">
                <form action="{{ route('olts.destroy', $olt->id) }}" method="POST" onsubmit="return confirm('Hapus OLT ini secara permanen? OLT yang masih dipakai pelanggan tidak bisa dihapus.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 text-xs font-bold uppercase hover:underline">Hapus Perangkat Permanen</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
