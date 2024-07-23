<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\AcceptSubscriptionRequest;
use App\Http\Requests\CreateCollageTripRequest;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Requests\RenewSubscriptionRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index() //accepted
    {
        $result = $this->subscriptionService->getAllSubscriptions('accepted');
        return ResponseHelper::success($result);
    }

    public function indexPending() //pending
    {
        $result = $this->subscriptionService->getAllSubscriptions('pending');
        return ResponseHelper::success($result);
    }

    public function createNewSubscription(CreateSubscriptionRequest $request)
    {
        $result = $this->subscriptionService->subscribe($request);
        return ResponseHelper::success($result);
    }

    public function renewSubscription(RenewSubscriptionRequest $request)
    {
        $result = $this->subscriptionService->renew($request);
        return ResponseHelper::success('Subscription ' . $result);
    }

    public function cancelSubscription() //user
    {
        $this->subscriptionService->unSubscribe();
        return ResponseHelper::success('Subscription cancelled successfully.');
    }

    public function update(AcceptSubscriptionRequest $request) //admin
    {
        $result = $this->subscriptionService->updateStatus($request);
        return ResponseHelper::success('Subscription ' . $result . ' successfully.');
    }
}
