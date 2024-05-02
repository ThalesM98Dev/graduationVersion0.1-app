<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\ImageUploadHelper;

class BusController extends Controller
{
    public function all_buses(){
     $allBus = Bus::all();
        return response()->json($allBus);
    }

    public function add_bus(Request $request){

        $validator = Validator::make($request->all(), [
            'bus_number' => 'required|integer',
            'type' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'number_of_seats' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bus = new Bus();
        $bus->bus_number = $request->bus_number;
        $bus->type = $request->type;
        $bus->image = ImageUploadHelper::upload($request->file('image'));
        $bus->number_of_seats = $request->number_of_seats;
        $bus->seats = array_fill(1, $bus->number_of_seats, true);
        $bus->save();
        $response = [
            'bus' => $bus
        ];
        return ResponseHelper::success($response);
    }

    public function deleteBus(Request $request, $id){
     $bus = Bus::find($id);

    if (!$bus) {
        return response()->json(['message' => 'The bus not found'], Response::HTTP_NOT_FOUND);
    }

    $bus->delete();
    return response()->json(['message' => 'Bus deleted successfully']);

    }
}
