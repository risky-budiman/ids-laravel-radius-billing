<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Kode OTP WhatsApp untuk masuk portal pelanggan.
 *  - 6 digit, berlaku 5 menit, disimpan sebagai HMAC (bukan teks asli).
 *  - Maksimal 5 kali salah per kode; kirim ulang paling cepat 60 detik, maksimal 5 kali per jam.
 *  - Dikirim langsung (bukan antrean) ke nomor TERDAFTAR pelanggan, agar kegagalan kirim langsung terlihat.
 */
class PortalOtpService
{
    /** Tidak bisa masuk portal: layanan sudah dicabut / batal. (Menunggu dismantle masih bisa — mungkin perlu melunasi.) */
    public const BLOCKED_STATUSES = [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED];

    public const TTL_SECONDS = 300;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;
    public const MAX_SENDS_PER_HOUR = 5;

    public function __construct(private WhatsAppService $whatsapp)
    {
    }

    public static function enabled(): bool
    {
        return (string) get_setting('portal_login_otp', '1') !== '0';
    }

    /**
     * @throws \DomainException  terlalu sering / gagal kirim
     */
    public function send(Customer $customer): void
    {
        $id = $customer->id;
        if (RateLimiter::tooManyAttempts("portal-otp-resend:{$id}", 1)) {
            throw new \DomainException('Tunggu ' . RateLimiter::availableIn("portal-otp-resend:{$id}") . ' detik sebelum meminta kode baru.');
        }
        if (RateLimiter::tooManyAttempts("portal-otp-hour:{$id}", self::MAX_SENDS_PER_HOUR)) {
            throw new \DomainException('Terlalu banyak permintaan kode. Coba lagi dalam ' . ceil(RateLimiter::availableIn("portal-otp-hour:{$id}") / 60) . ' menit.');
        }
        if (! $customer->phone) {
            throw new \DomainException('Nomor WhatsApp belum terdaftar. Silakan hubungi admin.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $company = get_setting('company_name', config('app.name'));
        $sent = $this->whatsapp->sendMessage($customer->phone,
            "*{$code}* adalah kode masuk Portal Pelanggan {$company}. Berlaku 5 menit.\n\nJangan berikan kode ini kepada siapa pun, termasuk yang mengaku petugas.");
        if (! $sent) {
            throw new \DomainException('Kode tidak dapat dikirim ke WhatsApp saat ini. Silakan coba lagi nanti atau hubungi admin.');
        }

        RateLimiter::hit("portal-otp-resend:{$id}", self::RESEND_SECONDS);
        RateLimiter::hit("portal-otp-hour:{$id}", 3600);
        Cache::put($this->key($id), ['hash' => $this->hash($id, $code), 'attempts' => 0], self::TTL_SECONDS);
    }

    /**
     * @throws \DomainException  kode salah / kedaluwarsa / terlalu banyak percobaan
     */
    public function verify(Customer $customer, string $code): void
    {
        $key = $this->key($customer->id);
        $entry = Cache::get($key);
        if (! $entry) {
            throw new \DomainException('Kode sudah kedaluwarsa. Minta kode baru.');
        }

        $entry['attempts']++;
        if ($entry['attempts'] > self::MAX_ATTEMPTS) {
            Cache::forget($key);
            throw new \DomainException('Terlalu banyak percobaan salah. Minta kode baru.');
        }

        if (! hash_equals($entry['hash'], $this->hash($customer->id, preg_replace('/\D/', '', $code)))) {
            Cache::put($key, $entry, self::TTL_SECONDS);
            $left = self::MAX_ATTEMPTS - $entry['attempts'];
            throw new \DomainException('Kode salah.' . ($left > 0 ? " Sisa {$left} percobaan." : ' Minta kode baru.'));
        }

        Cache::forget($key);
    }

    /** Cocokkan ID pelanggan (kode / username PPPoE) + nomor HP terdaftar (9 digit terakhir). */
    public static function findCustomer(string $code, string $phone): ?Customer
    {
        $digits = preg_replace('/\D/', '', $phone);
        if ($code === '' || strlen($digits) < 8) {
            return null;
        }
        $suffix = substr($digits, -9);

        // Bukan lagi id database (berurutan, mudah ditebak)
        return Customer::where(fn ($q) => $q->where('customer_code', $code)
                ->orWhere('username', $code)
                ->orWhere('username', 'like', str_replace(['%', '_'], ['\%', '\_'], $code) . '@%'))
            ->whereNotNull('phone')
            ->get()
            ->first(fn (Customer $c) => str_ends_with(preg_replace('/\D/', '', (string) $c->phone), $suffix));
    }

    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) <= 6 ? $digits : substr($digits, 0, 4) . str_repeat('•', strlen($digits) - 7) . substr($digits, -3);
    }

    private function key(int $customerId): string
    {
        return "portal-otp:{$customerId}";
    }

    private function hash(int $customerId, string $code): string
    {
        return hash_hmac('sha256', $customerId . '|' . $code, (string) config('app.key'));
    }
}
