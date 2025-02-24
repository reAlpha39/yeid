<?php

namespace App\Http\Controllers;

use App\Models\MasVendor;
use App\Traits\PermissionCheckerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VendorsExport;
use Exception;

class MasVendorController extends Controller
{
    use PermissionCheckerTrait;

    // Display a listing of vendors (with search functionality)
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');

            // Search by vendorcode or vendorname
            $vendors = MasVendor::when($search, function ($query, $search) {
                return $query->where('vendorcode', 'ILIKE', "$search%")
                    ->orWhere('vendorname', 'ILIKE', "$search%");
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

    // Fetch a single vendor by vendorcode
    public function show($vendorCode)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            $vendor = MasVendor::find($vendorCode);

            if ($vendor) {
                return response()->json([
                    'success' => true,
                    'data' => $vendor
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
            if (!$this->checkAccess('masterData', 'create')) {
                return $this->unauthorizedResponse();
            }

            $validatedData = $request->validate([
                'vendorcode' => [
                    'required',
                    'string',
                    'max:15',
                    // Custom rule to check uniqueness
                    function ($attribute, $value, $fail) {
                        $exists = DB::table('mas_vendor')
                            ->where('vendorcode', $value)
                            ->exists();

                        if ($exists) {
                            $fail('The vendorcode has already been taken.');
                        }
                    }
                ],
                'vendorname' => 'required|string|max:64',
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
            if (!$this->checkAccess('masterData', 'update')) {
                return $this->unauthorizedResponse();
            }

            $vendor = MasVendor::find($vendorCode);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found',
                ], 404);
            }

            $validatedData = $request->validate([
                'vendorname' => 'required|string|max:64',
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
            if (!$this->checkAccess('masterData', 'delete')) {
                return $this->unauthorizedResponse();
            }

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

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('masterData', 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(new VendorsExport($request->input('search')), 'vendors.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
