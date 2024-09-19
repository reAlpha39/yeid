<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class MasterPartController extends Controller
{
    public function getMasterPartList(Request $request)
    {
        try {
            // Retrieve search parameters from the request
            $partCode = $request->input('part_code', '');
            $partName = $request->input('part_name', '');
            $brand = $request->input('brand', '');
            $usedFlag = $request->input('used_flag', false);
            $specification = $request->input('specification', '');
            $address = $request->input('address', '');
            $vendorCode = $request->input('vendor_code', '');
            $note = $request->input('note', '');
            $category = $request->input('category', '');
            $vendorNameCmb = $request->input('vendor_name_cmb', '');
            $vendorNameText = $request->input('vendor_name_text', '');
            $minusFlag = $request->input('minus_flag', false);
            $orderFlag = $request->input('order_flag', false);
            $maxRows = $request->input('max_rows', 0);

            // Build the query
            $queryBuilder = DB::table('HOZENADMIN.MAS_INVENTORY as M')
                ->select(
                    'M.PARTCODE',
                    'M.PARTNAME',
                    'M.CATEGORY',
                    'M.SPECIFICATION',
                    'M.BRAND',
                    'M.EANCODE',
                    'M.USEDFLAG',
                    'M.VENDORCODE',
                    'M.ADDRESS',
                    'M.UNITPRICE',
                    'M.CURRENCY',
                    DB::raw('M.LASTSTOCKNUMBER + ISNULL(GI.SUM_QUANTITY, 0) as TOTALSTOCK'),
                    'M.MINSTOCK',
                    'M.MINORDER',
                    'M.ORDERPARTCODE',
                    'M.NOORDERFLAG',
                    'M.LASTSTOCKNUMBER',
                    DB::raw("ISNULL(M.STATUS, '-') as STATUS"),
                    DB::raw("ISNULL(M.NOORDERFLAG, '0') as NOORDERFLAG"),
                    DB::raw("ISNULL(M.NOTE, 'N/A') as NOTE"),
                    DB::raw("ISNULL(M.REQQUOTATIONDATE, ' ') as REQQUOTATIONDATE"),
                    DB::raw("ISNULL(M.ORDERDATE, ' ') as ORDERDATE"),
                    DB::raw("ISNULL(M.POSENTDATE, ' ') as POSENTDATE"),
                    DB::raw("ISNULL(M.ETDDATE, ' ') as ETDDATE")
                )
                ->leftJoin(DB::raw('(select
                T.PARTCODE,
                sum(case when T.JOBCODE = \'O\' then -T.QUANTITY else T.QUANTITY end) as SUM_QUANTITY
                from HOZENADMIN.TBL_INVRECORD as T
                left join HOZENADMIN.MAS_INVENTORY as MINV on T.PARTCODE = MINV.PARTCODE
                where T.JOBDATE > MINV.LASTSTOCKDATE
                group by T.PARTCODE
            ) as GI'), 'M.PARTCODE', '=', 'GI.PARTCODE')
                ->leftJoin('HOZENADMIN.MAS_VENDOR as V', 'M.VENDORCODE', '=', 'V.VENDORCODE')
                ->where('M.STATUS', '<>', 'D');

            // Apply search filters
            if (!empty($partCode)) {
                $queryBuilder->where('M.PARTCODE', 'like', $partCode . '%');
            }
            if (!empty($partName)) {
                $queryBuilder->where(DB::raw('upper(M.PARTNAME)'), 'like', '%' . strtoupper($partName) . '%');
            }
            if (!empty($brand)) {
                $queryBuilder->where(DB::raw('upper(M.BRAND)'), 'like', '%' . strtoupper($brand) . '%');
            }
            if ($usedFlag) {
                $queryBuilder->where('M.USEDFLAG', 'O');
            }
            if (!empty($specification)) {
                $queryBuilder->where(DB::raw('upper(M.SPECIFICATION)'), 'like', '%' . strtoupper($specification) . '%');
            }
            if (!empty($address)) {
                $queryBuilder->where(DB::raw('upper(M.ADDRESS)'), 'like', $address . '%');
            }
            if (!empty($vendorCode)) {
                $queryBuilder->where(DB::raw('upper(M.VENDORCODE)'), 'like', '%' . strtoupper($vendorCode) . '%');
            }
            if (!empty($note)) {
                $queryBuilder->where(DB::raw('upper(M.NOTE)'), 'like', '%' . strtoupper($note) . '%');
            }
            if (in_array($category, ['M', 'F', 'J', 'O'])) {
                $queryBuilder->where('M.CATEGORY', $category);
            }
            if (!empty($vendorNameCmb)) {
                $queryBuilder->where('M.VENDORCODE', $vendorNameCmb);
            } elseif (!empty($vendorNameText)) {
                $queryBuilder->where(DB::raw('upper(V.VENDORNAME)'), 'like', '%' . strtoupper($vendorNameText) . '%');
            }
            if ($minusFlag) {
                $queryBuilder->where(DB::raw('M.MINSTOCK'), '>', DB::raw('M.LASTSTOCKNUMBER + ISNULL(GI.SUM_QUANTITY, 0)'));
            }
            if ($orderFlag) {
                $queryBuilder->where(DB::raw('ISNULL(M.POSENTDATE, \' \')'), '<>', ' ');
            }
            if ($maxRows > 0) {
                $queryBuilder->limit($maxRows);
            }
            $queryBuilder->orderBy('PARTCODE');

            // Execute the query and get results
            $results = $queryBuilder->get();

            // Return the results as JSON
            return response()->json([
                'success' => true,
                'data' => $results
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

    public function addMasterPart(Request $request)
    {
        try {
            // Validate the input
            $request->validate([
                'part_code' => 'required',
                'part_name' => 'required',
                'category' => 'required|in:M,F,J,O',
                'specification' => 'nullable|string',
                'ean_code' => 'nullable|string',
                'brand' => 'required|string',
                'used_flag' => 'required|boolean',
                'location_id' => 'nullable|string',
                'address' => 'required|string',
                'vendor_code' => 'nullable|string',
                'unit_price' => 'required|numeric',
                'currency' => 'required|string',
                'min_stock' => 'required|numeric',
                'min_order' => 'required|numeric',
                'note' => 'nullable|string',
                'order_part_code' => 'nullable|string',
                'no_order_flag' => 'required|boolean',
            ]);

            // Retrieve data from the request
            $partCode = $request->input('part_code');
            $partName = $request->input('part_name');
            $category = $request->input('category', 'O');  // Default to 'O' if not provided
            $specification = $request->input('specification');
            $eanCode = $request->input('ean_code') ?? str_pad('', 13);
            $brand = $request->input('brand');
            $usedFlag = $request->input('used_flag', false) ? 'O' : ' ';
            $locationId = $request->input('location_id', 'P');
            $address = $request->input('address');
            $vendorCode = $request->input('vendor_code', null);
            $unitPrice = $request->input('unit_price', 0);
            $currency = $request->input('currency');
            $minStock = $request->input('min_stock', 0);
            $minOrder = $request->input('min_order', 0);
            $note = $request->input('note', '');
            $lastStockNumber = $request->input('last_stock_number', 0);
            $lastStockDate = Carbon::now()->format('Ymd');
            $orderPartCode = $request->input('order_part_code', null);
            $noOrderFlag = $request->input('no_order_flag', false) ? '1' : '0';
            $updateTime = Carbon::now();


            $queryBuilder = DB::table('HOZENADMIN.MAS_INVENTORY')->select(
                'PARTCODE'
            )->where(
                'PARTCODE',
                '=',
                $partCode
            );

            $isEmpty = $queryBuilder->get();

            if ($isEmpty->isEmpty()) {
                // Insert into MAS_INVENTORY table
                DB::table('HOZENADMIN.MAS_INVENTORY')->insert([
                    'PARTCODE' => $partCode,
                    'PARTNAME' => $partName,
                    'CATEGORY' => $category,
                    'SPECIFICATION' => $specification,
                    'EANCODE' => $eanCode,
                    'BRAND' => $brand,
                    'USEDFLAG' => $usedFlag,
                    'LOCATIONID' => 'P',
                    'LOCATIONID' => $locationId,
                    'ADDRESS' => $address,
                    'VENDORCODE' => $vendorCode,
                    'UNITPRICE' => $unitPrice,
                    'CURRENCY' => $currency,
                    'MINSTOCK' => $minStock,
                    'MINORDER' => $minOrder,
                    'NOTE' => $note,
                    'LASTSTOCKNUMBER' => $lastStockNumber,
                    'LASTSTOCKDATE' => $lastStockDate,
                    'STATUS' => ' ',  // Hardcoded as chr(0) in the VB code, using empty string for SQL Server
                    'ORDERPARTCODE' => $orderPartCode,
                    'NOORDERFLAG' => $noOrderFlag,
                    'UPDATETIME' => $updateTime,  // Current timestamp
                ]);
            } else {
                DB::table('HOZENADMIN.MAS_INVENTORY')
                    ->where('PARTCODE', $partCode)
                    ->update([
                        'PARTNAME' => $partName,
                        'CATEGORY' => $category,
                        'SPECIFICATION' => $specification,
                        'EANCODE' => $eanCode,
                        'BRAND' => $brand,
                        'USEDFLAG' => $usedFlag,
                        'LOCATIONID' => 'P',
                        'ADDRESS' => $address,
                        'VENDORCODE' => $vendorCode,
                        'UNITPRICE' => $unitPrice,
                        'CURRENCY' => $currency,
                        'MINSTOCK' => $minStock,
                        'MINORDER' => $minOrder,
                        'NOTE' => $note,
                        'ORDERPARTCODE' => $orderPartCode,
                        'NOORDERFLAG' => $noOrderFlag,
                        'UPDATETIME' => $updateTime,
                    ]);
            }

            // 
            // Delete MachineNo
            // 
            $affectedRows = DB::table('HOZENADMIN.MAS_INVMACHINE')
                ->where('PARTCODE', $partCode)
                ->delete();

            // Check if any rows were affected (i.e., if the delete was successful)
            if ($affectedRows > 0) {
                // console.log('')
            }

            $machines = $request->input('machines', []);
            // 
            foreach ($machines as $machine) {
                $machineNo = $machine['machine_no'];

                $rows = DB::table('HOZENADMIN.MAS_INVMACHINE')->select(
                    'MACHINENO'
                )
                    ->where('PARTCODE', $partCode)
                    ->where('MACHINENO',  $machineNo)
                    ->limit(1)
                    ->get();

                if ($rows->isEmpty()) {
                    DB::table('HOZENADMIN.MAS_INVMACHINE')->insert([
                        'PARTCODE' => $partCode,
                        'MACHINENO' => $machineNo,
                        'UPDATETIME' => $updateTime,
                    ]);
                }
            }


            return response()->json([
                'success' => true,
                'data' => [
                    'is_update' => $isEmpty->isNotEmpty()
                ],
                'message' => 'Inventory record inserted successfully!'
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

    public function deleteMasterPart(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'part_code' => 'required|string'
        ]);

        try {
            // Get the target PARTCODE from the request
            $partCode = $request->input('part_code');

            // Perform the delete operation
            $affectedRows = DB::table('HOZENADMIN.MAS_INVENTORY')
                ->where('PARTCODE', '=', $partCode)
                ->delete();

            // Delete MachineNo
            $affectedRows = DB::table('HOZENADMIN.MAS_INVMACHINE')
                ->where('PARTCODE', '=', $partCode)
                ->delete();

            if ($affectedRows > 0) {
                // Return success response if deletion was successful
                return response()->json([
                    'status' => 'success',
                    'message' => 'Part deleted successfully.'
                ], 200);
            } else {
                // Return failure response if no record was found
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Part not found or already deleted.'
                ], 404);
            }
        } catch (Exception $e) {
            // Handle any potential errors
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
