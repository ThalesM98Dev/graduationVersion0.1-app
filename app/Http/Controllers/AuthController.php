<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Nette\Utils\Random;


class AuthController extends Controller
{
    /**
     * Register new user.
     */
    public function register(StoreUserRequest $request)
    {
        return DB::transaction(function () use ($request) {
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
            $code = Random::generate(4, '0-9');
            $user->verification_code = $code;
            $user->save();
            $user->sendVerficationEmail($code);
            $response = [
                'user' => $user,
                'token' => $token
            ];
            return ResponseHelper::success($response);
        });
    }

    /**
     * Verify eMail
     */
    public function verifyEmail(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user->isVerified) {
            return ResponseHelper::error('Your account is already verified.');
        }
        if ($request->verification_code == $user->verification_code) {
            $user->isVerified = true;
            $user->verification_code = null;
            $user->save();
            return ResponseHelper::success('Your account is verified');
        }
        return ResponseHelper::error('Wrong verification code');
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'mobile_number' => 'required',
            'password' => 'required|string'
        ]);
        $user = User::where('mobile_number', $fields['mobile_number'])->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return ResponseHelper::error('Invalid credentials');
        }
        if ($user->isVerified) {
            $token = $user->createToken('myapptoken')->plainTextToken;
            $response = [
                'user' => $user,
                'token' => $token
            ];
            return ResponseHelper::success($response);
        }
        return ResponseHelper::error('Your account is not verified');
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return ResponseHelper::error('User not found');
        }
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return ResponseHelper::success('logged out');
    }

    /**
     * Refresh Token.
     */
    public function refresh()
    {
        $user = auth('sanctum')->user();
        $token = $user->createToken(
            'access_token',
            ['*'],
            now()->addMinutes(config('sanctum.ac_expiration'))
        );
        $response = [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
        return ResponseHelper::success($response);
    }

    public function getDrivers()
    {
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

    public function deleteDriver(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user || $user->role !== 'Driver') {
            return response()->json(['message' => 'The User must be a driver or not found'], Response::HTTP_NOT_FOUND);
        }

        $user->delete();
        return response()->json(['message' => 'Driver deleted successfully']);
    }
}
