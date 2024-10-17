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

    public function indexWork($recordId)
    {
        try {
            $results = DB::table('tbl_work')
                ->select(
                    'workid',
                    'staffname',
                    'inactivetime',
                    'periodicaltime',
                    'questiontime',
                    'preparetime',
                    'checktime',
                    'waittime',
                    'repairtime',
                    'confirmtime'
                )
                ->where('recordid', $recordId)
                ->orderBy('workid')
                ->get()
                ->map(function ($row) {
                    $row->inactivetime = (int) $row->inactivetime;
                    $row->periodicaltime = (int) $row->periodicaltime;
                    $row->questiontime = (int) $row->questiontime;
                    $row->preparetime = (int) $row->preparetime;
                    $row->checktime = (int) $row->checktime;
                    $row->waittime = (int) $row->waittime;
                    $row->repairtime = (int) $row->repairtime;
                    $row->confirmtime = (int) $row->confirmtime;
                    return $row;
                });

            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records found'
                ], 404);
            }

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

    public function indexPart($recordId)
    {
        try {
            $parts = DB::table('tbl_part')
                ->select(
                    'partid',
                    'partcode',
                    'partname',
                    'specification',
                    'brand',
                    'qtty',
                    'price',
                    'currency',
                    'isstock'
                )
                ->where('recordid', $recordId)
                ->orderBy('partid')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parts
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
                s.*,
                m.machinename,
                m.plantcode,
                m.shopcode,
                m.linecode,
                m.modelname,
                -- m.makername,  -- use s.makername instead
                m.serialno,
                m.installdate,
                COALESCE(s.orderfinishdate, '') AS orderfinishdate,
                COALESCE(s.approval, 0) AS approval,
                COALESCE(s.createempcode, '') AS createempcode,
                COALESCE(s.createempname, '') AS createempname,
                (SELECT shopname FROM mas_shop WHERE shopcode = m.shopcode) AS shopname
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
            $orderStopTime = $request->input('orderstoptime', 0); // Nullable
            $approval = $request->input('approval', null);
            $analysisQuarter = $request->input('analysisquarter', '');
            $analysisHalf = $request->input('analysishalf', '');
            $analysisTerm = $request->input('analysisterm', '');

            // Perform the update query
            DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->update([
                    'maintenancecode' => $mainteCode,
                    'orderdatetime' => $orderDate,
                    'orderempname' => $orderEmployeeName,
                    'ordershop' => $orderShop,
                    'machineno' => $machineNo,
                    'machinename' => $machineName,
                    'ordertitle' => $orderTitle,
                    'orderfinishdate' => $orderFinishDate,
                    'orderjobtype' => $orderJobType,
                    'orderqtty' => $orderQtty,
                    'orderstoptime' => $orderStopTime,
                    'approval' => $approval,
                    'occurdate' => date('Ymd', strtotime($orderDate)), // OCCURDATE
                    'analysisquarter' => $analysisQuarter,
                    'analysishalf' => $analysisHalf,
                    'analysisterm' => $analysisTerm,
                    'updatetime' => NOW()
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

    public function updateReport(Request $request, $recordId)
    {
        DB::beginTransaction();

        try {
            $startDateTime = $request->input('startdatetime'); // Assumed to be in 'Y-m-d H:i' format
            $endDateTime = $request->input('enddatetime');
            $restoredDateTime = $request->input('restoreddatetime');
            $machineStopTime = $request->input('machinestoptime', 0);
            $lineStopTime = $request->input('linestoptime', 0);
            $makerCode = $request->input('makercode', '');
            $yokotenkai = $request->input('yokotenkai', '');
            $makerName = $request->input('makername', '');
            $makerHour = $request->input('makerhour', 0);
            $makerService = $request->input('makerservice', 0);
            $makerParts = $request->input('makerparts', 0);
            $ltFactorCode = $request->input('ltfactorcode', '');
            $ltFactor = $request->input('ltfactor', '');
            $situationCode = $request->input('situationcode', '');
            $situation = $request->input('situation', '');
            $factorCode = $request->input('factorcode', '');
            $factor = $request->input('factor', '');
            $measureCode = $request->input('measurecode', '');
            $measure = $request->input('measure', '');
            $preventionCode = $request->input('preventioncode', '');
            $prevention = $request->input('prevention', '');
            $comment = $request->input('comments', '');
            $staffNum = $request->input('staffnum', 0);
            $inActiveSum = $request->input('inactivesum', 0);
            $periodicalSum = $request->input('periodicalsum', 0);
            $questionSum = $request->input('questionsum', 0);
            $prepareSum = $request->input('preparesum', 0);
            $checkSum = $request->input('checksum', 0);
            $waitSum = $request->input('waitsum', 0);
            $repairSum = $request->input('repairsum', 0);
            $confirmSum = $request->input('confirmsum', 0);
            $repairTotalSum = $request->input('totalrepairsum', 0);
            $partCostSum = $request->input('partcostsum', 0);
            $approval = $request->input('approval', 0);
            $workData = $request->input('workdata');
            $partData = $request->input('partdata');

            // Perform the update query
            DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->update([
                    'startdatetime' => DB::raw("to_timestamp('$startDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'enddatetime' => DB::raw("to_timestamp('$endDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'restoreddatetime' => DB::raw("to_timestamp('$restoredDateTime', 'YYYY-MM-DD HH24:MI')"),
                    'machinestoptime' => $machineStopTime,
                    'linestoptime' => $lineStopTime,
                    'makercode' => $makerCode,
                    'yokotenkai' => $yokotenkai,
                    'makername' => $makerName,
                    'makerhour' => $makerHour,
                    'makerservice' => $makerService,
                    'makerparts' => $makerParts,
                    'ltfactorcode' => $ltFactorCode,
                    'ltfactor' => $ltFactor,
                    'situationcode' => $situationCode,
                    'situation' => $situation,
                    'factorcode' => $factorCode,
                    'factor' => $factor,
                    'measurecode' => $measureCode,
                    'measure' => $measure,
                    'preventioncode' => $preventionCode,
                    'prevention' => $prevention,
                    'comments' => $comment,
                    'staffnum' => $staffNum,
                    'inactivesum' => $inActiveSum,
                    'periodicalsum' => $periodicalSum,
                    'questionsum' => $questionSum,
                    'preparesum' => $prepareSum,
                    'checksum' => $checkSum,
                    'waitsum' => $waitSum,
                    'repairsum' => $repairSum,
                    'confirmsum' => $confirmSum,
                    'totalrepairsum' => $repairTotalSum,
                    'partcostsum' => $partCostSum,
                    'approval' => $approval,
                    'updatetime' => DB::raw('CURRENT_TIMESTAMP')
                ]);

            // delete workdata data
            DB::table('tbl_work')->where('recordid', $recordId)->delete();

            // insert workdata
            foreach ($workData as $work) {
                DB::table('tbl_work')->insert([
                    'recordid' => $recordId,
                    'workid' => $work['workid'],
                    'staffname' => $work['staffname'],
                    'inactivetime' => $work['inactivetime'],
                    'periodicaltime' => $work['periodicaltime'],
                    'questiontime' => $work['questiontime'],
                    'preparetime' => $work['preparetime'],
                    'checktime' => $work['checktime'],
                    'waittime' => $work['waittime'],
                    'repairtime' => $work['repairtime'],
                    'confirmtime' => $work['confirmtime'],
                ]);
            }

            // delete partdata
            DB::table('tbl_part')->where('recordid', $recordId)->delete();

            // insert partdata
            foreach ($partData as $part) {
                DB::table('tbl_part')->insert([
                    'recordid' => $recordId,
                    'partid' => $part['partid'],
                    'partcode' => $part['partcode'],
                    'partname' => $part['partname'],
                    'specification' => $part['specification'],
                    'brand' => $part['brand'],
                    'qtty' => $part['qtty'],
                    'price' => $part['price'],
                    'currency' => $part['currency'],
                    'isstock' => $part['isstock']
                ]);
            }

            // Commit the transaction
            DB::commit();

            // Return success response
            return response()->json(['message' => 'Report updated successfully'], 200);
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            return response()->json(['error' => 'Failed to update report', 'message' => $e->getMessage()], 500);
        }
    }


    public function destroy($recordId)
    {
        DB::beginTransaction();

        try {
            $deletedRows = DB::table('tbl_spkrecord')
                ->where('recordid', $recordId)
                ->delete();

            // delete workdata data
            DB::table('tbl_work')->where('recordid', $recordId)->delete();

            // delete partdata
            DB::table('tbl_part')->where('recordid', $recordId)->delete();

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
