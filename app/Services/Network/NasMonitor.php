<?php

namespace App\Services\Network;

use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

/**
 * Pantau router/NAS (MikroTik) tanpa kredensial: ping, lalu koneksi TCP ke port umum MikroTik bila ICMP
 * diblokir. Peringatan "mati" dikirim setelah FAIL_THRESHOLD kali gagal berturut-turut (bukan karena satu
 * paket hilang), sekali sampai pulih. Dipanggil oleh noc:monitor (jadwal Integrasi › Bot NOC).
 */
class NasMonitor
{
    public const FAIL_THRESHOLD = 2;
    public const TCP_PORTS = [8728, 8291, 22, 80, 443];

    /** Hanya NAS dengan satu alamat IP (bukan rentang CIDR seperti 0.0.0.0/0). */
    public static function monitorable(Nas $nas): bool
    {
        return filter_var(trim((string) $nas->nasname), FILTER_VALIDATE_IP) !== false;
    }

    /** @return array{up: bool, via: ?string} */
    public function probe(string $ip): array
    {
        if ($this->ping($ip)) {
            return ['up' => true, 'via' => 'ping'];
        }
        foreach (self::TCP_PORTS as $port) {
            if ($this->tcp($ip, $port)) {
                return ['up' => true, 'via' => "tcp/{$port}"];
            }
        }

        return ['up' => false, 'via' => null];
    }

    protected function ping(string $ip): bool
    {
        $win = PHP_OS_FAMILY === 'Windows';
        $cmd = $win ? ['ping', '-n', '2', '-w', '1500', $ip] : ['ping', '-c', '2', '-W', '2', $ip];
        try {
            $p = new Process($cmd);
            $p->setTimeout(8);
            $p->run();
        } catch (\Throwable) {
            return false;   // ping tidak tersedia: lanjut cek TCP
        }

        // "TTL=" hanya muncul bila ada balasan dari host itu (Windows tetap exit 0 untuk "unreachable" dari gateway)
        return $p->isSuccessful() && stripos($p->getOutput(), 'ttl=') !== false;
    }

    protected function tcp(string $ip, int $port): bool
    {
        $target = (str_contains($ip, ':') ? "[{$ip}]" : $ip) . ":{$port}";
        $s = @stream_socket_client("tcp://{$target}", $errno, $errstr, 1.5);
        if ($s) {
            fclose($s);

            return true;
        }

        return false;
    }

    /**
     * Periksa satu NAS, simpan status & kirim peringatan mati/pulih bila perlu.
     *
     * @return array{up: bool, via: ?string, sessions: int, alert: ?string}
     */
    public function check(Nas $nas, ?NocAlertService $alerts = null): array
    {
        $ip = trim((string) $nas->nasname);
        $name = $nas->shortname ?: $ip;
        $probe = $this->probe($ip);
        $sessions = RadAcct::whereNull('acctstoptime')->where('nasipaddress', $ip)->count();
        $alert = null;

        if ($probe['up']) {
            Cache::forget("noc_nas_fail_{$nas->id}");
            if ($since = Cache::pull("noc_nas_down_{$nas->id}")) {
                $alert = "Router {$name} ({$ip}) kembali online (mati sejak {$since}). Sesi PPPoE aktif: {$sessions}.";
            }
        } else {
            $fails = (int) Cache::get("noc_nas_fail_{$nas->id}", 0) + 1;
            Cache::put("noc_nas_fail_{$nas->id}", $fails, now()->addDays(2));
            if ($fails >= self::FAIL_THRESHOLD && Cache::add("noc_nas_down_{$nas->id}", now()->format('d/m/Y H:i'), now()->addDays(30))) {
                $alert = "Router {$name} ({$ip}) TIDAK MERESPONS (ping & port " . implode('/', self::TCP_PORTS) . ", {$fails}x berturut-turut).\n"
                    . "Sesi PPPoE yang tercatat aktif: {$sessions}.";
            }
        }

        Cache::put("noc_nas_status_{$nas->id}", ['up' => $probe['up'], 'via' => $probe['via'], 'sessions' => $sessions,
            'checked_at' => now()->getTimestamp(), 'down_since' => Cache::get("noc_nas_down_{$nas->id}")], now()->addDays(7));

        if ($alert && $alerts) {
            $alerts->sendAlert($alert);
        }

        return $probe + ['sessions' => $sessions, 'alert' => $alert];
    }

    /** Cek sekarang dari halaman Router (NAS): perbarui tampilan tanpa menyentuh penghitung/peringatan. */
    public function refreshStatus(Nas $nas): array
    {
        $ip = trim((string) $nas->nasname);
        $probe = $this->probe($ip);
        $status = ['up' => $probe['up'], 'via' => $probe['via'],
            'sessions' => RadAcct::whereNull('acctstoptime')->where('nasipaddress', $ip)->count(),
            'checked_at' => now()->getTimestamp(), 'down_since' => Cache::get("noc_nas_down_{$nas->id}")];
        Cache::put("noc_nas_status_{$nas->id}", $status, now()->addDays(7));

        return $status;
    }

    /** Status terakhir dari pemantauan (null = belum pernah dicek). */
    public static function lastStatus(Nas $nas): ?array
    {
        $s = Cache::get("noc_nas_status_{$nas->id}");

        return is_array($s) ? $s : null;
    }
}
