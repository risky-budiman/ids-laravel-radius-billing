<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerIsolation;
use App\Models\Radius\RadGroupReply;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Isolir lewat RADIUS: pelanggan terisolir dipindah ke grup ISOLIR dan mendapat IP isolir.
 *
 *  - mode "range": aplikasi mengalokasikan satu IP dari rentang yang diatur pengguna
 *    (Framed-IP-Address per pelanggan, tercatat di customer_isolations).
 *  - mode "pool" : RADIUS mengirim Framed-Pool; IP dibagikan MikroTik dari pool tersebut.
 *
 * Grup paket & atribut IP asli (Framed-IP-Address / Framed-Pool) disimpan lalu dipulihkan
 * saat isolir dicabut. Perubahan baru berlaku setelah sesi PPPoE tersambung ulang — pemanggil
 * (jalur isolir / lunas) sudah memutus sesi.
 */
class IsolationService
{
    public const SETTINGS = [
        'isolir_enabled' => '0',
        'isolir_mode' => 'range',
        'isolir_group' => 'ISOLIR',
        'isolir_ip_start' => '',
        'isolir_ip_end' => '',
        'isolir_pool_name' => '',
        'isolir_rate_limit' => '',
        'isolir_address_list' => '',
        'isolir_mikrotik_group' => '',
        'isolir_page_host' => '',
        'isolir_allowed_hosts' => '',
    ];

    /** Nama address-list MikroTik untuk tujuan yang tetap boleh diakses saat isolir. */
    public const ALLOW_LIST = 'isolir-allow';

    /**
     * Domain halaman pembayaran per gateway (dibuka browser pelanggan, bukan hanya API server).
     * Midtrans: docs.midtrans.com/docs/ip-address (disarankan whitelist domain, bukan IP).
     * Xendit: invoice_url = checkout.xendit.co (staging: checkout-staging.xendit.co).
     * Duitku: domain halaman bayar diambil juga dari payment_url invoice yang tersimpan.
     */
    public const GATEWAY_HOSTS = [
        'midtrans' => ['app.midtrans.com', 'api.midtrans.com', 'app.sandbox.midtrans.com', 'api.sandbox.midtrans.com'],
        'xendit' => ['checkout.xendit.co', 'checkout-staging.xendit.co'],
        'duitku' => ['passport.duitku.com', 'sandbox.duitku.com'],
    ];

    /** Aset eksternal yang dimuat halaman tagihan/pembayaran aplikasi. */
    public const PORTAL_ASSET_HOSTS = ['fonts.bunny.net'];

    /** Atribut IP milik user yang harus disingkirkan sementara agar IP isolir berlaku. */
    private const IP_ATTRIBUTES = ['Framed-IP-Address', 'Framed-Pool'];

    public function config(): array
    {
        $cfg = [];
        foreach (self::SETTINGS as $key => $default) {
            $cfg[$key] = (string) (get_setting($key, $default) ?? $default);
        }

        return $cfg;
    }

    public function enabled(): bool
    {
        return $this->config()['isolir_enabled'] === '1';
    }

    public function groupName(): string
    {
        return $this->config()['isolir_group'] ?: 'ISOLIR';
    }

    // ------------------------------------------------------------------ isolir

    /**
     * Pasang isolir RADIUS untuk pelanggan (idempoten). Mengembalikan catatan isolir,
     * atau null bila fitur dimatikan.
     */
    public function isolate(Customer $customer): ?CustomerIsolation
    {
        if (! $this->enabled() || ! $customer->username) {
            return null;
        }

        $cfg = $this->config();

        return DB::transaction(function () use ($customer, $cfg) {
            $record = CustomerIsolation::where('customer_id', $customer->id)->lockForUpdate()->first();
            $group = $this->groupName();

            if (! $record) {
                $groups = RadUserGroup::where('username', $customer->username)
                    ->where('groupname', '!=', $group)
                    ->get(['groupname', 'priority'])->map(fn ($g) => ['groupname' => $g->groupname, 'priority' => (int) $g->priority])->values()->all();
                $replies = RadReply::where('username', $customer->username)
                    ->whereIn('attribute', self::IP_ATTRIBUTES)
                    ->get(['attribute', 'op', 'value'])->map(fn ($r) => $r->only(['attribute', 'op', 'value']))->values()->all();

                $record = CustomerIsolation::create([
                    'customer_id' => $customer->id,
                    'username' => $customer->username,
                    'mode' => $cfg['isolir_mode'],
                    'previous_groups' => $groups,
                    'previous_replies' => $replies,
                    'isolated_at' => now(),
                ]);
            } elseif ($record->username !== $customer->username) {
                // Username diganti saat terisolir: IP isolir ikut pindah ke username baru.
                RadReply::where('username', $record->username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
                $record->username = $customer->username;
                $record->save();
            }

            // Grup: hanya ISOLIR (atribut paket tidak ikut terkirim)
            RadUserGroup::where('username', $customer->username)->delete();
            RadUserGroup::insert(['username' => $customer->username, 'groupname' => $group, 'priority' => 0]);

            // Atribut IP asli disingkirkan; IP isolir dipasang sesuai mode
            RadReply::where('username', $customer->username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
            $this->applyAddress($record, $cfg);

            return $record->fresh();
        });
    }

    /** Cabut isolir: pulihkan grup paket & atribut IP asli, lepaskan IP isolir. */
    public function release(Customer $customer, bool $restoreRadius = true): void
    {
        $record = CustomerIsolation::where('customer_id', $customer->id)->first();
        if (! $record) {
            return;
        }

        DB::transaction(function () use ($customer, $record, $restoreRadius) {
            $username = $customer->username ?: $record->username;

            if ($restoreRadius && $username) {
                RadUserGroup::where('username', $username)->delete();
                // Paket pelanggan saat ini menang (paket bisa diganti selama terisolir);
                // grup tersimpan hanya dipakai bila pelanggan tidak punya paket.
                $groups = $customer->package
                    ? [['groupname' => $customer->package->name, 'priority' => 1]]
                    : ($record->previous_groups ?: []);
                foreach ($groups as $g) {
                    RadUserGroup::insert(['username' => $username, 'groupname' => $g['groupname'], 'priority' => $g['priority'] ?? 1]);
                }

                RadReply::where('username', $username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
                foreach ($record->previous_replies ?: [] as $r) {
                    RadReply::create(['username' => $username, 'attribute' => $r['attribute'], 'op' => $r['op'] ?: ':=', 'value' => $r['value']]);
                }
            }

            $record->delete();
        });
    }

    public function isIsolated(Customer $customer): bool
    {
        return CustomerIsolation::where('customer_id', $customer->id)->exists();
    }

    // -------------------------------------------------------------- pengaturan

    /**
     * Simpan pengaturan, tulis atribut grup ISOLIR ke radgroupreply, lalu terapkan ulang ke
     * semua pelanggan terisolir (IP di luar rentang baru dialokasikan ulang).
     *
     * @return array{isolated: int, released: int, without_ip: int}
     */
    public function saveSettings(array $values): array
    {
        $oldGroup = $this->groupName();

        foreach (self::SETTINGS as $key => $default) {
            if (array_key_exists($key, $values)) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) ($values[$key] ?? '')]);
            }
        }
        Cache::forget('app_settings');

        $this->syncGroupReplies($oldGroup);

        return $this->reapplyAll();
    }

    /** Atribut balasan grup ISOLIR (radgroupreply) sesuai pengaturan. */
    public function syncGroupReplies(?string $oldGroup = null): void
    {
        $cfg = $this->config();
        $group = $this->groupName();

        RadGroupReply::whereIn('groupname', array_unique(array_filter([$oldGroup, $group])))->delete();

        $rows = [];
        if ($cfg['isolir_mode'] === 'pool' && $cfg['isolir_pool_name'] !== '') {
            $rows['Framed-Pool'] = $cfg['isolir_pool_name'];
        }
        if ($cfg['isolir_rate_limit'] !== '') {
            $rows['Mikrotik-Rate-Limit'] = $cfg['isolir_rate_limit'];
        }
        if ($cfg['isolir_address_list'] !== '') {
            $rows['Mikrotik-Address-List'] = $cfg['isolir_address_list'];
        }
        if ($cfg['isolir_mikrotik_group'] !== '') {
            $rows['Mikrotik-Group'] = $cfg['isolir_mikrotik_group'];
        }
        foreach ($rows as $attribute => $value) {
            // ":=" agar menimpa atribut sejenis milik user (mis. batas kecepatan FUP)
            RadGroupReply::create(['groupname' => $group, 'attribute' => $attribute, 'op' => ':=', 'value' => $value]);
        }
    }

    /** Samakan kondisi RADIUS semua pelanggan terisolir dengan pengaturan saat ini. */
    public function reapplyAll(): array
    {
        $stats = ['isolated' => 0, 'released' => 0, 'without_ip' => 0];

        if (! $this->enabled()) {
            foreach (CustomerIsolation::with('customer')->get() as $record) {
                if ($record->customer) {
                    $this->release($record->customer);
                } else {
                    $record->delete();
                }
                $stats['released']++;
            }

            return $stats;
        }

        $customers = Customer::where('status', Customer::STATUS_SUSPENDED)->whereNotNull('username')->get();
        foreach ($customers as $customer) {
            $record = $this->isolate($customer);
            $stats['isolated']++;
            if ($record && $record->mode === 'range' && ! $record->ip_address) {
                $stats['without_ip']++;
            }
        }

        // Catatan isolir yatim (pelanggan sudah tidak terisolir) dibersihkan
        CustomerIsolation::whereNotIn('customer_id', $customers->pluck('id'))->get()
            ->each(function ($r) use (&$stats) {
                $r->customer ? $this->release($r->customer) : $r->delete();
                $stats['released']++;
            });

        return $stats;
    }

    // ------------------------------------------------------------ alokasi IP

    private function applyAddress(CustomerIsolation $record, array $cfg): void
    {
        $record->mode = $cfg['isolir_mode'];

        if ($cfg['isolir_mode'] !== 'range') {
            $record->ip_address = null;
            $record->save();

            return;
        }

        [$start, $end] = $this->range($cfg);
        $current = $record->ip_address ? ip2long($record->ip_address) : false;
        if ($current === false || $start === null || $current < $start || $current > $end) {
            $record->ip_address = $start === null ? null : $this->nextFreeIp($start, $end, $record->id);
        }
        $record->save();

        if ($record->ip_address) {
            RadReply::create(['username' => $record->username, 'attribute' => 'Framed-IP-Address', 'op' => ':=', 'value' => $record->ip_address]);
        } else {
            Log::warning("Isolir {$record->username}: rentang IP isolir habis atau belum diatur; pelanggan tetap di grup ISOLIR tanpa IP khusus.");
        }
    }

    /** @return array{0: int|null, 1: int|null} */
    public function range(?array $cfg = null): array
    {
        $cfg ??= $this->config();
        $start = ip2long($cfg['isolir_ip_start']);
        $end = ip2long($cfg['isolir_ip_end']);

        if ($start === false || $end === false || $end < $start) {
            return [null, null];
        }

        return [$start, $end];
    }

    public function capacity(): array
    {
        [$start, $end] = $this->range();
        $total = $start === null ? 0 : $end - $start + 1;
        $used = CustomerIsolation::whereNotNull('ip_address')->count();

        return ['total' => $total, 'used' => $used, 'free' => max(0, $total - $used)];
    }

    private function nextFreeIp(int $start, int $end, int $exceptRecordId): ?string
    {
        $used = CustomerIsolation::whereNotNull('ip_address')->whereKeyNot($exceptRecordId)->pluck('ip_address')
            ->map(fn ($ip) => ip2long($ip))->filter(fn ($n) => $n !== false)->flip();

        for ($n = $start; $n <= $end; $n++) {
            $octet = $n & 0xFF;
            if ($octet === 0 || $octet === 255) {
                continue;   // hindari alamat jaringan/broadcast pada batas /24
            }
            if (! $used->has($n)) {
                return long2ip($n);
            }
        }

        return null;
    }

    // ------------------------------------------------- walled garden / bypass

    /**
     * Tujuan yang tetap bisa diakses pelanggan terisolir: server billing & kanal pembayaran.
     *
     * @return array<int, array{address: string, source: string}>
     */
    public function allowedHosts(): array
    {
        $cfg = $this->config();
        $out = [];
        $add = function (?string $address, string $source) use (&$out) {
            $address = strtolower(trim((string) $address));
            if ($address === '' || isset($out[$address]) || ! self::isValidAllowEntry($address)
                || in_array($address, ['localhost'], true) || str_starts_with($address, '127.')) {
                return;
            }
            $out[$address] = ['address' => $address, 'source' => $source];
        };

        $add(parse_url((string) config('app.url'), PHP_URL_HOST), 'Aplikasi billing (APP_URL)');
        $add(explode(':', $cfg['isolir_page_host'])[0] ?? null, 'Server halaman isolir');

        foreach (self::PORTAL_ASSET_HOSTS as $h) {
            $add($h, 'Aset halaman pembayaran');
        }

        $active = \App\Models\Gateway::where('type', 'payment')->where('is_active', true)->pluck('provider')
            ->map(fn ($p) => strtolower((string) $p))->all();
        foreach (self::GATEWAY_HOSTS as $provider => $hosts) {
            if (in_array($provider, $active, true)) {
                foreach ($hosts as $h) {
                    $add($h, 'Gateway ' . ucfirst($provider));
                }
            }
        }

        // Domain halaman bayar yang benar-benar dipakai invoice terbaru (menangkap perubahan domain gateway)
        \App\Models\Invoice::whereNotNull('payment_url')->where('updated_at', '>=', now()->subDays(90))
            ->pluck('payment_url')
            ->map(fn ($u) => parse_url((string) $u, PHP_URL_HOST))
            ->filter()->unique()
            ->each(fn ($h) => $add($h, 'Dari link pembayaran invoice'));

        foreach (self::parseHostList($cfg['isolir_allowed_hosts']) as $h) {
            $add($h, 'Tambahan manual');
        }

        return array_values($out);
    }

    /** Host / IP / CIDR yang bisa dimasukkan ke address-list MikroTik. */
    public static function isValidAllowEntry(string $entry): bool
    {
        if (filter_var($entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        if (preg_match('#^(\d{1,3}(?:\.\d{1,3}){3})/(\d{1,2})$#', $entry, $m)) {
            return filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && (int) $m[2] <= 32;
        }

        return (bool) preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $entry);
    }

    /** Satu entri per baris (atau dipisah koma/spasi); komentar diawali #. */
    public static function parseHostList(string $text): array
    {
        $lines = preg_split('/[\r\n,]+/', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if ($line === '') {
                continue;
            }
            // Terima URL lengkap: ambil host-nya saja
            if (str_contains($line, '://')) {
                $line = (string) parse_url($line, PHP_URL_HOST);
            }
            $out[] = strtolower(trim($line, " \t/"));
        }

        return array_values(array_unique(array_filter($out)));
    }

    /**
     * Skrip RouterOS lengkap untuk isolir: daftar bypass, izinkan DNS, blokir internet,
     * redirect HTTP ke halaman isolir. Aman dijalankan ulang (aturan berlabel ISOLIR diganti).
     */
    /**
     * IP server billing / halaman isolir, diisi otomatis dari data sistem:
     * pengaturan "Alamat server halaman isolir", lalu host APP_URL (di-resolve bila nama domain).
     */
    public function serverIp(): ?string
    {
        $configured = explode(':', $this->config()['isolir_page_host'])[0] ?? '';
        if (filter_var($configured, FILTER_VALIDATE_IP)) {
            return $configured;
        }

        $host = $configured ?: (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($host === '') {
            return null;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        return filter_var($ip, FILTER_VALIDATE_IP) && ! str_starts_with($ip, '127.') && $ip !== '::1' ? $ip : null;
    }

    /** Port halaman isolir (default 8088: port khusus nginx yang selalu menampilkan /isolir). */
    public function pagePort(): string
    {
        $port = explode(':', $this->config()['isolir_page_host'])[1] ?? '';

        return ctype_digit($port) ? $port : '8088';
    }

    public function mikrotikScript(?\App\Models\Radius\Nas $nas = null): string
    {
        $cfg = $this->config();
        [$start, $end] = $this->range($cfg);
        $list = self::ALLOW_LIST;
        $serverIp = $this->serverIp();

        if ($cfg['isolir_address_list'] !== '') {
            $src = 'src-address-list=' . $cfg['isolir_address_list'];
        } elseif ($start !== null) {
            // Rentang isolir (mode rentang IP, atau rentang pool pada mode pool)
            $src = 'src-address=' . long2ip($start) . '-' . long2ip($end);
        } else {
            $src = 'src-address-list=isolir';   // mode pool tanpa address list: isi "Address list MikroTik"
        }

        $pageIp = $serverIp ?: '<IP-SERVER-BILLING>';
        $pagePort = $this->pagePort();

        $q = fn (string $s) => '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $s) . '"';

        $lines = [];
        $lines[] = '# Isolir pelanggan — dibuat ' . now()->format('d/m/Y H:i') . ' oleh ' . get_setting('company_name', config('app.name'));
        if ($nas) {
            $lines[] = "# Router: {$nas->shortname} ({$nas->nasname})";
        }
        $lines[] = '# Aman dijalankan ulang: aturan & daftar berlabel ISOLIR dihapus lalu dipasang kembali.';
        $lines[] = '# Router harus punya DNS (/ip dns) agar nama domain di address-list bisa di-resolve.';
        $lines[] = '';

        if ($nas && $serverIp) {
            $lines[] = '# 0) RADIUS ke server billing + CoA (agar sistem bisa memutus sesi saat isolir/lunas)';
            $lines[] = ":if ([:len [/radius find address={$serverIp}]] = 0) do={ /radius add address={$serverIp} secret=" . $q((string) $nas->secret) . ' service=ppp timeout=3s comment="Billing RADIUS" } else={ /radius set [find address=' . $serverIp . '] secret=' . $q((string) $nas->secret) . ' }';
            $lines[] = '/radius incoming set accept=yes port=3799';
            $lines[] = '/ppp aaa set use-radius=yes accounting=yes interim-update=5m';
            $lines[] = '';
        }
        if ($cfg['isolir_mikrotik_group'] !== '') {
            $profile = $cfg['isolir_mikrotik_group'];
            $extra = ($cfg['isolir_rate_limit'] !== '' ? ' rate-limit=' . $cfg['isolir_rate_limit'] : '')
                . ($cfg['isolir_address_list'] !== '' ? ' address-list=' . $cfg['isolir_address_list'] : '');
            $lines[] = '# Profil PPP isolir (dikirim RADIUS sebagai Mikrotik-Group). Dibuat bila belum ada,';
            $lines[] = '# disalin dari profil default agar local-address & DNS sama dengan pelanggan biasa.';
            $lines[] = ":if ([:len [/ppp profile find name=" . $q($profile) . "]] = 0) do={ /ppp profile add name=" . $q($profile) . ' copy-from=default comment="ISOLIR profil" }';
            if ($extra !== '') {
                $lines[] = '/ppp profile set [find name=' . $q($profile) . ']' . $extra;
            }
            $lines[] = '';
        }

        $lines[] = '# 1) Tujuan yang tetap bisa diakses pelanggan terisolir (billing & pembayaran)';
        $lines[] = "/ip firewall address-list remove [find list={$list} comment~\"^ISOLIR\"]";
        foreach ($this->allowedHosts() as $h) {
            $lines[] = "/ip firewall address-list add list={$list} address={$h['address']} comment=" . $q('ISOLIR ' . $h['source']);
        }
        if ($cfg['isolir_mode'] === 'pool' && $cfg['isolir_pool_name'] !== '') {
            $ranges = $start !== null ? long2ip($start) . '-' . long2ip($end) : '<IP-AWAL>-<IP-AKHIR>';
            $lines[] = '';
            $lines[] = '# IP pool isolir (rentang dari Pengaturan Isolir)';
            $lines[] = "/ip pool remove [find name={$cfg['isolir_pool_name']}]";
            $lines[] = "/ip pool add name={$cfg['isolir_pool_name']} ranges={$ranges} comment=\"ISOLIR pool\"";
        }
        $lines[] = '';
        $lines[] = '# 2) Firewall: bypass ke billing & pembayaran, izinkan DNS, blokir sisanya';
        $lines[] = '/ip firewall filter remove [find comment~"^ISOLIR:"]';
        $lines[] = "/ip firewall filter add chain=forward {$src} dst-address-list={$list} action=accept comment=\"ISOLIR: bypass billing & pembayaran\"";
        $lines[] = "/ip firewall filter add chain=forward {$src} protocol=udp dst-port=53 action=accept comment=\"ISOLIR: DNS\"";
        $lines[] = "/ip firewall filter add chain=forward {$src} protocol=tcp dst-port=53 action=accept comment=\"ISOLIR: DNS (tcp)\"";
        $lines[] = "/ip firewall filter add chain=forward {$src} action=reject reject-with=icmp-network-unreachable comment=\"ISOLIR: blokir internet\"";
        $lines[] = '';
        $lines[] = '# 3) Redirect HTTP (selain tujuan bypass) ke halaman isolir';
        $lines[] = '/ip firewall nat remove [find comment~"^ISOLIR:"]';
        $lines[] = "/ip firewall nat add chain=dstnat {$src} dst-address-list=!{$list} protocol=tcp dst-port=80 action=dst-nat to-addresses={$pageIp} to-ports={$pagePort} comment=\"ISOLIR: redirect ke halaman tagihan\"";
        $lines[] = '';
        $lines[] = '# 4) Pindahkan aturan ISOLIR ke paling atas (urutan dari bawah ke atas)';
        foreach (['ISOLIR: blokir internet', 'ISOLIR: DNS (tcp)', 'ISOLIR: DNS', 'ISOLIR: bypass billing & pembayaran'] as $c) {
            $lines[] = ":do { /ip firewall filter move [find comment=\"{$c}\"] destination=0 } on-error={}";
        }
        $lines[] = ':do { /ip firewall nat move [find comment="ISOLIR: redirect ke halaman tagihan"] destination=0 } on-error={}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Blok server nginx: port khusus yang selalu menampilkan /isolir (MikroTik tidak bisa
     * mengubah path saat redirect). Juga menjawab cek captive-portal HP (/generate_204 dsb.).
     */
    public function nginxSnippet(): string
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return implode("\n", [
            '# /etc/nginx/sites-enabled/isolir.conf',
            'server {',
            '    listen ' . $this->pagePort() . ';',
            '    server_name _;',
            '    location / {',
            '        rewrite ^ /isolir break;',
            '        proxy_pass http://127.0.0.1;',
            '        proxy_set_header Host ' . $appHost . ';',
            '        proxy_set_header X-Forwarded-For $remote_addr;',
            '    }',
            '}',
        ]) . "\n";
    }

    /** Pelanggan terisolir pemilik IP ini (untuk halaman isolir). */
    public function customerForIp(?string $ip): ?Customer
    {
        if (! $ip) {
            return null;
        }

        return CustomerIsolation::where('ip_address', $ip)->first()?->customer;
    }
}
