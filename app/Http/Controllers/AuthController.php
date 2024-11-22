<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MasUser;

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
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $user = MasUser::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong email or password'
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
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong email or password'
            ], 401);
        }

        $tokenResult = $user->createToken('PersonalApiToken');
        $token = $tokenResult->plainTextToken;

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
        return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
