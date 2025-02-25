<?php

namespace App\Http\Controllers;

use App\Models\MasLTFactor;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LTFactorExport;
use Exception;

class MasLTFactorController extends Controller
{
    use PermissionCheckerTrait;

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
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

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
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

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
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

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
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

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

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(new LTFactorExport($request->query('search')), 'ltfactors.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
