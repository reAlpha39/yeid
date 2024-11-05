<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ExchangeDataController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search');
            $targetDate = $request->input('target_date');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            $query = DB::table('tbl_exchangework')
                ->select([
                    'exchangedatetime',
                    'machineno',
                    'model',
                    'dieno',
                    'dieunitno',
                    'processname',
                    'partcode',
                    'partname',
                    'exchangeqtty',
                    'exchangeshotno',
                    'serialno',
                    'reason',
                    'employeecode',
                    'employeename'
                ])
                ->where('exchangedatetime', 'like', $targetDate . '%');

            if (!empty($search)) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('model', 'ILIKE', "{$search}%")
                        ->orWhere('dieunitno', 'ILIKE', "{$search}%")
                        ->orWhere('processname', 'ILIKE', "{$search}%")
                        ->orWhere('partname', 'ILIKE', "{$search}%")
                        ->orWhere('exchangeqtty', 'ILIKE', "{$search}%")
                        ->orWhere('exchangeshotno', 'ILIKE', "{$search}%")
                        ->orWhere('serialno', 'ILIKE', "{$search}%")
                        ->orWhere('reason', 'ILIKE', "{$search}%")
                        ->orWhere('employeecode', 'ILIKE', "{$search}%")
                        ->orWhere('employeename', 'ILIKE', "{$search}%");
                });
            }

            if (!empty($machineNo)) {
                $query->where('machineno', $machineNo);
            }

            if (!empty($model)) {
                $query->where('model', $model);
            }

            if (!empty($dieNo)) {
                $query->where('dieno', $dieNo);
            }

            if (!empty($partCode)) {
                $query->where('partcode', 'like', $partCode . '%');
            }

            $results = $query->orderByDesc('exchangedatetime')
                ->limit(1000)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexModelDie(Request $request)
    {
        try {
            $machineNo = $request->input('machine_no');

            $query = DB::table('mas_presspart')
                ->select([
                    'model',
                    'dieno',
                ]);

            if (!empty($machineNo)) {
                $query->where('partcode', 'ilike', $machineNo . '%');
            }

            $query->groupBy('model', 'dieno');
            $query->orderBy('model');
            $query->orderBy('dieno');

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
            ], 500);
        }
    }

    public function indexMachineNo()
    {
        try {
            $query = DB::table('mas_presspart as p')
                ->select('p.machineno', DB::raw("COALESCE(MAX(m.machinename), ' ') as machinename"))
                ->leftJoin('mas_machine as m', 'p.machineno', '=', 'm.machineno')
                ->groupBy('p.machineno')
                ->orderBy('p.machineno');

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
            ], 500);
        }
    }

    public function indexDieUnit()
    {
        try {
            $results = DB::table('mas_presspart')
                ->distinct()
                ->whereNotNull('dieunitno')
                ->orderBy('dieunitno')
                ->pluck('dieunitno');

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


    public function show($exchangeId)
    {
        try {

            $query = DB::table('tbl_exchangework')
                ->select([
                    'exchangedatetime',
                    'machineno',
                    'model',
                    'dieno',
                    'dieunitno',
                    'processname',
                    'partcode',
                    'partname',
                    'exchangeqtty',
                    'exchangeshotno',
                    'serialno',
                    'reason',
                    'employeecode',
                    'employeename'
                ])
                ->where('exchangedatetime',  $exchangeId);

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
            ], 500);
        }
    }
}
