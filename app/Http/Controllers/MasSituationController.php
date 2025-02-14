<?php

namespace App\Http\Controllers;

use App\Models\MasSituation;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\SituationsExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class MasSituationController extends Controller
{
    use PermissionCheckerTrait;

    // Fetch all situations
    public function index(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $search = $request->query('search');

            $query = MasSituation::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('situationcode', 'ILIKE', $search . '%')
                        ->orWhere('situationname', 'ILIKE', $search . '%')
                        ->orWhere('remark', 'ILIKE', $search . '%');
                });
            }

            $situations = $query->get();
            return response()->json([
                'success' => true,
                'data' => $situations
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new situation
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'situationcode' => [
                    'required',
                    'string',
                    'max:3',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_situation')
                            ->where('situationcode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The situationcode has already been taken.');
                        }
                    }
                ],
                'situationname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64',
            ]);

            $situation = MasSituation::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Situation created successfully!',
                'data' => $situation
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a situation by situationcode
    public function show($situationCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $situation = MasSituation::find($situationCode);

            if (!$situation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Situation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $situation
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a situation
    public function update(Request $request, $situationCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $situation = MasSituation::find($situationCode);

            if (!$situation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Situation not found'
                ], 404);
            }

            $validated = $request->validate([
                'situationname' => 'required|string|max:64',
                'remark' => 'nullable|string|max:64',
            ]);

            $situation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Situation updated successfully!',
                'data' => $situation
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a situation
    public function destroy($situationCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $situation = MasSituation::find($situationCode);

            if (!$situation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Situation not found'
                ], 404);
            }

            $situation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Situation deleted successfully!'
            ], 200);
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

            return Excel::download(new SituationsExport($request->query('search')), 'situations.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
