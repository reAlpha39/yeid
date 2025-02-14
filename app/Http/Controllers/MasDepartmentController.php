<?php

namespace App\Http\Controllers;

use App\Models\MasDepartment;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\DepartmentsExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class MasDepartmentController extends Controller
{
    use PermissionCheckerTrait;

    // Fetch all department records with optional searching
    public function index(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $query = MasDepartment::query();

            // Check for search parameters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('code', 'ILIKE', "{$search}%")
                    ->orWhere('name', 'ILIKE', "{$search}%");
            }

            $departments = $query->get();

            return response()->json([
                'success' => true,
                'data' => $departments
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new department record
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:64',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_department')
                            ->where('code', $value)
                            ->whereNull('deleted_at')
                            ->exists();

                        if ($exists) {
                            $fail('The code has already been taken.');
                        }
                    }
                ],
                'name' => 'required|string|max:128',
            ]);

            $department = MasDepartment::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully!',
                'data'    => $department
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a department record by ID
    public function show($id)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $department = MasDepartment::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $department
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

    // Update a department record
    public function update(Request $request, $id)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $department = MasDepartment::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:64',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) use ($id) {
                        $exists = DB::table('mas_department')
                            ->where('code', $value)
                            ->where('id', '<>', $id)
                            ->exists();

                        if ($exists) {
                            $fail('The code has already been taken.');
                        }
                    }
                ],
                'name' => 'required|string|max:128',
            ]);

            $department->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully!',
                'data'    => $department
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Soft delete a department record
    public function destroy($id)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $department = MasDepartment::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $department->delete();
            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Restore a soft-deleted department record
    public function restore($id)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $department = MasDepartment::withTrashed()->find($id);

            if ($department && $department->trashed()) {
                $department->restore();
                return response()->json([
                    'success' => true,
                    'message' => 'Department restored successfully!'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found or not deleted'
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

            return Excel::download(new DepartmentsExport($request->input('search')), 'departments.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
