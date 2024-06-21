<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FcmService;

class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function send(Request $request)
    {
        $request->validate([
            'device_token' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $this->fcmService->sendNotification(
            $request->input('device_token'),
            $request->input('title'),
            $request->input('body')
        );

        return response()->json(['message' => 'Notification sent successfully.']);
    }
}
