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
 * Saat ini hanya operasi BACA (login, info perangkat, daftar izin ONU). Perintah tulis (allow / hapus ONU)
 * ditambahkan setelah formatnya dipastikan di OLT asli dengan ONU uji.
 */
class HsgqWebClient
{
    protected ?string $token = null;

    public function __construct(protected Olt $olt)
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

        return Http::withHeaders($headers)->timeout(10)->withoutVerifying();
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
     * Nama & deskripsi ONU per port dari web OLT (SNMP HSGQ tidak memuat deskripsi), dikunci MAC tanpa pemisah
     * huruf besar. Endpoint dicoba berurutan karena tiap firmware HSGQ berbeda; endpoint yang 404 dilewati.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public function onuDescriptions(int $port): array
    {
        $out = [];
        $lastError = null;
        foreach (['onu_info', 'onu_list', 'onu_allow_list'] as $endpoint) {
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
                $out[$mac] = [
                    'name' => trim((string) ($row['onu_name'] ?? $row['name'] ?? '')),
                    'description' => $desc,
                ];
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
}
