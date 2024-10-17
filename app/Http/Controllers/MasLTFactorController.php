<?php

namespace App\Http\Controllers;

use App\Models\MasLTFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasLTFactorController extends Controller
{
    // Fetch all LTFactors with optional searching
    public function index(Request $request)
    {
        try {

            $search = $request->query('search');

            $query = MasLTFactor::query();

            // Check for search parameters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ltfactorcode', 'ILIKE', $search . '%')
                        ->orWhere('ltfactorname', 'ILIKE', $search . '%')
                        ->orWhere('remark', 'ILIKE', $search . '%');
                });
            }

            $ltFactors = $query->get();

            return response()->json([
                'success' => true,
                'data' => $ltFactors
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new LTFactor
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ltfactorcode' => [
                    'required',
                    'string',
                    'max:3',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_ltfactor')
                            ->where('ltfactorcode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The ltfactorcode has already been taken.');
                        }
                    }
                ],
                'ltfactorname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64'
            ]);

            $ltFactor = MasLTFactor::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'LTFactor created successfully!',
                'data' => $ltFactor
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a LTFactor by LTFACTORCODE
    public function show($ltFactorCode)
    {
        try {
            $ltFactor = MasLTFactor::find($ltFactorCode);

            if (!$ltFactor) {
                return response()->json([
                    'success' => false,
                    'message' => 'LTFactor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ltFactor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a LTFactor
    public function update(Request $request, $ltFactorCode)
    {
        try {
            $ltFactor = MasLTFactor::find($ltFactorCode);

            if (!$ltFactor) {
                return response()->json([
                    'success' => false,
                    'message' => 'LTFactor not found'
                ], 404);
            }

            $validated = $request->validate([
                'ltfactorname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64'
            ]);

            $ltFactor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'LTFactor updated successfully!',
                'data' => $ltFactor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a LTFactor
    public function destroy($ltFactorCode)
    {
        try {
            $ltFactor = MasLTFactor::find($ltFactorCode);

            if ($ltFactor) {
                $ltFactor->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'LTFactor deleted successfully!'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'LTFactor not found'
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
