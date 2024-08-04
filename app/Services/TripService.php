<?php

namespace App\Services;


use App\Enum\RolesEnum;
use App\Helpers\ImageUploadHelper;
use App\Models\CollageTrip;
use App\Models\DailyCollageReservation;
use App\Models\Day;
use App\Models\Envelope;
use App\Models\Reservation;
use App\Models\Station;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use http\Env;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
                'driver_id' => $request['driver_id']
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

    public function updateCollageTrip($tripId, $request) //TODO
    {
        return DB::transaction(function () use ($tripId, $request) {
            $trip = CollageTrip::findOrFail($tripId);
            $trip->update([
                'go_price' => $request->go_price,
                'round_trip_price' => $request->round_trip_price,
                'semester_round_trip_price' => $request->semester_round_trip_price,
                'go_points' => $request->go_points,
                'round_trip_points' => $request->round_trip_points,
                'required_go_points' => $request['go_points'],
                'required_round_trip_points' => $request['round_trip_points'],
                'required_semester_round_trip_points' => $request['semester_round_trip_points'],
                'driver_id' => $request['driver_id']
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
            return $trip->with(['stations', 'days:id,name'])->get();
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
        return Cache::remember('collage_trips', 1, function () use ($result) {
            return $result->with('trips')->get();
        });
    }

    public function collageTripDetails($trip_id)
    {
        return CollageTrip::with([
            'driver',
            'stations',
            'days:id,name',
            'trips' => function ($query) {
                $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'))
                    ->with('dailyCollageReservation.user');
            }
        ])
            ->with('subscriptions', function ($query) {
                $query->where('status', '=', 'accepted')
                    ->with('user');
            })
            ->findOrFail($trip_id);
    }

    public function collageTripDetailsMobile($trip_id)
    {
        return CollageTrip::with([
            'stations',
            'days:id,name',
            'trips' => function ($query) {
                $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
            }
        ])->findOrFail($trip_id);
        // return Cache::remember('collage_trip_details_mobile' . $trip_id, 5, function () use ($result) {
        //     return $result;
        // });
    }

    public function deleteCollageTrip($trip_id)
    {
        return CollageTrip::findOrFail($trip_id)->delete();
    }

    public function bookDailyCollageTrip($request)
    {
        return DB::transaction(function () use ($request) {
            $day = Day::findOrFail($request->day_id);
            $date = Carbon::now()->next($day->name)->format('Y-m-d');
            $trip = CollageTrip::findOrFail($request->collage_trip_id)
                ->trips()
                ->whereDate('date', $date)
                ->first();
            if ($trip->available_seats > 0) {
                $reservation['trip_id'] = $trip->id;
                $reservation['user_id'] = auth('sanctum')->id();
                $reservation['day_id'] = $request->day_id;
                $reservation['type'] = $request->type;
                $user = User::findOrFail($reservation['user_id']);
                if ($request->points) {
                    $collage_trip = $trip->collageTrip()->first();
                    $points = $this->pointsDiscountDaily($request->points, $user->points, $collage_trip, $request->type, true);
                    $reservation['cost'] = $points['cost'];
                    $reservation['used_points'] = $points['required_points'];
                    $reservation['earned_points'] = $points['earned_points'];
                }
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
                    'period' => Carbon::parse($group->first()->trip->date)->format('Y'),
                    'reservation_count' => $group->count()
                ];
            })->values();
        } elseif ('month' == $request['type']) {
            $statistics = $reservations->groupBy(function ($reservation) {
                return Carbon::parse($reservation->trip->date)->format('m');
            })->map(function ($group) {
                return [
                    'period' => Carbon::parse($group->first()->trip->date)->format('Y-m'),
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
        $user = User::findOrFail(auth('sanctum')->id());
        return $user->dailyCollageReservations()->with(['trip', 'days:id,name'])->get();
    }

    public function dailyReservations() //not used
    {
        return DailyCollageReservation::with(['user', 'trip' => function ($query) {
            $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
        }, 'day:id,name'])->get();
    }

    public function checkCost($request): array
    {
        $user = User::findOrFail(auth('sanctum')->id());
        $trip = CollageTrip::findOrFail($request->trip_id);
        if ('daily' == $request->type) {
            $result = $this->pointsDiscountDaily($request->points, $user->points, $trip, $request->type, true);
        } else {
            $result = $this->pointsDiscountDaily($request->points, $user->points, $trip, $request->type, false);
        }
        return $result;
    }

    public function pointsDiscountDaily($points, $userPoints, $trip, $type, $status): array
    {
        switch ($type) {
            case 'Go':
            case 'Back':
                //
                $earnedPoints = $trip->go_points;
                $requiredPoints = $trip->required_go_points;
                $tripPrice = $trip->go_price;
                break;
            case 'Round Trip':
                //
                $earnedPoints = $status ? $trip->round_trip_points : $trip->semester_round_trip_points;
                $requiredPoints = $status ? $trip->required_round_trip_points : $trip->required_semester_round_trip_points;
                $tripPrice = $status ? $trip->round_trip_price : $trip->semester_round_trip_price;
                break;
            default:
                //
                $earnedPoints = 0;
                $requiredPoints = 0;
                $tripPrice = 0;
                break;
        }
        $result = [];
        return $this->calculate($points, $userPoints, $requiredPoints, $earnedPoints, $tripPrice, $result);
    }

    /**
     * @param $userPoints
     * @param mixed $tripPoints
     * @param mixed $tripPrice
     * @param array $result
     * @return array
     */
    public function calculate($points, $userPoints, mixed $requiredPoints, mixed $earnedPoints, mixed $tripPrice, array $result): array
    {
        $result = [];
        $points = intval($points);
        if ($points < $requiredPoints) {
            //
            $cost = round(($points * $tripPrice) / $requiredPoints);
            $result['cost'] = $tripPrice - $cost;
            $result['required_points'] = $points;
            $result['remaining_points'] = $userPoints - $points;
            $result['earned_points'] = $earnedPoints;
        } else {
            $result['cost'] = 0;
            $result['required_points'] = $requiredPoints;
            $result['remaining_points'] = $userPoints - $points;
            $result['earned_points'] = $earnedPoints;
        }
        return $result;
    }

    public function payReservation($user, $reservation)
    {
        //pay daily reservation
        return DB::transaction(function () use ($user, $reservation) {
            $reservation->update([
                'status' => 'paid',
            ]);
            $user->points = ($user->points - $reservation->used_points) + $reservation->earned_points;
            $user->save();
        });
    }

    public function usersCollageReservations($user, $date, $status)
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');
        //return Cache::remember('user_collage_reservations' . $user->id, 2, function () use ($user, $date, $status) {
        return $user->dailyCollageReservations()
            ->where('status', $status)
            ->whereHas('trip', function ($query) use ($date) {
                $query->whereDate('date', '>=', $date);
            })
            ->with(['trip'])
            ->get();
        // });
    }


    public function getDriverTrips($request)
    {
        $driver = User::findOrFail(auth('sanctum')->id());
        $trips = $driver->collageTrip()->with(['subscriptions', 'stations']);
        if ('upcoming' == $request->status) {
            return $trips->with(['trips' => function ($query) {
                $query->whereDate('date', '>=', Carbon::now()->format('Y-m-d'));
            }])->get();
        } elseif ('archived' == $request->status) {
            return $trips->with(['trips' => function ($query) {
                $query->whereDate('date', '<', Carbon::now()->format('Y-m-d'));
            }])->get();
        }
        return 'empty';
    }

    /**
     * Envelop
     */
    public function createEnvelopOrder($request) //user
    {
        //
        return Envelope::create([
            'user_id' => auth('sanctum')->id(),
            'trip_id' => $request->trip_id,
            'image' => ImageUploadHelper::upload($request->image),
            'description' => $request->description
        ]);
    }

    public function approveEnvelopOrder($request) //driver
    {
        $envelope = Envelope::findOrFail($request->envelope_id);
        if ('accept' == $request->status) {
            $envelope->update(['isAccepted' => true]);
            return $envelope->fresh();
        }
        return $envelope->delete();
    }

    public function getEnvelopOrders(): ?Collection //user and driver and admin (by trip)
    {
        $user = auth('sanctum')->user();
        $result = null;
        switch ($user->role) {
            case RolesEnum::ADMIN->value:
                $result = Trip::with(['envelops', 'driver'])
                    ->orderBy('date')
                    ->get();
                break;
            case RolesEnum::DRIVER->value://if the role is driver, return the trips (with envelopes) ordered by date from latest to oldest.
                $result = Trip::with('envelops')
                    ->where('driver_id', auth('sanctum')->id())
                    ->orderBy('date')
                    ->get();
                break;
            case RolesEnum::USER->value://if the role is user, return the envelopes ordered by date from latest to oldest.
                $result = Envelope::with('trip')
                    ->where('user_id', auth('sanctum')->id())
                    ->orderBy('created_at')
                    ->get();
                break;
        }
        return $result;
    }
}
