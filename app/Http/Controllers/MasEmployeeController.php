<?php

namespace App\Http\Controllers;

use App\Models\MasEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasEmployeeController extends Controller
{
    // Fetch all employee records with optional searching
    public function index(Request $request)
    {
        try {
            $query = MasEmployee::query();

            // Check for search parameters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('EMPLOYEECODE', 'LIKE', "{$search}%")
                        ->orWhere('EMPLOYEENAME', 'LIKE', "{$search}%")
                        ->orWhere('MLEVEL', 'LIKE', "{$search}%")
                        ->orWhere('PASSWORD', 'LIKE', "{$search}%");
                });
            }

            $employees = $query->get();

            return response()->json([
                'success' => true,
                'data' => $employees
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new employee record
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'EMPLOYEECODE' => [
                    'required',
                    'string',
                    'max:8',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_EMPLOYEE')
                            ->where('EMPLOYEECODE', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The EMPLOYEECODE has already been taken.');
                        }
                    }
                ],
                'EMPLOYEENAME' => 'nullable|string|max:30',
                'MLEVEL'       => 'nullable|string|max:1',
                'PASSWORD'     => 'nullable|string|max:20'
            ]);

            $employee = MasEmployee::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully!',
                'data' => $employee
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find an employee record by EMPLOYEECODE
    public function show($employeeCode)
    {
        try {
            $employee = MasEmployee::find($employeeCode);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $employee
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

    // Update an employee record
    public function update(Request $request, $employeeCode)
    {
        try {
            $employee = MasEmployee::find($employeeCode);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $validated = $request->validate([
                'EMPLOYEENAME' => 'nullable|string|max:30',
                'MLEVEL'       => 'nullable|string|max:1',
                'PASSWORD'     => 'nullable|string|max:20'
            ]);

            $employee->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'data' => $employee
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete an employee record
    public function destroy($employeeCode)
    {
        try {
            $employee = MasEmployee::find($employeeCode);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $employee->delete();
            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully!'
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
