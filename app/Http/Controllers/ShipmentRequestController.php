<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Foodstuff;
use App\Models\ShipmentTrip;
use App\Models\ShipmentFoodstuff;
use App\Models\ShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\ImageUploadHelper;

class ShipmentRequestController extends Controller
{

    public function add_foodstuff(Request $request){

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


    public function add_shipment_request(Request $request){

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
       $shipmentTrip = ShipmentTrip::findOrFail($request->shipment_trip_id);

          if ($weight > $shipmentTrip->available_weight) {
              return response()->json(['errors' => 'Weight exceeds available weight.'], Response::HTTP_UNPROCESSABLE_ENTITY);
           }

        $shipmentRequest = new ShipmentRequest();
        $shipmentRequest->weight = $weight;
        $shipmentRequest->user_id = $request->user_id;
        $shipmentRequest->shipment_trip_id = $request->shipment_trip_id;
        $shipmentRequest->image_of_ID = ImageUploadHelper::upload($request->image_of_ID);
        $shipmentRequest->image_of_customs_declaration = ImageUploadHelper::upload($request->image_of_customs_declaration);
        $shipmentRequest->image_of_commercial_register = ImageUploadHelper::upload($request->image_of_commercial_register);
        $shipmentRequest->image_of_industrial_register = ImageUploadHelper::upload($request->image_of_industrial_register);
        $shipmentRequest->image_of_pledge = ImageUploadHelper::upload($request->image_of_pledge);
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
}
