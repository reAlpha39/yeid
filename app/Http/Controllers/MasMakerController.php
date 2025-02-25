<?php

namespace App\Http\Controllers;

use App\Models\MasMaker;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MakersExport;
use Exception;

class MasMakerController extends Controller
{
    use PermissionCheckerTrait;
    // Fetch all makers
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            // Start building the query
            $query = MasMaker::query();

            // Apply filters based on the query parameters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('makercode', 'ILIKE', $search . '%')
                        ->orWhere('makername', 'ILIKE', $search . '%')
                        ->orWhere('remark', 'ILIKE', $search . '%');
                });
            }

            // Execute the query and get the results
            $makers = $query->get();

            return response()->json([
                'success' => true,
                'data' => $makers
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    // Store a new maker
    public function store(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'makercode' => [
                    'required',
                    'string',
                    'max:6',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_maker')
                            ->where('makercode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The makercode has already been taken.');
                        }
                    }
                ],
                'makername' => 'required|string|max:50',
                'remark' => 'nullable|string|max:50',
            ]);

            $maker = MasMaker::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Maker created successfully!',
                'data' => $maker
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    // Find a maker by MAKERCODE
    public function show($makerCode)
    {
        try {
            // if (!$this->checkAccess('masterData', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $maker = MasMaker::find($makerCode);

            if (!$maker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maker not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $maker
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    // Update a maker
    public function update(Request $request, $makerCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $maker = MasMaker::find($makerCode);

            if (!$maker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maker not found'
                ], 404);
            }

            $validated = $request->validate([
                'makername' => 'required|string|max:50',
                'remark' => 'nullable|string|max:50',
            ]);

            $maker->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Maker updated successfully!',
                'data' => $maker
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    // Delete a maker
    public function destroy($makerCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

            $maker = MasMaker::find($makerCode);

            if (!$maker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maker not found'
                ], 404);
            }

            $maker->delete();

            return response()->json([
                'success' => true,
                'message' => 'Maker deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(new MakersExport($request->query('search')), 'makers.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
