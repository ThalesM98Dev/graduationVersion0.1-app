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
use App\Helpers\ImageUploadHelper;

class ReservationController extends Controller
{
    public function creat_reservation(Request $request)
    
    {
        $validator = Validator::make($request->all(), [
             //'ticket_type' => 'required|string',
           // 'ticket_number' => 'required|integer',
            'seat_number' => 'required|integer',
            'image_of_ID' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_of_passport' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_of_security_clearance' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_of_visa' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'order_id' => 'required|exists:orders,id',
            'trip_id' => 'required|exists:trips,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $seatNumber = $request->seat_number;
        $tripId = $request->trip_id;
        $trip = Trip::find($tripId);

    if (in_array($seatNumber, $trip->bus->seats) && !$this->isSeatTaken($trip, $seatNumber) && ($trip->status =='pending')){
    $reserv = new Reservation();
    $reserv->seat_number = $seatNumber;
    $reserv->image_of_ID = ImageUploadHelper::upload($request->file('image_of_ID'));
    $reserv->image_of_passport = ImageUploadHelper::upload($request->file('image_of_passport'));
    $reserv->image_of_security_clearance = ImageUploadHelper::upload($request->file('image_of_security_clearance'));
    $reserv->image_of_visa = ImageUploadHelper::upload($request->file('image_of_visa'));
    $reserv->order_id = $request->order_id;
    $reserv->trip_id = $tripId;
     $this->updateSeatAvailability($trip->bus, $seatNumber, false);
        $reserv->save();
      $trip = Trip::find($tripId);
     $trip->available_seats -= 1; 
     $trip->save();  
       // Return a response indicating success
        $response = [
            'reserv' => $reserv
        ];
        return ResponseHelper::success($response);
    }else{
        return response()->json(['message' => 'Seat not available OR the trip not available'], 422);
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

  public function confirmReservation(Request $request , $id)
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

}
