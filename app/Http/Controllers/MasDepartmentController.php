<?php

namespace App\Http\Controllers;

use App\Models\MasDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasDepartmentController extends Controller
{
    // Fetch all department records with optional searching
    public function index(Request $request)
    {
        try {
            $query = MasDepartment::query();

            // Check for search parameters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('DEPARTMENTCODE', 'LIKE', "{$search}%")
                    ->orWhere('DEPARTMENTID', 'LIKE', "{$search}%")
                    ->orWhere('DEPARTMENTNAME', 'LIKE', "{$search}%");
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
            $validated = $request->validate([
                'DEPARTMENTCODE' => [
                    'required',
                    'string',
                    'max:10',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_DEPARTMENT')
                            ->where('DEPARTMENTCODE', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The DEPARTMENTCODE has already been taken.');
                        }
                    }
                ],
                'DEPARTMENTID'   => 'required|integer',
                'DEPARTMENTNAME' => 'required|string|max:50',
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

    // Find a department record by DEPARTMENTCODE
    public function show($departmentCode)
    {
        try {
            $department = MasDepartment::find($departmentCode);

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
    public function update(Request $request, $departmentCode)
    {
        try {
            $department = MasDepartment::find($departmentCode);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            $validated = $request->validate([
                'DEPARTMENTID'   => 'required|integer',
                'DEPARTMENTNAME' => 'required|string|max:50',
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

    // Delete a department record
    public function destroy($departmentCode)
    {
        try {
            $department = MasDepartment::find($departmentCode);

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
}
