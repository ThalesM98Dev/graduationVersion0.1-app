<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Destination;
use App\Models\Trip;
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
            'bus_id' => 'required|exists:buses,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $trip->save();

        // Return a response indicating success
        return response()->json($trip, Response::HTTP_OK);
    }
    

    public function show_trip_details($id)
    {
        $trip = Trip::with('bus', 'destination')->findOrFail($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($trip, Response::HTTP_OK);
    }
}
