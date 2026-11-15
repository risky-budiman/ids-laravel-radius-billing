@php
    $v = fn ($k) => old($k, $cfg[$k] ?? '');
    $enabled = old('isolir_enabled', $cfg['isolir_enabled']) == '1';
    $mode = $v('isolir_mode') ?: 'range';
    $group = $v('isolir_group') ?: 'ISOLIR';
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

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="isolir_ip_start" class="label">IP awal</label>
                        <input id="isolir_ip_start" name="isolir_ip_start" value="{{ $v('isolir_ip_start') }}" class="input font-mono" placeholder="10.255.0.2" inputmode="decimal">
                    </div>
                    <div>
                        <label for="isolir_ip_end" class="label">IP akhir</label>
                        <input id="isolir_ip_end" name="isolir_ip_end" value="{{ $v('isolir_ip_end') }}" class="input font-mono" placeholder="10.255.3.254" inputmode="decimal">
                        <p class="help-text" x-text="mode === 'range' ? 'Alamat berakhiran .0 dan .255 dilewati. Gunakan subnet yang tidak dipakai pelanggan aktif.' : 'Mode pool: rentang ini dipakai untuk membuat IP pool & aturan firewall di skrip MikroTik.'"></p>
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
                        <label for="isolir_mikrotik_group" class="label">Profil PPP isolir di MikroTik <span class="text-surface-400 font-normal">(opsional)</span></label>
                        <input id="isolir_mikrotik_group" name="isolir_mikrotik_group" value="{{ $v('isolir_mikrotik_group') }}" class="input font-mono" placeholder="kosongkan = profil default PPPoE">
                        <p class="help-text">Kosongkan bila router tidak punya profil khusus isolir — pelanggan memakai profil default PPPoE server. Bila diisi, skrip di bawah membuat profil ini otomatis (salinan profil <span class="font-mono">default</span>) jika belum ada.</p>
                    </div>
                    <div>
                        <label for="isolir_group" class="label">Nama grup RADIUS <span class="text-surface-400 font-normal">(internal)</span></label>
                        <input id="isolir_group" name="isolir_group" value="{{ $group }}" class="input font-mono" placeholder="ISOLIR">
                        <p class="help-text">Grup di database FreeRADIUS yang dibuat otomatis oleh sistem — <strong>bukan</strong> profil MikroTik dan tidak perlu dibuat di router. Biarkan ISOLIR.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="isolir_page_host" class="label">Alamat server halaman isolir <span class="text-surface-400 font-normal">(untuk contoh skrip di bawah)</span></label>
                        <input id="isolir_page_host" name="isolir_page_host" value="{{ $v('isolir_page_host') }}" class="input font-mono sm:max-w-sm" placeholder="{{ ($serverIp ?? '103.10.10.5') . ':' . $pagePort }} (otomatis)">
                    </div>
                </div>

                {{-- Bypass: tujuan yang tetap bisa diakses pelanggan terisolir --}}
                <div class="pt-2 border-t border-surface-100 dark:border-surface-800">
                    <p class="label">Tetap bisa diakses saat isolir (bypass)</p>
                    <p class="help-text !mt-0 mb-3">Server billing & kanal pembayaran agar pelanggan terisolir tetap bisa membayar. Daftar otomatis disusun dari data sistem:</p>
                    <div class="rounded-lg border border-surface-200 dark:border-surface-700 divide-y divide-surface-100 dark:divide-surface-800 max-h-60 overflow-y-auto">
                        @forelse($allowedHosts as $h)
                            <div class="flex items-center justify-between gap-3 px-3 py-1.5 text-xs">
                                <span class="font-mono text-surface-800 dark:text-surface-200">{{ $h['address'] }}</span>
                                <span class="text-surface-400 text-right">{{ $h['source'] }}</span>
                            </div>
                        @empty
                            <p class="px-3 py-3 text-xs text-surface-500">Belum ada — isi APP_URL / alamat server, aktifkan gateway pembayaran, atau tambah manual.</p>
                        @endforelse
                    </div>
                    <label for="isolir_allowed_hosts" class="label mt-4">Tambahan manual <span class="text-surface-400 font-normal">(opsional, satu per baris)</span></label>
                    <textarea id="isolir_allowed_hosts" name="isolir_allowed_hosts" rows="3" class="input !h-auto font-mono text-xs"
                              placeholder="# domain, IP, atau CIDR&#10;bayar.contoh-bank.co.id&#10;103.20.30.0/24">{{ $v('isolir_allowed_hosts') }}</textarea>
                    <p class="help-text">Mis. halaman bank / e-wallet lain. Wildcard (*.domain) tidak didukung MikroTik — tulis domain lengkapnya.</p>
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

        {{-- Masa tenggang sebelum isolir --}}
        <form method="POST" action="{{ route('settings.isolation.grace') }}" class="card">
            @csrf
            <div class="card-header">
                <h3 class="card-title">Masa tenggang sebelum isolir</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">Berapa hari setelah tanggal jatuh tempo invoice pelanggan yang belum membayar diisolir (billing harian). Berlaku juga untuk daftar "akan diisolir" di laporan Piutang. Masa aktif prabayar (renewal) tidak terpengaruh.</p>
            </div>
            <div class="card-body flex flex-wrap items-end gap-3">
                <div>
                    <label for="isolir_grace_days" class="label">Isolir setelah jatuh tempo +</label>
                    <div class="flex items-center gap-2">
                        <input id="isolir_grace_days" name="isolir_grace_days" type="number" min="0" max="{{ \App\Models\Invoice::MAX_GRACE_DAYS }}"
                               value="{{ old('isolir_grace_days', \App\Models\Invoice::graceDays()) }}" class="input !w-28 text-right tabular-nums" required>
                        <span class="text-sm text-surface-600 dark:text-surface-300">hari</span>
                    </div>
                    <p class="help-text">0 = diisolir sehari setelah jatuh tempo (bawaan).</p>
                </div>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>

        {{-- Pengajuan dismantle otomatis --}}
        @php
            $adEnabled = old('auto_dismantle_enabled', $autoDismantle->enabled() ? '1' : '0') == '1';
            $adDays = old('auto_dismantle_days', $autoDismantle->days());
            $adCandidates = $autoDismantle->candidates();
        @endphp
        <form method="POST" action="{{ route('settings.isolation.dismantle') }}" class="card">
            @csrf
            <div class="card-header flex items-center justify-between gap-4">
                <div>
                    <h3 class="card-title">Pengajuan dismantle otomatis</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Pelanggan yang terisolir <strong>karena menunggak</strong> lebih lama dari batas di bawah otomatis diubah menjadi <strong>Menunggu dismantle</strong>: tiket penarikan perangkat dibuat, tagihan bulanan berhenti, dan isolir tetap berlaku sampai perangkat ditarik. Dijalankan setiap hari pukul 01:30.</p>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                    <input type="hidden" name="auto_dismantle_enabled" value="0">
                    <input type="checkbox" name="auto_dismantle_enabled" value="1" @checked($adEnabled) class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm font-medium">Aktifkan</span>
                </label>
            </div>
            <div class="card-body space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="auto_dismantle_days" class="label">Ajukan setelah terisolir</label>
                        <div class="flex items-center gap-2">
                            <input id="auto_dismantle_days" name="auto_dismantle_days" type="number" min="{{ \App\Services\AutoDismantleService::MIN_DAYS }}" max="{{ \App\Services\AutoDismantleService::MAX_DAYS }}"
                                   value="{{ $adDays }}" class="input !w-28 text-right tabular-nums" required>
                            <span class="text-sm text-surface-600 dark:text-surface-300">hari</span>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>

                <div>
                    <p class="text-sm font-medium text-surface-900 dark:text-white">
                        Akan diajukan pada run berikutnya: {{ $adCandidates->count() }} pelanggan
                        @unless($autoDismantle->enabled())<span class="badge-gray ml-1">nonaktif — belum ada yang diajukan</span>@endunless
                    </p>
                    @if($adCandidates->isNotEmpty())
                        <div class="mt-2 overflow-x-auto rounded-lg border border-surface-200 dark:border-surface-700">
                            <table class="table">
                                <thead><tr><th>Pelanggan</th><th>Terisolir sejak</th><th class="text-right">Lama</th><th class="text-right">Tunggakan</th></tr></thead>
                                <tbody>
                                    @foreach($adCandidates as $c)
                                        <tr>
                                            <td>
                                                <a href="{{ route('customers.show', $c) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $c->name }}</a>
                                                <span class="block text-xs text-surface-500">{{ $c->customer_code }}</span>
                                            </td>
                                            <td class="text-xs whitespace-nowrap">{{ $c->suspended_at->locale('id')->translatedFormat('d F Y') }}</td>
                                            <td class="amount">{{ (int) $c->suspended_at->diffInDays(now()) }} hari</td>
                                            <td class="amount">{{ rupiah($c->unpaid_total ?? 0, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <p class="help-text">Bila pelanggan melunasi sebelum perangkat ditarik, batalkan tiket dismantle-nya: tanpa tunggakan tersisa, layanannya langsung aktif kembali.</p>
                </div>
            </div>
        </form>

        {{-- Contoh konfigurasi router: diisi otomatis dari data sistem --}}
        <section class="card" x-data="{ copied: false }">
            <div class="card-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="card-title">Skrip MikroTik (otomatis dari data sistem)</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400">
                        Server billing: <span class="font-mono">{{ $serverIp ?? 'tidak terdeteksi — isi "Alamat server halaman isolir"' }}</span>
                        · halaman isolir port <span class="font-mono">{{ $pagePort }}</span>
                        · {{ count($allowedHosts) }} tujuan bypass
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($nasList->isNotEmpty())
                        <form method="GET" action="{{ route('settings.isolation') }}">
                            <label for="nas" class="sr-only">Router (NAS)</label>
                            <select id="nas" name="nas" class="input !h-9 !w-auto" onchange="this.form.submit()">
                                @foreach($nasList as $n)
                                    <option value="{{ $n->id }}" @selected($nas && $nas->id === $n->id)>{{ $n->shortname ?: $n->nasname }} ({{ $n->nasname }})</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <button type="button" class="btn-secondary btn-sm"
                            @click="navigator.clipboard.writeText($refs.script.innerText); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-text="copied ? 'Tersalin' : 'Salin'"></span>
                    </button>
                    <a href="{{ route('settings.isolation.script', ['nas' => $nas?->id]) }}" class="btn-primary btn-sm">Unduh .rsc</a>
                </div>
            </div>
<pre x-ref="script" class="m-0 max-h-[28rem] overflow-auto bg-surface-950 text-surface-100 text-xs leading-relaxed p-5 font-mono">{{ $script }}</pre>
            <div class="card-body text-xs text-surface-500 dark:text-surface-400 space-y-3">
                <p>
                    Jalankan di <strong>setiap router</strong> (pilih router di atas — bagian RADIUS memakai secret NAS tersebut).
                    Unggah file <span class="font-mono">.rsc</span> ke router lalu jalankan <span class="font-mono">/import isolir-{{ $nas?->shortname ?: 'mikrotik' }}.rsc</span>, atau tempel di Terminal.
                    Skrip aman dijalankan ulang setiap kali daftar bypass berubah.
                </p>
                <p>Halaman isolir: siapkan port khusus nginx di server billing yang selalu menampilkan <span class="font-mono">{{ route('isolation.page') }}</span>:</p>
<pre class="m-0 overflow-x-auto bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-200 p-3 rounded-lg font-mono">{{ $nginx }}</pre>
                <p>
                    Agar halaman isolir mengenali pelanggan, server billing harus melihat IP isolir asli (tanpa masquerade dari router).
                    Bila tidak, halaman tetap tampil dengan tombol masuk ke portal pelanggan.
                </p>
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
