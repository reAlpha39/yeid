<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\InventoryControlExport;
use App\Exports\StockReportExport;
use App\Traits\PermissionCheckerTrait;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class InventoryControlController extends Controller
{
    use PermissionCheckerTrait;

    public function getRecords(Request $request)
    {
        try {
            // if (!$this->checkAccess(['invControlInbound', 'invControlOutbound'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $startDate = $request->input('start_date', '');
            $endDate = $request->input('end_date', '');
            $jobCode = $request->input('job_code', 'I');

            $partCode = $request->input('part_code', '');
            $partName = $request->input('part_name', '');
            $brand = $request->input('brand', '');
            $specification = $request->input('specification', '');
            $vendorCode = $request->input('vendor_code', '');
            $note = $request->input('note', '');
            $usedFlag = $request->input('used_flag', '0');
            $minusFlag = $request->input('minus_flag', '0');
            $orderFlag = $request->input('order_flag', '0');

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

            // Build the main query
            $query = DB::table('tbl_invrecord AS i')
                ->leftJoin('mas_machine AS m', 'i.machineno', '=', 'm.machineno')
                ->leftJoin('mas_employee as e', 'i.employeecode', '=', 'e.employeecode');

            // Add joins for minus_flag and order_flag
            if ($minusFlag === '1' || $orderFlag === '1') {
                $query->leftJoin('mas_inventory AS mi', 'i.partcode', '=', 'mi.partcode');
            }

            // Add join for minus_flag
            if ($minusFlag === '1') {
                $subQuery = DB::table('tbl_invrecord AS t')
                    ->select('t.partcode')
                    ->selectRaw('SUM(CASE WHEN t.jobcode = \'O\' THEN -t.quantity ELSE t.quantity END) as sum_quantity')
                    ->leftJoin('mas_inventory AS minv', 't.partcode', '=', 'minv.partcode')
                    ->where(
                        't.jobdate',
                        '>',
                        DB::raw('minv.laststockdate')
                    )
                    ->groupBy('t.partcode');

                $query->leftJoinSub($subQuery, 'gi', function ($join) {
                    $join->on('i.partcode', '=', 'gi.partcode');
                });
            }

            // Select fields
            $query->select(
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
                'e.employeename',
                'i.note',
                'i.vendorcode'
            );

            $query->whereBetween(
                'i.jobdate',
                [$startDate, $endDate]
            )
                ->where('i.jobcode', $jobCode);

            if (!empty($partCode)) {
                $query->where('i.partcode', 'ILIKE', '%' . $partCode . '%');
            }
            if (!empty($partName)) {
                $query->where('i.partname', 'ILIKE', '%' . $partName . '%');
            }
            if (!empty($brand)) {
                $query->where('i.brand', 'ILIKE', '%' . $brand . '%');
            }
            if (!empty($specification)) {
                $query->where('i.specification', 'ILIKE', '%' . $specification . '%');
            }
            if (!empty($vendorCode)) {
                $query->where('i.vendorcode', $vendorCode);
            }
            if (!empty($note)) {
                $query->where('i.note', 'ILIKE', '%' . $note . '%');
            }
            if ($usedFlag === '1') {
                $query->where('i.usedflag', 'O');
            }
            if ($minusFlag === '1') {
                $query->whereRaw('mi.minstock > (mi.laststocknumber + COALESCE(gi.sum_quantity, 0))');
            }
            if ($orderFlag === '1') {
                $query->whereRaw("COALESCE(mi.posentdate, '') <> ''");
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
            ], 400);
        }
    }

    public function getPartInfo(Request $request)
    {
        try {
            // if (!$this->checkAccess(['invControlInbound', 'invControlOutbound', 'pressShotPartList'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            // Get search parameters
            $search = $request->input('query', '');
            $partCode = $request->input('partCode');
            $partName = $request->input('partName');
            $vendor = $request->input('vendorcode');
            $currency = $request->input('currency');
            $spec = $request->input('spec');
            $brand = $request->input('brand');

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

            // Build the main query with the totalstock calculation
            $query = DB::table('mas_inventory as m')
                ->select(
                    'm.partcode',
                    'm.partname',
                    'm.specification',
                    'm.brand',
                    'm.vendorcode',
                    'm.unitprice',
                    'm.currency',
                    DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                    'm.minstock'
                )
                ->leftJoin(DB::raw('(
                    select
                        t.partcode,
                        sum(case when t.jobcode = \'O\' then -t.quantity else t.quantity end) as sum_quantity
                    from tbl_invrecord as t
                    left join mas_inventory as minv on t.partcode = minv.partcode
                    where t.jobdate > minv.laststockdate
                    group by t.partcode
                ) as gi'), 'm.partcode', '=', 'gi.partcode')
                ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
                ->where('m.status', '<>', 'D');

            // Apply filters
            if ($partCode) {
                $query->where('m.partcode', 'ILIKE', $partCode . '%');
            }

            if ($partName) {
                $query->where('m.partname', 'ILIKE', $partName . '%');
            }

            if ($vendor) {
                $query->where('m.vendorcode', $vendor);
            }

            if ($currency) {
                $query->where('m.currency', $currency);
            }

            if ($spec) {
                $query->where('m.specification', 'ILIKE', $spec . '%');
            }

            if ($brand) {
                $query->where('m.brand', 'ILIKE', $brand . '%');
            }

            // Apply sorting
            if ($sortBy) {
                // Handle the totalstock field separately since it's a calculated field
                if ($sortBy === 'totalstock') {
                    $query->orderByRaw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) ' . $sortDirection);
                } else {
                    // For other fields, prefix with table alias
                    $query->orderBy('m.' . $sortBy, $sortDirection);
                }
            } else {
                $query->orderBy('m.partcode', 'asc');
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
                'message' => 'An error occurred while fetching data',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getVendor(Request $request)
    {
        try {
            // if (!$this->checkAccess(['invControlInbound', 'invControlOutbound', 'masterDataPart', 'invControlPartList', 'invControlMasterPart'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

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
            ], 400); // Internal server error
        }
    }

    public function getStaff(Request $request)
    {
        try {
            // if (!$this->checkAccess(['invControlInbound', 'invControlOutbound', 'masterDataPart', 'invControlPartList', 'invControlMasterPart'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

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
            ], 400); // Internal server error
        }
    }

    public function getMachines(Request $request)
    {
        try {
            // if (!$this->checkAccess(['invControlInbound', 'invControlOutbound', 'masterDataPart', 'invControlPartList', 'invControlMasterPart'], 'view')) {
            //     return $this->unauthorizedResponse();
            // }

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
            ], 400); // Internal server error
        }
    }

    public function updateInventoryOutBound(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlOutbound'], 'update')) {
                return $this->unauthorizedResponse();
            }

            $validated = $request->validate([
                'record_id' => 'required|integer',
                'machine_no' => 'required|string',
                'machine_name' => 'required|string',
                'quantity' => 'required|numeric|min:1'
            ]);

            $result = DB::transaction(function () use ($validated) {
                $record = DB::table('tbl_invrecord')
                    ->where('recordid', $validated['record_id'])
                    ->first();

                if (!$record) {
                    throw new \Exception('Record not found');
                }

                // Step 1: Update the main record
                $updated = DB::table('tbl_invrecord')
                    ->where('recordid', $validated['record_id'])
                    ->update([
                        'machineno' => explode('|', $validated['machine_no'])[0],
                        'machinename' => $validated['machine_name'],
                        'quantity' => $validated['quantity'],
                        'total' => DB::raw('unitprice * ' . $validated['quantity']),
                        'updatetime' => now()
                    ]);

                // If record update fails, rollback everything
                if (!$updated) {
                    throw new \Exception('Failed to update record');
                }

                // Step 2: Check and update inventory if needed
                $recordPart = DB::table('mas_inventory')->where('partcode', $record->partcode)->first();

                if (!$recordPart) {
                    throw new \Exception('Inventory part not found');
                }

                // If the jobdate of the record is earlier than the laststockdate of the part
                if ((int)$record->jobdate <= (int)$recordPart->laststockdate) {
                    // newstock is calculated as the laststocknumber + previous quantity + new quantity
                    $newStock = (int)$recordPart->laststocknumber + (int)$record->quantity - (int)$validated['quantity'];

                    $updatedPart = DB::table('mas_inventory')
                        ->where('partcode', $record->partcode)
                        ->update([
                            'laststocknumber' => $newStock,
                        ]);

                    // If inventory update fails, rollback everything
                    if (!$updatedPart) {
                        throw new \Exception('Failed to update inventory stock');
                    }
                }

                // Return the updated record
                return DB::table('tbl_invrecord')
                    ->where('recordid', $validated['record_id'])
                    ->first();
            });

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the record',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function storeInvRecord(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlInbound', 'invControlOutbound', 'masterDataPart', 'invControlPartList', 'invControlMasterPart'], ['create', 'update'])) {
                return $this->unauthorizedResponse();
            }

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
            ], 400); // Internal server error
        }
    }

    public function deleteRecord(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlInbound', 'invControlOutbound'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            // Validate incoming data
            $request->validate([
                'record_id' => 'required|integer'
            ]);

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
            ], 400);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlInbound', 'invControlOutbound'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $filters = [
                'start_date' => $request->input('start_date', '20240417'),
                'end_date' => $request->input('end_date', '20240516'),
                'job_code' => $request->input('job_code', 'I'),
                'part_code' => $request->input('part_code', ''),
                'part_name' => $request->input('part_name', ''),
                'brand' => $request->input('brand', ''),
                'specification' => $request->input('specification', ''),
                'vendor_code' => $request->input('vendor_code', ''),
                'note' => $request->input('note', ''),
                'used_flag' => $request->input('used_flag', '0'),
                'minus_flag' => $request->input('minus_flag', '0'),
                'order_flag' => $request->input('order_flag', '0'),
            ];

            // Handle sorting
            $sortBy = $request->input('sortBy');
            if ($sortBy) {
                if (is_string($sortBy) && str_contains($sortBy, '{')) {
                    $filters['sortBy'] = json_decode($sortBy, true);
                } else {
                    $filters['sortBy'] = $sortBy;
                    $filters['sortDirection'] = $request->input('sortDirection', 'desc');
                }
            }

            return Excel::download(new InventoryControlExport($filters), 'inventory_records.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function exportStockReport(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

            return Excel::download(new StockReportExport(), 'StockTakenReport_' . date('Ymd') . '.xlsx');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate stock report',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function updateQuantity(Request $request)
    {
        try {
            if (!$this->checkAccess(['invControlPartList'], 'update')) {
                return $this->unauthorizedResponse();
            }

            // Create a temporary table with aggregated inventory movements
            DB::statement('
            CREATE TEMPORARY TABLE temp_inventory_movements AS
            SELECT 
                t.partcode,
                SUM(CASE 
                    WHEN t.jobcode = \'O\' THEN -t.quantity 
                    ELSE t.quantity 
                END) as total_movement
            FROM tbl_invrecord AS t
            JOIN mas_inventory AS m ON t.partcode = m.partcode
            WHERE t.jobdate > m.laststockdate
            GROUP BY t.partcode
        ');

            // Create an index on the temporary table
            DB::statement('CREATE INDEX idx_temp_movements_partcode ON temp_inventory_movements (partcode)');

            // Update using the temporary table
            $affected = DB::update('
            UPDATE mas_inventory
            SET 
                laststocknumber = mas_inventory.laststocknumber + COALESCE(tmp.total_movement, 0),
                laststockdate = ?
            FROM temp_inventory_movements AS tmp
            WHERE mas_inventory.partcode = tmp.partcode
        ', [date('Ymd')]);

            // Update records that don't have movements
            $affectedNoMovement = DB::update('
            UPDATE mas_inventory
            SET laststockdate = ?
            WHERE partcode NOT IN (SELECT partcode FROM temp_inventory_movements)
        ', [date('Ymd')]);

            $affected += $affectedNoMovement;

            // Drop the temporary table
            DB::statement('DROP TABLE IF EXISTS temp_inventory_movements');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated quantities',
                'affected_rows' => $affected
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            try {
                DB::statement('DROP TABLE IF EXISTS temp_inventory_movements');
            } catch (Exception $dropEx) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update quantities',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
