<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RequestWorkshopController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|string',
                'year' => 'nullable|string',
                'only_active' => 'nullable|boolean',
                'shop_code' => 'nullable|string',
                'employee_code' => 'nullable|string',
            ]);

            $search = $request->input('search');
            $year = $request->input('year');
            $onlyActive = $request->input('only_active');
            $shopCode = $request->input('shop_code');
            $employeeCode = $request->input('employee_code');

            $query = DB::table('tbl_wsrrecord')
                ->select(
                    'wsrid',
                    'status',
                    'requestdate',
                    'shopname',
                    'ordername',
                    'title',
                    'reqfinishdate',
                    'asapflag',
                    'deliveryplace',
                    'employeename',
                    'finishdate',
                    'note',
                    'inspector'
                )
                ->where('requestdate', 'ILIKE', $year . '%');

            if ($onlyActive) {
                $query->where('status', 'R');
            }

            if ($shopCode) {
                $query->where('shopcode', $shopCode);
            }

            if ($employeeCode) {
                $query->where('employeecode', $employeeCode);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'ILIKE',  $search . '%')
                        ->orWhere('wsrid', 'ILIKE', $search . '%')
                        ->orWhere('ordername', 'ILIKE', $search . '%')
                        ->orWhere('employeename', 'ILIKE', $search . '%')
                        ->orWhere('shopname', 'ILIKE', $search . '%');
                });
            }

            $results = $query->orderBy('wsrid', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($wsrid)
    {
        try {
            $result = DB::table('tbl_wsrrecord')
                ->where('wsrid', $wsrid)
                ->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $newWsrId = DB::select("SELECT nextval('seq_wsr') as wsrid")[0]->wsrid;

            $newRecordId =
                DB::table('tbl_wsrrecord')->insertGetId([
                    'wsrid' => $newWsrId,
                    'requestdate' => $request->input('requestdate'),
                    'employeecode' => $request->input('employeecode'),
                    'employeename' => $request->input('employeename'),
                    'shopcode' => $request->input('shopcode'),
                    'shopname' => $request->input('shopname'),
                    'title' => $request->input('title'),
                    'reason' => $request->input('reason'),
                    'reqfinishdate' => $request->input('reqfinishdate'),
                    'asapflag' => $request->input('asapflag'),
                    'deliveryplace' => $request->input('deliveryplace'),
                    'note' => $request->input('note'),
                    'finishdate' => $request->input('finishdate'),
                    'inspector' => $request->input('inspector'),
                    'ordername' => $request->input('ordername'),
                    'status' => $request->input('status') ?? 'R',
                    'staffnames' => $request->input('staffnames'),
                    'updatetime' => now()
                ], 'wsrid');

            DB::commit();

            return response()->json([
                'success' => false,
                'message' => 'Record created successfully',

            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $wsrid)
    {
        try {
            // Validate the request data
            $request->validate([
                'requestdate' => 'required|date',
                'employeecode' => 'required|string',
                'employeename' => 'required|string',
                'shopcode' => 'required|string',
                'shopname' => 'required|string',
                'title' => 'nullable|string',
                'reason' => 'nullable|string',
                'reqfinishdate' => 'nullable|string',
                'asapflag' => 'required|string',
                'deliveryplace' => 'nullable|string',
                'note' => 'nullable|string',
                'finishdate' => 'nullable|date',
                'inspector' => 'nullable|string',
                'status' => 'nullable|string',
                'ordername' => 'nullable|string',
                'staffnames' => 'nullable|string',
            ]);

            // Prepare the data for updating
            $data = [
                'requestdate' => $request->input('requestdate'),
                'employeecode' => $request->input('employeecode'),
                'employeename' => $request->input('employeename'),
                'shopcode' => $request->input('shopcode'),
                'shopname' => $request->input('shopname'),
                'title' => $request->input('title') ?? '',
                'reason' => $request->input('reason') ?? '',
                'reqfinishdate' => $request->input('reqfinishdate') ?? null,
                'asapflag' => $request->input('asapflag'),
                'deliveryplace' => $request->input('deliveryplace') ?? '',
                'note' => $request->input('note') ?? '',
                'finishdate' => $request->input('finishdate') ?? null,
                'inspector' => $request->input('inspector') ?? '',
                'status' => $request->input('status') ?? 'R',
                'ordername' => $request->input('ordername') ?? '',
                'staffnames' => $request->input('staffnames') ?? '',
                'updatetime' => now(),
            ];

            $affectedRows = DB::table('tbl_wsrrecord')
                ->where('wsrid', $wsrid)
                ->update($data);

            if ($affectedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found or no changes made'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($wsrid)
    {
        DB::beginTransaction();

        try {
            DB::table('tbl_wsrrecord')
                ->where('wsrid', $wsrid)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully!'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}