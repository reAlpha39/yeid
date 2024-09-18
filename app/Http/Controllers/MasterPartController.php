<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasterPartController extends Controller
{
    public function getMasterPartList(Request $request)
    {
        try {
            // Get the optional query parameter from the request
            $part = $request->input('part', '');
            $category = $request->input('category', '');

            // Start building the query for the mas_inventory table
            $queryBuilder = DB::table('HOZENADMIN.mas_inventory');


            $queryBuilder->select(
                'PARTCODE',
                'PARTNAME',
                'CATEGORY',
                'LASTSTOCKNUMBER',
                'MINSTOCK',
                'CURRENCY',
                'UNITPRICE',
            )->where('PARTCODE', 'like', $part . '%')
                ->orWhere('PARTNAME', 'like', $part . '%')
                ->where('CATEGORY', $category);

            // Execute the query and get the results
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
