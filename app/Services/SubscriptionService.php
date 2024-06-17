<?php

namespace App\Services;

use App\Models\CollageTrip;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function getAllSubscriptions()
    {
        return Subscription::with(['user', 'collageTrip'])->get();
    }

    public function subscribe($request)
    {
        return DB::transaction(function () use ($request) {
            $trip = CollageTrip::findOrFail($request->collage_trip_id);
            $subscription = Subscription::create([
                'user_id' => auth('sanctum')->id(),
                'collage_trip_id' => $trip->id,
                'amount' => $request->semester_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            return $subscription;
        });
    }


    public function renew($request)
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
        return Subscription::findOrFail($request->subscription_id)->update([
            'status' => $request->status,
        ]);
    }
}
