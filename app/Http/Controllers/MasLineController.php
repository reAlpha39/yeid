<?php

namespace App\Http\Controllers;

use App\Models\MasLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasLineController extends Controller
{
    // Fetch all lines
    public function index(Request $request)
    {
        try {
            $search = $request->query('query');
            $shopCode = $request->query('shop_code');

            $query = MasLine::query();

            // Apply the shop code filter only if it's provided
            if ($shopCode) {
                $query->where('SHOPCODE', $shopCode);
            }

            // Apply the search filter if a search query is provided
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('LINECODE', 'like', '%' . $search . '%')
                        ->orWhere('LINENAME', 'like', '%' . $search . '%');
                });
            }

            $lines = $query->get();

            return response()->json([
                'success' => true,
                'data' => $lines
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Store a new line
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'SHOPCODE' => 'required|string|max:4',
                'LINECODE' => 'required|string|max:2',
                'LINENAME' => 'required|string|max:50',
                'UNITPRICE' => 'nullable|numeric',
                'TACTTIME' => 'nullable|numeric',
                'STAFFNUM' => 'nullable|numeric'
            ]);

            $line = MasLine::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Line created successfully!',
                'data' => $line
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Find a line by SHOPCODE and LINECODE
    public function show($shopCode, $lineCode)
    {
        try {
            $line = MasLine::where('SHOPCODE', $shopCode)
                ->where('LINECODE', $lineCode)
                ->first();

            if (!$line) {
                return response()->json([
                    'success' => false,
                    'message' => 'Line not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $line
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update a line
    public function update(Request $request, $shopCode, $lineCode)
    {
        try {
            // Validate the incoming request data
            $validated = $request->validate([
                'LINENAME' => 'nullable|string|max:50',
                'UNITPRICE' => 'nullable|numeric',
                'TACTTIME' => 'nullable|numeric',
                'STAFFNUM' => 'nullable|numeric'
            ]);

            // Prepare the update query
            $query = DB::table('HOZENADMIN.MAS_LINE')
                ->where('SHOPCODE', $shopCode)
                ->where('LINECODE', $lineCode);

            // Execute the update
            $updated = $query->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Line updated successfully!',
                'updated' => $updated
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a line
    public function destroy($shopCode, $lineCode)
    {
        try {
            $line = MasLine::where('SHOPCODE', $shopCode)
                ->where('LINECODE', $lineCode)
                ->first();

            if (!$line) {
                return response()->json([
                    'success' => false,
                    'message' => 'Line not found'
                ], 404);
            }

            $deleted = DB::table('HOZENADMIN.MAS_LINE')
                ->where('SHOPCODE', $shopCode)
                ->where('LINECODE', $lineCode)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Line deleted successfully!',
                'deleted' => $deleted
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
