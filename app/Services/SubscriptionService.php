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
        return Subscription::with(['user', 'collageTrip'])
            ->where('status', $type)
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
            $subscription = Subscription::query()
                ->where('user_id', \auth('sanctum')->id())
                ->first();
            $trip = CollageTrip::query()->findOrFail($subscription->trip_id);
            $subscription->update([
                'amount' => $trip->semester_round_trip_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            return true;
        });
    }

    public function unSubscribe()
    {
        return Subscription::query()
            ->where('user_id', \auth('sanctum')->id())
            ->first()->delete();
    }

    public function updateStatus($request)
    {
        if ('accepted' == $request->status) {
            $subscription = Subscription::findOrFail($request->subscription_id);
            //points
            $user = User::findOrFail($subscription->user_id);
            $result = app(TripService::class)->pointsDiscountDaily($subscription->used_points, $user->points, $subscription->collageTrip()->first(), 'Round Trip', false);
            //dd($result);
            $subscription->update([
                'status' => $request->status,
                'amount' => $result['cost'],
                'used_points' => $result['required_points']
            ]);
            $user->points = $result['remaining_points'];
            $user->save();
            return 'accepted';
        } elseif ('rejected' == $request->status) {
            Subscription::findOrFail($request->subscription_id)->delete();
            return 'rejected';
        }
        return false;
    }
}
