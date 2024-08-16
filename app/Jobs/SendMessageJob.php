<?php

namespace App\Jobs;

use App\Services\VerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $receiverNumber;
    protected $messageBody;

    public function __construct($receiverNumber, $messageBody)
    {
        $this->receiverNumber = $receiverNumber;
        $this->messageBody = $messageBody;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(VerificationService::class)->sendVerificationMessage($this->receiverNumber, $this->messageBody);
    }
}
