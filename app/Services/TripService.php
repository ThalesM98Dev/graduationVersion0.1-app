<?php

namespace App\Services;

use App\Models\Bus;
use App\Models\Destination;
use App\Models\Reservation;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class TripService
{

    public function createCollageTrip($request)//TODO needs test
    {
        return DB::transaction(function () use ($request) {
            $bus = Bus::query()->findOrFail($request->bus_id);
            $trip = Trip::query()->create([
                'trip_number' => $request->trip_number,
                'date' => $request->date,
                'depature_hour' => $request->depature_hour,
                'back_hour' => $request->back_hour,
                'trip_type' => $request->trip_type,
                'starting_place' => $request->starting_place,
                'destination_id' => $request->destination_id,
                'bus_id' => $bus->id,
                'driver_id' => $request->driver_id,
                'price' => $request->daily_price,
                'semester_price' => $request->semester_price,
                'daily_points' => $request->daily_points,
                'semester_points' => $request->semester_points,
                'available_seats' => $bus->number_of_seats
            ]);
            foreach ($request->stations as $station) {
                if (isset($station['name']) && isset($station['arrival_dateTime'])) {
                    $stationTrip = Destination::query()->updateOrCreate([
                        'name' => $station['name'],
                    ]);
                    $trip->stations()->attach($stationTrip->id, ['arrival_dateTime' => $station['arrival_dateTime']]);
                }
            }
            return Trip::with(['stations' => function ($query) {
                $query->select('name');
                $query->withPivot('arrival_datetime');
            }])->get();
        });
    }

    public function updateCollageTrip($request)
    {
        return DB::transaction(function () use ($request) {
            $trip = Trip::query()->findOrFail($request->trip_id);
            $trip->update([
            ]);
        });
    }

    public function listCollageTrips($request)
    {
        return Trip::query()->where('status', $request->status)
            ->where('trip_type', 'collage trip')
            ->where('starting_place', $request->starting_place)
            ->select('id', 'starting_place', 'date', 'price')
            ->groupBy('id', 'starting_place', 'date', 'price')
            ->get();
    }

    public function collageTripDetails($trip_id)
    {
        $trip = Trip::query()
            ->with([
                'stations' => function ($query) {
                    $query->select('name');
                    $query->withPivot('arrival_datetime');
                }])
            ->findOrFail($trip_id);
        $trip->subscriptions;
        return $trip;
    }

    public function bookDailyCollageTrip($request)
    {
        return DB::transaction(function () use ($request) {
            $trip = Trip::query()->findOrFail($request->trip_id);
            $seats = $trip->bus->seats;
            $seat_number = null;
            foreach ($seats as $index => $seat) {
                if ($seat) {
                    $seats[$index] = false;
                    $seat_number = $index;
                }
                break;
            }
            $trip->available_seats--;
            $trip->save();
            $trip->bus->seats = $seats;
            $trip->bus->save();
            $reservation = Reservation::query()->create([
                'order_id' => $request->order_id,
                'trip_id' => $request->trip_id,
                'seat_number' => $seat_number,
            ]);
            return $reservation;
        });
    }


}
