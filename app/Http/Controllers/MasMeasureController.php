<?php

namespace App\Http\Controllers;

use App\Models\MasMeasure;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\MeasuresExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class MasMeasureController extends Controller
{
    use PermissionCheckerTrait;

    // Fetch all Measures with optional searching
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = MasMeasure::query();

            // Check for search parameters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('measurecode', 'ILIKE', $search . '%')
                        ->orWhere('measurename', 'ILIKE', $search . '%')
                        ->orWhere('remark', 'ILIKE', $search . '%');
                });
            }

            $measures = $query->get();

            return response()->json([
                'success' => true,
                'data' => $measures
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Store a new Measure
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'measurecode' => [
                    'required',
                    'string',
                    'max:3',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_measure')
                            ->where('measurecode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The measurecode has already been taken.');
                        }
                    }
                ],
                'measurename' => 'required|string|max:64',
                'remark'      => 'nullable|string|max:64'
            ]);

            $measure = MasMeasure::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Measure created successfully!',
                'data' => $measure
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Find a Measure by measurecode
    public function show($measureCode)
    {
        try {
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $measure = MasMeasure::find($measureCode);

            if (!$measure) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measure not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $measure
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Update a Measure
    public function update(Request $request, $measureCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $measure = MasMeasure::find($measureCode);

            if (!$measure) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measure not found'
                ], 404);
            }

            $validated = $request->validate([
                'measurename' => 'required|string|max:64',
                'remark'      => 'nullable|string|max:64'
            ]);

            $measure->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Measure updated successfully!',
                'data' => $measure
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Delete a Measure
    public function destroy($measureCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $measure = MasMeasure::find($measureCode);

            if (!$measure) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measure not found'
                ], 404);
            }

            $measure->delete();
            return response()->json([
                'success' => true,
                'message' => 'Measure deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(new MeasuresExport($request->query('search')), 'measures.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
