<?php

namespace App\Services;

use App\Models\CollageTrip;
use App\Models\DailyCollageReservation;
use App\Models\Reservation;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function createCollageTrip($request)
    {
        $trip = CollageTrip::query()->create([
            'day' => $request['day'],
            'departure_time' => Carbon::parse($request['departure_time'])->format('H:i:s'),
            'arrival_time' => Carbon::parse($request['arrival_time'])->format('H:i:s'),
            'go_price' => $request['go_price'],
            'round_trip_price' => $request['round_trip_price'],
            'semester_go_price' => $request['semester_go_price'],
            'semester_round_trip_price' => $request['semester_round_trip_price'],
            'go_points' => $request['go_points'],
            'round_trip_points' => $request['round_trip_points'],
            'semester_go_points' => $request['semester_go_points'],
            'semester_round_trip_points' => $request['semester_round_trip_points'],
        ]);
        $stations = $request['stations'];
        if ($stations) {
            foreach ($stations as $station) {
                //dd(strtotime($station['in_time']));
                Station::create([
                    'name' => $station['name'],
                    'collage_trip_id' => $trip->id,
                    'in_time' => Carbon::parse($station['in_time'])->format('H:i:s'),
                    'out_time' => Carbon::parse($station['out_time'])->format('H:i:s'),
                ]);
            }
        }
        return $trip->with('stations')->findOrFail($trip->id);
    }

    public function updateCollageTrip($request)//TODO
    {
        return DB::transaction(function () use ($request) {
            $trip = CollageTrip::findOrFail($request->trip_id);
            $trip->update([
                'day' => $request->day,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'go_price' => $request->go_price,
                'round_trip_price' => $request->round_trip_price,
                'semester_go_price' => $request->semester_go_price,
                'semester_round_trip_price' => $request->semester_round_trip_price,
                'go_points' => $request->go_points,
                'round_trip_points' => $request->round_trip_points,
                'semester_go_points' => $request->semester_go_points,
            ]);
            $stations = $request['stations'];
            if ($stations) {
                $trip->stations()->delete();
                foreach ($stations as $station) {
                    Station::create([
                        'name' => $station['name'],
                        'collage_trip_id' => $trip->id,
                        'in_time' => strtotime($station['in_time']),
                        'out_time' => strtotime($station['out_time']),
                    ]);
                }
            }
            return $trip->with('stations')->get();
        });
    }

    public function listCollageTrips($request)//TODO TEST
    {
        if ('archived' == $request->type) {
            $result = CollageTrip::with(['stations'])
                ->with('trips', function ($query) {
                    $query->whereDate('date', '<=', Carbon::now());
                })
                ->get();
        }
        if ('upcoming' == $request->type) {
            $result = CollageTrip::with(['stations'])
                ->with('trips', function ($query) {
                    $query->whereDate('date', '>=', Carbon::now());
                })
                ->get();
        }
        return $result;
    }

    public function collageTripDetails($trip_id)
    {
        return CollageTrip::with(['stations', 'subscriptions'])
            ->whereHas('trips', function ($query) {
                $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
            })
            ->with(['trips.dailyCollageReservation'])
            ->findOrFail($trip_id);
    }

    public function deleteCollageTrip($trip_id)
    {
        return CollageTrip::findOrFail($trip_id)->delete();
    }

    public function bookDailyCollageTrip($trip_id)
    {
        return DailyCollageReservation::create([
            'trip_id' => $trip_id,
            'user_id' => auth('sanctum')->id(),
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
        })->get();
    }
}
