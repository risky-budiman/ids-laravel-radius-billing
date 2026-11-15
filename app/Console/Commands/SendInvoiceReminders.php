<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceWhatsappJob;
use App\Models\Invoice;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat tagihan via WhatsApp sebelum jatuh tempo (template "invoice_reminder").
 *
 * Hari pengingat diatur lewat setting `invoice_reminder_days` (default "3,1,0" = H-3, H-1, hari H;
 * angka negatif = setelah jatuh tempo). Hanya tagihan langganan yang belum dibayar, pelanggan
 * aktif yang punya nomor HP. Setiap invoice hanya diingatkan sekali per hari pengingat.
 */
class SendInvoiceReminders extends Command
{
    protected $signature = 'app:send-invoice-reminders {--dry-run : Tampilkan saja, tanpa mengirim}';
    protected $description = 'Kirim pengingat tagihan WhatsApp H-3 / H-1 / hari H jatuh tempo';

    public function handle(): int
    {
        $template = WhatsappTemplate::where('type', 'invoice_reminder')->first();
        if (! $template || ! $template->is_active) {
            $this->info('Template "invoice_reminder" tidak aktif — pengingat tidak dikirim.');

            return self::SUCCESS;
        }

        $days = self::reminderDays();
        $today = now()->startOfDay();
        $delay = 0;
        $sent = 0;

        foreach ($days as $daysBefore) {
            $dueDate = $today->copy()->addDays($daysBefore)->toDateString();

            $invoices = Invoice::query()->recurringBill()
                ->where('status', 'unpaid')
                ->whereDate('due_date', $dueDate)
                ->whereHas('customer', fn ($q) => $q->where('status', \App\Models\Customer::STATUS_ACTIVE)
                    ->whereNotNull('phone')->where('phone', '!=', ''))
                ->whereDoesntHave('reminders', fn ($q) => $q->where('days_before_due', $daysBefore))
                ->with('customer:id,name,phone')
                ->get();

            foreach ($invoices as $invoice) {
                $label = $daysBefore > 0 ? "H-{$daysBefore}" : ($daysBefore === 0 ? 'hari H' : 'H+' . abs($daysBefore));
                if ($this->option('dry-run')) {
                    $this->line("[dry-run] {$label} {$invoice->invoice_number} → {$invoice->customer->name}");
                    continue;
                }

                // Catat dulu (unik per invoice + hari) agar run ganda/bersamaan tidak mengirim dua kali
                $inserted = DB::table('invoice_reminders')->insertOrIgnore([
                    'invoice_id' => $invoice->id, 'days_before_due' => $daysBefore, 'sent_at' => now(),
                ]);
                if (! $inserted) {
                    continue;
                }

                $job = SendInvoiceWhatsappJob::dispatch($invoice->id, null, 'invoice_reminder');
                if ($delay > 0) {
                    $job->delay(now()->addSeconds($delay));
                }
                $delay += WhatsAppService::getRandomDelay();
                $sent++;
                $this->line("{$label} {$invoice->invoice_number} → {$invoice->customer->name}");
            }
        }

        $this->info("Pengingat dijadwalkan: {$sent} pesan (hari: " . implode(', ', $days) . ').');

        return self::SUCCESS;
    }

    /** @return int[] hari sebelum jatuh tempo, mis. [3, 1, 0] */
    public static function reminderDays(): array
    {
        $raw = (string) get_setting('invoice_reminder_days', '3,1,0');
        $days = array_values(array_unique(array_map('intval', array_filter(array_map('trim', explode(',', $raw)), 'is_numeric'))));

        return array_values(array_filter($days, fn ($d) => $d >= -30 && $d <= 30));
    }
}
