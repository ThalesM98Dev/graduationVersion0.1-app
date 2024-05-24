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

        $tripId = $request->input('trip_id');
        $trip = Trip::find($tripId);
        $seatNumbers = $request->input('seat_numbers');
        $reservations = [];
        $orders = [];
        $unavailableSeats = [];

        foreach ($seatNumbers as $seatNumber) {
            if (!in_array($seatNumber, $trip->bus->seats) || $this->isSeatTaken($trip, $seatNumber) || $trip->status !== 'pending') {
                $unavailableSeats[] = $seatNumber;
            }
        }

    if (!empty($unavailableSeats)) {
        $message = 'Seats ' . implode(', ', $unavailableSeats) . ' are not available or the trip is not available';
        return response()->json(['message' => $message], 422);
    }
    foreach ($request->input('orders') as $index => $orderData) {
        $order = new Order([
            'name' => $orderData['name'],
            'mobile_number' => $orderData['mobile_number'],
            'age' => $orderData['age'],
            'address' => $orderData['address'],
            'nationality' => $orderData['nationality'],
           // 'image_of_ID' => ImageUploadHelper::upload($orderData['image_of_ID']),
            'user_id' => $orderData['user_id'],
        ]);
        $order->save();
        dd();
        $orders[] = $order;

        foreach ($seatNumbers as $seatNumber) {
            $reservation = new Reservation([
                'seat_number' => intval($seatNumber),
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

        if (count($reservations) > 0) {
            $response = [
                'reservations' => $reservations,
                'orders' => $orders,
            ];
            return ResponseHelper::success($response);
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

            $reservation->delete();

            return response()->json(['message' => 'Reservation deleted successfully'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
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
    }

    public function getAllReservation()
    {
        $reservation = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
            ->join('orders', 'reservations.order_id', '=', 'orders.id')
            ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
            ->where('reservations.status', 'pending')
            ->orderBy('reservations.id')
            ->get();
        $response = [
            'reservation' => $reservation
        ];
        return ResponseHelper::success($response);
    }

    public function showReservationDetails($id)
    {
        $reservation = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
            ->join('orders', 'reservations.order_id', '=', 'orders.id')
            ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
            ->where('reservations.id', $id)
            ->first();
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }
        $response = [
            'reservation' => $reservation
        ];
        return ResponseHelper::success($response);
    }

    public function allAcceptedReservations()
    {
//     $reservation = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
//            ->join('orders', 'reservations.order_id', '=', 'orders.id')
//             ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
//            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
//        ->where('reservations.status', 'accept')
//        ->get();
//         if ($reservation->isEmpty()) {
//        return response()->json(['message' => 'No accepted reservations found'], 404);
//        }
//        $response = [
//            'reservation' => $reservation
//        ];
//        return ResponseHelper::success($response);
        $reservation = Reservation::with(['trip.destination', 'order'])
            ->where('status', 'accept')
            ->get()->toArray();
        return ResponseHelper::success($reservation);


    }

    public function allConfirmedReservations()
    {
        $reservation = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
            ->join('orders', 'reservations.order_id', '=', 'orders.id')
            ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
            ->where('reservations.status', 'confirmed')
            ->get();
        if ($reservation->isEmpty()) {
            return response()->json(['message' => 'No confirmed reservations found'], 404);
        }
        $response = [
            'reservation' => $reservation
        ];
        return ResponseHelper::success($response);

    }

    public function searchInAllReservation(Request $request)
    {
        $userName = $request->input('userName');

        $reservations = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
            ->join('orders', 'reservations.order_id', '=', 'orders.id')
            ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
            ->where('orders.name', 'LIKE', "%$userName%")
            ->where('reservations.status', 'pending')
            ->get();

        if ($reservations->isEmpty()) {
            return response()->json(['message' => 'No reservations found'], 404);
        }

        $response = [
            'reservations' => $reservations
        ];

        return response()->json($response, 200);
    }

    public function searchInAllAcceptReserv(Request $request)
    {
        $userName = $request->input('userName');

        $reservations = Reservation::join('trips', 'reservations.trip_id', '=', 'trips.id')
            ->join('orders', 'reservations.order_id', '=', 'orders.id')
            ->join('destinations', 'trips.destination_id', '=', 'destinations.id')
            ->select('reservations.*', 'trips.*', 'orders.*', 'destinations.name as destination_name', 'trips.destination_id as destination_id')
            ->where('orders.name', 'LIKE', "%$userName%")
            ->where('reservations.status', 'accept')
            ->get();

        if ($reservations->isEmpty()) {
            return response()->json(['message' => 'No reservations found'], 404);
        }

        $response = [
            'reservations' => $reservations
        ];

        return response()->json($response, 200);
    }

}
