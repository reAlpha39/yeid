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
            // Base SQL query
            $sql = "SELECT
                        S.RECORDID,
                        S.MAINTENANCECODE,
                        S.ORDERDATETIME,
                        S.ORDEREMPNAME,
                        S.ORDERSHOP,
                        S.MACHINENO,
                        M.MACHINENAME,
                        S.ORDERTITLE,
                        S.ORDERFINISHDATE,
                        S.ORDERJOBTYPE,
                        S.ORDERQTTY,
                        S.ORDERSTOPTIME,
                        S.UPDATETIME,
                        ISNULL(S.PLANID, 0) AS PLANID,
                        ISNULL(S.APPROVAL, 0) AS APPROVAL,
                        ISNULL(S.CREATEEMPCODE, '') AS CREATEEMPCODE,
                        ISNULL(S.CREATEEMPNAME, '') AS CREATEEMPNAME
                    FROM HOZENADMIN.TBL_SPKRECORD S
                    LEFT JOIN HOZENADMIN.MAS_MACHINE M ON S.MACHINENO = M.MACHINENO
                    WHERE 1=1";

            // Apply filters
            if ($request->input('only_active') == 'true') {
                $sql .= " AND ISNULL(S.APPROVAL, 0) < 119";
            }

            // Implement search across multiple fields
            if ($request->input('search')) {
                $search = $request->input('search');
                $sql .= " AND (S.RECORDID LIKE '$search%'
                    OR S.MAINTENANCECODE LIKE '$search%'
                    OR S.ORDEREMPNAME LIKE '$search%'
                    OR S.ORDERSHOP LIKE '$search%'
                    OR S.MACHINENO LIKE '$search%'
                    OR M.MACHINENAME LIKE '$search%'
                    OR S.ORDERTITLE LIKE '$search%')";
            }

            // Order by RECORDID descending
            $sql .= " ORDER BY S.RECORDID DESC";

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
            $maxRecordId = DB::table('HOZENADMIN.TBL_SPKRECORD')
                ->max('RECORDID');

            $newRecordId = $maxRecordId ? $maxRecordId + 1 : 1;

            DB::table('HOZENADMIN.TBL_SPKRECORD')->insert([
                'RECORDID' => $newRecordId,
                'MAINTENANCECODE' => $request->input('MAINTENANCECODE'),
                'ORDERDATETIME' => $request->input('ORDERDATETIME'),
                'ORDEREMPCODE' => $request->input('ORDEREMPCODE'),
                'ORDEREMPNAME' => $request->input('ORDEREMPNAME'),
                'ORDERSHOP' => $request->input('ORDERSHOP'),
                'MACHINENO' => $request->input('MACHINENO'),
                'MACHINENAME' => $request->input('MACHINENAME'),
                'ORDERTITLE' => $request->input('ORDERTITLE'),
                'ORDERFINISHDATE' => $request->input('ORDERFINISHDATE'),
                'ORDERJOBTYPE' => $request->input('ORDERJOBTYPE'),
                'ORDERQTTY' => $request->input('ORDERQTTY'),
                'ORDERSTOPTIME' => $request->input('ORDERSTOPTIME'),
                'PLANID' => $request->input('PLANID'),
                'APPROVAL' => $request->input('APPROVAL'),
                'UPDATETIME' => now(),
                'OCCURDATE' => $request->input('OCCURDATE'),
                'ANALYSISQUARTER' => $request->input('ANALYSISQUARTER'),
                'ANALYSISHALF' => $request->input('ANALYSISHALF'),
                'ANALYSISTERM' => $request->input('ANALYSISTERM'),
                'CREATEEMPCODE' => $request->input('CREATEEMPCODE'),
                'CREATEEMPNAME' => $request->input('CREATEEMPNAME'),

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
                    S.RECORDID,
                    S.MAINTENANCECODE,
                    S.ORDERDATETIME,
                    S.ORDEREMPCODE,
                    S.ORDEREMPNAME,
                    S.ORDERSHOP,
                    S.MACHINENO,
                    M.MACHINENAME,
                    M.PLANTCODE,
                    M.SHOPCODE,
                    M.LINECODE,
                    M.MODELNAME,
                    M.MAKERNAME,
                    M.SERIALNO,
                    M.INSTALLDATE,
                    S.ORDERTITLE,
                    ISNULL(S.ORDERFINISHDATE, '') AS ORDERFINISHDATE,
                    S.ORDERJOBTYPE,
                    S.ORDERQTTY,
                    S.ORDERSTOPTIME,
                    ISNULL(S.APPROVAL, 0) AS APPROVAL, 
                    (SELECT SHOPNAME FROM HOZENADMIN.MAS_SHOP WHERE SHOPCODE = M.SHOPCODE) AS SHOPNAME,
                    ISNULL(S.CREATEEMPCODE, '') AS CREATEEMPCODE,
                    ISNULL(S.CREATEEMPNAME, '') AS CREATEEMPNAME,
                    S.UPDATETIME
                FROM
                    HOZENADMIN.TBL_SPKRECORD S
                LEFT JOIN
                    HOZENADMIN.MAS_MACHINE M ON S.MACHINENO = M.MACHINENO
                WHERE 
                    S.RECORDID = :spkNo
                ";

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
            $mainteCode = $request->input('MAINTENANCECODE');
            $orderDate = $request->input('ORDERDATETIME'); // Expecting in 'Y-m-d H:i' format
            $orderEmployeeName = $request->input('ORDEREMPNAME', '');
            $orderShop = $request->input('ORDERSHOP', '');
            $machineNo = $request->input('MACHINENO', '');
            $machineName = $request->input('MACHINENAME', '');
            $orderTitle = $request->input('ORDERTITLE', '');
            $orderFinishDate = $request->input('ORDERFINISHDATE', null); // Nullable
            $orderJobType = $request->input('ORDERJOBTYPE', '');
            $orderQtty = $request->input('ORDERQTTY', 0);
            $orderStopTime = $request->input('ORDERSTOPTIME', null); // Nullable
            $approval = $request->input('APPROVAL', null);
            $analysisQuarter = $request->input('ANALYSISQUARTER', '');
            $analysisHalf = $request->input('ANALYSISHALF', '');
            $analysisTerm = $request->input('ANALYSISTERM', '');

            // Update query
            $updateQuery = "UPDATE HOZENADMIN.TBL_SPKRECORD
                            SET MAINTENANCECODE = ?,
                                ORDERDATETIME = ?,
                                ORDEREMPNAME = ?,
                                ORDERSHOP = ?,
                                MACHINENO = ?,
                                MACHINENAME = ?,
                                ORDERTITLE = ?,
                                ORDERFINISHDATE = ?,
                                ORDERJOBTYPE = ?,
                                ORDERQTTY = ?,
                                ORDERSTOPTIME = ?,
                                APPROVAL = ?,
                                UPDATETIME = GETDATE(),
                                OCCURDATE = ?,
                                ANALYSISQUARTER = ?,
                                ANALYSISHALF = ?,
                                ANALYSISTERM = ?
                            WHERE RECORDID = ?";

            // Execute update query
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
            $deletedRows = DB::table('HOZENADMIN.TBL_SPKRECORD')
                ->where('RECORDID', $recordId)
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
