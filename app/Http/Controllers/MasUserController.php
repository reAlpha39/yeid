<?php

namespace App\Http\Controllers;

use App\Models\MasUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Exception;

class MasUserController extends Controller
{
    // Fetch all user records with optional searching
    public function index(Request $request)
    {
        try {
            $query = MasUser::query();

            $search = $request->input('search');
            $department = $request->input('department');
            $roleAccess = $request->input('roleAccess');
            $status = $request->input('status');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', $search . '%')
                        ->orWhere('email', 'ILIKE', $search . '%');
                });
            }

            if ($department) {
                $query->where('department_id', $department);
            }

            if ($roleAccess) {
                $query->where('role_access', $roleAccess);
            }

            if (isset($status)) {
                $query->where('status', $status);
            }

            // Eager load the department relationship and get the results
            $users = $query->with('department')->get();

            // Format the response to include the department name
            $users = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department_id' => $user->department_id,
                    'department_name' => $user->department ? $user->department->name : null,
                    'role_access' => $user->role_access,
                    'status' => $user->status,
                    'control_access' => $user->control_access,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new user record
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:64',
                'email' => [
                    'required',
                    'string',
                    'max:64',
                    Rule::unique('mas_user')->whereNull('deleted_at')
                ],
                'nik' => [
                    'required',
                    'string',
                    'min:16',
                    'max:16',
                    Rule::unique('mas_user')->whereNull('deleted_at')
                ],
                'phone' => 'required|string|max:14',
                'department_id' => [
                    'required',
                    Rule::exists('mas_department', 'id')->whereNull('deleted_at')
                ],
                'role_access' => 'required|string|max:1',
                'status' => 'required|string|max:1',
                'control_access' => 'required|json',
                'password' => 'required|string',
                // 'c_password' => 'required|same:password'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $validated = $validator->validated();

            // Remove confirmation password and hash the password
            // unset($validated['c_password']);
            $validated['password'] = Hash::make($validated['password']);

            $user = MasUser::create($validated);

            // Generate token
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data'    => $user,
                'accessToken' => $token
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a user record by ID
    public function show($id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json(
                [
                    'success' => true,
                    'data' => $user
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

    // Update a user record
    public function update(Request $request, $id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validationRules = [
                'name' => 'required|string|max:64',
                'email' => [
                    'required',
                    'string',
                    'max:64',
                    function ($attribute, $value, $fail) use ($id) {
                        $exists = DB::table('mas_user')
                            ->where('email', $value)
                            ->where('id', '<>', $id)
                            ->exists();

                        if ($exists) {
                            $fail('Email has already been taken.');
                        }
                    }
                ],
                'nik' => [
                    'required',
                    'string',
                    'min:16',
                    'max:16',
                    function ($attribute, $value, $fail) use ($id) {
                        $exists = DB::table('mas_user')
                            ->where('nik', $value)
                            ->where('id', '<>', $id)
                            ->exists();

                        if ($exists) {
                            $fail('NIK has already been taken.');
                        }
                    }
                ],
                'phone' => 'required|string|max:14',
                'department_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_department')
                            ->where('id', $value)
                            ->exists();

                        if (!$exists) {
                            $fail('The selected department is invalid.');
                        }
                    },
                ],
                'role_access' => 'required|string|max:1',
                'status' => 'required|string|max:1',
                'control_access' => 'required|json',
                'password' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $validated = $validator->validated();

            // Remove password from validated data if it's empty
            if (empty($validated['password'])) {
                unset($validated['password']);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'data'    => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the input
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $validated = $validator->validated();

            // Find the user by ID
            $user = MasUser::find($id);

            // If user not found, return a 404 response
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Update only the status column
            $user->status = $validated['status'];
            $user->save();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'data'    => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Delete a user record
    public function destroy($id)
    {
        try {
            $user = MasUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Restore a soft-deleted user record
    public function restore($id)
    {
        try {
            $user = MasUser::withTrashed()->find($id);

            if ($user && $user->trashed()) {
                $user->restore();
                return response()->json([
                    'success' => true,
                    'message' => 'User restored successfully!'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or not deleted'
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

    public function export()
    {
        try {
            return Excel::download(new UsersExport(), 'users.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
