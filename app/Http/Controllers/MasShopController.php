<?php

namespace App\Http\Controllers;

use App\Models\MasShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ShopsExport;
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
                $query->where('shopcode', 'ILIKE', '%' . $shopCode . '%');
            }

            if ($shopName) {
                $query->orWhere('shopname', 'ILIKE', '%' . $shopName . '%');
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

    // Fetch a single shop by shopcode
    public function show($shopCode)
    {
        try {
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
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    // Create a new shop
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'shopcode' => [
                    'required',
                    'string',
                    'max:4',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_shop')
                            ->where('shopcode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The shopcode has already been taken.');
                        }
                    }
                ],
                'shopname' => 'required|string|max:20',
                'planttype' => 'required|string|max:1',
                'countflag' => 'required|string|max:1',
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
                'shopname' => 'sometimes|required|string|max:20',
                'planttype' => 'sometimes|required|string|max:1',
                'countflag' => 'sometimes|required|string|max:1',
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

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new ShopsExport(
                    $request->query('shop_code'),
                    $request->query('shop_name')
                ),
                'shops.xlsx'
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
