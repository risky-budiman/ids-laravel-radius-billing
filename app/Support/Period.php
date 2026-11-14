<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pemilihan periode laporan bulanan / tahunan (?period=month|year&year=&month=).
 */
class Period
{
    public const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /** @return array{0: int, 1: int|null} [tahun, bulan|null untuk tahunan] */
    public static function fromRequest(Request $request): array
    {
        $validated = $request->validate([
            'period' => 'nullable|in:month,year',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);
        $year = (int) ($validated['year'] ?? now()->year);
        $month = ($validated['period'] ?? 'month') === 'year' ? null : (int) ($validated['month'] ?? now()->month);

        return [$year, $month];
    }

    public static function label(int $year, ?int $month): string
    {
        return $month ? self::MONTHS[$month] . ' ' . $year : 'Tahun ' . $year;
    }

    /** Tahun dari sekarang mundur sampai tahun data pertama. */
    public static function years(mixed $firstDate): array
    {
        $first = $firstDate ? (int) substr((string) $firstDate, 0, 4) : now()->year;

        return range(now()->year, min($first, now()->year));
    }
}
