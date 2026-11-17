<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * Isolir otomatis: aturan siapa yang diisolir dan sakelar aktif/nonaktif (Pengaturan Isolir).
 *
 * Sakelar menahan SEMUA isolir otomatis — tunggakan langganan (app:process-billing), masa aktif prabayar
 * renewal habis, dan batas bayar instalasi (customer:check-grace-period) — termasuk job isolir yang sudah
 * terlanjur antre. Isolir manual oleh admin tidak terpengaruh. Bawaan aktif; migrasi mematikannya pada
 * server lama yang sudah berisi pelanggan agar update tidak mengisolir semua penunggak sekaligus.
 */
class AutoSuspension
{
    public const KEY = 'isolir_auto_enabled';

    /** Dibaca langsung dari database (bukan cache pengaturan) — sakelar ini tidak boleh memakai nilai basi. */
    public static function enabled(): bool
    {
        return Setting::where('key', self::KEY)->value('value') !== '0';
    }

    public static function setEnabled(bool $on): void
    {
        Setting::updateOrCreate(['key' => self::KEY], ['value' => $on ? '1' : '0', 'type' => 'boolean']);
        \Illuminate\Support\Facades\Cache::forget('app_settings');
    }

    /** Pelanggan aktif yang memenuhi syarat isolir karena tagihan (tanpa melihat sakelar). */
    public static function dueQuery(): Builder
    {
        $today = now()->toDateString();

        return Customer::where('is_active', true)
            ->where('status', Customer::STATUS_ACTIVE)
            ->where(function ($q) use ($today) {
                $q->whereHas('invoices', fn ($i) => $i->suspendableRecurring())   // lewat jatuh tempo + masa tenggang
                    // Renewal: masa aktif habis walau invoice belum/tidak terbit.
                    ->orWhere(fn ($r) => $r->where('billing_type', 'prepaid')
                        ->where('billing_method', 'renewal')
                        ->whereNotNull('expired_at')
                        ->whereDate('expired_at', '<', $today));
            });
    }

    /** Masih layak diisolir saat job dijalankan (mis. sudah membayar selagi job antre → tidak). */
    public static function stillDue(Customer $customer): bool
    {
        return self::dueQuery()->whereKey($customer->getKey())->exists();
    }
}
