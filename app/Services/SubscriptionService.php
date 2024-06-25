<?php

namespace App\Services;

use App\Models\CollageTrip;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function getAllSubscriptions($type)
    {
        return Subscription::where('status', '=', $type)
            ->with(['user', 'collageTrip'])
            ->get();
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
            $subscription['used_points'] = $result['required_points'];
            $subscription['amount'] = $result['cost'];
            $subscription['earned_points'] = $result['earned_points'];
            return Subscription::create($subscription);
        });
    }

    public function renew($request) //TODO
    {
        return DB::transaction(function () use ($request) {
            $user = User::findOrFail(auth('sanctum')->id());
            $subscription = $user->subscription()->first();
            //dd($subscription);
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
            $subscription->save();
            return 'renewed successfully.';
        });
    }

    public function unSubscribe() //TODO
    {
        $user = User::findOrFail(auth('sanctum')->id());
        return  $user->subscription()->first()->delete();
    }

    public function updateStatus($request)
    {
        return DB::transaction(function () use ($request) {
                if ('accepted' == $request->status) {
                    $subscription = Subscription::findOrFail($request->subscription_id);
                    //points
                    $user = User::findOrFail($subscription->user_id);
                    $subscription->update([
                        'status' => 'accepted',
                    ]);
                    $user->points =  ($user->points - $subscription->used_points) + $subscription->earned_points;
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
