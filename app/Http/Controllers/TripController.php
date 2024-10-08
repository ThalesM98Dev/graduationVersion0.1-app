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
use ArPHP\I18N\Arabic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class TripController extends Controller
{

    public function all_trip()
    {
        $trips = Trip::where('status', 'pending')
            ->where('trip_type', 'External')
            ->with('destination', 'bus', 'driver', 'envelops.user')
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
        $depatureHour = Carbon::createFromFormat('h:i A', $request->depature_hour)->format('H:i:s');
        $arrivalHour = Carbon::createFromFormat('h:i A', $request->arrival_hour)->format('H:i:s');
        $existingTripBus = Trip::where('bus_id', $request->bus_id)
            ->where('date', $request->date)
            ->where(function ($query) use ($depatureHour, $arrivalHour) {
                $query->whereBetween('depature_hour', [$depatureHour, $arrivalHour])
                    ->orWhereBetween('arrival_hour', [$depatureHour, $arrivalHour])
                    ->orWhere(function ($query) use ($depatureHour, $arrivalHour) {
                        $query->where('depature_hour', '<=', $depatureHour)
                            ->where('arrival_hour', '>=', $arrivalHour);
                    });
            })
            ->first();

        if ($existingTripBus) {
            return response()->json(['message' => 'A trip with the same bus overlaps with the specified departure and arrival hours'], Response::HTTP_BAD_REQUEST);
        }

        $existingTripDriver = Trip::where('driver_id', $request->driver_id)
            ->where('date', $request->date)
            ->where(function ($query) use ($depatureHour, $arrivalHour) {
                $query->whereBetween('depature_hour', [$depatureHour, $arrivalHour])
                    ->orWhereBetween('arrival_hour', [$depatureHour, $arrivalHour])
                    ->orWhere(function ($query) use ($depatureHour, $arrivalHour) {
                        $query->where('depature_hour', '<=', $depatureHour)
                            ->where('arrival_hour', '>=', $arrivalHour);
                    });
            })
            ->first();

        if ($existingTripDriver) {
            return response()->json(['message' => 'A trip with the same driver overlaps with the specified departure and arrival hours'], Response::HTTP_BAD_REQUEST);
        }
        //dd($existingTrip);

        // Create a new trip instance
        $trip = new Trip();
        $trip->trip_number = $request->trip_number;
        $trip->date = $request->date;
        $trip->depature_hour = $depatureHour;
        $trip->arrival_hour = $arrivalHour;
        $trip->trip_type = $request->trip_type;
        $trip->starting_place = $request->starting_place;
        $trip->price = $request->price;
        $trip->bus_id = $request->bus_id;
        $trip->destination_id = $request->destination_id;
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
        }])->with('envelops')
            ->find($id);

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

        // Check if the trip exists and is not already confirmed
        if ($trip && $trip->status != 'done') {

            // Check if all reservations in the trip are confirmed
            $allReservationsConfirmed = $trip->reservations()
                    ->where('status', 'confirmed')->count() === $trip->reservations()->count();

            if ($allReservationsConfirmed) {
                $trip->status = 'done';
                $trip->save();

                $archive = new Archive();
                $archive->fill($trip->toArray());
                $archive->save();

                // Return a response indicating success
                $response = [
                    'message' => 'Trip is done'
                ];

                return ResponseHelper::success($response);
            } else {
                return response()->json(['message' => 'Not all reservations are confirmed'], 422);
            }
        }

        return response()->json(['message' => 'Invalid trip ID or trip already done'], 422);
    }

    public function getTripsByDestinationInArchive(Request $request)
    {
        $destinationName = $request->input('destination');

        $trips = Trip::whereHas('destination', function ($query) use ($destinationName) {
            $query->where('name', 'LIKE', "%{$destinationName}%");
        })
            ->where('status', 'done')
            ->with('destination', 'bus', 'driver', 'envelops.user')
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
        $reservations = Reservation::join('reservation_orders', 'reservations.id', '=', 'reservation_orders.reservation_id')
            ->join('orders', 'orders.id', '=', 'reservation_orders.order_id')
            ->join('trips', 'trips.id', '=', 'reservations.trip_id')
            ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
            ->select(
                'reservations.id as reservation_id',
                'reservations.total_price',
                'reservations.count_of_persons',
                'reservations.status',
                'trips.id as trip_id',
                'trips.depature_hour as depature_hour',
                'trips.arrival_hour as arrival_hour',
                'trips.starting_place as starting_place',
                'trips.id as trip_id',
                'trips.date as trip_date',
                'trips.status as trip_status',
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
            ->where('orders.user_id', $userId)
            ->where('trips.status', 'pending')
            ->get();

        $response = [];

        foreach ($reservations as $reservation) {
            $reservationData = [
                'reservation_id' => $reservation->reservation_id,
                'total_price' => $reservation->total_price,
                'count_of_persons' => $reservation->count_of_persons,
                'status' => $reservation->status,
                'trip' => [
                    'trip_id' => $reservation->trip_id,
                    'trip_date' => $reservation->trip_date,
                    'trip_status' => $reservation->trip_status,
                    'depature_hour' => $reservation->depature_hour,
                    'arrival_hour' => $reservation->arrival_hour,
                    'starting_place' => $reservation->starting_place,
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

    public function getEndingTripsByUser($userId)
    {
        $reservations = Reservation::join('reservation_orders', 'reservations.id', '=', 'reservation_orders.reservation_id')
            ->join('orders', 'orders.id', '=', 'reservation_orders.order_id')
            ->join('trips', 'trips.id', '=', 'reservations.trip_id')
            ->join('destinations', 'destinations.id', '=', 'trips.destination_id')
            ->select(
                'reservations.id as reservation_id',
                'reservations.total_price',
                'reservations.count_of_persons',
                'reservations.status',
                'trips.id as trip_id',
                'trips.depature_hour as depature_hour',
                'trips.arrival_hour as arrival_hour',
                'trips.starting_place as starting_place',
                'trips.date as trip_date',
                'trips.status as trip_status',
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
            ->where('orders.user_id', $userId)
            ->where('trips.status', 'done')
            ->get();

        $response = [];

        foreach ($reservations as $reservation) {
            $reservationData = [
                'reservation_id' => $reservation->reservation_id,
                'total_price' => $reservation->total_price,
                'count_of_persons' => $reservation->count_of_persons,
                'status' => $reservation->status,
                'trip' => [
                    'trip_id' => $reservation->trip_id,
                    'trip_date' => $reservation->trip_date,
                    'trip_status' => $reservation->trip_status,
                    'depature_hour' => $reservation->depature_hour,
                    'arrival_hour' => $reservation->arrival_hour,
                    'starting_place' => $reservation->starting_place,
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

    public function getTripsByDriver($driverId)
    {

        $trips = Trip::where('driver_id', $driverId)
            ->where('status', 'pending')
            ->with('destination', 'bus', 'driver')
            ->get();
        if ($trips->isEmpty()) {
            return response()->json(['message' => 'No trip found'], 404);
        }

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }

    public function getEndingTripsForDriver($driverId)
    {

        $trips = Trip::where('driver_id', $driverId)
            ->where('status', 'done')
            ->with('destination', 'bus', 'driver', 'envelops.user')
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

    public function downloadTripOrdersPdf($id)
    {
        $trip = Trip::with(['bus', 'destination', 'driver', 'reservations' => function ($query) {
            $query->where('status', 'confirmed');
        }])->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], Response::HTTP_NOT_FOUND);
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

        // Generate the PDF content
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans'); // Use a font that supports Arabic, such as DejaVu Sans
        $pdf = new Dompdf($pdfOptions);

        $html = view('pdf.trip_orders', compact('trip'))->render(); // Create a view for the PDF content
        $arabic = new Arabic();
        $p = $arabic->arIdentify($html);

        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $reportHtml = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');

        // Render the PDF content
        $pdf->render();

        // Generate a unique filename for the PDF
        $filename = 'trip_orders_' . $trip->id . '.pdf';

        // Store the PDF file in the storage directory
        $pdf->stream($filename, ['Attachment' => true]);

        return response()->json(['message' => 'PDF generated and downloaded successfully']);
    }
}
