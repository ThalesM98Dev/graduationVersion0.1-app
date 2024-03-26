<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Destination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class DestController extends Controller
{
    public function all_destinations(){
     $allDest = Destination::all();
        return response()->json($allDest);
    }
    public function add_destination(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dest = new Destination();
        $dest->name = $request->name;
        $dest->save();
        return response()->json($dest, Response::HTTP_OK);
    }

    public function showWithTrips($id)
    {
    $destination = Destination::with('trips')->find($id);

    if (!$destination) {
        return response()->json(['error' => 'Destination not found'], 404);
    }

    return response()->json($destination);
    }
}
