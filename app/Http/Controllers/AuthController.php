<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\MasUser;
use App\Services\ActivityLogger;

class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            // 'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        // $user = MasUser::where('email', $request->email)->first();
        $user = MasUser::with('department:id,name')
            ->where('nik', $request->nik)
            ->first();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong nik or password'
            ], 401);
        }

        // Check if user is deleted
        if ($user->deleted_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deleted'
            ], 403);
        }

        // Check if user is deactivated
        if ($user->status === "0") {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated'
            ], 403);
        }

        // Attempt authentication
        // $credentials = request(['email', 'password']);
        $credentials = request(['nik', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong nik or password'
            ], 401);
        }

        $tokenResult = $user->createToken('PersonalApiToken');
        $token = $tokenResult->plainTextToken;

        ActivityLogger::log(
            'login',
            'user-login',
            'User login id: ' . $user->id
        );

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ], 200);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        ActivityLogger::log(
            'logout',
            'user-logout',
            'User logout id: ' . $request->user()->id
        );

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }


    /**
     * Change user password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|same:new_password_confirmation',
            'new_password_confirmation' => 'required|string'
        ]);

        $user = $request->user();

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Log the password change
        ActivityLogger::log(
            'change_password',
            'user-password-change',
            'User changed password id: ' . $user->id
        );

        // Revoke all tokens
        $user->tokens()->delete();

        // Create new token
        $tokenResult = $user->createToken('PersonalApiToken');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Password successfully changed',
            'token' => $token
        ]);
    }
}
