<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\FcmNotification;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification(string $deviceToken, string $title, string $body, User $user, bool $isWelcome)
    {
        if (!$isWelcome) {
            $this->storeNotification(user: $user, title: $title, body: $body);
        }
        $notification = Notification::create($title, $body);
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        return $this->messaging->send($message);
    }

    public function userNotifications()
    {
        $userID = auth('sanctum')->id();
        $user = User::findOrFail($userID);
        return ResponseHelper::success(data: $user->notifications, message: 'User notifications retrieved successfully');
    }

    private function storeNotification(User $user, string $title, string $body)
    {
        FcmNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
