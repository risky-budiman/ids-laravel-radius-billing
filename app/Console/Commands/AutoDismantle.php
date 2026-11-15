<?php

namespace App\Console\Commands;

use App\Services\AutoDismantleService;
use Illuminate\Console\Command;

class AutoDismantle extends Command
{
    protected $signature = 'app:auto-dismantle {--dry-run : Tampilkan kandidat tanpa mengajukan}';
    protected $description = 'Ajukan dismantle untuk pelanggan yang terisolir lebih dari N hari (Pengaturan Isolir)';

    public function handle(AutoDismantleService $service): int
    {
        if ($this->option('dry-run')) {
            foreach ($service->candidates() as $c) {
                $this->line("[dry-run] {$c->customer_code} {$c->name} — terisolir sejak {$c->suspended_at->toDateString()}");
            }
            $this->info(($service->enabled() ? 'Aktif' : 'NONAKTIF') . ", batas {$service->days()} hari.");

            return self::SUCCESS;
        }

        if (! $service->enabled()) {
            $this->info('Dismantle otomatis nonaktif.');

            return self::SUCCESS;
        }

        $done = $service->run();
        foreach ($done as $d) {
            $this->line("{$d['customer']->customer_code} {$d['customer']->name}: diajukan ({$d['days']} hari) · tiket " . ($d['ticket']?->ticket_number ?? '-'));
        }
        $this->info('Diajukan dismantle: ' . count($done));

        return self::SUCCESS;
    }
}
