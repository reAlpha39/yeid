<?php

namespace App\Http\Controllers;

use App\Models\MasPrevention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasPreventionController extends Controller
{
    // Fetch all Prevention records with optional searching
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = MasPrevention::query();

            // Check for search parameters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('preventioncode', 'LIKE', $search . '%')
                        ->orWhere('preventionname', 'LIKE', $search . '%')
                        ->orWhere('remark', 'LIKE', $search . '%');
                });
            }

            $preventions = $query->get();

            return response()->json([
                'success' => true,
                'data' => $preventions
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new Prevention record
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'preventioncode' => [
                    'required',
                    'string',
                    'max:3',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_prevention')
                            ->where('preventioncode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The preventioncode has already been taken.');
                        }
                    }
                ],
                'preventionname' => 'required|string|max:64',
                'remark'         => 'nullable|string|max:64'
            ]);

            $prevention = MasPrevention::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Prevention created successfully!',
                'data' => $prevention
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a Prevention record by preventioncode
    public function show($preventionCode)
    {
        try {
            $prevention = MasPrevention::find($preventionCode);

            if (!$prevention) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prevention not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $prevention
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a Prevention record
    public function update(Request $request, $preventionCode)
    {
        try {
            $prevention = MasPrevention::find($preventionCode);

            if (!$prevention) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prevention not found'
                ], 404);
            }

            $validated = $request->validate([
                'preventionname' => 'required|string|max:64',
                'remark'         => 'nullable|string|max:64'
            ]);

            $prevention->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Prevention updated successfully!',
                'data' => $prevention
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a Prevention record
    public function destroy($preventionCode)
    {
        try {
            $prevention = MasPrevention::find($preventionCode);

            if (!$prevention) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prevention not found'
                ], 404);
            }

            $prevention->delete();
            return response()->json([
                'success' => true,
                'message' => 'Prevention deleted successfully!'
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
