<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('app:process-billing')->dailyAt('00:01');
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
