<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\InventoryControlExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class InventoryControlController extends Controller
{
    public function getRecords(Request $request)
    {
        try {
            // Basic filters
            $search = $request->input('search');
            $startDate = $request->input('startDate', '20240417');
            $endDate = $request->input('endDate', '20240516');
            $jobCode = $request->input('jobCode', 'I');
            $vendorCode = $request->input('vendorcode');
            $currency = $request->input('currency');

            // Pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'desc');

            // Handle Vuetify sorting format
            if ($sortBy && is_string($sortBy) && str_contains($sortBy, '{')) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'desc';
                } catch (Exception $e) {
                    // If JSON decode fails, use original value
                }
            }

            // Build the query
            $query = DB::table('tbl_invrecord AS i')
                ->leftJoin('mas_machine AS m', 'i.machineno', '=', 'm.machineno')
                ->select(
                    'i.recordid',
                    'i.jobcode',
                    'i.jobdate',
                    'i.jobtime',
                    'i.partcode',
                    'i.partname',
                    'i.specification',
                    'i.brand',
                    'i.usedflag',
                    'i.quantity',
                    'i.unitprice',
                    'i.currency',
                    'i.total',
                    'i.machineno',
                    DB::raw('COALESCE(m.shopname, \'-\') AS shopname'),
                    DB::raw('COALESCE(m.linecode, \'-\') AS linecode'),
                    'i.employeecode',
                    'i.note',
                    'i.vendorcode'
                )
                ->whereBetween('i.jobdate', [$startDate, $endDate])
                ->where('i.jobcode', $jobCode);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('i.partcode', 'ILIKE', "{$search}%")
                        ->orWhere('i.partname', 'ILIKE', "{$search}%");
                });
            }

            // Apply additional filters
            if ($vendorCode) {
                $query->where('i.vendorcode', $vendorCode);
            }

            if ($currency) {
                $query->where('i.currency', $currency);
            }

            // Apply sorting
            if ($sortBy) {
                // Handle special cases for computed/joined columns
                switch ($sortBy) {
                    case 'shopname':
                        $query->orderBy(DB::raw('COALESCE(m.shopname, \'-\')'), $sortDirection);
                        break;
                    case 'linecode':
                        $query->orderBy(DB::raw('COALESCE(m.linecode, \'-\')'), $sortDirection);
                        break;
                    default:
                        // For regular columns, determine the table prefix
                        $prefix = in_array($sortBy, [
                            'shopname',
                            'linecode'
                        ]) ? 'm.' : 'i.';
                        $query->orderBy($prefix . $sortBy, $sortDirection);
                }

                // Add secondary sort if primary sort isn't jobdate/jobtime
                if ($sortBy !== 'jobdate' && $sortBy !== 'jobtime') {
                    $query->orderBy('i.jobdate', 'desc')
                        ->orderBy('i.jobtime', 'desc');
                }
            } else {
                // Default sorting
                $query->orderBy('i.jobdate', 'desc')
                    ->orderBy('i.jobtime', 'desc');
            }

            // Execute pagination
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'next_page_url' => $results->nextPageUrl(),
                    'prev_page_url' => $results->previousPageUrl(),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPartInfo(Request $request)
    {
        try {
            $query = $request->input('query', '');
            $vendor = $request->input('vendorcode');
            $currency = $request->input('currency');

            $query = DB::table('mas_inventory')
                ->where(function ($q) use ($query) {
                    $q->where('partcode', 'ILIKE', $query . '%')
                        ->orWhere('partname', 'ILIKE', $query . '%');
                });

            if ($vendor) {
                $query->where('vendorcode', $vendor);
            }

            if ($currency) {
                $query->where('currency', $currency);
            }

            $results = $query->limit(100)->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVendor(Request $request)
    {
        try {
            // Get the query parameter from the request
            $query = $request->input('query', '');

            // Perform the query on the mas_inventory table
            $results = DB::table('mas_vendor')
                ->where('vendorcode', 'ILIKE', $query . '%')
                ->orWhere('vendorname', 'ILIKE', $query . '%')
                ->limit(100)
                ->get();

            // Return the results as JSON
            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    public function getStaff(Request $request)
    {
        try {
            // Get the query parameter from the request
            $query = $request->input('query', '');

            // Perform the query on the mas_employee table
            $results = DB::table('mas_employee')
                ->where('employeecode', 'ILIKE', $query . '%')
                ->orWhere('employeename', 'ILIKE', $query . '%')
                ->limit(100)
                ->get();

            // Return the results as JSON
            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    public function getMachines(Request $request)
    {
        try {
            // Get the partCode from the request
            $partCode = $request->input('partCode');

            // Check if partCode is provided
            if (!$partCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Part code is required'
                ], 400); // Bad request
            }

            // Perform the query using the Query Builder
            $machines = DB::table('mas_invmachine as i')
                ->leftJoin(
                    'mas_machine as m',
                    'i.machineno',
                    '=',
                    'm.machineno'
                )
                ->select(
                    'i.machineno',
                    DB::raw("COALESCE(m.machinename, 'NO REGISTER') as machinename"),
                    DB::raw("COALESCE(m.shopname, 'N/A') as shopname"),
                    DB::raw("COALESCE(m.linecode, 'N/A') as linecode"),
                    DB::raw("COALESCE(m.modelname, 'N/A') as modelname"),
                    DB::raw("COALESCE(m.makername, 'N/A') as makername"),
                    DB::raw("COALESCE(m.shopcode, 'N/A') as shopcode")
                )
                ->where('i.partcode', '=', $partCode)
                ->orderBy('i.machineno')
                ->get();


            if ($machines->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No machines found for the given part code'
                ], 404); // Not found
            }

            // Return the result as JSON
            return response()->json([
                'success' => true,
                'data' => $machines
            ], 200);
        } catch (Exception $e) {
            // Catch any exceptions and return an error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data',
                'error' => $e->getMessage() // You can remove this line in production for security reasons
            ], 500); // Internal server error
        }
    }

    public function storeInvRecord(Request $request)
    {
        try {
            // Validate that the request contains an array of items
            $request->validate([
                'records' => 'required|array',
                'records.*.locationid' => 'required',
                'records.*.jobcode' => 'required',
                'records.*.jobdate' => 'required|date',
                'records.*.jobtime' => 'required',
                'records.*.partcode' => 'required',
                'records.*.partname' => 'required',
                'records.*.specification' => 'nullable',
                'records.*.brand' => 'nullable',
                'records.*.usedflag' => 'nullable',
                'records.*.quantity' => 'required|numeric',
                'records.*.unitprice' => 'required|numeric',
                'records.*.price' => 'required|numeric',
                'records.*.currency' => 'required',
                'records.*.vendorcode' => 'nullable',
                'records.*.machineno' => 'nullable',
                'records.*.machinename' => 'nullable',
                'records.*.note' => 'nullable',
                'records.*.employeecode' => 'nullable'
            ]);

            $records = $request->input('records');
            $dataToInsert = [];

            foreach ($records as $record) {
                // Extract values for each record
                $recordId = DB::table('tbl_invrecord')->max('recordid') + 1; // Simulating sequence
                $locationId = $record['locationid'];
                $jobCode = $record['jobcode'];
                $jobDate = $record['jobdate'];
                $jobTime = $record['jobtime'];
                $partCode = $record['partcode'];
                $partName = $record['partname'];
                $specification = $record['specification'] ?? ''; // Allow null values
                $brand = $record['brand'] ?? ''; // Allow null values
                $usedFlag = $record['usedflag'] ?? str_pad('', 1);
                $quantity = $record['quantity'];
                $unitPrice = str_replace(',', '', $record['unitprice']);
                $totalPrice = str_replace(',', '', $record['price']);
                $currency = $record['currency'];
                $vendorCode = $record['vendorcode'] ?? ''; // Allow null values
                $machineNo = $record['machineno'] ?? '';
                $machineName = $record['machinename'] ?? ''; // Use chr(0) if null
                $note = $record['note'] ?? str_pad('', 128); // Use chr(0) if null
                $employeeCode = $record['employeecode'] ?? str_pad('', 8);
                $updateTime = now(); // Current timestamp

                // Prepare data for batch insert
                $dataToInsert[] = [
                    'recordid' => $recordId,
                    'locationid' => $locationId,
                    'jobcode' => $jobCode,
                    'jobdate' => $jobDate,
                    'jobtime' => $jobTime,
                    'partcode' => $partCode,
                    'partname' => $partName,
                    'specification' => $specification,
                    'brand' => $brand,
                    'usedflag' => $usedFlag,
                    'quantity' => $quantity,
                    'unitprice' => $unitPrice,
                    'total' => $totalPrice,
                    'currency' => $currency,
                    'vendorcode' => $vendorCode,
                    'machineno' => $machineNo,
                    'machinename' => $machineName,
                    'note' => $note,
                    'employeecode' => $employeeCode,
                    'updatetime' => $updateTime
                ];

                // Perform a batch insert
                DB::table('tbl_invrecord')->insert($dataToInsert);
                $dataToInsert = [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Records inserted successfully',
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

    public function deleteRecord(Request $request)
    {
        // Validate incoming data (RECORDID is required and must be an integer)
        $request->validate([
            'record_id' => 'required|integer'
        ]);

        try {
            // Get the target RECORDID from the request
            $recordId = $request->input('record_id');

            // Perform the delete operation
            $affectedRows = DB::table('tbl_invrecord')
                ->where('recordid', $recordId)
                ->delete();

            // Check if any rows were affected (i.e., if the delete was successful)
            if ($affectedRows > 0) {
                // Return success response if deletion was successful
                return response()->json([
                    'status' => 'success',
                    'message' => 'Record deleted successfully.'
                ], 200);
            } else {
                // Return failure response if no record was found
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Record not found or already deleted.'
                ], 404);
            }
        } catch (Exception $e) {
            // Handle any potential errors
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $startDate = $request->input('startDate', '20240101');
            $endDate = $request->input('endDate', '20240101');
            $jobCode = $request->input('jobCode', 'I');
            $vendorCode = $request->input('vendorcode');
            $currency = $request->input('currency');
            $search = $request->input('search');

            return Excel::download(
                new InventoryControlExport(
                    $startDate,
                    $endDate,
                    $jobCode,
                    $vendorCode,
                    $currency,
                    $search
                ),
                'inventory_records.xlsx'
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
