<?php

namespace App\Services;

use App\Models\Bus;
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
                'go_price' => $request['go_price'],
                'round_trip_price' => $request['round_trip_price'],
                'semester_round_trip_price' => $request['semester_round_trip_price'],
                'go_points' => $request['go_points'],
                'round_trip_points' => $request['round_trip_points'],
                'semester_round_trip_points' => $request['semester_round_trip_points'],
                'required_go_points' => $request['go_points'],
                'required_round_trip_points' => $request['round_trip_points'],
                'required_semester_round_trip_points' => $request['semester_round_trip_points'],
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
                        'type' => $station['type'],
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
                    'total_seats' => $request->total_seats,
                    'available_seats' => $request->total_seats,
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
                'go_price' => $request->go_price,
                'round_trip_price' => $request->round_trip_price,
                'semester_round_trip_price' => $request->semester_round_trip_price,
                'go_points' => $request->go_points,
                'round_trip_points' => $request->round_trip_points,
                'required_go_points' => $request['go_points'],
                'required_round_trip_points' => $request['round_trip_points'],
                'required_semester_round_trip_points' => $request['semester_round_trip_points'],
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
                        'type' => $station['type'],
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
                $query->whereDate('date', '<', Carbon::now());
            });
        }
        if ('upcoming' == $request->type) {
            $result->whereHas('trips', function ($query) {
                $query->whereDate('date', '>=', Carbon::now());
            });
        }
        return $result->with('trips')->get();
    }

    public function collageTripDetails($trip_id, $operator)
    {
        return CollageTrip::with([
            'stations',
            'days:id,name',
        ])->with('trips', function ($query) use ($operator) {
            $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'))
                ->with('dailyCollageReservation');
        })
            ->with('subscriptions', function ($query) {
                $query->where('status', 'accepted');
            })->with('subscriptions.user')->findOrFail($trip_id);
    }

    public function deleteCollageTrip($trip_id)
    {
        return CollageTrip::findOrFail($trip_id)->delete();
    }

    public function bookDailyCollageTrip($request)
    {
        return DB::transaction(function () use ($request) {
            $reservation['trip_id'] = $request->trip_id;
            $reservation['user_id'] = auth('sanctum')->id();
            $reservation['day_id'] = $request->day_id;
            $reservation['type'] = $request->type;
            $trip = Trip::findOrFail($request->trip_id);
            if ($trip->available_seats > 0) {
                $trip->available_seats = $trip->available_seats - 1;
                $trip->save();
                return DailyCollageReservation::create($reservation);
            }
            return false;
        });
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

    public function dailyReservations() //not used
    {
        return DailyCollageReservation::with(['user', 'trip' => function ($query) {
            $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
        }, 'day:id,name'])
            ->get();
    }

    public function pointsDiscountDaily($userPoints, $trip, $type, $status)
    {
        switch ($type) {
            case 'Go' | 'Back': //???
                //
                $tripPoints = $trip->go_points;
                $tripPrice = $trip->go_price;
                break;
            case 'Round Trip':
                //
                //dd(2);
                $tripPoints = $status ? $trip->round_trip_points : $trip->required_semester_round_trip_points;
                $tripPrice = $status ? $trip->round_trip_price : $trip->semester_round_trip_price;
                break;
            default:
                //
                $tripPoints = 0;
                $tripPrice = 0;
                break;
        }
        $result = [];
        return $this->calculate($userPoints, $tripPoints, $tripPrice, $result);
    }

    public function pointsDiscountSemster($userPoints, $trip) //not used
    {
        $result = [];
        $tripPoints = $trip->required_semester_round_trip_points;
        $tripPrice = $trip->semester_round_trip_price;
        return $this->calculate($userPoints, $tripPoints, $tripPrice, $result);
    }

    /**
     * @param $userPoints
     * @param mixed $tripPoints
     * @param mixed $tripPrice
     * @param array $result
     * @return array
     */
    public function calculate($userPoints, mixed $tripPoints, mixed $tripPrice, array $result): array
    {
        if ($userPoints < $tripPoints) {
            //
            $cost = round(($userPoints * $tripPrice) / $tripPoints);
            $result['cost'] = $cost;
            $result['required_points'] = $userPoints;
            $result['remaining_points'] = 0;
        } else {
            $result['cost'] = 0;
            $result['required_points'] = $tripPoints;
            $result['remaining_points'] = $userPoints - $tripPoints;
        }
        return $result;
    }

    public function payReservation($user, $reservation)
    {
        //pay daily reservation
        return DB::transaction(function () use ($user, $reservation) {
            $trip = $reservation->trip()->first()->collageTrip()->first();
            $result = $this->pointsDiscountDaily($user->points, $trip, $reservation->type, true);
            $reservation->update([
                'cost' => $result['cost'],
                'used_points' => $result['required_points']
            ]);
            $user->points = $result['remaining_points'];
            $user->save();
        });
    }
}
