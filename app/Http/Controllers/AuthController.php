<?php

namespace App\Http\Controllers;

use App\Enum\MessagesEnum;
use App\Enum\NotificationsEnum;
use App\Enum\RolesEnum;
use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreFcmTokenRequest;
use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\SendMessageJob;
use App\Services\NotificationService;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
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
                'password' => Hash::make($request['password']),
                'mobile_number' => $request['mobile_number'],
                'age' => $request['age'],
                'address' => $request['address'],
                'nationality' => $request['nationality'],
                'role' => $request['role']
            ]);
            $token = $user->createToken('myapptoken')->plainTextToken;
            if ($request['role'] == RolesEnum::USER->value) {
                $code = Random::generate(4, '0-9');
                $user->verification_code = $code;
                $body = MessagesEnum::VERIFICATION->formatMessage($code);
                //app(VerificationService::class)->sendVerificationMessage($user->mobile_number, $code);
                dispatch(new SendMessageJob($user->mobile_number, $body));
            } else {
                $user->isVerified = true;
            }
            // $user->isVerified = true; //Temp !!!
            $user->save();
            $response = [
                'user' => $user,
                'token' => $token
            ];
            return ResponseHelper::success($response);
        });
    }

    /**
     * Verify Account via WhatsApp Message
     */
    public function verifyAccount(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user->isVerified) {
            return ResponseHelper::error(data: null, message: 'Your account is already verified.');
        }
        if ($request->verification_code == $user->verification_code) {
            $user->isVerified = true;
            $user->verification_code = null;
            $user->save();
            return ResponseHelper::success(data: null, message: 'Verified Successfully.');
        }
        return ResponseHelper::error(data: null, message: 'Wrong verification code');
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'mobile_number' => 'required|exists:users,mobile_number',
            'password' => 'required|string',
            'fcm_token' => 'nullable'
        ]);
        $user = User::where('mobile_number', $fields['mobile_number'])->first();
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return ResponseHelper::error('Wrong Password.');
        }
        if ($user->isVerified || $user->role != RolesEnum::USER->value) {
            if (isset($fields['fcm_token'])) {
                $user->fcm_token = $fields['fcm_token'];
                $user->save();
                //test
                dispatch(new SendNotificationJob($user->fcm_token, $user, NotificationsEnum::WELCOME->value, true));
            }
            $token = $user->createToken('myapptoken')->plainTextToken;
            $response = [
                'user' => $user,
                'token' => $token
            ];
            return ResponseHelper::success($response);
        }
        return ResponseHelper::error('Your account is not verified yet');
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

    /**
     * Request Reset Password Code.
     */
    public function requestResetPasswordCode()
    {
        $user = auth('sanctum')->user();
        return DB::transaction(function () use ($user) {
            //send message to the user contains reset code
            $code = Random::generate(4, '0-9');
            $user->verification_code = $code;
            $user->save();
            $body = MessagesEnum::RECOVER_PASSWORD->formatMessage($code);
            //            app(VerificationService::class)->sendVerificationMessage($user->mobile_number, $body);
            dispatch(new SendMessageJob($user->mobile_number, $body));
            return ResponseHelper::success(data: null, message: 'Recovery Code sent to your Whatsapp account successfully.');
        });
    }

    /**
     * Reset Password.
     */
    public function resetPassword(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user->verification_code) {
            return ResponseHelper::error(data: null, message: 'Request Reset Code First.');
        }
        if ($request->verification_code == $user->verification_code) {
            $user->verification_code = null;
            $user->password = Hash::make($request->password);
            $user->save();
            return ResponseHelper::success(data: null, message: 'Your Password has been reset successfully.');
        }
        return ResponseHelper::error(data: null, message: 'Wrong verification code');
    }

    public function storeFcmToken(StoreFcmTokenRequest $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return ResponseHelper::error(data: null, message: 'User not found');
        }
        $user->fcm_token = $request->fcm_token;
        $user->save();
        return ResponseHelper::success(data: null, message: 'Token Stored Successfully.');
    }

    public function deleteUser($userID)
    {
        $user = User::findOrFail($userID)->delete();
        return ResponseHelper::success($user, 'User Delete successfully');
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);
        $user->mobile_number = $request->input('mobile_number', $user->mobile_number);
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->age = $request->input('age', $user->age);
        $user->address = $request->input('address', $user->address);
        $user->nationality = $request->input('nationality', $user->nationality);
        $user->isVerified = $request->input('isVerified', $user->isVerified);
        $user->save();
        return ResponseHelper::success($user->first(), 'User updated successfuly');
    }

    public function all_Users()
    {
        $users = User::where('role', 'User')->get();
        {
            $response = [
                'users' => $users
            ];

            return ResponseHelper::success($response);
        }
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
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'The User Not Found'], Response::HTTP_NOT_FOUND);
        }

        // Update the user fields if they are present in the request
        if ($request->has('name')) {
            $user->name = $request->input('name');
        }

        if ($request->has('mobile_number')) {
            $user->mobile_number = $request->input('mobile_number');
        }

        if ($request->has('age')) {
            $user->age = $request->input('age');
        }

        if ($request->has('address')) {
            $user->address = $request->input('address');
        }

        if ($request->has('nationality')) {
            $user->nationality = $request->input('nationality');
        }

        // Update other fields as needed

        // Save the changes
        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated successfully', 'data' => $user]);
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
