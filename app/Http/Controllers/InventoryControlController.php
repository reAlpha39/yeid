<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryControlController extends Controller
{
    public function getRecords(Request $request)
    {
        // Fetch parameters from the request, set default values if not provided
        $startDate = $request->input('startDate', '20240417');
        $endDate = $request->input('endDate', '20240516');
        $jobCode = $request->input('jobCode', 'I');
        $limit = $request->input('limit', 0);

        // Validate and set orderBy parameters, default to 'jobdate' and 'jobtime' descending
        $orderByColumn = $request->input('orderBy', 'jobdate');
        $orderByDirection = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Perform the query with join, filters, and ordering
        $records = DB::table('HOZENADMIN.tbl_invrecord AS I')
            ->leftJoin('HOZENADMIN.mas_machine AS M', 'I.machineno', '=', 'M.machineno')
            ->select(
                'I.recordid',
                'I.jobcode',
                'I.jobdate',
                'I.jobtime',
                'I.partcode',
                'I.partname',
                'I.specification',
                'I.brand',
                'I.usedflag',
                'I.quantity',
                'I.unitprice',
                'I.currency',
                'I.total',
                'I.machineno',
                DB::raw("COALESCE(M.shopname, '-') AS shopname"),
                DB::raw("COALESCE(M.linecode, '-') AS linecode"),
                'I.employeecode',
                'I.note',
                'I.vendorcode'
            )
            ->whereBetween('I.jobdate', [$startDate, $endDate])
            ->where('I.jobcode', $jobCode)
            ->orderBy($orderByColumn, $orderByDirection)
            ->orderBy('I.jobtime', $orderByDirection) // Always secondary sort by jobtime
            ->limit($limit)
            ->get();

        return response()->json($records);
    }

    public function getPartInfo(Request $request)
    {
        try {
            // Get the query parameter from the request
            $query = $request->input('query', '');

            // Perform the query on the mas_inventory table
            $results = DB::table('HOZENADMIN.mas_inventory')
                ->where('PARTCODE', 'like', $query . '%')
                ->orWhere('PARTNAME', 'like', $query . '%')
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

    public function getVendor(Request $request)
    {
        try {
            // Get the query parameter from the request
            $query = $request->input('query', '');

            // Perform the query on the mas_inventory table
            $results = DB::table('HOZENADMIN.mas_vendor')
                ->where('VENDORCODE', 'like', $query . '%')
                ->orWhere('VENDORNAME', 'like', $query . '%')
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
            $results = DB::table('HOZENADMIN.mas_employee')
                ->where('EMPLOYEECODE', 'like', $query . '%')
                ->orWhere('EMPLOYEENAME', 'like', $query . '%')
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
            $machines = DB::table('HOZENADMIN.MAS_INVMACHINE as I')
                ->leftJoin('HOZENADMIN.MAS_MACHINE as M', 'I.MACHINENO', '=', 'M.MACHINENO')
                ->select(
                    'I.MACHINENO',
                    DB::raw("COALESCE(M.MACHINENAME, 'NO REGISTER') as MACHINENAME"),
                    DB::raw("COALESCE(M.SHOPNAME, 'N/A') as SHOPNAME"),
                    DB::raw("COALESCE(M.LINECODE, 'N/A') as LINECODE"),
                    DB::raw("COALESCE(M.MODELNAME, 'N/A') as MODELNAME"),
                    DB::raw("COALESCE(M.MAKERNAME, 'N/A') as MAKERNAME"),
                    DB::raw("COALESCE(M.SHOPCODE, 'N/A') as SHOPCODE")
                )
                ->where('I.PARTCODE', '=', $partCode)
                ->orderBy('I.MACHINENO')
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
                'records.*.locationId' => 'required',
                'records.*.jobCode' => 'required',
                'records.*.jobDate' => 'required|date',
                'records.*.jobTime' => 'required',
                'records.*.partCode' => 'required',
                'records.*.partName' => 'required',
                'records.*.specification' => 'nullable',
                'records.*.brand' => 'nullable',
                'records.*.usedFlag' => 'nullable',
                'records.*.quantity' => 'required|numeric',
                'records.*.unitPrice' => 'required|numeric',
                'records.*.price' => 'required|numeric',
                'records.*.currency' => 'required',
                'records.*.vendorCode' => 'nullable',
                'records.*.machineNo' => 'nullable',
                'records.*.machineName' => 'nullable',
                'records.*.note' => 'nullable',
                'records.*.employeeCode' => 'nullable'
            ]);

            $records = $request->input('records');
            $dataToInsert = [];

            foreach ($records as $record) {
                // Extract values for each record
                $recordId = DB::table('HOZENADMIN.TBL_INVRECORD')->max('RECORDID') + 1; // Simulating sequence
                $locationId = $record['locationId'];
                $jobCode = $record['jobCode'];
                $jobDate = $record['jobDate'];
                $jobTime = $record['jobTime'];
                $partCode = $record['partCode'];
                $partName = $record['partName'];
                $specification = $record['specification'] ?? ''; // Allow null values
                $brand = $record['brand'] ?? ''; // Allow null values
                $usedFlag = $record['usedFlag'] ?? str_pad('', 1);
                $quantity = $record['quantity'];
                $unitPrice = str_replace(',', '', $record['unitPrice']);
                $totalPrice = str_replace(',', '', $record['price']);
                $currency = $record['currency'];
                $vendorCode = $record['vendorCode'] ?? ''; // Allow null values
                $machineNo = $record['machineNo'] ?? '';
                $machineName = $record['machineName'] ?? ''; // Use chr(0) if null
                $note = $record['note'] ?? str_pad('', 128); // Use chr(0) if null
                $employeeCode = $record['employeeCode'] ?? str_pad('', 8);
                $updateTime = now(); // Current timestamp

                // Prepare data for batch insert
                $dataToInsert[] = [
                    'RECORDID' => $recordId,
                    'LOCATIONID' => $locationId,
                    'JOBCODE' => $jobCode,
                    'JOBDATE' => $jobDate,
                    'JOBTIME' => $jobTime,
                    'PARTCODE' => $partCode,
                    'PARTNAME' => $partName,
                    'SPECIFICATION' => $specification,
                    'BRAND' => $brand,
                    'USEDFLAG' => $usedFlag,
                    'QUANTITY' => $quantity,
                    'UNITPRICE' => $unitPrice,
                    'TOTAL' => $totalPrice,
                    'CURRENCY' => $currency,
                    'VENDORCODE' => $vendorCode,
                    'MACHINENO' => $machineNo,
                    'MACHINENAME' => $machineName,
                    'NOTE' => $note,
                    'EMPLOYEECODE' => $employeeCode,
                    'UPDATETIME' => $updateTime
                ];

                // Perform a batch insert
                DB::table('HOZENADMIN.TBL_INVRECORD')->insert($dataToInsert);
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
            $affectedRows = DB::table('HOZENADMIN.TBL_INVRECORD')
                ->where('RECORDID', $recordId)
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
}
