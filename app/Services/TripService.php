<?php

namespace App\Services;

use App\Models\CollageTrip;
use App\Models\DailyCollageReservation;
use App\Models\Reservation;
use App\Models\Station;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function createCollageTrip($request)
    {
        return DB::transaction(function () use ($request) {
            $trip = CollageTrip::query()->create([
                //'day' => $request['day'],
                'go_price' => $request['go_price'],
                'round_trip_price' => $request['round_trip_price'],
                'semester_go_price' => $request['semester_go_price'],
                'semester_round_trip_price' => $request['semester_round_trip_price'],
                'go_points' => $request['go_points'],
                'round_trip_points' => $request['round_trip_points'],
                'semester_go_points' => $request['semester_go_points'],
                'semester_round_trip_points' => $request['semester_round_trip_points'],
            ]);
            $days = $request['days'];
            if ($days) {
                $trip->days()->attach($days);
            }
            $stations = $request['stations'];
            if ($stations) {
                foreach ($stations as $station) {
                    Station::create([
                        'name' => $station['name'],
                        'collage_trip_id' => $trip->id,
                        'in_time' => Carbon::parse($station['in_time'])->format('H:i:s'),
                        'out_time' => Carbon::parse($station['out_time'])->format('H:i:s'),
                        //'isSource' => $station['isSource'],
                    ]);
                }
            }
            $latestTrip = Trip::latest()->first();
            $latestTripNumber = $latestTrip ? $latestTrip->trip_number : 0;
            foreach ($trip->days as $day) {
                $nextWeekTripDate = Carbon::now()->next($day->name);
                Trip::create([
                    'trip_number' => ++$latestTripNumber,
                    'collage_trip_id' => $trip->id,
                    'date' => $nextWeekTripDate->format('Y-m-d'),
                    'trip_type' => 'Universities',
                ]);
            }
            return $trip->with('stations')->findOrFail($trip->id);
        });
    }

    public function updateCollageTrip($request) //TODO
    {
        return DB::transaction(function () use ($request) {
            $trip = CollageTrip::findOrFail($request->trip_id);
            $trip->update([
                //'day' => $request->day,
                'go_price' => $request->go_price,
                'round_trip_price' => $request->round_trip_price,
                'semester_go_price' => $request->semester_go_price,
                'semester_round_trip_price' => $request->semester_round_trip_price,
                'go_points' => $request->go_points,
                'round_trip_points' => $request->round_trip_points,
                'semester_go_points' => $request->semester_go_points,
            ]);
            $days = $request['days'];

            if ($days) {
                $trip->days()->detach();
                $trip->days()->attach($days);
            }
            $stations = $request['stations'];
            if ($stations) {
                $trip->stations()->delete();
                foreach ($stations as $station) {
                    Station::create([
                        'name' => $station['name'],
                        'collage_trip_id' => $trip->id,
                        'in_time' => Carbon::parse($station['in_time'])->format('H:i:s'),
                        'out_time' => Carbon::parse($station['out_time'])->format('H:i:s'),
                    ]);
                }
            }
            return $trip->with('stations')->get();
        });
    }

    public function listCollageTrips($request)
    {
        $result = CollageTrip::with(['stations', 'days:id,name']);
        if ('archived' == $request->type) {
            $result->whereHas('trips', function ($query) {
                $query->whereDate('date', '<=', Carbon::now());
            });
        }
        if ('upcoming' == $request->type) {
            $result->whereHas('trips', function ($query) {
                $query->whereDate('date', '>=', Carbon::now());
            });
        }
        return $result->with('trips')->get();
    }

    public function collageTripDetails($trip_id)
    {
        return CollageTrip::with([
            'stations',
            'subscriptions' => function ($query) {
                $query->where('status', 1);
            },
            'days:id,name',
            'trips' => function ($query) {
                $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'))
                    ->with('dailyCollageReservation');
            }
        ])
            ->findOrFail($trip_id);
    }

    public function deleteCollageTrip($trip_id)
    {
        return CollageTrip::findOrFail($trip_id)->delete();
    }

    public function bookDailyCollageTrip($request)
    {
        return DailyCollageReservation::create([
            'trip_id' => $request->trip_id,
            'user_id' => auth('sanctum')->id(),
            'day_id' => $request->day_id,
            'type' => $request->type
        ]);
    }

    public function tripsCountDestinationPeriod($request)
    {
        $destination_id = $request['destination_id'];
        $reservations = Reservation::with('trip')
            ->whereHas('trip', function ($query) use ($destination_id) {
                $query->where('destination_id', $destination_id);
            })->get();
        return $this->getStatistics($request, $reservations);
    }

    public function tripsCountPerDatePeriod($request)
    {
        $startDate = Carbon::parse($request['start_date']);
        $endDate = Carbon::parse($request['end_date']);
        $reservations = Reservation::with('trip')
            ->whereHas('trip', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })->get();
        return $this->getStatistics($request, $reservations);
    }

    /**
     * @param $request
     * @param Collection|array $reservations
     * @return array|\Illuminate\Support\Collection
     */
    public function getStatistics($request, Collection|array $reservations): \Illuminate\Support\Collection|array
    {
        $statistics = [];
        if ('year' == $request['type']) {
            $statistics = $reservations->groupBy(function ($reservation) {
                return Carbon::parse($reservation->trip->date)->format('Y');
            })->map(function ($group) {
                return [
                    'period' => $group->first()->trip->date->format('Y'),
                    'reservation_count' => $group->count()
                ];
            })->values();
        } elseif ('month' == $request['type']) {
            $statistics = $reservations->groupBy(function ($reservation) {
                return Carbon::parse($reservation->trip->date)->format('m');
            })->map(function ($group) {
                return [
                    'period' => $group->first()->trip->date->format('m'),
                    'reservation_count' => $group->count()
                ];
            })->values();
        }
        return $statistics;
    }

    public function searchByDestination($station)
    {
        return CollageTrip::whereHas('stations', function ($query) use ($station) {
            $query->where('name', $station);
        })->with(['stations', 'trips', 'days:id,name'])
            ->get();
    }

    public function userReservations($request)
    {
        return DailyCollageReservation::with(['trip', 'days:id,name'])
            ->where('user_id', auth('sanctum')->id())
            ->get();
    }
}
