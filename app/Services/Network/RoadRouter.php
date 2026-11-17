<?php

namespace App\Services\Network;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Jalur mengikuti jalan lewat server routing OSRM (API /route/v1). Alamat server diatur administrator
 * (Peta Pelanggan › Pengaturan jalur); tanpa alamat fitur ini nonaktif — koordinat tidak dikirim ke mana pun.
 * Yang dikirim hanya koordinat titik (tanpa nama/data pelanggan).
 */
class RoadRouter
{
    public const URL_KEY = 'map_routing_url';
    public const PROFILE_KEY = 'map_routing_profile';

    public static function baseUrl(): ?string
    {
        $url = trim((string) Setting::where('key', self::URL_KEY)->value('value'));

        return $url !== '' ? rtrim($url, '/') : null;
    }

    public static function profile(): string
    {
        $p = (string) Setting::where('key', self::PROFILE_KEY)->value('value');

        return in_array($p, ['driving', 'foot', 'bike'], true) ? $p : 'driving';
    }

    public static function enabled(): bool
    {
        return self::baseUrl() !== null;
    }

    /**
     * Jalur jalan melewati titik-titik (urut) — [[lat, lng], ...] → ['path' => [[lat, lng], ...], 'distance' => meter].
     *
     * @param  list<array{0: float, 1: float}>  $points
     */
    public function route(array $points): array
    {
        $base = self::baseUrl();
        if (! $base) {
            throw new RuntimeException('Server routing jalan belum diatur. Administrator dapat mengisinya di Pengaturan jalur.');
        }
        if (count($points) < 2 || count($points) > 25) {
            throw new RuntimeException('Jalur butuh 2–25 titik.');
        }
        $coords = implode(';', array_map(fn ($p) => round((float) $p[1], 6) . ',' . round((float) $p[0], 6), $points));
        $profile = self::profile();

        return Cache::remember('road_route_' . md5("{$base}|{$profile}|{$coords}"), now()->addDays(30), function () use ($base, $profile, $coords) {
            try {
                $res = Http::timeout(15)->connectTimeout(5)->acceptJson()
                    ->get("{$base}/route/v1/{$profile}/{$coords}", ['overview' => 'full', 'geometries' => 'geojson', 'continue_straight' => 'false']);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new RuntimeException('Server routing jalan tidak bisa dihubungi: ' . \Illuminate\Support\Str::limit($e->getMessage(), 120));
            }
            $route = $res->json('routes.0');
            if (! $res->successful() || $res->json('code') !== 'Ok' || ! $route) {
                throw new RuntimeException('Server routing tidak menemukan jalan: ' . ($res->json('message') ?? $res->json('code') ?? 'HTTP ' . $res->status()));
            }
            $path = array_map(fn ($c) => [round((float) $c[1], 6), round((float) $c[0], 6)], $route['geometry']['coordinates'] ?? []);
            if (count($path) < 2) {
                throw new RuntimeException('Server routing mengembalikan jalur kosong.');
            }

            return ['path' => $path, 'distance' => (int) round((float) ($route['distance'] ?? 0))];
        });
    }
}
