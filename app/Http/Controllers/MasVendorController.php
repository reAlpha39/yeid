<?php

namespace App\Http\Controllers;

use App\Models\MasVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasVendorController extends Controller
{
    // Display a listing of vendors (with search functionality)
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');

            // Search by VENDORCODE or VENDORNAME
            $vendors = MasVendor::when($search, function ($query, $search) {
                return $query->where('VENDORCODE', 'like', "%$search%")
                    ->orWhere('VENDORNAME', 'like', "%$search%");
            })->get();

            return response()->json([
                'success' => true,
                'data' => $vendors
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Fetch a single shop by VENDORCODE
    public function show($vendorCode)
    {
        try {
            $vendors = MasVendor::find($vendorCode);

            if ($vendors) {
                return response()->json([
                    'success' => true,
                    'data' => $vendors
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Vendor not found'
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

    // Store a newly created vendor in storage
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'VENDORCODE' => [
                    'required',
                    'string',
                    'max:15',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('HOZENADMIN.MAS_VENDOR')
                            ->where('VENDORCODE', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The VENDORCODE has already been taken.');
                        }
                    }
                ],
                'VENDORNAME' => 'required|string|max:64',
            ]);

            $vendor = MasVendor::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully!',
                'data' => $vendor,
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Update the specified vendor in storage
    public function update(Request $request, $vendorCode)
    {
        try {
            $vendor = MasVendor::find($vendorCode);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                ], 404);
            }

            $validatedData = $request->validate([
                'VENDORNAME' => 'required|string|max:64',
            ]);

            $vendor->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully!',
                'data' => $vendor,
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }

    // Remove the specified vendor from storage
    public function destroy($vendorCode)
    {
        try {
            $vendor = MasVendor::find($vendorCode);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                ], 404);
            }

            $vendor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully!',
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500);
        }
    }
}
