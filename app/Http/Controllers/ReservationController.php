<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Trip;
use App\Models\Reservation;
use App\Models\ReservationOrder;
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
        'orders.*.user_id' => 'required|exists:users,id',
        'orders.*.image_of_ID' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'orders.*.image_of_passport' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'orders.*.image_of_security_clearance' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'orders.*.image_of_visa' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'seat_numbers' => 'required|array',
        'seat_numbers.*' => 'required|integer',
        'trip_id' => 'required|exists:trips,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Start the transaction
   // DB::beginTransaction();

    //try {
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
            $reservation = new Reservation([
                'trip_id' => $tripId,
                'total_price' => 0,
                'count_of_persons' => 0,
            ]);

            $reservation->save();
            $totalPrice = 0;
            $countOfPersons = 0;

            foreach ($seatNumbers as $index => $seatNumber) {
                $orderData = $request->input('orders')[$index];
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
            $reservationOrder = new ReservationOrder([
                    'reservation_id' => $reservation->id,
                    'order_id' => $order->id,
                    'seat_number' => intval($seatNumber),
                ]);

                $reservationOrder->save();
                $this->updateSeatAvailability($trip->bus, $seatNumber, false);
                $reservations[] = $reservationOrder;
                $totalPrice += $trip->price;
                $countOfPersons++;

            }      
            $reservation->total_price = $totalPrice;
            $reservation->count_of_persons = $countOfPersons + $reservation->count_of_persons;
            $reservation->save();   
        }

        $trip->available_seats -= count($seatNumbers);
        $trip->save();

        // Commit the transaction
       // DB::commit();

        if (count($reservations) > 0) {
            $response = [
                'reservations' => $reservations,
                'orders' => $orders,
            ];
            return ResponseHelper::success($response);
        }
   // } catch (\Exception $e) {
        // Rollback the transaction in case of an exception
    //    DB::rollBack();
      //  throw $e;
   // }
}
private function isSeatTaken($trip, $seatNumber)
{
    return ReservationOrder::join('reservations', 'reservation_orders.reservation_id', '=', 'reservations.id')
        ->where('reservations.trip_id', $trip->id)
        ->where('reservation_orders.seat_number', $seatNumber)
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
            $tripId = $reservation->trip_id;
            $trip = Trip::find($tripId);

            if ($trip) {
                $orderSeatNumbers = $reservation->orders->pluck('seat_number');

                foreach ($orderSeatNumbers as $seatNumber) {
                    $this->updateSeatAvailability($trip->bus, $seatNumber, true);
                }

                $trip->available_seats += $reservation->orders->count();
                $trip->save();
            }

            $reservation->orders()->delete();
            $reservation->delete();

            DB::commit();

            return response()->json(['message' => 'Reservation and associated orders deleted successfully'], Response::HTTP_OK);
        } else {
            DB::rollBack();

            return response()->json(['message' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }
    } catch (\Exception $e) {
        DB::rollBack();

        // Handle the exception or log the error

        return response()->json(['message' => 'Failed to delete reservation and associated orders'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    $reservations = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'trips.id as trip_id',
            'destinations.name as destination_name'
        )
        ->where('reservations.status', 'pending')
        ->get();

    // Retrieve the user information for the first order of each reservation
    foreach ($reservations as $reservation) {
        $firstOrder = ReservationOrder::where('reservation_id', $reservation->reservation_id)
            ->orderBy('id')
            ->first();

        if ($firstOrder) {
            $user = Order::find($firstOrder->order_id)->user;
            
            if ($user) {
                $reservation->user = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                ];
            }
        }
    }

    return ResponseHelper::success($reservations);
}

    public function showReservationDetails($id)
{
    $reservations = Reservation::join('reservation_orders', 'reservations.id', '=', 'reservation_orders.reservation_id')
        ->join('orders', 'orders.id', '=', 'reservation_orders.order_id')
        ->join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'reservations.count_of_persons',
            'trips.id as trip_id',
            'destinations.name as destination_name',
            'orders.id as order_id',
            'orders.name as order_name',
            'orders.address as order_address',
            'orders.mobile_number as order_mobile_number',
            'orders.nationality as order_nationality',
            'orders.age as order_age',
            'orders.image_of_ID as order_image_of_ID',
            'orders.image_of_passport as order_image_of_passport',
            'orders.image_of_security_clearance as order_image_of_security_clearance',
            'orders.image_of_visa as order_image_of_visa',
            'reservation_orders.seat_number'
        )
        ->where('reservations.id',$id)
        ->get();

    $response = [];

    foreach ($reservations as $reservation) {
        $reservationData = [
            'reservation_id' => $reservation->reservation_id,
            'total_price' => $reservation->total_price,
            'count_of_persons' => $reservation->count_of_persons,
            'trip' => [
                'trip_id' => $reservation->trip_id,
                'destination_name' => $reservation->destination_name,
            ],
            'orders' => [],
        ];

        if (!isset($response[$reservation->reservation_id])) {
            $response[$reservation->reservation_id] = $reservationData;
        }

        $response[$reservation->reservation_id]['orders'][] = [
            'order_id' => $reservation->order_id,
            'order_name' => $reservation->order_name,
            'order_address' => $reservation->order_address,
            'order_mobile_number' => $reservation->order_mobile_number,
            'order_nationality' => $reservation->order_nationality,
            'order_age' => $reservation->order_age,
            'order_image_of_ID' => $reservation->order_image_of_ID,
            'order_image_of_passport' => $reservation->order_image_of_passport,
            'order_image_of_security_clearance' => $reservation->order_image_of_security_clearance,
            'order_image_of_visa' => $reservation->order_image_of_visa,
            'seat_number' => $reservation->seat_number,
        ];
    }

    return ResponseHelper::success(array_values($response));
}

    public function allAcceptedReservations(){
        $reservations = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'trips.id as trip_id',
            'destinations.name as destination_name'
        )
        ->where('reservations.status', 'accept')
        ->get();

    // Retrieve the user information for the first order of each reservation
    foreach ($reservations as $reservation) {
        $firstOrder = ReservationOrder::where('reservation_id', $reservation->reservation_id)
            ->orderBy('id')
            ->first();

        if ($firstOrder) {
            $user = Order::find($firstOrder->order_id)->user;
            
            if ($user) {
                $reservation->user = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                ];
            }
        }
    }

    return ResponseHelper::success($reservations);

    }
    public function allConfirmedReservations()
{
    $reservations = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'trips.id as trip_id',
            'destinations.name as destination_name'
        )
        ->where('reservations.status', 'confirmed')
        ->get();

    // Retrieve the user information for the first order of each reservation
    foreach ($reservations as $reservation) {
        $firstOrder = ReservationOrder::where('reservation_id', $reservation->reservation_id)
            ->orderBy('id')
            ->first();

        if ($firstOrder) {
            $user = Order::find($firstOrder->order_id)->user;
            
            if ($user) {
                $reservation->user = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                    'age' => $user->age,
                    'address' => $user->address,
                    'nationality' => $user->nationality,
                ];
            }
        }
    }

    return ResponseHelper::success($reservations);
}
    public function searchInAllReservation(Request $request)
   {
    $userName = $request->input('userName');
    $reservations = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'trips.id as trip_id',
            'destinations.name as destination_name'
        )
        ->where('reservations.status', 'pending')
        ->whereHas('orders.user', function ($query) use ($userName) {
            $query->where('name', 'LIKE', "%{$userName}%");
        })
        ->get();

    // Retrieve the user information for the first order of each reservation
    foreach ($reservations as $reservation) {
        $firstOrder = ReservationOrder::where('reservation_id', $reservation->reservation_id)
            ->orderBy('id')
            ->first();

        if ($firstOrder) {
            $user = Order::find($firstOrder->order_id)->user;
            
            if ($user) {
                $reservation->user = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                    'age' => $user->age,
                    'address' => $user->address,
                    'nationality' => $user->nationality,
                ];
            }
        }
    }

    return ResponseHelper::success($reservations);
   }
   public function searchInAllAcceptReserv(Request $request)
   {
    $userName = $request->input('userName');
    $reservations = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
        ->select(
            'reservations.id as reservation_id',
            'reservations.total_price',
            'trips.id as trip_id',
            'destinations.name as destination_name'
        )
        ->where('reservations.status', 'accept')
        ->whereHas('orders.user', function ($query) use ($userName) {
            $query->where('name', 'LIKE', "%{$userName}%");
        })
        ->get();

    // Retrieve the user information for the first order of each reservation
    foreach ($reservations as $reservation) {
        $firstOrder = ReservationOrder::where('reservation_id', $reservation->reservation_id)
            ->orderBy('id')
            ->first();

        if ($firstOrder) {
            $user = Order::find($firstOrder->order_id)->user;
            
            if ($user) {
                $reservation->user = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                    'age' => $user->age,
                    'address' => $user->address,
                    'nationality' => $user->nationality,
                ];
            }
        }
    }

    return ResponseHelper::success($reservations);
   }


  public function addPersonFromDash(Request $request, $id)
  {

     // Define the validation rules
    $rules = [
        'name' => 'required|string',
        'mobile_number' => 'required|string',
        'age' => 'required|integer',
        'address' => 'required|string',
        'nationality' => 'required|string',
        'seat_number' => 'required|integer',
        'user_id' => 'required|exists:users,id',
    ];

    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        // Return a response with validation errors
        return response()->json(['errors' => $validator->errors()], 422);
    }
    
    //try {
        // Start the transaction
        //DB::beginTransaction();
        
        $trip = Trip::find($id);

         if (!$trip) {
            $message = 'Trip not found';
             return response()->json(['message' => $message], 404);
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
                'user_id' => $request->input('user_id'),
            ]);
            $order->save();
            $reservation = new Reservation([
                'trip_id' => $trip->id,
                'total_price' => $trip->price,
                'count_of_persons' => 1,
            ]);
            $reservation->save();

            $reservationOrder = new ReservationOrder([
                    'reservation_id' => $reservation->id,
                    'order_id' => $order->id,
                    'seat_number' => intval($seatNumber),
                ]);
            $reservationOrder->save();
            

            $this->updateSeatAvailability($trip->bus, $seatNumber, false);
        }

        $trip->available_seats--;
        $trip->save();

        // Commit the transaction
       // DB::commit();

        // Return a response indicating success
        $response = [
            'reservation' => $reservation,
            'order' => $order,
        ];
        return ResponseHelper::success($response);
   // } catch (\Exception $e) {
        // Something went wrong, rollback the transaction
    //    DB::rollback();
     //   return response()->json(['message' => 'An error occurred while processing the request'], 500);
    }
  //}

 public function updateReservationFromDash(Request $request, $orderId)
{
    try {
        // Start the transaction
        DB::beginTransaction();

        $order = Order::find($orderId);
        if (!$order) {
            $message = 'Order not found';
            return response()->json(['message' => $message], 404);
        }

        $reservationOrder = ReservationOrder::where('order_id', $order->id)->first();
        if (!$reservationOrder) {
            $message = 'Reservation order not found';
            return response()->json(['message' => $message], 404);
        }

        $reservation = $reservationOrder->reservation;
        $trip = $reservation->trip;

        if ($request->has('seat_number')) {
            $reservationOrder->seat_number = (int)$request->input('seat_number');
        }

        if (
            !in_array($reservationOrder->seat_number, $trip->bus->seats) ||
            $this->isSeatTaken($trip, $reservationOrder->seat_number) ||
            $trip->status !== 'pending'
        ) {
            $message = 'Seat is not available';
            return response()->json(['message' => $message], 422);
        }

        if ($request->has('name')) {
            $order->name = $request->input('name');
        }

        if ($request->has('mobile_number')) {
            $order->mobile_number = $request->input('mobile_number');
        }

        if ($request->has('age')) {
            $order->age = $request->input('age');
        }

        if ($request->has('address')) {
            $order->address = $request->input('address');
        }

        if ($request->has('nationality')) {
            $order->nationality = $request->input('nationality');
        }

        // Save the updated order and reservation order
        $order->save();
        $reservationOrder->save();

        // Commit the transaction
        DB::commit();

        // Return a response indicating success
        return response()->json(['message' => 'Reservation updated successfully']);
    } catch (\Exception $e) {
        // Something went wrong, rollback the transaction
        DB::rollBack();
        return response()->json(['message' => 'An error occurred while processing the request'], 500);
    }
}

}
