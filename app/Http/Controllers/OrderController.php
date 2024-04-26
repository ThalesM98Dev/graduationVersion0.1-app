<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class OrderController extends Controller
{

    public function all_orders(){
     $allOrder = Order::all();
        $response = [
            'allOrder' => $allOrder
        ];
        return ResponseHelper::success($response);
         }

    
    public function add_order(Request $request)
    
    {
        $validator = Validator::make($request->all(), [
             'name' => 'required|string',
            'mobile_number' => 'required|regex:/^[0-9]{10}$/',
            'age' => 'required|integer',
            'address' => 'required|string',
            'nationality' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create a new trip instance
        $order = new Order();
        $order->name = $request->name;
        $order->mobile_number = $request->mobile_number;
        $order->age = $request->age;
        $order->address = $request->address;
        $order->nationality = $request->nationality;
        $order->user_id = $request->user_id;
        $order->save();

        // Return a response indicating success
        return response()->json($order, Response::HTTP_OK);
    }

    public function show_OrdersForUser($userId)
    {
        $orders = Order::where('user_id', $userId)->get();

        $response = [
            'orders' => $orders
        ];
        return ResponseHelper::success($response);
        
    }
}
