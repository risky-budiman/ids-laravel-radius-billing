<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Jalur kabel satu sambungan (titik → induknya) di peta. */
class CableRoute extends Model
{
    public const NODE_TYPES = ['customer', 'odp', 'odc', 'stb', 'sto'];

    protected $fillable = ['node_type', 'node_id', 'path', 'length_m', 'mode', 'updated_by'];

    protected $casts = ['path' => 'array', 'length_m' => 'integer'];

    /** Panjang jalur dalam meter (haversine antar titik). */
    public static function lengthOf(array $path): int
    {
        $total = 0.0;
        for ($i = 1; $i < count($path); $i++) {
            [$lat1, $lng1] = $path[$i - 1];
            [$lat2, $lng2] = $path[$i];
            $dLat = deg2rad($lat2 - $lat1);
            $dLng = deg2rad($lng2 - $lng1);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
            $total += 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
        }

        return (int) round($total);
    }

    /** Semua jalur sebagai peta "type:id" => [path, length, mode]. */
    public static function keyed(): array
    {
        return static::query()->get(['node_type', 'node_id', 'path', 'length_m', 'mode'])
            ->mapWithKeys(fn ($r) => ["{$r->node_type}:{$r->node_id}" => ['path' => $r->path, 'length' => $r->length_m, 'mode' => $r->mode]])
            ->all();
    }
}
