<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pengajuan dismantle otomatis: pelanggan yang terisolir lebih dari N hari diubah menjadi
 * "Menunggu dismantle". Observer pelanggan lalu membuat tiket penarikan perangkat, tagihan bulanan
 * berhenti (Customer::BILLABLE_STATUSES) dan isolir tetap berlaku sampai perangkat ditarik.
 *
 * Nonaktif secara default (setting auto_dismantle_enabled), karena menghentikan tagihan dan
 * memberangkatkan teknisi.
 */
class AutoDismantleService
{
    public const MIN_DAYS = 14;
    public const MAX_DAYS = 365;

    public function __construct(private CustomerLifecycleService $lifecycle)
    {
    }

    public function enabled(): bool
    {
        return get_setting('auto_dismantle_enabled', '0') === '1';
    }

    public function days(): int
    {
        return max(self::MIN_DAYS, min(self::MAX_DAYS, (int) get_setting('auto_dismantle_days', 60)));
    }

    public function saveSettings(bool $enabled, int $days): void
    {
        Setting::updateOrCreate(['key' => 'auto_dismantle_enabled'], ['value' => $enabled ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'auto_dismantle_days'], ['value' => (string) max(self::MIN_DAYS, min(self::MAX_DAYS, $days))]);
        Cache::forget('app_settings');
    }

    /** Pelanggan yang akan diajukan pada run berikutnya (terlama dulu). */
    public function candidates(?int $days = null): Collection
    {
        $cutoff = now()->subDays($days ?? $this->days());

        return Customer::where('status', Customer::STATUS_SUSPENDED)
            ->whereNotNull('suspended_at')->where('suspended_at', '<=', $cutoff)
            // Hanya yang terisolir karena menunggak. Isolir manual tanpa tunggakan (mis. cuti
            // berlangganan) tidak boleh berujung penarikan perangkat.
            ->whereHas('invoices', fn ($q) => $q->overdueRecurring())
            ->withSum(['invoices as unpaid_total' => fn ($q) => $q->where('status', 'unpaid')], 'amount')
            ->orderBy('suspended_at')
            ->get();
    }

    /**
     * Ajukan dismantle untuk semua kandidat.
     *
     * @return array<int, array{customer: Customer, ticket: ?Ticket, days: int}>
     */
    public function run(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $done = [];
        foreach ($this->candidates() as $customer) {
            $days = (int) $customer->suspended_at->diffInDays(now());
            $since = $customer->suspended_at->locale('id')->translatedFormat('d F Y');

            if (! $this->lifecycle->requestDismantle($customer)) {
                continue;
            }

            // Tiket dismantle dibuat observer saat status berubah; beri keterangan asal pengajuan
            $ticket = Ticket::where('customer_id', $customer->id)->where('type', 'dismantle')
                ->whereIn('status', ['open', 'in_progress'])->latest('id')->first();
            if ($ticket) {
                TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => null,
                    'is_system' => true,
                    'message' => "⚙️ Diajukan otomatis: pelanggan terisolir sejak {$since} ({$days} hari, batas {$this->days()} hari). "
                        . 'Tunggakan: Rp ' . number_format((float) $customer->unpaid_total, 0, ',', '.') . '. '
                        . 'Bila pelanggan melunasi sebelum perangkat ditarik, batalkan tiket ini.',
                ]);
            }

            Log::info("Dismantle otomatis: {$customer->username} terisolir {$days} hari.");
            $done[] = ['customer' => $customer, 'ticket' => $ticket, 'days' => $days];
        }

        return $done;
    }
}
