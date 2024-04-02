<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

}
