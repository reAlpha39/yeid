<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
                    'M.USEDFLAG',
                    'M.ADDRESS',
                    'M.UNITPRICE',
                    'M.CURRENCY',
                    DB::raw('M.LASTSTOCKNUMBER + ISNULL(GI.SUM_QUANTITY, 0) as TOTALSTOCK'),
                    'M.MINSTOCK',
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
}
