<?php

namespace App\Http\Controllers;

use App\Enum\NotificationsEnum;
use App\Jobs\SendNotificationJob;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Foodstuff;
use App\Models\User;
use App\Models\ShipmentTrip;
use App\Models\ShipmentFoodstuff;
use App\Models\ShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\ImageUploadHelper;
use Illuminate\Support\Facades\Cache;

class ShipmentRequestController extends Controller
{

    public function ShowShipmentRequestDetails($id)
    {
        $shipmentRequest = ShipmentRequest::with(['user', 'shipmentTrip', 'shipmentFoodstuffs.foodstuff'])
            ->where('id', $id)
            ->first();

        if (!$shipmentRequest) {
            $response = [
                'success' => false,
                'message' => 'Shipment Request not found',
                'data' => [],
                'status' => 404,
            ];

            return response()->json($response, $response['status']);
        }

        $foodstuffs = $shipmentRequest->shipmentFoodstuffs->map(function ($shipmentFoodstuff) {
            return $shipmentFoodstuff->foodstuff->stuff;
        });

        $formattedRequest = [
            'id' => $shipmentRequest->id,
            // Add other shipment request properties if needed
            'foodstuffs' => $foodstuffs,
        ];

        $response = [
            'shipmentRequest' => $shipmentRequest,
        ];

        return ResponseHelper::success($response);
    }

    public function add_foodstuff(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'stuff' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $foodstuff = new Foodstuff();
        $foodstuff->stuff = $request->stuff;
        $foodstuff->save();
        $response = [
            'foodstuff' => $foodstuff
        ];
        return ResponseHelper::success($response);
    }

    public function allFoodstuffs()
    {
        $allFood = Foodstuff::all();

        $response = [
            'allFood' => $allFood
        ];
        return ResponseHelper::success($response);
    }


    public function addShipmentRequestFromUser(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'weight' => 'required|integer|min:1',
            'user_id' => 'required|exists:users,id',
            'shipment_trip_id' => 'required|exists:shipment_trips,id',
            'image_of_ID' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'image_of_customs_declaration' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'image_of_commercial_register' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'image_of_industrial_register' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'image_of_pledge' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'foodstuffs' => 'required|array',
            'foodstuffs.*.foodstuff_id' => 'required|exists:foodstuffs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $weight = $request->weight;
        $shipmentTrip = ShipmentTrip::find($request->shipment_trip_id);

        if ($weight > $shipmentTrip->available_weight) {
            return response()->json(['errors' => 'Weight exceeds available weight.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $kiloPrice = $shipmentTrip->killoPrice;
        $price = $weight * $kiloPrice;

        $shipmentRequest = new ShipmentRequest();
        $shipmentRequest->weight = $weight;
        $shipmentRequest->user_id = $request->user_id;
        $shipmentRequest->shipment_trip_id = $request->shipment_trip_id;
        $shipmentRequest->image_of_ID = ImageUploadHelper::upload($request->image_of_ID);
        $shipmentRequest->image_of_customs_declaration = ImageUploadHelper::upload($request->image_of_customs_declaration);
        $shipmentRequest->image_of_commercial_register = ImageUploadHelper::upload($request->image_of_commercial_register);
        $shipmentRequest->image_of_industrial_register = ImageUploadHelper::upload($request->image_of_industrial_register);
        $shipmentRequest->image_of_pledge = ImageUploadHelper::upload($request->image_of_pledge);
        $shipmentRequest->price = $price;
        $shipmentRequest->save();

        $foodstuffs = $request->input('foodstuffs');
        foreach ($foodstuffs as $foodstuff) {
            $shipmentFoodstuff = new ShipmentFoodstuff();
            $shipmentFoodstuff->foodstuff_id = $foodstuff['foodstuff_id'];
            $shipmentFoodstuff->shipment_request_id = $shipmentRequest->id;
            $shipmentFoodstuff->save();
        }

        $shipmentTrip = ShipmentTrip::findOrFail($request->shipment_trip_id);
        $shipmentTrip->available_weight -= $request->weight;
        $shipmentTrip->save();

        $user = $shipmentRequest->user;
        $fcmToken = $user->fcm_token;
//        app(NotificationService::class)
//            ->sendNotification($fcmToken, NotificationsEnum::TITLE->value, NotificationsEnum::SHIPMENT_ORDER->value);
        dispatch(new SendNotificationJob($fcmToken, $user, NotificationsEnum::SHIPMENT_ORDER->value, false));
        $response = [
            'shipmentRequest' => $shipmentRequest
        ];
        return ResponseHelper::success($response);
    }

    public function addShipmentRequestFromDash(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'weight' => 'required|integer|min:1',
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'address' => 'required|string',
            'nationality' => 'required|string',
            'age' => 'required|numeric',
            'id_number' => 'required|numeric',
            'mobile_number' => 'required|numeric|digits:10',
            'shipment_trip_id' => 'required|exists:shipment_trips,id',
             'foodstuffs' => 'nullable|array',
            'foodstuffs.*.foodstuff_id' => 'nullable|exists:foodstuffs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $weight = $request->weight;
        $shipmentTrip = ShipmentTrip::find($request->shipment_trip_id);

        if ($weight > $shipmentTrip->available_weight) {
            return response()->json(['errors' => 'Weight exceeds available weight.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $kiloPrice = $shipmentTrip->killoPrice;
        $price = $weight * $kiloPrice;

        $shipmentRequest = new ShipmentRequest();
        $shipmentRequest->weight = $weight;
        $shipmentRequest->user_id = $request->user_id;
        $shipmentRequest->shipment_trip_id = $request->shipment_trip_id;
        $shipmentRequest->name = $request->name;
        $shipmentRequest->address = $request->address;
        $shipmentRequest->nationality = $request->nationality;
        $shipmentRequest->mobile_number = $request->mobile_number;
        $shipmentRequest->id_number = $request->id_number;
        $shipmentRequest->age = $request->age;
        $shipmentRequest->price = $price;
        $shipmentRequest->status = 'accept';
        $shipmentRequest->save();
        

        $foodstuffs = $request->input('foodstuffs');
        foreach ($foodstuffs as $foodstuff) {
            $shipmentFoodstuff = new ShipmentFoodstuff();
            $shipmentFoodstuff->foodstuff_id = $foodstuff['foodstuff_id'];
            $shipmentFoodstuff->shipment_request_id = $shipmentRequest->id;
            $shipmentFoodstuff->save();
        }

        $shipmentTrip = ShipmentTrip::findOrFail($request->shipment_trip_id);
        $shipmentTrip->available_weight -= $request->weight;
        $shipmentTrip->save();

        $response = [
            'shipmentRequest' => $shipmentRequest
        ];
        return ResponseHelper::success($response);
    }

    public function acceptShipmentRequest(Request $request, $id)
    {
        $shipmentRequest = ShipmentRequest::find($id);

        if (!$shipmentRequest) {
            return response()->json(['message' => 'Shipment Request not found'], 404);
        }
        $shipmentRequest->status = 'accept';
        $shipmentRequest->save();
        $user = $shipmentRequest->user;
        $fcmToken = $user->fcm_token;
//        app(NotificationService::class)
//            ->sendNotification($fcmToken, NotificationsEnum::TITLE->value, NotificationsEnum::SHIPMENT_ACCEPTANCE->value);
        dispatch(new SendNotificationJob($user->fcm_token, $user, NotificationsEnum::SHIPMENT_ACCEPTANCE->value, false));

        $response = [
            'shipmentRequest' => $shipmentRequest
        ];
        return ResponseHelper::success($response);
    }

    public function rejectDeleteShipmentRequest(Request $request, $id)
    {
        $shipmentRequest = ShipmentRequest::find($id);

        if ($shipmentRequest) {
            $shipmentRequest->delete();
            $shipmentTrip = ShipmentTrip::findOrFail($shipmentRequest->shipment_trip_id);
            $shipmentTrip->available_weight += $shipmentRequest->weight;
            $shipmentTrip->save();

            return response()->json(['message' => 'Shipment Request rejected successfully'], Response::HTTP_OK);
        } else {

            return response()->json(['message' => 'Shipment Request not found'], Response::HTTP_NOT_FOUND);
        }
    }

    public function AllMyShipmentRequests($id)
    {
        $user = User::find($id);

        // Generate a unique cache key based on the user ID
        $cacheKey = 'shipment_requests_' . $id;

        // Retrieve cached results if available
        $shipmentRequests = Cache::remember($cacheKey, 2, function () use ($id) {
            return ShipmentRequest::with(['user', 'shipmentTrip', 'shipmentFoodstuffs.foodstuff'])
                ->whereHas('shipmentTrip', function ($query) {
                    $query->where('status', 'pending');
                })
                ->where('user_id', $id)
                ->get();
        });

        $formattedRequests = $shipmentRequests->map(function ($request) {
            $foodstuffs = $request->shipmentFoodstuffs->map(function ($shipmentFoodstuff) {
                return $shipmentFoodstuff->foodstuff->stuff;
            });

            return [
                'id' => $request->id,
                // Add other shipment request properties if needed
                'foodstuffs' => $foodstuffs,
            ];
        });

        $response = [
            'shipmentRequests' => $shipmentRequests,
        ];

        return ResponseHelper::success($response);
    }

    public function AllMyDoneShipmentRequests($id)
    {
        $user = User::find($id);

        // Generate a unique cache key based on the user ID
        $cacheKey = 'done_shipment_requests_' . $id;

        // Retrieve cached results if available
        $shipmentRequests = Cache::remember($cacheKey, 2, function () use ($id) {
            return ShipmentRequest::with(['shipmentTrip', 'shipmentFoodstuffs.foodstuff'])
                ->whereHas('shipmentTrip', function ($query) {
                    $query->where('status', 'done');
                })
                ->where('user_id', $id)
                ->get();
        });

        $formattedRequests = $shipmentRequests->map(function ($request) {
            $foodstuffs = $request->shipmentFoodstuffs->map(function ($shipmentFoodstuff) {
                return $shipmentFoodstuff->foodstuff->stuff;
            });

            return [
                'id' => $request->id,
                // Add other shipment request properties if needed
                'foodstuffs' => $foodstuffs,
            ];
        });

        $response = [
            'shipmentRequests' => $shipmentRequests,
        ];

        return ResponseHelper::success($response);
    }

    public function getAllAcceptedShipmentRequests()
    {
        // Generate a unique cache key
        $cacheKey = 'accepted_shipment_requests';

        // Retrieve cached results if available
        $shipmentRequests = Cache::remember($cacheKey, 2, function () {
            return ShipmentRequest::with('shipmentTrip', 'shipmentFoodstuffs')
                ->where('status', 'accept')
                ->get();
        });

        $response = [
            'shipmentRequests' => $shipmentRequests
        ];

        return ResponseHelper::success($response);
    }

    public function getAllShipmentRequests()
    {
        // Generate a unique cache key
        $cacheKey = 'pending_shipment_requests';

        // Retrieve cached results if available
        $shipmentRequests = Cache::remember($cacheKey, 2, function () {
            return ShipmentRequest::with('user', 'shipmentTrip', 'shipmentFoodstuffs')
                ->where('status', 'pending')
                ->get();
        });

        $response = [
            'shipmentRequests' => $shipmentRequests
        ];

        return ResponseHelper::success($response);
    }
}
