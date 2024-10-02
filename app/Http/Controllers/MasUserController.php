<?php

namespace App\Http\Controllers;

use App\Models\MasUser;
use Illuminate\Http\Request;
use Exception;

class MasUserController extends Controller
{
    // Fetch all user records with optional searching
    public function index(Request $request)
    {
        try {
            $query = MasUser::query();

            // Check for search parameters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('FULLNAME', 'LIKE', "{$search}%")
                    ->orWhere('EMAIL', 'LIKE', "{$search}%")
                    ->orWhere('DEPARTMENT', 'LIKE', "{$search}%")
                    ->orWhere('ROLEACCESS', 'LIKE', "{$search}%")
                    ->orWhere('STATUS', 'LIKE', "{$search}%");
            }

            $users = $query->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new user record
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'FULLNAME'      => 'required|string|max:64',
                'EMAIL'         => 'required|string|max:64',
                'PHONE'         => 'required|string|max:14',
                'DEPARTMENT'    => 'required|string|max:12',
                'ROLEACCESS'    => 'required|string|max:1',
                'STATUS'        => 'required|string|max:1',
                'CONTROLACCESS' => 'required|json',
            ]);

            $user = MasUser::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data'    => $user
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Find a user record by ID
    public function show($id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $user
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a user record
    public function update(Request $request, $id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validated = $request->validate([
                'FULLNAME'      => 'required|string|max:64',
                'EMAIL'         => 'required|string|max:64',
                'PHONE'         => 'required|string|max:14',
                'DEPARTMENT'    => 'required|string|max:12',
                'ROLEACCESS'    => 'required|string|max:1',
                'STATUS'        => 'required|string|max:1',
                'CONTROLACCESS' => 'required|json',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'data'    => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a user record
    public function destroy($id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
