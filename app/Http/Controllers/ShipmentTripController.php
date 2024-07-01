<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Truck;
use App\Models\ShipmentTrip;
use App\Models\ShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ShipmentTripController extends Controller
{
    
    public function allShipmentTrips()
{
    // Generate a unique cache key
    $cacheKey = 'shipment_trips';

    // Retrieve cached results if available
    $allTrips = Cache::remember($cacheKey, 2, function () {
        return ShipmentTrip::where('status', 'pending')
            ->with('destination', 'truck')
            ->get();
    });

    $response = [
        'allTrips' => $allTrips
    ];

    return ResponseHelper::success($response);
}
    public function allPublicShipmentTrips()
{
    // Generate a unique cache key
    $cacheKey = 'public_shipment_trips';

    // Retrieve cached results if available
    $allTrips = Cache::remember($cacheKey, 2, function () {
        return ShipmentTrip::where('status', 'pending')
            ->where('type', 'Public')
            ->with('destination', 'truck')
            ->get();
    });

    $response = [
        'allTrips' => $allTrips
    ];

    return ResponseHelper::success($response);
}

    public function showArchive()
{
    // Generate a unique cache key
    $cacheKey = 'archive_shipment_trips';

    // Retrieve cached results if available
    $allTrips = Cache::remember($cacheKey, 2, function () {
        return ShipmentTrip::where('status', 'done')
            ->with('destination', 'truck')
            ->get();
    });

    $response = [
        'allTrips' => $allTrips
    ];

    return ResponseHelper::success($response);
}
    public function allTruck(){

     $allTruck = Truck::all();
        $response = [
            'allTruck' => $allTruck
        ];
        return ResponseHelper::success($response);
    }  


     public function add_truck(Request $request){

        $validator = Validator::make($request->all(), [
            'truck_number' => 'required|integer|digits:6|unique:trucks',
            'carrying_capacity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $truck = new Truck();
        $truck->truck_number = $request->truck_number;
        $truck->carrying_capacity = $request->carrying_capacity;
        $truck->save();
        $response = [
            'truck' => $truck
        ];
        return ResponseHelper::success($response);
    }

    public function delete_truck(Request $request, $id){
     $truck = Truck::find($id);

    if (!$truck) {
        return response()->json(['message' => 'The Truck not found'], Response::HTTP_NOT_FOUND);
    }

    $truck->delete();
    return response()->json(['message' => 'Truck deleted successfully']);

    }

    public function add_shipment_trip(Request $request){

        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|integer|unique:shipment_trips',
            'killoPrice' => 'required|integer',
            'destination_id' => 'required|exists:destinations,id',
            'truck_id' => 'required|exists:trucks,id',
            'date' => 'required|date',
            'type' => 'required|string|in:Public,Private',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

         $existingTrip = ShipmentTrip::where('truck_id', $request->truck_id)
        ->where('date', $request->date)
        ->first();

       if ($existingTrip) {
            return response()->json(['errors' => 'The truck is already assigned to another trip on the same date.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $shipmentTrip = new ShipmentTrip();
        $shipmentTrip->trip_number = $request->trip_number;
        $shipmentTrip->destination_id = $request->destination_id;
        $shipmentTrip->truck_id = $request->truck_id;
        $shipmentTrip->killoPrice = $request->killoPrice;
        $shipmentTrip->date = $request->date;
        $shipmentTrip->type = $request->type;
        $truck = Truck::find($request->truck_id);
        $shipmentTrip->available_weight = $truck->carrying_capacity;
        $shipmentTrip->save();
        $response = [
            'shipmentTrip' => $shipmentTrip
        ];
        return ResponseHelper::success($response);
        
    }

    public function endShipmentTrip(Request $request, $id)
{
    $shipmentTrip = ShipmentTrip::find($id);

    // Check if the trip exists and is not already confirmed
    if ($shipmentTrip && $shipmentTrip->status != 'done') {

        // Check if all shipment requests in the trip are accepted
        $allRequestsAccepted = $shipmentTrip->shipmentRequests()->where('status', 'accepted')->count() === $shipmentTrip->shipmentRequests()->count();

        if ($allRequestsAccepted) {
            $shipmentTrip->status = 'done';
            $shipmentTrip->save();
            
            // Return a response indicating success
            return response()->json(['message' => 'Shipment Trip is done'], 200);
        } else {
            return response()->json(['message' => 'Not all shipment requests are accepted'], 422);
        }
    }

    return response()->json(['message' => 'Invalid trip ID or trip already confirmed'], 422);
}

    public function ShowShipmentTripDetails($id)
   {
    $shipmentTrip = ShipmentTrip::with(['destination','truck','shipmentRequests' => function ($query) {
            $query->where('status', 'accept')
            ->with('user');
        }])
        ->where('id', $id)
        ->first();
    if (!$shipmentTrip) {
        $response = [
            'success' => false,
            'message' => 'Shipment Request not found',
            'data' => [],
            'status' => 404,
        ];

        return response()->json($response, $response['status']);
    }

    $response = [
        'shipmentTrip' => $shipmentTrip,
    ];

    return ResponseHelper::success($response);
   }
   public function filterByType(Request $request)
    {
        $type = $request->input('type');

        $shipmentTrips = ShipmentTrip::where('type', $type)
        ->where('status', 'pending')
        ->get();

        $response = [
            'shipmentTrips' => $shipmentTrips,
        ];
    
        return ResponseHelper::success($response);
    }


}

