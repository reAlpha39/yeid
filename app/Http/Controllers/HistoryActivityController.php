<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PressPartHistoryActivityExport;
use App\Traits\PermissionCheckerTrait;
use Exception;

class HistoryActivityController extends Controller
{
    use PermissionCheckerTrait;

    public function index(Request $request)
    {
        try {
            // if (!$this->checkAccess('pressShotHistoryAct', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $search = $request->input('search');

            $query = DB::table('tbl_activity')
                ->select([
                    'datetime',
                    'machineno',
                    'model',
                    'dieno',
                    'processname',
                    'partcode',
                    'partname',
                    'qty',
                    'employeecode',
                    'employeename',
                    'mainform',
                    'submenu',
                    'reason',
                    'updatetime'
                ])
                ->where('datetime', 'like', "{$targetDate}%");

            if (!empty($machineNo)) {
                $query->where('machineno', $machineNo);
            }

            if (!empty($model)) {
                $query->where('model', $model);
            }

            if (!empty($dieNo)) {
                $query->where('dieno', $dieNo);
            }

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('processname', 'ilike', "$search%")
                        ->orWhere('partcode', 'ilike', "$search%")
                        ->orWhere('partname', 'ilike', "$search%")
                        ->orWhere('qty', 'ilike', "$search%")
                        ->orWhere('employeecode', 'ilike', "$search%")
                        ->orWhere('employeename', 'ilike', "$search%")
                        ->orWhere('mainform', 'ilike', "$search%")
                        ->orWhere('submenu', 'ilike', "$search%")
                        ->orWhere('reason', 'ilike', "$search%")
                        ->orWhere('updatetime', 'ilike', "$search%");
                });
            }

            $query->orderBy('updatetime', 'desc')
                ->orderBy('model')
                ->orderBy('dieno')
                ->orderBy('partcode');

            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch data',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function show(Request $request)
    {
        try {
            // if (!$this->checkAccess('pressShotHistoryAct', 'view')) {
            //     return $this->unauthorizedResponse();
            // }

            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');

            $query = DB::table('tbl_activity')
                ->select([
                    'datetime',
                    'machineno',
                    'model',
                    'dieno',
                    'processname',
                    'partcode',
                    'partname',
                    'qty',
                    'employeecode',
                    'employeename',
                    'mainform',
                    'submenu',
                    'reason',
                    'updatetime'
                ])
                ->where('datetime', $targetDate)
                ->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo);

            $results = $query->first();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch data',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function export(Request $request)
    {
        try {
            if (!$this->checkAccess('pressShotHistoryAct', 'view')) {
                return $this->unauthorizedResponse();
            }

            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $search = $request->input('search');

            return Excel::download(
                new PressPartHistoryActivityExport(
                    $targetDate,
                    $machineNo,
                    $model,
                    $dieNo,
                    $search
                ),
                'press_part_history_activity.xlsx'
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
