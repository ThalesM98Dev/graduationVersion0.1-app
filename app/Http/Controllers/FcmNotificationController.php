<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class FcmNotificationController extends Controller
{
    public function index()
    {
       return app(NotificationService::class)->userNotifications();
    }
}
