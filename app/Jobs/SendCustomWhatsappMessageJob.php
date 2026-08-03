<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCustomWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct($target, $message)
    {
        $this->target = $target;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $waService->sendMessage($this->target, $this->message);
    }
}
