<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API web bawaan OLT HSGQ (EPON) — untuk registrasi/allow ONU dari aplikasi, karena SNMP OLT HSGQ tidak
 * menyediakan tabel registrasi. Format login & endpoint baca mengikuti web OLT HSGQ (dipakai juga oleh proyek
 * open-source olt-redaman-api): POST /userlogin?form=login dengan key = md5("user:pass") dan value = base64(pass);
 * token sesi dikirim balik lewat header X-Token.
 *
 * Baca: info perangkat, daftar izin & data optik ONU. Tulis: reboot, nama/deskripsi, allow, hapus ONU — format
 * mengikuti JavaScript aplikasi web OLT itu sendiri; perintah ke ONU yang sudah ada selalu didahului cek MAC.
 */
class HsgqWebClient
{
    protected ?string $token = null;

    public function __construct(protected Olt $olt, protected int $timeout = 10)
    {
    }

    public function configured(): bool
    {
        return filled($this->olt->web_username) && filled($this->olt->web_password);
    }

    protected function baseUrl(): string
    {
        $scheme = $this->olt->web_https ? 'https' : 'http';
        $port = (int) ($this->olt->web_port ?: ($this->olt->web_https ? 443 : 80));
        $default = $this->olt->web_https ? 443 : 80;

        return "{$scheme}://{$this->olt->ip_address}" . ($port === $default ? '' : ":{$port}") . '/';
    }

    protected function http()
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => $this->baseUrl() . 'index.html',
        ];
        if ($this->token) {
            $headers['X-Token'] = $this->token;
        }

        return Http::withHeaders($headers)->timeout($this->timeout)->connectTimeout(min(5, $this->timeout))->withoutVerifying();
    }

    /** Login ke web OLT; melempar RuntimeException berisi alasan yang bisa ditampilkan. */
    public function login(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Login web OLT belum diisi (Ubah OLT → Akses web OLT).');
        }
        $user = (string) $this->olt->web_username;
        $pass = (string) $this->olt->web_password;

        try {
            $res = $this->http()->asJson()->post($this->baseUrl() . 'userlogin?form=login', [
                'method' => 'set',
                'param' => [
                    'name' => $user,
                    'key' => md5($user . ':' . $pass),
                    'value' => base64_encode($pass),
                    'captcha_v' => '',
                    'captcha_f' => '',
                ],
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Web OLT tidak bisa dihubungi: ' . $e->getMessage());
        }

        $code = $res->json('code');
        if (! in_array($code, [1, 200, '1', '200'], true)) {
            $msg = (string) ($res->json('message') ?? 'HTTP ' . $res->status());
            throw new RuntimeException('Login web OLT ditolak: ' . $msg);
        }
        $this->token = $res->header('X-Token') ?: ($res->json('data.token') ?? $res->json('token'));
        if (! $this->token) {
            throw new RuntimeException('Login web OLT berhasil tetapi token sesi tidak diterima.');
        }
    }

    /** GET endpoint web OLT (login otomatis bila belum). @return array isi "data" */
    public function get(string $path, array $query = []): array
    {
        if (! $this->token) {
            $this->login();
        }
        $res = $this->http()->get($this->baseUrl() . ltrim($path, '/'), $query + ['_' => (int) (microtime(true) * 1000)]);
        if ($res->status() === 404) {
            throw new RuntimeException("Endpoint web OLT '{$path}' tidak tersedia di firmware ini.");
        }
        $code = $res->json('code');
        if ($code !== null && ! in_array($code, [1, 200, '1', '200'], true)) {
            throw new RuntimeException("Web OLT menolak '{$path}': " . ($res->json('message') ?? 'kode ' . $code));
        }
        $data = $res->json('data');

        return is_array($data) ? $data : [];
    }

    /** Info perangkat (nama produk, firmware) — dipakai tombol "Tes login web". */
    public function boardInfo(): array
    {
        return $this->get('board_info');
    }

    /** Daftar izin (allow list) ONU di satu port PON. */
    public function allowList(int $port): array
    {
        return $this->get('onu_allow_list', ['port_id' => $port]);
    }

    /**
     * Detail ONU per port dari web OLT, dikunci MAC tanpa pemisah huruf besar. Di HSGQ-E04ID (IR_V3.3.3ID)
     * daftar izin memuat deskripsi, status pasti (Online/Offline), alasan & waktu putus terakhir, vendor, tipe,
     * waktu daftar; ponmgmt?form=optical_onu memuat Tx, suhu, tegangan & bias.
     *
     * @return array<string, array<string, mixed>>
     */
    public function onuDetails(int $port): array
    {
        $out = $this->onuDescriptions($port);
        try {
            foreach ($this->get('ponmgmt', ['form' => 'optical_onu', 'port_id' => $port]) as $row) {
                $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', (string) ($row['macaddr'] ?? '')));
                if ($mac === '') {
                    continue;
                }
                $num = fn ($v) => is_numeric($n = trim(preg_replace('/[^0-9.\-]/', '', (string) $v))) ? (float) $n : null;
                $out[$mac] = ($out[$mac] ?? ['name' => '', 'description' => '']) + [
                    'tx_power' => $num($row['transmit_power'] ?? null),
                    'temperature' => $num($row['work_temprature'] ?? $row['work_temperature'] ?? null),
                    'voltage' => $num($row['work_voltage'] ?? null),
                    'bias' => $num($row['transmit_bias'] ?? null),
                ];
            }
        } catch (RuntimeException) {
            // data optik tambahan bersifat opsional
        }

        return $out;
    }

    /**
     * Nama & deskripsi (dan bila ada: status, alasan putus, vendor, tipe) ONU per port dari web OLT.
     * Endpoint dicoba berurutan karena tiap firmware HSGQ berbeda; endpoint yang 404 dilewati.
     *
     * @return array<string, array<string, mixed>>
     */
    public function onuDescriptions(int $port): array
    {
        $out = [];
        $lastError = null;
        foreach (['onu_allow_list', 'onu_info', 'onu_list'] as $endpoint) {
            try {
                $rows = $this->get($endpoint, ['port_id' => $port]);
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Login') || str_contains($e->getMessage(), 'belum diisi') || str_contains($e->getMessage(), 'dihubungi')) {
                    throw $e;
                }
                $lastError = $e;
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', (string) ($row['macaddr'] ?? $row['mac'] ?? $row['onu_sn'] ?? $row['sn'] ?? '')));
                $desc = trim((string) ($row['onu_desc'] ?? $row['desc'] ?? $row['description'] ?? ''));
                if ($mac === '' || ($desc === '' && isset($out[$mac]))) {
                    continue;
                }
                $status = strtolower(trim((string) ($row['status'] ?? '')));
                $out[$mac] = array_filter([
                    'name' => trim((string) ($row['onu_name'] ?? $row['name'] ?? '')),
                    'description' => $desc,
                    'status' => in_array($status, ['online', 'offline'], true) ? $status : null,
                    'last_down_reason' => trim((string) ($row['last_down_reason'] ?? '')) ?: null,
                    'last_down_time' => trim((string) ($row['last_down_time'] ?? '')) ?: null,
                    'register_time' => trim((string) ($row['register_time'] ?? '')) ?: null,
                    'vendor' => trim((string) ($row['vendor'] ?? '')) ?: null,
                    'onu_type' => trim((string) ($row['onu_type'] ?? '')) ?: null,
                ], fn ($v) => $v !== null) + ['name' => '', 'description' => ''];
            }
            if ($out && collect($out)->contains(fn ($o) => $o['description'] !== '')) {
                break;
            }
        }
        if (! $out && $lastError) {
            throw $lastError;
        }

        return $out;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Perintah tulis. Format diambil dari aplikasi web OLT HSGQ itu sendiri (static/js, firmware IR_V3.3.3ID):
    //   POST onumgmt?form=config        {method:set, param:{port_id, onu_id, flags, fec_mode:1, onu_name, onu_desc}}
    //        flags 1 = reboot, 2 = deregister, 4 = ubah FEC, 8 = simpan nama & deskripsi
    //   POST onu_allow_list?form=onucfg {method:add|set|delete|reject, param:{port_id, onu_id, macaddr, auth_state, …}}
    // ---------------------------------------------------------------------------------------------------------

    /** POST JSON ke web OLT; melempar RuntimeException berisi pesan OLT bila ditolak. */
    public function post(string $path, array $body): array
    {
        if (! $this->token) {
            $this->login();
        }
        try {
            $res = $this->http()->asJson()->post($this->baseUrl() . ltrim($path, '/'), $body);
        } catch (\Throwable $e) {
            throw new RuntimeException('Web OLT tidak bisa dihubungi: ' . $e->getMessage());
        }
        if ($res->status() === 404) {
            throw new RuntimeException("Perintah web OLT '{$path}' tidak tersedia di firmware ini.");
        }
        $code = $res->json('code');
        if (! in_array($code, [1, 200, '1', '200'], true)) {
            throw new RuntimeException('OLT menolak perintah' . ($code !== null ? " ({$code})" : '') . ': ' . ($res->json('message') ?? 'HTTP ' . $res->status()));
        }

        return (array) $res->json();
    }

    /** MAC ke format OLT ("aa:bb:cc:dd:ee:ff"); null bila bukan MAC 12 digit heksa. */
    public static function formatMac(?string $mac): ?string
    {
        $hex = strtolower(preg_replace('/[^A-Fa-f0-9]/', '', (string) $mac));

        return strlen($hex) === 12 ? implode(':', str_split($hex, 2)) : null;
    }

    /** Baris daftar izin untuk MAC tertentu di satu port (null bila tidak ada). */
    public function findOnu(int $port, string $mac): ?array
    {
        $want = self::formatMac($mac);
        foreach ($this->allowList($port) as $row) {
            if (is_array($row) && self::formatMac($row['macaddr'] ?? '') === $want) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Pastikan ONU di posisi (port, onu_id) memang MAC yang dimaksud — mencegah perintah mengenai ONU pelanggan
     * lain bila daftar di layar sudah basi. @return array baris daftar izin
     */
    public function verifyOnu(int $port, int $onuId, string $mac): array
    {
        $row = $this->findOnu($port, $mac);
        if (! $row) {
            throw new RuntimeException('ONU ' . strtoupper((string) self::formatMac($mac)) . " tidak ada di PON {$port}. Muat ulang daftar ONU.");
        }
        if ((int) ($row['onu_id'] ?? 0) !== $onuId) {
            throw new RuntimeException("ONU itu sekarang di posisi {$port}:{$row['onu_id']}, bukan {$port}:{$onuId}. Muat ulang daftar ONU.");
        }

        return $row;
    }

    public function rebootOnu(int $port, int $onuId): void
    {
        $this->post('onumgmt?form=config', ['method' => 'set', 'param' => [
            'port_id' => $port, 'onu_id' => $onuId, 'flags' => 1, 'fec_mode' => 1,
        ]]);
    }

    /** Simpan nama (maks 16, tanpa spasi) & deskripsi (maks 64) ONU. */
    public function setOnuInfo(int $port, int $onuId, string $name, string $description): void
    {
        $name = preg_replace('/\s+/', '', $name);
        if (mb_strlen($name) > 16 || mb_strlen($description) > 64) {
            throw new RuntimeException('Nama ONU maksimal 16 karakter tanpa spasi; deskripsi maksimal 64 karakter.');
        }
        $this->post('onumgmt?form=config', ['method' => 'set', 'param' => [
            'port_id' => $port, 'onu_id' => $onuId, 'flags' => 8, 'fec_mode' => 1,
            'onu_name' => $name, 'onu_desc' => $description,
        ]]);
    }

    /**
     * Izinkan (allow) ONU di port: ONU yang sudah ada tapi belum diizinkan → auth_state 1; ONU baru → ditambahkan
     * ke daftar izin (onu_id 0 = dipilih OLT). @return string added | authorized | already
     */
    public function allowOnu(int $port, string $mac, string $name = '', string $description = ''): string
    {
        $macaddr = self::formatMac($mac);
        if (! $macaddr || in_array($macaddr, ['ff:ff:ff:ff:ff:ff', '00:00:00:00:00:00'], true)) {
            throw new RuntimeException('MAC ONU tidak valid (contoh: E8:68:19:02:FF:70).');
        }
        $name = mb_substr(preg_replace('/\s+/', '', $name), 0, 16);
        $description = mb_substr(trim($description), 0, 64);

        $existing = $this->findOnu($port, $macaddr);
        if ($existing && (int) ($existing['auth_state'] ?? 0) === 1) {
            return 'already';
        }
        if ($existing) {
            $this->post('onu_allow_list?form=onucfg', ['method' => 'set', 'param' => [
                'port_id' => $port, 'onu_id' => (int) $existing['onu_id'], 'macaddr' => $macaddr, 'auth_state' => 1,
                'onu_type' => '', 'onu_desc' => (string) ($existing['onu_desc'] ?? ''),
            ]]);

            return 'authorized';
        }
        $this->post('onu_allow_list?form=onucfg', ['method' => 'add', 'param' => [
            'port_id' => $port, 'onu_id' => 0, 'macaddr' => $macaddr, 'auth_state' => 1, 'onu_type' => '',
            'splitter_id' => 0, 'onu_desc' => $description, 'onu_name' => $name,
        ]]);

        return 'added';
    }

    /** Hapus ONU dari daftar izin port (ONU terputus & harus di-allow ulang). */
    public function deleteOnu(int $port, int $onuId, string $mac): void
    {
        $this->post('onu_allow_list?form=onucfg', ['method' => 'delete', 'param' => [
            'port_id' => $port, 'onu_id' => $onuId, 'macaddr' => self::formatMac($mac),
        ]]);
    }

    /**
     * ONU di port yang menunggu izin: tercatat tapi belum diizinkan (auth_state 0) dan yang ada di daftar tolak.
     *
     * @return array<int, array{mac: string, onu_id: int|null, source: string, name: string, description: string, status: string}>
     */
    public function pendingOnus(int $port): array
    {
        $out = [];
        foreach ($this->allowList($port) as $row) {
            if (is_array($row) && (int) ($row['auth_state'] ?? 1) === 0 && ($mac = self::formatMac($row['macaddr'] ?? ''))) {
                $out[$mac] = ['mac' => strtoupper($mac), 'onu_id' => (int) ($row['onu_id'] ?? 0) ?: null, 'source' => 'unauthorized',
                    'name' => (string) ($row['onu_name'] ?? ''), 'description' => (string) ($row['onu_desc'] ?? ''), 'status' => (string) ($row['status'] ?? '')];
            }
        }
        try {
            foreach ($this->get('onu_deny_list', ['port_id' => $port]) as $row) {
                if (is_array($row) && ($mac = self::formatMac($row['macaddr'] ?? '')) && ! isset($out[$mac])) {
                    $out[$mac] = ['mac' => strtoupper($mac), 'onu_id' => null, 'source' => 'denied',
                        'name' => '', 'description' => (string) ($row['reason'] ?? ''), 'status' => ''];
                }
            }
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Login') || str_contains($e->getMessage(), 'dihubungi')) {
                throw $e;
            }
        }

        return array_values($out);
    }
}
