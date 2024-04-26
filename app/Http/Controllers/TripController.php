<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollageTripRequest;
use App\Services\TripService;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Destination;
use App\Models\User;
use App\Models\Trip;
use App\Models\Archive;
use App\Models\Reservation;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class TripController extends Controller
{

    public $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }


    public function all_trip()
    {
        $trips = Trip::where('status', 'pending')
            ->with('destination','bus','driver')
            ->get();
        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);

    }

    public function add_trip(Request $request)

    {
        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|integer|unique:trips',
            'date' => 'required|date',
            'depature_hour' => 'required|date_format:H:i',
            'back_hour' => 'required|date_format:H:i',
            'trip_type' => 'required|string',
            'starting_place' => 'required|string',
            'destination_id' => 'required|exists:destinations,id',
            'bus_id' => 'required|exists:buses,id',
            'driver_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $driver = User::find($request->driver_id);
        if (!$driver || $driver->role !== 'Driver') {
            return response()->json(['message' => 'The User must be a driver'], Response::HTTP_NOT_FOUND);
        }
        // Create a new trip instance
        $trip = new Trip();
        $trip->trip_number = $request->trip_number;
        $trip->date = $request->date;
        $trip->depature_hour = $request->depature_hour;
        $trip->back_hour = $request->back_hour;
        $trip->trip_type = $request->trip_type;
        $trip->starting_place = $request->starting_place;
        $trip->price = $request->price;
        $trip->destination_id = $request->destination_id;
        $trip->bus_id = $request->bus_id;
        $trip->driver_id = $request->driver_id;
        $bus = Bus::find($request->bus_id);
        $trip->available_seats = $bus->number_of_seats;
        $trip->save();

        // Return a response indicating success
        $response = [
            'trip' => $trip
        ];
        return ResponseHelper::success($response);
    }


    public function show_trip_details($id)
    {
        $trip = Trip::with('bus', 'destination','driver','orders')->findOrFail($id);
         $reservations = Reservation::where('trip_id', $id)
         ->where('status', 'confirmed')
         ->orderBy('order_id')
         ->get();
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], Response::HTTP_NOT_FOUND);
        }
        $response = [
            'trip' => $trip,
            'reservations' => $reservations
        ];
        return ResponseHelper::success($response);
    }

    public function endTrip(Request $request, $id)
    {
        $trip = Trip::find($id);

        // Check if the reservation exists and is not already confirmed
        if ($trip && $trip->status != 'done') {
            $trip->status = 'done';
            $trip->save();
            $archive = new Archive();
            $archive->fill($trip->toArray());
            $archive->save();

            // Return a response indicating success
            return response()->json(['message' => 'Trip is done'], 200);
        }
        return response()->json(['message' => 'Invalid trip ID or trip already confirmed'], 422);
    }

      public function getTripsByDestination($destination)
         {
            $trips = Trip::whereHas('destination', function ($query) use ($destination) {
        $query->where('name', $destination);
        })->where('status', 'pending')
            ->with('destination','bus','driver')
            ->get();

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }

    public function getPendingTripsByUser($userId)
    {
    $trips = Trip::join('reservations', 'reservations.trip_id', '=', 'trips.id')
                 ->join('orders', 'orders.id', '=', 'reservations.order_id')
                 ->where('orders.user_id', $userId)
                 ->where('trips.status', 'pending')
                 ->with('destination','bus','driver')
                 ->get();

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }

    public function getEndingTripsByUser($userId)
    {
    $trips = Trip::join('reservations', 'reservations.trip_id', '=', 'trips.id')
                 ->join('orders', 'orders.id', '=', 'reservations.order_id')
                 ->where('orders.user_id', $userId)
                 ->where('trips.status', 'done')
                 ->with('destination','bus','driver')
                 ->get();
    $response = [
        'trips' => $trips
    ];
    return ResponseHelper::success($response);
    }

        public function getTripsByDriver($driverId)
        {
        $trips = Trip::where('driver_id', $driverId)
        ->where('status', 'pending')
        ->with('destination','bus','driver')
        ->get();

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }


    /*
    Collage Trips
    */

    public function createCollageTrip(CollageTripRequest $request)
    {
        $result = $this->tripService->createCollageTrip($request);
        return ResponseHelper::success($result);
    }

    public function updateCollageTrip(CollageTripRequest $request)//TODO
    {
        $result = $this->tripService->updateCollageTrip($request);
        return ResponseHelper::success($result);
    }

    public function collageTrips(Request $request)
    {
        $result = $this->tripService->listCollageTrips($request);
        return ResponseHelper::success($result);
    }

    public function collageTripDetails(Request $request)
    {
        $result = $this->tripService->collageTripDetails($request->trip_id);
        return ResponseHelper::success($result);
    }

    public function bookDailyCollageTrip(Request $request)
    {
        $result = $this->tripService->bookDailyCollageTrip($request);
        return ResponseHelper::success($result);
    }


}
