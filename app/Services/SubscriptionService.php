<?php

namespace App\Services;

use App\Enum\NotificationsEnum;
use App\Jobs\SendNotificationJob;
use App\Models\CollageTrip;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function getAllSubscriptions($type)
    {
        //return Cache::remember('subscriptions', 5, function () use ($type) {
        return Subscription::where('status', '=', $type)
            ->with(['user', 'collageTrip'])
            ->paginate(10);
        //});
    }

    public function subscribe($request)
    {
        return DB::transaction(function () use ($request) {
            $subscription['user_id'] = auth('sanctum')->id();
            $subscription['collage_trip_id'] = $request->collage_trip_id;
            $subscription['start_date'] = $request->start_date;
            $subscription['end_date'] = $request->end_date;
            $user = User::findOrFail($subscription['user_id']);
            $collageTrip = CollageTrip::findOrFail($subscription['collage_trip_id']);
            $result = app(TripService::class)->pointsDiscountDaily($request->points, $user->points, $collageTrip, 'Round Trip', false);
            $subscription['used_points'] = $result['entered_points'];
            $subscription['amount'] = $result['cost'];
            $subscription['earned_points'] = $result['earned_points'];
            $subscription = Subscription::create($subscription);
            $fcmToken = $subscription->user->fcm_token;
            $variables = ['tripNumber' => $collageTrip->id];
            $message = NotificationsEnum::SUBSCRIBE_ORDER->formatMessage(NotificationsEnum::SUBSCRIBE_ORDER->value, $variables);
//            app(NotificationService::class)->sendNotification($fcmToken, NotificationsEnum::TITLE->value, $message);
            dispatch(new SendNotificationJob($fcmToken, $user, $message, false));
            return $subscription;
        });
    }

    public function renew($request) //TODO
    {
        return DB::transaction(function () use ($request) {
            $user = User::findOrFail(auth('sanctum')->id());
            $subscription = $user->subscription()->first();
            if (!$subscription) {
                return 'not exist.';
            }
            $collageTrip = CollageTrip::query()->findOrFail($subscription->collage_trip_id);
            $subscription->start_date = $request->start_date;
            $subscription->end_date = $request->end_date;
            $result = app(TripService::class)->pointsDiscountDaily($request->points, $user->points, $collageTrip, 'Round Trip', false);
            $subscription->used_points = $result['required_points'];
            $subscription->amount = $result['cost'];
            $subscription->earned_points = $result['earned_points'];
            $subscription->status = 'pending';
            $subscription->save();
            return 'renewed successfully.';
        });
    }

    public function unSubscribe() //TODO
    {
        $user = User::findOrFail(auth('sanctum')->id());
        return $user->subscription()->first()->delete();
    }

    public function updateStatus($request)
    {
        return DB::transaction(
            function () use ($request) {
                if ('accepted' == $request->status) {
                    $subscription = Subscription::findOrFail($request->subscription_id);
                    //points
                    $user = User::findOrFail($subscription->user_id);
                    $subscription->update([
                        'status' => 'accepted',
                    ]);
                    $user->points = ($user->points - $subscription->used_points) + $subscription->earned_points;
                    $user->save();
                    return 'accepted';
                } elseif ('rejected' == $request->status) {
                    Subscription::findOrFail($request->subscription_id)->delete();
                    return 'rejected';
                }
                return false;
            }
        );
    }
}
