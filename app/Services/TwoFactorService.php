<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Verifikasi dua langkah staf: aktifkan, verifikasi kode / kode cadangan, kewajiban per role. */
class TwoFactorService
{
    public const REQUIRED_KEY = 'two_factor_required_roles';
    public const RECOVERY_COUNT = 8;
    public const STAFF_ROLES = [User::ROLE_ADMINISTRATOR, User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_TEKNISI, User::ROLE_SALES, User::ROLE_CS];

    public static function issuer(): string
    {
        return (string) (get_setting('company_name') ?: config('app.name', 'Billing'));
    }

    /** @return list<string> role yang wajib 2FA (bawaan: tidak ada) */
    public static function requiredRoles(): array
    {
        $v = (string) Setting::where('key', self::REQUIRED_KEY)->value('value');

        return array_values(array_intersect(self::STAFF_ROLES, array_filter(explode(',', $v))));
    }

    public static function setRequiredRoles(array $roles): void
    {
        Setting::updateOrCreate(['key' => self::REQUIRED_KEY], ['value' => implode(',', array_intersect(self::STAFF_ROLES, $roles)), 'type' => 'string']);
        Cache::forget('app_settings');
    }

    public static function requiredFor(User $user): bool
    {
        return in_array($user->role, self::requiredRoles(), true);
    }

    /** @return list<string> kode cadangan dalam bentuk teks (ditampilkan sekali); yang disimpan hanya hash-nya */
    public function newRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_COUNT; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => hash('sha256', $c), $codes)])->save();

        return $codes;
    }

    /** Aktifkan setelah kode pertama dari aplikasi benar. @return list<string>|null kode cadangan */
    public function confirm(User $user, string $secret, string $code): ?array
    {
        $step = Totp::verify($secret, $code);
        if ($step === null) {
            return null;
        }
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step])->save();
        ActivityLog::log('two_factor_enabled', "Verifikasi dua langkah diaktifkan untuk {$user->name}.", $user);

        return $this->newRecoveryCodes($user);
    }

    public function disable(User $user, string $reason): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null])->save();
        ActivityLog::log('two_factor_disabled', "Verifikasi dua langkah {$user->name} dinonaktifkan ({$reason}).", $user);
    }

    /**
     * Cek kode 6 digit (sekali pakai per langkah waktu) atau kode cadangan (sekali pakai).
     *
     * @return string|null 'totp' | 'recovery' | null
     */
    public function verify(User $user, string $input): ?string
    {
        if (! $user->hasTwoFactor()) {
            return null;
        }
        $input = trim($input);
        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {
            return DB::transaction(function () use ($user, $input) {
                $fresh = User::whereKey($user->id)->lockForUpdate()->first();
                $step = Totp::verify($fresh->two_factor_secret, $input);
                if ($step === null || ($fresh->two_factor_last_step !== null && $step <= (int) $fresh->two_factor_last_step)) {
                    return null;   // salah, atau kode yang sudah pernah dipakai
                }
                $fresh->forceFill(['two_factor_last_step' => $step])->save();

                return 'totp';
            });
        }

        $hash = hash('sha256', strtoupper(preg_replace('/\s+/', '', $input)));
        $codes = (array) $user->two_factor_recovery_codes;
        foreach ($codes as $i => $stored) {
            if (hash_equals($stored, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                ActivityLog::log('two_factor_recovery_used', "{$user->name} masuk dengan kode cadangan (" . count($codes) . ' tersisa).', $user);

                return 'recovery';
            }
        }

        return null;
    }
}
