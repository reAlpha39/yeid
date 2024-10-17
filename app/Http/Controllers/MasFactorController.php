<?php

namespace App\Http\Controllers;

use App\Models\MasFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasFactorController extends Controller
{
    // Fetch all factors with optional searching
    public function index(Request $request)
    {
        try {

            $search = $request->query('search');

            // Start building the query
            $query = MasFactor::query();

            // Apply filters based on the query parameters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('factorcode', 'ILIKE', $search . '%')
                        ->orWhere('factorname', 'ILIKE', $search . '%')
                        ->orWhere('remark', 'ILIKE', $search . '%');
                });
            }

            $factors = $query->get();

            return response()->json([
                'success' => true,
                'data' => $factors
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new factor
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'factorcode' => [
                    'required',
                    'string',
                    'max:3',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_factor')
                            ->where('factorcode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The factorcode has already been taken.');
                        }
                    }
                ],
                'factorname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64'
            ]);

            $factor = MasFactor::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Factor created successfully!',
                'data' => $factor
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a factor by FACTORCODE
    public function show($factorCode)
    {
        try {
            $factor = MasFactor::find($factorCode);

            if (!$factor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $factor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a factor
    public function update(Request $request, $factorCode)
    {
        try {
            $factor = MasFactor::find($factorCode);

            if (!$factor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factor not found'
                ], 404);
            }

            $validated = $request->validate([
                'factorname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64'
            ]);

            $factor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Factor updated successfully!',
                'data' => $factor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a factor
    public function destroy($factorCode)
    {
        try {
            $factor = MasFactor::find($factorCode);

            if (!$factor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factor not found'
                ], 404);
            }

            $factor->delete();
            return response()->json([
                'success' => true,
                'message' => 'Factor deleted successfully!'
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
