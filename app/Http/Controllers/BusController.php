<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\Bus;
use App\Models\ImageOfBus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\ImageUploadHelper;

class BusController extends Controller
{

    public function all_buses()
    {
        $allBus = Bus::all();
        return response()->json($allBus);
    }

    public function add_bus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bus_number' => 'required|integer|digits:6|unique:buses',
            'type' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'number_of_seats' => 'required|integer|min:1',
            'image_of_buse_id' => 'required|exists:image_of_buses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bus = new Bus();
        $bus->bus_number = $request->bus_number;
        $bus->type = $request->type;
        $bus->image_of_buse_id = $request->image_of_buse_id;
        $bus->image = ImageUploadHelper::upload($request->file('image'));
        $bus->number_of_seats = $request->number_of_seats;
        $bus->save();
        $response = [
            'bus' => $bus
        ];
        return ResponseHelper::success($response);
    }

    public function deleteBus(Request $request, $id)
    {
        $bus = Bus::find($id);

        if (!$bus) {
            return response()->json(['message' => 'The bus not found'], Response::HTTP_NOT_FOUND);
        }

        $bus->delete();
        return response()->json(['message' => 'Bus deleted successfully']);

    }

    public function add_imageOfBus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imageBus = new ImageOfBus();
        $imageBus->image = ImageUploadHelper::upload($request->file('image'));
        $imageBus->save();
        $response = [
            'imageBus' => $imageBus
        ];
        return ResponseHelper::success($response);
    }

    public function allImageOfBus(Request $request)
    {
        $imageBus = ImageOfBus::all();
        $response = [
            'imageBus' => $imageBus
        ];
        return ResponseHelper::success($response);
    }
}
