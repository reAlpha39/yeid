<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PressPartProductionExport;
use App\Services\ActivityLogger;
use Exception;

class ProductionDataController extends Controller
{
    public function index(Request $request)
    {
        try {
            $isSummary = $request->input('is_summary', false);
            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');

            $query = DB::table('tbl_presswork');

            if (!$isSummary) {
                // Detail query
                $query->select([
                    'machineno',
                    'model',
                    'dieno',
                    'dieunitno',
                    'startdatetime',
                    'enddatetime',
                    'shotcount',
                    'reason',
                    'employeecode',
                    'employeename',
                    'updatetime'
                ])
                    ->where('startdatetime', 'like', $targetDate . '%');

                if (!empty($machineNo)) {
                    $query->where('machineno', explode('|', $machineNo)[0]);
                }

                if (!empty($model)) {
                    $modelData = explode('-', $model);
                    $query->where('model', $modelData[0])
                        ->when(isset($modelData[1]), function ($query) use ($modelData) {
                            return $query->where('dieno', $modelData[1]);
                        });
                }

                $query->orderByDesc('startdatetime');
            } else {
                // Summary query
                $query->selectRaw("
                '' as machineno,
                model, dieno, dieunitno,
                max(startdatetime) as startdatetime,
                max(enddatetime) as enddatetime,
                sum(shotcount) as shotcount,
                max(updatetime) as updatetime
            ")
                    ->groupBy('model', 'dieno', 'dieunitno')
                    ->orderByDesc(DB::raw('max(startdatetime)'));
            }

            $results = $query->limit(1000)->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $updateTime = $request->input('update_time');
            $dieUnitNo = $request->input('die_unit_no');

            $query = DB::table('tbl_presswork');

            $query->select([
                'machineno',
                'model',
                'dieno',
                'dieunitno',
                'startdatetime',
                'enddatetime',
                'shotcount',
                'reason',
                'employeecode',
                'employeename',
                'updatetime'
            ])->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo)
                ->where('startdatetime',  $startDate)
                ->where('enddatetime', $endDate)
                ->where('updatetime', $updateTime)
                ->where('dieunitno', $dieUnitNo);

            $results = $query->first();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $dieUnitNo = $request->input('die_unit_no');
            $shotCount = intval($request->input('shot_count', 0));
            $startDateTime = $request->input('start_datetime');
            $endDateTime = $request->input('end_datetime');
            $reason = $request->input('reason');

            $loginUserCode = $request->input('login_user_code');
            $loginUserName = $request->input('login_user_name');

            $currentDateTime = now();

            DB::table('tbl_presswork')->insert([
                'machineno' => $machineNo,
                'model' => $model,
                'dieno' => $dieNo,
                'dieunitno' => $dieUnitNo,
                'shotcount' => $shotCount,
                'startdatetime' => $startDateTime,
                'enddatetime' => $endDateTime,
                'reason' => $reason,
                'updatetime' => $currentDateTime
            ]);

            // Insert Log
            DB::table('tbl_activity')->insert([
                'datetime' => $currentDateTime->format('YmdHis'),
                'machineno' => $machineNo,
                'model' => $model,
                'dieno' => $dieNo,
                'processname' => '',       // Empty string as per original code
                'partcode' => '',          // Empty string as per original code
                'partname' => '',          // Empty string as per original code
                'qty' => $shotCount,
                'employeecode' => $loginUserCode,
                'employeename' => $loginUserName,
                'mainform' => 'Production Data',
                'submenu' => 'Add Result Data Production',
                'reason' => $reason,
                'updatetime' => $currentDateTime,
            ]);

            ActivityLogger::log(
                'press-shot-production-data',
                'store_production-data',
                'data: ' . json_encode($request->all())
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data inserted successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error inserting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $isSummary = $request->input('is_summary', false);
            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');

            $filename = $isSummary ? 'press_part_production_summary.xlsx' : 'press_part_production_detail.xlsx';

            ActivityLogger::log(
                'press-shot-production-data',
                'export_production_data',
                'data: ' . json_encode($request->all())
            );

            return Excel::download(
                new PressPartProductionExport($isSummary, $targetDate, $machineNo, $model),
                $filename
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
