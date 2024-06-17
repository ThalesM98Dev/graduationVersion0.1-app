<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollageTripRequest;
use App\Services\TripService;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Destination;
use App\Models\ReservationOrder;
use App\Models\User;
use App\Models\Trip;
use App\Models\Order;
use App\Models\Archive;
use App\Models\Reservation;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Mpdf\Mpdf;

class TripController extends Controller
{




   public function all_trip()
{
    $trips = Trip::where('status', 'pending')
        ->where('trip_type', 'External')
        ->with('destination', 'bus', 'driver')
        ->get();
    return ResponseHelper::success($trips);
}

    public function add_trip(Request $request)

    {
        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|integer|unique:trips',
            'price' => 'required|integer',
            'date' => 'required|date',
            'depature_hour' => 'required|regex:/^\d{1,2}:\d{2}\s?[AP]M$/',
            'trip_type' => 'required|in:External,Universities',
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
        $existingTripBus = Trip::where('date',$request->date)
        ->where('bus_id', $request->bus_id)
        ->where(function ($query) use ($request) {
        $query->where(function ($query) use ($request) {
            $query->where('depature_hour', '>=', $request->depature_hour)
                ->where('depature_hour', '<=', $request->arrival_hour);
        })->orWhere(function ($query) use ($request) {
            $query->where('arrival_hour', '>=', $request->depature_hour)
                ->where('arrival_hour', '<=', $request->arrival_hour);
        });
    })
    ->first();
        if ($existingTripBus) {
        return response()->json(['message' => 'The Bus You Enterd Not Available In This Date Or Hour'], Response::HTTP_NOT_FOUND);
        }
        $existingTripDriver = Trip::where('date',$request->date)
        ->where('driver_id', $request->driver_id)
        ->where(function ($query) use ($request) {
        $query->where(function ($query) use ($request) {
            $query->where('depature_hour', '>=', $request->depature_hour)
                ->where('depature_hour', '<=', $request->arrival_hour);
        })->orWhere(function ($query) use ($request) {
            $query->where('arrival_hour', '>=', $request->depature_hour)
                ->where('arrival_hour', '<=', $request->arrival_hour);
        });
    })
    ->first();
        if ($existingTripDriver) {
        return response()->json(['message' => 'The Driver You Enterd Not Available In This Date Or Hour'], Response::HTTP_NOT_FOUND);
        }
        // Create a new trip instance
        $trip = new Trip();
        $trip->trip_number = $request->trip_number;
        $trip->date = $request->date;
        $trip->depature_hour = Carbon::createFromFormat('h:i A', $request->depature_hour)->format('H:i:s');
        $trip->arrival_hour = Carbon::createFromFormat('h:i A', $request->arrival_hour)->format('H:i:s');
        $trip->trip_type = $request->trip_type;
        $trip->starting_place = $request->starting_place;
        $trip->price = $request->price;
        $trip->destination_id = $request->destination_id;
        $trip->bus_id = $request->bus_id;
        $trip->driver_id = $request->driver_id;
        $bus = Bus::find($request->bus_id);
        $trip->available_seats = $bus->number_of_seats;
        $trip->seats = array_fill(1, $trip->bus->number_of_seats, true);
        $trip->save();

        // Return a response indicating success
        return ResponseHelper::success($trip);
    }


   public function show_trip_details($id)
{
    $trip = Trip::with(['bus', 'destination', 'driver', 'reservations' => function ($query) {
        $query->where('status', 'confirmed');
    }])->find($id);

    if (!$trip) {
        return response()->json(['message' => 'Trip not found'], Response::HTTP_NOT_FOUND);
    }

    // Retrieve all orders and reservation orders for every reservation
    foreach ($trip->reservations as $reservation) {
        $reservation->orders = $reservation->orders()->get();
        $reservation->reservationOrders = $reservation->reservationOrders()->get();

        // Map seat number to each order
        foreach ($reservation->orders as $order) {
            $reservationOrder = $reservation->reservationOrders->firstWhere('order_id', $order->id);
            if ($reservationOrder) {
                $order->seat_number = $reservationOrder->seat_number;
                $order->is_seat_assigned = true; // Add a new property to indicate seat assignment
            }
        }
    }

    // Extract seat numbers for each order
    $orders = $trip->reservations->flatMap(function ($reservation) {
        return $reservation->orders->map(function ($order) use ($reservation) {
            $reservationOrder = $reservation->reservationOrders->firstWhere('order_id', $order->id);
            $order->seat_number = $reservationOrder ? $reservationOrder->seat_number : null;
            $order->is_seat_assigned = ($reservationOrder !== null); // Check if seat is assigned
            return $order;
        });
    });

    $trip->orders = $orders;

     $availableSeatNumbers = collect($trip->seats)
    ->filter(function ($seat) {
        return $seat === true;
    })
    ->keys()
    ->map(function ($seatNumber) {
        return (int)$seatNumber;
    });

    $trip->available_seat_numbers = $availableSeatNumbers;

    return ResponseHelper::success($trip);
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

      public function getTripsByDestinationInArchive(Request $request)
{
    $destinationName = $request->input('destination');
    
    $trips = Trip::whereHas('destination', function ($query) use ($destinationName) {
            $query->where('name', 'LIKE', "%{$destinationName}%");
        })
        ->where('status', 'done')
        ->with('destination', 'bus', 'driver')
        ->get();
    
    if ($trips->isEmpty()) {
        return response()->json(['message' => 'No trips found'], 404);
    }

    $response = [
        'trips' => $trips
    ];
    
    return ResponseHelper::success($response);
}
 public function getTripsByDestinationInAllTrips(Request $request)
{
    $destinationName = $request->input('destination');
    
    $trips = Trip::whereHas('destination', function ($query) use ($destinationName) {
            $query->where('name', 'LIKE', "%{$destinationName}%");
        })
        ->where('status', 'pending')
        ->with('destination', 'bus', 'driver')
        ->get();
    
    if ($trips->isEmpty()) {
        return response()->json(['message' => 'No trips found'], 404);
    }

    $response = [
        'trips' => $trips
    ];
    
    return ResponseHelper::success($response);
}

    public function getPendingTripsByUser($userId)
{
    $trips = Trip::join('reservations', 'reservations.trip_id', '=', 'trips.id')
        ->join('reservation_orders', 'reservation_orders.reservation_id', '=', 'reservations.id')
        ->join('orders', 'orders.id', '=', 'reservation_orders.order_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->join('buses', 'buses.id', '=', 'trips.bus_id')
        ->where('orders.user_id', $userId)
        ->where('trips.status', 'pending')
        ->select('trips.*', 'destinations.*', 'buses.*')
        ->distinct()
        ->get();

    if ($trips->isEmpty()) {
        return response()->json(['message' => 'No trips found'], 404);
    }
    return ResponseHelper::success($trips);
}

    public function getEndingTripsByUser($userId)
    {
    $trips = Trip::join('reservations', 'reservations.trip_id', '=', 'trips.id')
        ->join('reservation_orders', 'reservation_orders.reservation_id', '=', 'reservations.id')
        ->join('orders', 'orders.id', '=', 'reservation_orders.order_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->join('buses', 'buses.id', '=', 'trips.bus_id')
        ->where('orders.user_id', $userId)
        ->where('trips.status', 'done')
        ->select('trips.*', 'destinations.*', 'buses.*')
        ->distinct()
        ->get();

    if ($trips->isEmpty()) {
        return response()->json(['message' => 'No trips found'], 404);
    }
    return ResponseHelper::success($trips);
    }

    public function getTripsByDriver($driverId) {

        $trips = Trip::where('driver_id', $driverId)
        ->where('status', 'pending')
        ->with('destination','bus','driver')
        ->get();
        if ($trips->isEmpty()) {
        return response()->json(['message' => 'No trip found'], 404);
        }

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }
    public function deleteTrip($id)
{
    $trip = Trip::find($id);

    // Check if the trip exists and has no reservations with confirmed status
    if ($trip && !$trip->reservations()->where('status', 'confirmed')->exists()) {
        $reservationIds = $trip->reservations->pluck('id');

        // Retrieve the order IDs associated with the reservations
        $orderIds = ReservationOrder::whereIn('reservation_id', $reservationIds)->pluck('order_id');

        // Delete reservation orders associated with the reservations
        ReservationOrder::whereIn('reservation_id', $reservationIds)->delete();

        // Delete the reservations
        Reservation::whereIn('id', $reservationIds)->delete();

        // Delete the orders associated with the reservations
        Order::whereIn('id', $orderIds)->delete();

        // Delete the trip
        $trip->delete();

        // Return a response indicating success
        return response()->json(['message' => 'Trip, associated reservations, and orders deleted successfully'], 200);
    }

    return response()->json(['message' => 'Invalid trip ID or trip has confirmed reservations'], 422);
  }

}
