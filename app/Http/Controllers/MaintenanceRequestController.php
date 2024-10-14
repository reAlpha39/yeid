<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Base SQL query for PostgreSQL
            $sql = "SELECT
                    s.recordid,
                    s.maintenancecode,
                    s.orderdatetime,
                    s.orderempname,
                    s.ordershop,
                    s.machineno,
                    m.machinename,
                    s.ordertitle,
                    s.orderfinishdate,
                    s.orderjobtype,
                    s.orderqtty,
                    s.orderstoptime,
                    s.updatetime,
                    COALESCE(s.planid, 0) AS planid,
                    COALESCE(s.approval, 0) AS approval,
                    COALESCE(s.createempcode, '') AS createempcode,
                    COALESCE(s.createempname, '') AS createempname
                FROM tbl_spkrecord s
                LEFT JOIN mas_machine m ON s.machineno = m.machineno
                WHERE 1=1";

            // Apply filters
            if ($request->input('only_active') == 'true') {
                $sql .= " AND COALESCE(s.approval, 0) < 119";
            }

            // Implement search across multiple fields
            if ($request->input('search')) {
                $search = $request->input('search');
                $sql .= " AND (s.recordid LIKE '$search%'
                OR s.maintenancecode LIKE '$search%'
                OR s.orderempname LIKE '$search%'
                OR s.ordershop LIKE '$search%'
                OR s.machineno LIKE '$search%'
                OR m.machinename LIKE '$search%'
                OR s.ordertitle LIKE '$search%')";
            }

            // Order by recordid descending
            $sql .= " ORDER BY s.recordid DESC";

            // Execute the query
            $results = DB::select($sql);

            // Return the data
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

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $maxRecordId = DB::table('tbl_spkrecord')
                ->max('recordid');

            $newRecordId = $maxRecordId ? $maxRecordId + 1 : 1;

            DB::table('tbl_spkrecord')->insert([
                'recordid' => $newRecordId,
                'maintenancecode' => $request->input('maintenancecode'),
                'orderdatetime' => $request->input('orderdatetime'),
                'orderempcode' => $request->input('orderempcode'),
                'orderempname' => $request->input('orderempname'),
                'ordershop' => $request->input('ordershop'),
                'machineno' => $request->input('machineno'),
                'machinename' => $request->input('machinename'),
                'ordertitle' => $request->input('ordertitle'),
                'orderfinishdate' => $request->input('orderfinishdate'),
                'orderjobtype' => $request->input('orderjobtype'),
                'orderqtty' => $request->input('orderqtty'),
                'orderstoptime' => $request->input('orderstoptime'),
                'planid' => $request->input('planid'),
                'approval' => $request->input('approval'),
                'updatetime' => now(),
                'occurdate' => $request->input('occurdate'),
                'analysisquarter' => $request->input('analysisquarter'),
                'analysishalf' => $request->input('analysishalf'),
                'analysisterm' => $request->input('analysisterm'),
                'createempcode' => $request->input('createempcode'),
                'createempname' => $request->input('createempname'),

            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully'
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

    public function show($spkNo)
    {
        try {
            // Prepare the SQL query
            $sql = "SELECT
                s.recordid,
                s.maintenancecode,
                s.orderdatetime,
                s.orderempcode,
                s.orderempname,
                s.ordershop,
                s.machineno,
                m.machinename,
                m.plantcode,
                m.shopcode,
                m.linecode,
                m.modelname,
                m.makername,
                m.serialno,
                m.installdate,
                s.ordertitle,
                COALESCE(s.orderfinishdate, '') AS orderfinishdate,
                s.orderjobtype,
                s.orderqtty,
                s.orderstoptime,
                COALESCE(s.approval, 0) AS approval,
                (SELECT shopname FROM mas_shop WHERE shopcode = m.shopcode) AS shopname,
                COALESCE(s.createempcode, '') AS createempcode,
                COALESCE(s.createempname, '') AS createempname,
                s.updatetime
            FROM
                tbl_spkrecord s
            LEFT JOIN
                mas_machine m ON s.machineno = m.machineno
            WHERE
                s.recordid = :spkNo";

            $data = DB::select($sql, ['spkNo' => $spkNo]);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            $record = $data[0];

            return response()->json([
                'success' => true,
                'data' => $record,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $recordId)
    {
        try {
            // Get input data from request
            $mainteCode = $request->input('maintenancecode');
            $orderDate = $request->input('orderdatetime'); // Expecting in 'Y-m-d H:i' format
            $orderEmployeeName = $request->input('orderempname', '');
            $orderShop = $request->input('ordershop', '');
            $machineNo = $request->input('machineno', '');
            $machineName = $request->input('machinename', '');
            $orderTitle = $request->input('ordertitle', '');
            $orderFinishDate = $request->input('orderfinishdate', null); // Nullable
            $orderJobType = $request->input('orderjobtype', '');
            $orderQtty = $request->input('orderqtty', 0);
            $orderStopTime = $request->input('orderstoptime', null); // Nullable
            $approval = $request->input('approval', null);
            $analysisQuarter = $request->input('analysisquarter', '');
            $analysisHalf = $request->input('analysishalf', '');
            $analysisTerm = $request->input('analysisterm', '');

            // Update query for PostgreSQL
            $updateQuery = "UPDATE tbl_spkrecord
                        SET maintenancecode = $1,
                            orderdatetime = $2,
                            orderempname = $3,
                            ordershop = $4,
                            machineno = $5,
                            machinename = $6,
                            ordertitle = $7,
                            orderfinishdate = $8,
                            orderjobtype = $9,
                            orderqtty = $10,
                            orderstoptime = $11,
                            approval = $12,
                            updatetime = NOW(),
                            occurdate = $13,
                            analysisquarter = $14,
                            analysishalf = $15,
                            analysisterm = $16
                        WHERE recordid = $17";

            // Execute update query with PostgreSQL syntax
            DB::update($updateQuery, [
                $mainteCode,
                $orderDate,
                $orderEmployeeName,
                $orderShop,
                $machineNo,
                $machineName,
                $orderTitle,
                $orderFinishDate,
                $orderJobType,
                $orderQtty,
                $orderStopTime,
                $approval,
                date('Ymd', strtotime($orderDate)), // OCCURDATE
                $analysisQuarter,
                $analysisHalf,
                $analysisTerm,
                $recordId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record successfully updated',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($recordId)
    {
        DB::beginTransaction();

        try {
            $deletedRows = DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Record not found'
                ], 404);
            }

            DB::commit();

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully',
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
