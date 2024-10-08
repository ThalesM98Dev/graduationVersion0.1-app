<?php

namespace App\Services;


use App\Enum\NotificationsEnum;
use App\Enum\RolesEnum;
use App\Helpers\ImageUploadHelper;
use App\Helpers\ResponseHelper;
use App\Jobs\SendNotificationJob;
use App\Models\CollageTrip;
use App\Models\DailyCollageReservation;
use App\Models\Day;
use App\Models\Envelope;
use App\Models\Reservation;
use App\Models\Station;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
                    if ($station['type'] === 'Back') {
                        $station['out_time'] = null;
                    } else {
                        $station['out_time'] = Carbon::parse($station['out_time'])->format('H:i:s');
                    }
                    Station::create([
                        'name' => $station['name'],
                        'collage_trip_id' => $trip->id,
                        'in_time' => Carbon::parse($station['in_time'])->format('H:i:s'),
                        'out_time' => $station['out_time'],
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
        $result = CollageTrip::with([
            'stations',
            'days:id,name',
            'trips' => function ($query) use ($request) {
                if ('archived' == $request->type) {
                    $query->whereDate('date', '<', Carbon::now());
                }
                if ('upcoming' == $request->type) {
                    $query->whereDate('date', '>=', Carbon::now());
                }
            }
        ]);
        return $result->get();
    }

    public function collageTripDetails($collageTripID) //incoming
    {
        return CollageTrip::with([
            'driver',
            'stations',
            'days:id,name',
            'trips' => function ($query) {
                $query->whereDate('date', '>=', Carbon::now());
            },
            'trips.dailyCollageReservation.user',
            'subscriptions' => function ($query) {
                $query->where('status', 'accepted');
            },
            'subscriptions.user'
        ])
            ->findOrFail($collageTripID);
    }

    public function archivedCollageTripDetails($collageTripID) //archived
    {
        return CollageTrip::with([
            'driver',
            'stations',
            'days:id,name',
            'trips' => function ($query) {
                $query->whereDate('date', '<', Carbon::now());
            },
            'trips.dailyCollageReservation.user',
            'subscriptions' => function ($query) {
                $query->where('status', 'accepted');
            },
            'subscriptions.user'
        ])
            ->findOrFail($collageTripID);
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
            $user = User::findOrFail(auth('sanctum')->id());
            $trip = Trip::where('collage_trip_id', $request->collage_trip_id)
                ->whereDate('date', $date)
                ->first();
            if ($trip) {
                if ($trip->available_seats == 0) {
                    return ResponseHelper::error(message: 'There is no available seats on this trip.');
                }
                $existReservation = $user->dailyCollageReservations()->where('trip_id', $trip->id)->first();
                if ($existReservation) {
                    return ResponseHelper::error(message: 'Your allready have a reservation.');
                }
                $reservation['trip_id'] = $trip->id;
                $reservation['user_id'] = $user->id;
                $reservation['day_id'] = $request->day_id;
                $reservation['type'] = $request->type;
                if ($request->points >= 0) {
                    $collage_trip = $trip->collageTrip()->first();
                    $points = $this->pointsDiscountDaily($request->points, $user->points, $collage_trip, $request->type, true);
                    $reservation['cost'] = $points['cost'];
                    $reservation['used_points'] = $points['entered_points'];
                    $reservation['earned_points'] = $points['earned_points'];
                }
                $trip->available_seats = $trip->available_seats - 1;
                $trip->save();
                $res = DailyCollageReservation::create($reservation);
                return ResponseHelper::success($res);
            }
            return ResponseHelper::error(message: 'There is no Trip found at this day.');
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

    public function dailyReservationsInfo($resId)
    {
        $reservation = DailyCollageReservation::findOrFail($resId);
        return $reservation->load(['user', 'trip', 'day']);
    }

    public function checkCost($request): array
    {
        $user = User::findOrFail(auth('sanctum')->id());
        $collageTrip = CollageTrip::findOrFail($request->collage_trip_id);
        $result = [];
        if ('daily' == $request->reservation_type) {
            $result = $this->pointsDiscountDaily($request->points, $user->points, $collageTrip, $request->trip_type, true);
        }
        if ('monthly' == $request->reservation_type) {
            $result = $this->pointsDiscountDaily($request->points, $user->points, $collageTrip, $request->trip_type, false);
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
        // dd($requiredPoints);
        if ($points < $requiredPoints) {
            // dd($requiredPoints);
            //
            $cost = round(($points * $tripPrice) / $requiredPoints);
            $result['cost'] = $tripPrice - $cost;
            $result['entered_points'] = $points;
            $result['remaining_points'] = $userPoints - $points;
            $result['earned_points'] = $earnedPoints;
        } else {
            $result['cost'] = 0;
            $result['entered_points'] = $requiredPoints;
            $result['remaining_points'] = $userPoints - $points;
            $result['earned_points'] = $earnedPoints;
        }
        return $result;
    }

    public function payReservation($id)
    {
        //pay daily reservation
        $reservation = DailyCollageReservation::findOrFail($id);
        $user = $reservation->user;
        if ('paid' == $reservation->status) {
            return ResponseHelper::error('Already Paid');
        }
        return DB::transaction(function () use ($user, $reservation) {
            $reservation->update([
                'status' => 'paid',
            ]);
            $user->points = ($user->points - $reservation->used_points) + $reservation->earned_points;
            $user->save();
            return ResponseHelper::success(data: $reservation->load('user'), message: 'Paid successfully');
        });
    }

    public function usersCollageReservations($user, $date, $status)
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');
        return $user->dailyCollageReservations()
            ->where('status', $status)
            ->whereHas('trip', function ($query) use ($date) {
                $query->whereDate('date', '>=', $date);
            })
            ->with(['trip'])
            ->get();
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

    public function checkTripExistence($nextWeekTripDate, $collageTripId)
    {
        $trip = Trip::where('collage_trip_id', $collageTripId)
            ->whereDate('date', '=', $nextWeekTripDate->format('Y-m-d'))
            ->where('trip_type', 'Universities')
            ->first();
        if ($trip) {
            return $trip;
        }
        return null;
    }

    public function checkReservationExistence($subscription, $tripId)
    {
        $reservation = $subscription->reservation()
            ->where('trip_id', $tripId)
            ->first();
        if ($reservation) {
            return $reservation;
        }
        return null;
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
            'description' => $request->description,
            'receiver_name' => $request->receiver_name,
            'receiver_phone' => $request->receiver_phone,
            'receiver_location' => $request->receiver_location,
        ]);
    }

    public function approveEnvelopOrder($request) //driver
    {
        $user = auth('sanctum')->user();
        $envelope = Envelope::findOrFail($request->envelope_id);
        $status = $request->status;
        $trip = $envelope->trip;
        if ($user->id != $trip->driver_id) {
            return 'This envelope is not belongs to you!';
        }
        if ($envelope->isAccepted) {
            return 'Already approved envelop';
        }
        return DB::transaction(function () use ($user, $envelope, $trip, $status) {
            $fcmToken = $envelope->user->fcm_token;
            $driver = $envelope->trip->driver;
            $variables = ['driverName' => $driver->name];
            if ('accept' == $status) {
                $envelope->update(['isAccepted' => true]);
                $message = NotificationsEnum::ENVELOPE_ORDER_ACCEPTANCE->formatMessage(NotificationsEnum::ENVELOPE_ORDER_ACCEPTANCE->value, $variables);
                //                app(NotificationService::class)->sendNotification($fcmToken, NotificationsEnum::TITLE, $message);
                dispatch(new SendNotificationJob($fcmToken, $envelope->user, $message, false));

                return $envelope;
            }
            $message = NotificationsEnum::ENVELOPE_ORDER_REJECT
                ->formatMessage(NotificationsEnum::ENVELOPE_ORDER_REJECT->value, $variables);
            //            app(NotificationService::class)->sendNotification($fcmToken, NotificationsEnum::TITLE, $message);
            dispatch(new SendNotificationJob($fcmToken, $envelope->user, $message, false));

            return $envelope->delete();
        });
    }

    public function getDriverEnvelopOrders($user) //Driver
    {
        $result = Envelope::with(['user'])
            ->whereHas('trip', function ($query) use ($user) {
                $query->where('driver_id', $user->id);
            })
            ->with(['trip' => function ($query) use ($user) {
                $query
                    //->where('driver_id', $user->id)
                    ->orderBy('date')
                    ->with(['destination']);
            }])
            ->orderBy('created_at')
            ->get();
        return ResponseHelper::success(data: $result);
    }

    public function getUserEnvelopes($user)
    {
        $result = Envelope::with(['user', 'trip' => function ($query) {
            $query->with(['driver', 'destination']);
        }])
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();
        return ResponseHelper::success(data: $result);
    }

    public function showEnvelop($id) //all roles
    {
        $envelope = Envelope::findOrFail($id);
        return ResponseHelper::success(data: $envelope->load(['user', 'trip.destination']));
    }
}
