<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\SubscriptionService;
use App\Services\TripService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index()
    {
        $result = $this->subscriptionService->getAllSubscriptions();
        return ResponseHelper::success($result);
    }

    public function createNewSubscription(Request $request)
    {
        $result = $this->subscriptionService->subscribe($request);
        return ResponseHelper::success($result);
    }

    public function renewSubscription(Request $request)
    {
        $this->subscriptionService->renew($request);
        return ResponseHelper::success('Subscription renewed successfully.');
    }

    public function cancelSubscription()
    {
        $this->subscriptionService->unSubscribe();
        return ResponseHelper::success('Subscription cancelled successfully.');
    }
}
