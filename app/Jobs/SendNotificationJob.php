<?php

namespace App\Jobs;

use App\Enum\NotificationsEnum;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $isWelcome;
    protected $body;
    protected $fcmToken;
    protected $user;

    public function __construct($fcmToken, $user, $body, $isWelcome)
    {
        $this->user = $user;
        $this->fcmToken = $fcmToken;
        $this->isWelcome = $isWelcome;
        $this->body = $body;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->isWelcome) {
            app(NotificationService::class)
                ->storeNotification(user: $this->user, title: NotificationsEnum::TITLE->value, body: $this->body);
        }
        //
        app(NotificationService::class)
            ->sendNotification($this->fcmToken, NotificationsEnum::TITLE->value, $this->body, $this->user, $this->isWelcome);
    }
}
