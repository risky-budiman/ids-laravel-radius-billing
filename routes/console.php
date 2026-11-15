<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('app:process-billing')->dailyAt('00:01');
// Backup database harian (.sql.gz, rotasi 7 harian + 6 bulanan) — lihat DatabaseDumpService
// Peringatan tiket belum diambil (>2 jam) / lewat SLA (24 jam atau jadwal) ke grup WhatsApp teknisi
Schedule::command('app:ticket-sla-alerts')->everyFifteenMinutes()->withoutOverlapping(10);
// Pengingat tagihan WhatsApp H-3 / H-1 / hari H (template invoice_reminder, setting invoice_reminder_days)
Schedule::command('app:send-invoice-reminders')->dailyAt('09:00')->withoutOverlapping(60);
Schedule::command('app:backup-database')->dailyAt('02:30')->withoutOverlapping(120);
Schedule::command('customer:check-grace-period')->everyMinute();

// FUP & Quota Management
Schedule::command('app:sync-usage')->everyFiveMinutes();
Schedule::command('app:reset-usage')->monthlyOn(1, '00:00');

// Auto close resolved tickets after 24 hours
Schedule::call(function () {
    $cutoff = now()->subHours(24);
    
    $resolvedTickets = \App\Models\Ticket::where('status', 'resolved')
        ->where('updated_at', '<=', $cutoff)
        ->get();

    foreach ($resolvedTickets as $ticket) {
        $ticket->update(['status' => 'closed']);
        
        // Log system milestone message in the chat
        \App\Models\TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'is_system' => true,
            'message' => '⚙️ Tiket ditutup otomatis oleh sistem setelah 24 jam dalam status selesai (resolved).',
        ]);
    }
})->hourly();
