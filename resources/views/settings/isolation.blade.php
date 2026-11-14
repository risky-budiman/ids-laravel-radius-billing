@php
    $v = fn ($k) => old($k, $cfg[$k] ?? '');
    $enabled = old('isolir_enabled', $cfg['isolir_enabled']) == '1';
    $mode = $v('isolir_mode') ?: 'range';
    $group = $v('isolir_group') ?: 'ISOLIR';
    $host = $cfg['isolir_page_host'] ?: '<IP-SERVER-BILLING>';
    $hostIp = explode(':', $host)[0];
    $rangeText = ($cfg['isolir_ip_start'] && $cfg['isolir_ip_end']) ? $cfg['isolir_ip_start'] . '-' . $cfg['isolir_ip_end'] : '<IP-AWAL>-<IP-AKHIR>';
    $srcMatch = $cfg['isolir_address_list'] ? 'src-address-list=' . $cfg['isolir_address_list'] : 'src-address=' . $rangeText;
    $pct = $capacity['total'] ? min(100, round($capacity['used'] / $capacity['total'] * 100)) : 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Pengaturan Isolir</h2>
            <p class="page-subtitle">IP isolir yang diberikan RADIUS kepada pelanggan yang diisolir karena menunggak.</p>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Status" :value="$cfg['isolir_enabled'] === '1' ? 'Aktif' : 'Nonaktif'" icon="shield-check"
                         :tone="$cfg['isolir_enabled'] === '1' ? 'success' : 'gray'"
                         :hint="$cfg['isolir_enabled'] === '1' ? 'Mode: ' . ($cfg['isolir_mode'] === 'pool' ? 'Pool MikroTik' : 'Rentang IP') : 'Isolir hanya memutus sesi'" />
            <x-stat-card label="Pelanggan terisolir" :value="number_format($isolations->count(), 0, ',', '.')" icon="users" tone="warning"
                         :hint="$suspendedCount . ' pelanggan berstatus suspended'" />
            <x-stat-card label="IP isolir terpakai" :value="$capacity['total'] ? $capacity['used'] . ' / ' . number_format($capacity['total'], 0, ',', '.') : '—'"
                         icon="signal" :tone="$pct >= 90 ? 'danger' : 'info'"
                         :hint="$capacity['total'] ? $capacity['free'] . ' IP tersedia' : 'Rentang IP belum diatur'" />
        </div>

        <form method="POST" action="{{ route('settings.isolation.update') }}" class="card" x-data="{ enabled: {{ $enabled ? 'true' : 'false' }}, mode: '{{ $mode }}' }">
            @csrf
            <div class="card-header flex items-center justify-between gap-4">
                <div>
                    <h3 class="card-title">Isolir lewat RADIUS</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Saat diisolir, pelanggan dipindah ke grup <span class="font-mono">{{ $group }}</span> dan mendapat IP isolir. Setelah lunas, paket & IP aslinya dipulihkan otomatis.</p>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                    <input type="hidden" name="isolir_enabled" value="0">
                    <input type="checkbox" name="isolir_enabled" value="1" x-model="enabled" class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm font-medium">Aktifkan</span>
                </label>
            </div>

            <div class="card-body space-y-6" :class="enabled ? '' : 'opacity-60'">
                {{-- Mode alokasi --}}
                <fieldset>
                    <legend class="label">Cara pemberian IP isolir</legend>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="flex gap-3 rounded-lg border p-4 cursor-pointer transition-colors"
                               :class="mode === 'range' ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-500/10' : 'border-surface-200 dark:border-surface-700'">
                            <input type="radio" name="isolir_mode" value="range" x-model="mode" class="mt-1 text-primary-600 focus:ring-primary-500">
                            <span>
                                <span class="block font-medium text-surface-900 dark:text-white">Rentang IP (dialokasikan sistem)</span>
                                <span class="block text-xs text-surface-500 mt-1">Sistem memilih 1 IP kosong per pelanggan dan mengirimnya lewat RADIUS (Framed-IP-Address). Halaman isolir bisa mengenali pelanggan & menampilkan tagihannya.</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border p-4 cursor-pointer transition-colors"
                               :class="mode === 'pool' ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-500/10' : 'border-surface-200 dark:border-surface-700'">
                            <input type="radio" name="isolir_mode" value="pool" x-model="mode" class="mt-1 text-primary-600 focus:ring-primary-500">
                            <span>
                                <span class="block font-medium text-surface-900 dark:text-white">Pool MikroTik</span>
                                <span class="block text-xs text-surface-500 mt-1">RADIUS mengirim nama pool (Framed-Pool); MikroTik yang membagikan IP. Halaman isolir hanya menampilkan pesan umum.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="grid sm:grid-cols-2 gap-4" x-show="mode === 'range'">
                    <div>
                        <label for="isolir_ip_start" class="label">IP awal</label>
                        <input id="isolir_ip_start" name="isolir_ip_start" value="{{ $v('isolir_ip_start') }}" class="input font-mono" placeholder="10.255.0.2" inputmode="decimal">
                    </div>
                    <div>
                        <label for="isolir_ip_end" class="label">IP akhir</label>
                        <input id="isolir_ip_end" name="isolir_ip_end" value="{{ $v('isolir_ip_end') }}" class="input font-mono" placeholder="10.255.3.254" inputmode="decimal">
                        <p class="help-text">Alamat berakhiran .0 dan .255 dilewati. Gunakan subnet yang tidak dipakai pelanggan aktif.</p>
                    </div>
                </div>

                <div x-show="mode === 'pool'" x-cloak>
                    <label for="isolir_pool_name" class="label">Nama IP pool di MikroTik</label>
                    <input id="isolir_pool_name" name="isolir_pool_name" value="{{ $v('isolir_pool_name') }}" class="input font-mono sm:max-w-xs" placeholder="pool-isolir">
                    <p class="help-text">Pool dengan nama ini harus ada di setiap router (NAS).</p>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 pt-2 border-t border-surface-100 dark:border-surface-800">
                    <div>
                        <label for="isolir_rate_limit" class="label">Batas kecepatan saat isolir <span class="text-surface-400 font-normal">(opsional)</span></label>
                        <input id="isolir_rate_limit" name="isolir_rate_limit" value="{{ $v('isolir_rate_limit') }}" class="input font-mono" placeholder="256k/256k">
                        <p class="help-text">Format Mikrotik-Rate-Limit, mis. <span class="font-mono">256k/256k</span>.</p>
                    </div>
                    <div>
                        <label for="isolir_address_list" class="label">Address list MikroTik <span class="text-surface-400 font-normal">(opsional)</span></label>
                        <input id="isolir_address_list" name="isolir_address_list" value="{{ $v('isolir_address_list') }}" class="input font-mono" placeholder="isolir">
                        <p class="help-text">IP pelanggan otomatis masuk address list ini — memudahkan aturan firewall.</p>
                    </div>
                    <div>
                        <label for="isolir_mikrotik_group" class="label">Profil PPP isolir <span class="text-surface-400 font-normal">(opsional)</span></label>
                        <input id="isolir_mikrotik_group" name="isolir_mikrotik_group" value="{{ $v('isolir_mikrotik_group') }}" class="input font-mono" placeholder="profile-isolir">
                    </div>
                    <div>
                        <label for="isolir_group" class="label">Nama grup RADIUS</label>
                        <input id="isolir_group" name="isolir_group" value="{{ $group }}" class="input font-mono" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="isolir_page_host" class="label">Alamat server halaman isolir <span class="text-surface-400 font-normal">(untuk contoh skrip di bawah)</span></label>
                        <input id="isolir_page_host" name="isolir_page_host" value="{{ $v('isolir_page_host') }}" class="input font-mono sm:max-w-sm" placeholder="103.10.10.5:8088">
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-lg bg-surface-50 dark:bg-surface-800/50 p-4 cursor-pointer">
                    <input type="checkbox" name="disconnect_now" value="1" class="mt-0.5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm">
                        <span class="font-medium">Putus sesi semua pelanggan terisolir setelah disimpan</span>
                        <span class="block text-xs text-surface-500 mt-0.5">Agar langsung memakai IP isolir baru. Tanpa ini, perubahan berlaku saat pelanggan tersambung ulang.</span>
                    </span>
                </label>
            </div>

            <div class="px-6 py-4 border-t border-surface-200 dark:border-surface-800 flex justify-end">
                <button type="submit" class="btn-primary">Simpan & terapkan</button>
            </div>
        </form>

        {{-- Contoh konfigurasi router --}}
        <section class="card" x-data="{ copied: false }">
            <div class="card-header flex items-center justify-between gap-4">
                <div>
                    <h3 class="card-title">Contoh konfigurasi MikroTik</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Sesuaikan dengan jaringan Anda sebelum dijalankan di setiap router (NAS).</p>
                </div>
                <button type="button" class="btn-secondary btn-sm"
                        @click="navigator.clipboard.writeText($refs.script.innerText); copied = true; setTimeout(() => copied = false, 1500)">
                    <span x-text="copied ? 'Tersalin' : 'Salin'"></span>
                </button>
            </div>
<pre x-ref="script" class="m-0 overflow-x-auto bg-surface-950 text-surface-100 text-xs leading-relaxed p-5 rounded-b-xl font-mono"><code>@if($cfg['isolir_mode'] === 'pool')/ip pool add name={{ $cfg['isolir_pool_name'] ?: 'pool-isolir' }} ranges={{ $rangeText }}
@endif
# Izinkan DNS & akses ke server billing, arahkan HTTP ke halaman isolir, blokir sisanya
/ip firewall nat add chain=dstnat {{ $srcMatch }} protocol=tcp dst-port=80 dst-address=!{{ $hostIp }} action=dst-nat to-addresses={{ $hostIp }} to-ports={{ explode(':', $host)[1] ?? '80' }} comment="Isolir: redirect ke halaman tagihan"
/ip firewall filter add chain=forward {{ $srcMatch }} dst-address={{ $hostIp }} action=accept comment="Isolir: izinkan server billing"
/ip firewall filter add chain=forward {{ $srcMatch }} protocol=udp dst-port=53 action=accept comment="Isolir: izinkan DNS"
/ip firewall filter add chain=forward {{ $srcMatch }} action=drop comment="Isolir: blokir internet"</code></pre>
            <div class="card-body text-xs text-surface-500 dark:text-surface-400 space-y-2">
                <p>Halaman isolir tersedia di <span class="font-mono text-surface-700 dark:text-surface-200">{{ route('isolation.page') }}</span>. Karena redirect MikroTik tidak bisa mengubah path, siapkan port khusus di nginx yang selalu menampilkan halaman ini, misalnya:</p>
<pre class="m-0 overflow-x-auto bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-200 p-3 rounded-lg font-mono">server {
    listen {{ explode(':', $host)[1] ?? '8088' }};
    location / { proxy_pass http://127.0.0.1/isolir; proxy_set_header Host {{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost' }}; proxy_set_header X-Forwarded-For $remote_addr; }
}</pre>
                <p>Pastikan aplikasi mempercayai header <span class="font-mono">X-Forwarded-For</span> dari 127.0.0.1 agar pelanggan dikenali dari IP isolirnya.</p>
            </div>
        </section>

        {{-- Daftar pelanggan terisolir --}}
        <section class="card overflow-hidden">
            <div class="card-header"><h3 class="card-title">Pelanggan terisolir ({{ $isolations->count() }})</h3></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr><th>Pelanggan</th><th>Username</th><th>IP isolir</th><th>Sejak</th></tr>
                    </thead>
                    <tbody>
                        @forelse($isolations as $iso)
                            <tr>
                                <td>
                                    @if($iso->customer)
                                        <a href="{{ route('customers.show', $iso->customer) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $iso->customer->name }}</a>
                                        <span class="block text-xs text-surface-500">{{ $iso->customer->customer_code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="font-mono text-xs">{{ $iso->username }}</td>
                                <td class="font-mono">
                                    @if($iso->mode === 'pool')
                                        <span class="text-surface-500">dari pool</span>
                                    @else
                                        {{ $iso->ip_address ?? '—' }}
                                        @unless($iso->ip_address)<span class="badge-danger ml-1">rentang penuh</span>@endunless
                                    @endif
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $iso->isolated_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-surface-500 py-8">Belum ada pelanggan yang diisolir lewat RADIUS.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
