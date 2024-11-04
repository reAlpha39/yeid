<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
}
