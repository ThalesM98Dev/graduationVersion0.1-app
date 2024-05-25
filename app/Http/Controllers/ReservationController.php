<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Trip;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\Order;
use App\Helpers\ImageUploadHelper;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
public function creatReservation(Request $request)
{
    $validator = Validator::make($request->all(), [
        'orders' => 'required|array',
        'orders.*.name' => 'required|string',
        'orders.*.address' => 'required|string',
        'orders.*.mobile_number' => 'required|numeric',
        'orders.*.age' => 'required|numeric',
        'orders.*.nationality' => 'required|string',
        // 'orders.*.image_of_ID' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'seat_numbers' => 'required|array',
        'seat_numbers.*' => 'required|integer',
        'trip_id' => 'required|exists:trips,id',
    ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

    // Start the transaction
    DB::beginTransaction();

    try {
        $tripId = $request->input('trip_id');
        $trip = Trip::find($tripId);
        $seatNumbers = $request->input('seat_numbers');
        $reservations = [];
        $orders = [];
        $unavailableSeats = [];

        // Check seat availability and trip status
        foreach ($seatNumbers as $seatNumber) {
            if (!in_array($seatNumber, $trip->bus->seats) || $this->isSeatTaken($trip, $seatNumber) || $trip->status !== 'pending') {
                $unavailableSeats[] = $seatNumber;
            }
        }

        if (!empty($unavailableSeats)) {
            $message = 'Seats ' . implode(', ', $unavailableSeats) . ' are not available or the trip is not available';
            return response()->json(['message' => $message], 422);
        } else {
            foreach ($request->input('orders') as $index => $orderData) {
                $order = new Order([
                    'name' => $orderData['name'],
                    'mobile_number' => $orderData['mobile_number'],
                    'age' => $orderData['age'],
                    'address' => $orderData['address'],
                    'nationality' => $orderData['nationality'],
                    'image_of_ID' => ImageUploadHelper::upload($request->file('orders')[$index]['image_of_ID'], "images"),
                    'image_of_passport' => ImageUploadHelper::upload($request->file('orders')[$index]['image_of_passport'], "images"),
                    'image_of_security_clearance' => ImageUploadHelper::upload($request->file('orders')[$index]['image_of_security_clearance'], "images"),
                    'image_of_visa' => ImageUploadHelper::upload($request->file('orders')[$index]['image_of_visa'], "images"),
                    'user_id' => $orderData['user_id'],
                ]);
                $order->save();
                $orders[] = $order;

                $reservation = new Reservation([
                    'seat_number' => intval($seatNumbers[$index]),
                    'trip_id' => $tripId,
                    'order_id' => $order->id,
                ]);
                $reservation->save();
                $this->updateSeatAvailability($trip->bus, $seatNumber, false);
                $reservations[] = $reservation;
            }
        }

        $trip->available_seats -= count($seatNumbers);
        $trip->save();

        // Commit the transaction
        DB::commit();

        if (count($reservations) > 0) {
            $response = [
                'reservations' => $reservations,
                'orders' => $orders,
            ];
            return ResponseHelper::success($response);
        }
    } catch (\Exception $e) {
        // Rollback the transaction in case of an exception
        DB::rollBack();
        throw $e;
    }
}
private function isSeatTaken($trip, $seatNumber)
{
    return Reservation::where('trip_id', $trip->id)
        ->where('seat_number', $seatNumber)
        ->exists();
}

    private function updateSeatAvailability($bus, $seatNumber, $isAvailable)
    {
        $seatsB = $bus->seats;
        $seatsB[$seatNumber] = $isAvailable;
        $bus->seats = $seatsB;
        $bus->save();
    }

    public function acceptTripRequest(Request $request, $id)
    {
        $reserv = Reservation::find($id);

        if (!$reserv) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $reserv->status = 'accept';
        $reserv->save();

        return response()->json($reserv, Response::HTTP_OK);
    }

    public function rejectDeleteTripRequest(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $reservation = Reservation::find($id);

        if ($reservation) {
            $seatNumber = $reservation->seat_number;
            $tripId = $reservation->trip_id;
            $trip = Trip::find($tripId);

            if ($trip) {
                $this->updateSeatAvailability($trip->bus, $seatNumber, true);
                $trip->available_seats += 1;
                $trip->save();
            }

            // Get the associated order ID
            $orderId = $reservation->order_id;

            // Verify that the order ID is valid
            if ($orderId) {
                // Delete the associated order
                $order = Order::find($orderId);
                if ($order) {
                    $order->delete();
                }
            }

            $reservation->delete();

            DB::commit();

            return response()->json(['message' => 'Reservation and associated order deleted successfully'], Response::HTTP_OK);
        } else {
            DB::rollBack();

            return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }
    } catch (\Exception $e) {
        DB::rollBack();

        // Handle the exception or log the error

        return response()->json(['message' => 'Failed to delete reservation and associated order'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    public function confirmReservation(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        // Check if the reservation exists and is not already confirmed
        if ($reservation && $reservation->status != 'confirmed') {
            $reservation->status = 'confirmed';
            $reservation->save();

            // Return a response indicating success
            return response()->json(['message' => 'Reservation confirmed'], 200);
        }

        // Return a response indicating an error if the reservation doesn't exist or is already confirmed
        return response()->json(['message' => 'Invalid reservation ID or reservation already confirmed'], 422);
      // Return a response indicating an error if the reservation doesn't exist or is already confirmed
    return response()->json(['message' => 'Invalid reservation ID or reservation already confirmed'], 422);  
    }
public function getAllReservation()
{
    $reservation = Reservation::with(['trip.destination', 'order'])
            ->where('status', 'pending')
            ->get();
        if (!$reservation) {
            return response()->json(['message' => 'No Reservations Found'], Response::HTTP_NOT_FOUND);
        }
        return ResponseHelper::success($reservation);
}
    public function showReservationDetails($id)
    {
        $reservation = Reservation::with(['trip.destination', 'order'])
            ->where('reservations.id', $id)
            ->first();
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }
         return ResponseHelper::success($reservation);
    }

    public function allAcceptedReservations(){
        $reservation = Reservation::with(['trip.destination', 'order'])
            ->where('status', 'accept')
            ->get();
        if (!$reservation) {
            return response()->json(['message' => 'No Reservations Found'], Response::HTTP_NOT_FOUND);
        }
        return ResponseHelper::success($reservation);

    }
    public function allConfirmedReservations(){
     $reservation = Reservation::with(['trip.destination', 'order'])
            ->where('status', 'confirmed')
            ->get();
    if (!$reservation) {
            return response()->json(['message' => 'No Reservations Found'], Response::HTTP_NOT_FOUND);
      }
     return ResponseHelper::success($reservation);

    }
    public function searchInAllReservation(Request $request)
   {
    $userName = $request->input('userName');
    $reservations = Reservation::with(['trip.destination', 'order'])
        ->where('status', 'pending')
        ->whereHas('order', function ($query) use ($userName) {
            $query->where('name', 'LIKE', "%$userName%");
        })
        ->get();
    if ($reservations->isEmpty()) {
        return response()->json(['message' => 'No reservations found'], 404);
    }

    return ResponseHelper::success($reservations);
   }
   public function searchInAllAcceptReserv(Request $request)
   {
    $userName = $request->input('userName');
    $reservations = Reservation::with(['trip.destination', 'order'])
        ->where('status', 'accept')
        ->whereHas('order', function ($query) use ($userName) {
            $query->where('name', 'LIKE', "%$userName%");
        })
        ->get();
    if ($reservations->isEmpty()) {
        return response()->json(['message' => 'No reservations found'], 404);
    }

    return ResponseHelper::success($reservations);
   }


  public function addPersonFromDash(Request $request, Trip $trip)
  {

     // Define the validation rules
    $rules = [
        'name' => 'required|string',
        'mobile_number' => 'required|string',
        'age' => 'required|integer',
        'address' => 'required|string',
        'nationality' => 'required|string',
        'seat_number' => 'required|integer',
    ];

    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        // Return a response with validation errors
        return response()->json(['errors' => $validator->errors()], 422);
    }
    
    try {
        // Start the transaction
        DB::beginTransaction();

        // Access the trip ID using the $trip parameter
        $tripId = $trip->id;
        $trip = Trip::find($tripId);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $seatNumber = $request->input('seat_number');

        if (!in_array($seatNumber, $trip->bus->seats) || $this->isSeatTaken($trip, $seatNumber) || $trip->status !== 'pending') {
            $message = 'Seat is not available or the trip is not available';
            return response()->json(['message' => $message], 422);
        } else {
            // Create the order
            $order = new Order([
                'name' => $request->input('name'),
                'mobile_number' => $request->input('mobile_number'),
                'age' => $request->input('age'),
                'address' => $request->input('address'),
                'nationality' => $request->input('nationality'),
            ]);
            $order->save();

            $reservation = new Reservation([
                'seat_number' => intval($seatNumber),
                'trip_id' => $tripId,
                'order_id' => $order->id,
            ]);
            $reservation->save();

            $this->updateSeatAvailability($trip->bus, $seatNumber, false);
        }

        $trip->available_seats--;
        $trip->save();

        // Commit the transaction
        DB::commit();

        // Return a response indicating success
        $response = [
            'reservation' => $reservation,
            'order' => $order,
        ];
        return ResponseHelper::success($response);
    } catch (\Exception $e) {
        // Something went wrong, rollback the transaction
        DB::rollback();
        return response()->json(['message' => 'An error occurred while processing the request'], 500);
    }
  }

}
