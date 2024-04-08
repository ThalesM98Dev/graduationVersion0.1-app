<?php

namespace App\Http\Controllers;

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

    public function all_trip(){
     $allTrip = Trip::all();
        return response()->json($allTrip);

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
        $trip = Trip::with('bus', 'destination','driver')->findOrFail($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], Response::HTTP_NOT_FOUND);
        }
        $response = [
            'trip' => $trip
        ];
        return ResponseHelper::success($response);
    }
    public function endTrip(Request $request , $id)
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

    // Return a response indicating an error if the reservation doesn't exist or is already confirmed
    return response()->json(['message' => 'Invalid trip ID or trip already confirmed'], 422);
}

      public function getTripsByDestination($destination)
         {
            $trips = Trip::whereHas('destination', function ($query) use ($destination) {
        $query->where('name', $destination);
    })->get();

             $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
         }

    public function getTripsByUserId($userId)
       {
         $trips = Trip::where('status', 'pending')
        ->whereHas('orders', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('orders.reservations', function ($query) {
            $query->where('status', 'confirmed');
        })
        ->get();

         return response()->json(['trips' => $trips], 200);
        }

        public function getTripsByDriver($driverId)
        {
        $trips = Trip::where('driver_id', $driverId)->get();

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
        }
}
