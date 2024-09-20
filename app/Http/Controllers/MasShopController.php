<?php

namespace App\Http\Controllers;

use App\Models\MasShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasShopController extends Controller
{
    // Fetch all shops
    public function index(Request $request)
    {
        try {
            // Get query parameters for filtering
            $shopCode = $request->query('shop_code');
            $shopName = $request->query('shop_name');

            // Start building the query
            $query = MasShop::query();

            // Apply filters based on the query parameters
            if ($shopCode) {
                $query->where('SHOPCODE', 'like', '%' . $shopCode . '%');
            }

            if ($shopName) {
                $query->orWhere('SHOPNAME', 'like', '%' . $shopName . '%');
            }

            // Execute the query and get the results
            $shops = $query->get();

            return response()->json([
                'success' => true,
                'data' => $shops
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

    // Fetch a single shop by SHOPCODE
    public function show($shopCode)
    {
        $shop = MasShop::find($shopCode);

        if ($shop) {
            return response()->json([
                'success' => true,
                'data' => $shop
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Shop not found'
        ], 404);
    }

    // Create a new shop
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'SHOPCODE' => [
                    'required',
                    'string',
                    'max:4',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_SHOP')
                            ->where('SHOPCODE', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The SHOPCODE has already been taken.');
                        }
                    }
                ],
                'SHOPNAME' => 'required|string|max:20',
                'PLANTTYPE' => 'required|string|max:1',
                'COUNTFLAG' => 'required|string|max:1',
            ]);

            $shop = MasShop::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Shop created successfully',
                'data' => $shop
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

    // Update an existing shop
    public function update(Request $request, $shopCode)
    {
        try {
            $shop = MasShop::find($shopCode);

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found'
                ], 404);
            }

            $validated = $request->validate([
                'SHOPNAME' => 'sometimes|required|string|max:20',
                'PLANTTYPE' => 'sometimes|required|string|max:1',
                'COUNTFLAG' => 'sometimes|required|string|max:1',
            ]);

            $shop->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Shop updated successfully',
                'data' => $shop
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

    // Delete a shop
    public function destroy($shopCode)
    {
        try {
            $shop = MasShop::find($shopCode);

            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found',
                ], 404);
            }

            $shop->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shop deleted successfully',
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
