<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Trip;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{

    public function subscribe($request)
    {
        return DB::transaction(function () use ($request) {
            $trip = Trip::query()->findOrFail($request->trip_id);
            $subscription = Subscription::query()->create([
                'user_id' => \auth('sanctum')->id(),
                'trip_id' => $request->trip_id,
                'amount' => $trip->semester_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            ///
            ///
            return $subscription;
        });
    }


    public function renew($request)
    {
        return DB::transaction(function () use ($request) {
            $subscription = Subscription::query()
                ->where('user_id', \auth('sanctum')->id())
                ->first();
            $trip = Trip::query()->findOrFail($subscription->trip_id);
            $subscription->update([
                'amount' => $trip->semester_price,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            return true;
        });
    }

    public function unSubscribe()
    {
        return DB::transaction(function () {
            Subscription::query()
                ->where('user_id', \auth('sanctum')->id())
                ->first()->delete();
            return true;
        });
    }
}
