<?php

namespace App\Http\Controllers;

use App\Models\MasMaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasMakerController extends Controller
{
    // Fetch all makers
    public function index(Request $request)
    {
        try {
            $search = $request->query('query');

            // Start building the query
            $query = MasMaker::query();

            // Apply filters based on the query parameters

            $query->where('MAKERCODE', 'like', '%' . $search . '%');
            $query->orWhere('MAKERNAME', 'like', '%' . $search . '%');
            $query->orWhere('REMARK', 'like', '%' . $search . '%');

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
            $validated = $request->validate([
                'MAKERCODE' => [
                    'required',
                    'string',
                    'max:6',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_MAKER')
                            ->where('MAKERCODE', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The MAKERCODE has already been taken.');
                        }
                    }
                ],
                'MAKERNAME' => 'required|string|max:50',
                'REMARK' => 'nullable|string|max:50',
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
            $maker = MasMaker::find($makerCode);

            if (!$maker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maker not found'
                ], 404);
            }

            $validated = $request->validate([
                'MAKERNAME' => 'required|string|max:50',
                'REMARK' => 'nullable|string|max:50',
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
}
