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
            $search = $request->input('search', '');
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
            $queryBuilder = DB::table('mas_inventory as m')
                ->select(
                    'm.partcode',
                    'm.partname',
                    'm.category',
                    'm.specification',
                    'm.brand',
                    'm.eancode',
                    'm.usedflag',
                    'm.vendorcode',
                    'm.address',
                    'm.unitprice',
                    'm.currency',
                    DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                    'm.minstock',
                    'm.minorder',
                    'm.orderpartcode',
                    'm.noorderflag',
                    'm.laststocknumber',
                    DB::raw("COALESCE(m.status, '-') as status"),
                    DB::raw("COALESCE(m.noorderflag, '0') as noorderflag"),
                    DB::raw("COALESCE(m.note, 'N/A') as note"),
                    DB::raw("COALESCE(m.reqquotationdate, ' ') as reqquotationdate"),
                    DB::raw("COALESCE(m.orderdate, ' ') as orderdate"),
                    DB::raw("COALESCE(m.posentdate, ' ') as posentdate"),
                    DB::raw("COALESCE(m.etddate, ' ') as etddate")
                )
                ->leftJoin(DB::raw('(
                        select
                            t.partcode,
                            sum(case
                                when t.jobcode = \'O\' then -t.quantity
                                when t.jobcode = \'I\' then t.quantity
                                when t.jobcode = \'A\' then t.quantity
                                else 0 end) as sum_quantity
                        from tbl_invrecord as t
                        left join mas_inventory as minv on t.partcode = minv.partcode
                        where t.updatetime > minv.updatetime
                        group by t.partcode
                    ) as gi'), 'm.partcode', '=', 'gi.partcode')
                ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
                ->where('m.status', '<>', 'D');

            // Apply search filters
            if ($search) {
                $queryBuilder->where(function ($q) use ($search) {
                    $q->where('m.partcode', 'ILIKE', $search . '%')
                        ->orWhere(DB::raw('upper(m.partname)'), 'ILIKE',  strtoupper($search) . '%');
                });
            }

            if (!empty($partCode)) {
                $queryBuilder->where(DB::raw('m.partcode', $partCode));
            }

            if (!empty($brand)) {
                $queryBuilder->where(DB::raw('upper(m.brand)'), 'ILIKE',  strtoupper($brand) . '%');
            }
            if ($usedFlag) {
                $queryBuilder->where('m.usedflag', 'O');
            }
            if (!empty($specification)) {
                $queryBuilder->where(DB::raw('upper(m.specification)'), 'ILIKE',  strtoupper($specification) . '%');
            }
            if (!empty($address)) {
                $queryBuilder->where(DB::raw('upper(m.address)'), 'ILIKE', $address . '%');
            }
            if (!empty($vendorCode)) {
                $queryBuilder->where(DB::raw('upper(m.vendorcode)'), 'ILIKE', strtoupper($vendorCode) . '%');
            }
            if (!empty($note)) {
                $queryBuilder->where(DB::raw('upper(m.note)'), 'ILIKE',  strtoupper($note) . '%');
            }
            if (in_array($category, ['M', 'F', 'J', 'O'])) {
                $queryBuilder->where('m.category', $category);
            }
            if (!empty($vendorNameCmb)) {
                $queryBuilder->where('m.vendorcode', $vendorNameCmb);
            } elseif (!empty($vendorNameText)) {
                $queryBuilder->where(DB::raw('upper(v.vendorname)'), 'ILIKE', strtoupper($vendorNameText) . '%');
            }
            if ($minusFlag) {
                $queryBuilder->where(DB::raw('m.minstock'), '>', DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'));
            }
            if ($orderFlag) {
                $queryBuilder->where(DB::raw('COALESCE(m.posentdate, \' \')'), '<>', ' ');
            }
            if ($maxRows > 0) {
                $queryBuilder->limit($maxRows);
            }
            $queryBuilder->orderBy('partcode');

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

            $queryBuilder = DB::table('mas_inventory')->select(
                'partcode'
            )->where(
                'partcode',
                '=',
                $partCode
            );

            $isEmpty = $queryBuilder->get();

            if ($isEmpty->isEmpty()) {
                // Insert into mas_inventory table
                DB::table('mas_inventory')->insert([
                    'partcode' => $partCode,
                    'partname' => $partName,
                    'category' => $category,
                    'specification' => $specification,
                    'eancode' => $eanCode,
                    'brand' => $brand,
                    'usedflag' => $usedFlag,
                    'locationid' => $locationId,
                    'address' => $address,
                    'vendorcode' => $vendorCode,
                    'unitprice' => $unitPrice,
                    'currency' => $currency,
                    'minstock' => $minStock,
                    'minorder' => $minOrder,
                    'note' => $note,
                    'laststocknumber' => $lastStockNumber,
                    'laststockdate' => $lastStockDate,
                    'status' => ' ',  // Hardcoded as chr(0) in the VB code
                    'orderpartcode' => $orderPartCode,
                    'noorderflag' => $noOrderFlag,
                    'updatetime' => $updateTime,  // Current timestamp
                ]);
            } else {
                DB::table('mas_inventory')
                    ->where('partcode', $partCode)
                    ->update([
                        'partname' => $partName,
                        'category' => $category,
                        'specification' => $specification,
                        'eancode' => $eanCode,
                        'brand' => $brand,
                        'usedflag' => $usedFlag,
                        'locationid' => $locationId,
                        'address' => $address,
                        'vendorcode' => $vendorCode,
                        'unitprice' => $unitPrice,
                        'currency' => $currency,
                        'minstock' => $minStock,
                        'minorder' => $minOrder,
                        'note' => $note,
                        'orderpartcode' => $orderPartCode,
                        'noorderflag' => $noOrderFlag,
                        'updatetime' => $updateTime,
                    ]);
            }

            // Delete MachineNo
            $affectedRows = DB::table('mas_invmachine')
                ->where('partcode', $partCode)
                ->delete();

            // Check if any rows were affected (i.e., if the delete was successful)
            if ($affectedRows > 0) {
                // console.log('')
            }

            $machines = $request->input('machines', []);
            // 
            foreach ($machines as $machine) {
                $machineNo = $machine['machine_no'];

                $rows = DB::table('mas_invmachine')->select(
                    'machineno'
                )
                    ->where('partcode', $partCode)
                    ->where('machineno',  $machineNo)
                    ->limit(1)
                    ->get();

                if ($rows->isEmpty()) {
                    DB::table('mas_invmachine')->insert([
                        'partcode' => $partCode,
                        'machineno' => $machineNo,
                        'updatetime' => $updateTime,
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
            // Get the target part_code from the request
            $partCode = $request->input('part_code');

            // Perform the delete operation on mas_inventory
            $affectedRows = DB::table('mas_inventory')
                ->where('partcode', '=', $partCode)
                ->delete();

            // Delete related records from mas_invmachine
            $affectedRows += DB::table('mas_invmachine')
                ->where('partcode', '=', $partCode)
                ->delete();

            if ($affectedRows > 0) {
                // Return success response if deletion was successful
                return response()->json([
                    'success' => true,
                    'message' => 'Part deleted successfully.'
                ], 200);
            } else {
                // Return failure response if no record was found
                return response()->json([
                    'success' => false,
                    'message' => 'Part not found or already deleted.'
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
