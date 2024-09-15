<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getVendor(Request $request)
    {
        // Get the query parameter from the request
        $query = $request->input('query', '');

        // Perform the query on the mas_inventory table
        $results = DB::table('HOZENADMIN.mas_inventory')
            ->where('PARTCODE', 'like', $query . '%')
            ->orWhere('PARTNAME', 'like', $query . '%')
            ->get();

        // Return the results as JSON
        return response()->json(['data' => $results]);
    }
}
