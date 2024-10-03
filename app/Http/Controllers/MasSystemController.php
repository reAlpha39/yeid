<?php

namespace App\Http\Controllers;

use App\Models\MasSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasSystemController extends Controller
{
    // Fetch all system records with optional searching
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = MasSystem::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('YEAR', 'LIKE', $search . '%')
                        ->orWhere('USD2IDR', 'LIKE', $search . '%')
                        ->orWhere('JPY2IDR', 'LIKE', $search . '%')
                        ->orWhere('EUR2IDR', 'LIKE', $search . '%')
                        ->orWhere('SGD2IDR', 'LIKE', $search . '%');
                });
            }

            $systems = $query->get();

            return response()->json([
                'success' => true,
                'data' => $systems
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new system record
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'YEAR' => [
                    'required',
                    'string',
                    'max:4',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_SYSTEM')
                            ->where('YEAR', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The YEAR has already been taken.');
                        }
                    }
                ],
                'USD2IDR' => 'required|string|max:10',
                'JPY2IDR' => 'required|string|max:10',
                'EUR2IDR' => 'required|string|max:10',
                'SGD2IDR' => 'required|string|max:10'
            ]);

            $system = MasSystem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'System created successfully!',
                'data' => $system
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a system record by YEAR
    public function show($year)
    {
        try {
            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $system
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a system record
    public function update(Request $request, $year)
    {
        try {
            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            $validated = $request->validate([
                'USD2IDR' => 'required|string|max:10',
                'JPY2IDR' => 'required|string|max:10',
                'EUR2IDR' => 'required|string|max:10',
                'SGD2IDR' => 'required|string|max:10'
            ]);

            $system->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'System updated successfully!',
                'data' => $system
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a system record
    public function destroy($year)
    {
        try {
            $system = MasSystem::find($year);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System not found'
                ], 404);
            }

            $system->delete();

            return response()->json([
                'success' => true,
                'message' => 'System deleted successfully!'
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
