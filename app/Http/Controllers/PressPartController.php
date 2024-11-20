<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PressPartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            $query = DB::table('mas_presspart as m')
                ->leftJoin('mas_inventory as i', function ($join) {
                    $join->on(
                        DB::raw('substring(m.partcode, 1, 8)'),
                        '=',
                        DB::raw('substring(i.partcode, 1, 8)')
                    )
                        ->whereNotNull('i.partcode');
                })
                ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
                ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
                ->select([
                    'c.machinename',
                    'm.machineno',
                    'm.model',
                    'm.dieno',
                    'm.dieunitno',
                    'm.processname',
                    'm.partcode',
                    'm.partname',
                    'm.category',
                    DB::raw('COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) as counter'),
                    'm.companylimit',
                    'm.makerlimit',
                    'm.qttyperdie',
                    'm.drawingno',
                    'm.note',
                    'm.exchangedatetime',
                    'm.minstock',
                    DB::raw('COALESCE(i.laststocknumber, 0) +
                    COALESCE((
                        SELECT sum(CASE
                            WHEN jobcode = \'O\' THEN -quantity
                            ELSE quantity
                        END)
                        FROM tbl_invrecord
                        WHERE partcode = i.partcode
                        AND jobdate > i.laststockdate
                    ), 0) as currentstock'),
                    'i.unitprice',
                    'i.currency',
                    'i.brand',
                    DB::raw('COALESCE(v.vendorname, \'-\') as vendorname'),
                    DB::raw('CASE
                    WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                    WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                    'i.address'
                ])
                ->where('m.status', '<>', 'D')
                // Use simple LIKE comparison instead of date extraction
                ->where('m.exchangedatetime', 'LIKE', $year . '%');

            if ($machineNo) {
                $query->where('m.machineno', $machineNo);
            }

            if ($model) {
                $query->where('m.model', $model);
            }

            if ($dieNo) {
                $query->where('m.dieno', $dieNo);
            }

            if ($partCode) {
                $query->where('m.partcode', 'ILIKE', "{$partCode}%")
                    ->orWhere('m.partname', 'ILIKE', "{$partCode}%");
            }

            // Cache results
            // $cacheKey = sprintf(
            //     "presspart_query_%s_%s_%s_%s_%s",
            //     $year,
            //     $machineNo ?? 'all',
            //     $model ?? 'all',
            //     $dieNo ?? 'all',
            //     $partCode ?? 'all'
            // );

            // $result = cache()->remember(
            //     $cacheKey,
            //     now()->addMinutes(30),
            //     function () use ($query) {
            //         return $query->get();
            //     }
            // );

            $result = $query->get();

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching parts data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($exchangeid)
    {
        try {

            $query = DB::table('mas_presspart as m')
                ->leftJoin('mas_inventory as i', function ($join) {
                    $join->on(
                        DB::raw('substring(m.partcode, 1, 8)'),
                        '=',
                        DB::raw('substring(i.partcode, 1, 8)')
                    )
                        ->whereNotNull('i.partcode');
                })
                ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
                ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
                ->select([
                    'c.machinename',
                    'm.machineno',
                    'm.model',
                    'm.dieno',
                    'm.dieunitno',
                    'm.processname',
                    'm.partcode',
                    'm.partname',
                    'm.category',
                    DB::raw('COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) as counter'),
                    'm.companylimit',
                    'm.makerlimit',
                    'm.qttyperdie',
                    'm.drawingno',
                    'm.note',
                    'm.exchangedatetime',
                    'm.minstock',
                    DB::raw('COALESCE(i.laststocknumber, 0) +
                    COALESCE((
                        SELECT sum(CASE
                            WHEN jobcode = \'O\' THEN -quantity
                            ELSE quantity
                        END)
                        FROM tbl_invrecord
                        WHERE partcode = i.partcode
                        AND jobdate > i.laststockdate
                    ), 0) as currentstock'),
                    'i.unitprice',
                    'i.currency',
                    'i.brand',
                    DB::raw('COALESCE(v.vendorname, \'-\') as vendorname'),
                    DB::raw('CASE
                    WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                    WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                    'i.address'
                ])
                ->where('m.status', '<>', 'D')
                ->where('m.exchangedatetime',  $exchangeid);


            $result = $query->first();


            return response()->json([
                'success' => true,
                'data' => $result,
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
