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

    if (in_array($seatNumber, $trip->bus->seats) && !$this->isSeatTaken($trip, $seatNumber)){
       $reserv = new Reservation();
        $reserv->seat_number = $seatNumber;
           $file_name = rand() . time() . '.' . $request->image_of_ID->getClientOriginalExtension();
          $request->image_of_ID->move('uploads/id', $file_name);
        $reserv->image_of_ID = '/' . 'uploads/id' . '/' . $file_name;
          $file_name = rand() . time() . '.' . $request->image_of_passport->getClientOriginalExtension();
          $request->image_of_passport->move('uploads/passport', $file_name);
        $reserv->image_of_passport = '/' . 'uploads/passport' . '/' . $file_name;
        $file_name = rand() . time() . '.' . $request->image_of_security_clearance->getClientOriginalExtension();
          $request->image_of_security_clearance->move('uploads/secur', $file_name);
        $reserv->image_of_security_clearance = '/' . 'uploads/secur' . '/' . $file_name;
        $file_name = rand() . time() . '.' . $request->image_of_visa->getClientOriginalExtension();
          $request->image_of_visa->move('uploads/visa', $file_name);
        $reserv->image_of_visa = '/' . 'uploads/visa' . '/' . $file_name;
        $reserv->order_id = $request->order_id;
        $reserv->trip_id = $tripId;
     $this->updateSeatAvailability($trip->bus, $seatNumber, false);
        $reserv->save();
       // Return a response indicating success
        return response()->json($reserv, Response::HTTP_OK); 
    }else{
        return response()->json(['message' => 'Seat not available'], 422);
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

    public function rejectTripRequest(Request $request, $id)
    {
         $reservation = Reservation::find($id);
        
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }
        
        $reservation->delete();
        
        return response()->json(['message' => 'Reservation deleted'], 200);
    }


public function delete_reservation(Request $request , $id)
{
    $reservation = Reservation::find($id);

    if ($reservation) {
        $seatNumber = $reservation->seat_number;
        $tripId = $reservation->trip_id;
        $trip = Trip::find($tripId);

        if ($trip) {
            $this->updateSeatAvailability($trip->bus, $seatNumber, true);
        }

        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully'], Response::HTTP_OK);
    } else {
        return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
    }
  }
}