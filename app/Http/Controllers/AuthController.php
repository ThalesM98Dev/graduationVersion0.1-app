<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;


class AuthController extends Controller
{
    /**
     * Register new user.
     */
    public function register(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'mobile_number' => $request['mobile_number'],
            'age' => $request['age'],
            'address' => $request['address'],
            'nationality' => $request['nationality'],
            'role' => $request['role']
        ]);
        $token = $user->createToken('myapptoken')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return ResponseHelper::success($response);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'mobile_number' => 'required|integer',
            'password' => 'required|string'
        ]);
        $user = User::where('mobile_number', $fields['mobile_number'])->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return ResponseHelper::error('Invalid credentials');
        }
        $token = $user->createToken('myapptoken')->plainTextToken;
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return ResponseHelper::success($response);
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return ResponseHelper::success('logged out');
    }

    /**
     * Refresh Token.
     */
    public function refresh()
    {
        $user = Auth::user();
        $token = $user->createToken('access_token', ['*'], now()->addMinutes(config('sanctum.ac_expiration')));
        $response = [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
        return ResponseHelper::success($response);
    }

    public function getDrivers(){
     $drivers = User::where('role', 'Driver')->get();
        $response = [
            'drivers' => $drivers
        ];
        return ResponseHelper::success($response);

    }

    public function updateDriver(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->role !== 'Driver') {
        return response()->json(['message' => 'The User must be a driver'], Response::HTTP_NOT_FOUND);
        }
        
        $validatedData = $request->validate([
            'name' => 'required|string',
            'mobile_number' => 'required|string',
        'age' => 'required|integer',
        'address' => 'required|string',
        'nationality' => 'required|string',
        ]);

        // Update the user fields
        $user->name = $validatedData['name'];
        $user->mobile_number = $validatedData['mobile_number'];
        $user->age = $validatedData['age'];
        $user->address = $validatedData['address'];
        $user->nationality = $validatedData['nationality'];
        // Update other fields as needed
        
        // Save the changes
        $user->save();

        return response()->json($user);
    }

    public function deleteDriver(Request $request, $id){
     $user = User::findOrFail($id);

    if (!$user || $user->role !== 'Driver') {
        return response()->json(['message' => 'The User must be a driver or not found'], Response::HTTP_NOT_FOUND);
    }

    $user->delete();
    return response()->json(['message' => 'Driver deleted successfully']);

    }

}
