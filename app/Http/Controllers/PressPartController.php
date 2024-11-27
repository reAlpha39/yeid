<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PressPartExport;
use Carbon\Carbon;
use Exception;

class PressPartController extends Controller
{
    public function indexParts(Request $request)
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

    public function indexMaster(Request $request)
    {
        // Get input parameters
        $year = $request->input('year');
        $machineNo = $request->input('machine_no');
        $model = $request->input('model');
        $dieNo = $request->input('die_no');
        $partCode = $request->input('part_code');

        $query = DB::table('mas_presspart as m')
            ->leftJoin('mas_inventory as i', function ($join) {
                $join->whereRaw('substring(m.partcode, 1, 8) = substring(i.partcode, 1, 8)');
            })
            ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
            ->leftJoin(
                DB::raw('(SELECT exchangedatetime, reason FROM tbl_exchangework) as e'),
                'm.exchangedatetime',
                '=',
                'e.exchangedatetime'
            )
            ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
            ->leftJoin(DB::raw('(
                SELECT 
                    partcode,
                    sum(CASE WHEN jobcode = \'O\' THEN -quantity ELSE quantity END) as total_quantity
                FROM tbl_invrecord
                WHERE jobdate > (SELECT laststockdate FROM mas_inventory WHERE partcode = tbl_invrecord.partcode)
                GROUP BY partcode
            ) as t'), 'i.partcode', '=', 't.partcode')
            ->select([
                'm.machineno',
                'm.model',
                'm.dieno',
                'm.dieunitno',
                'm.processname',
                'm.partcode',
                'm.partname',
                'm.category',
                'm.companylimit',
                'm.makerlimit',
                'm.autoflag',
                'm.qttyperdie',
                'm.drawingno',
                'm.note',
                'm.exchangedatetime',
                'e.reason as exchangereason',
                'm.minstock',
                DB::raw('COALESCE(i.laststocknumber + COALESCE(t.total_quantity, 0), 0) as currentstock'),
                DB::raw('COALESCE(i.unitprice, 0) as unitprice'),
                DB::raw('COALESCE(i.currency, \'-\') as currency'),
                'i.brand',
                DB::raw('COALESCE(v.vendorname, \'-\') as supplier'),
                DB::raw('CASE
                    WHEN substring(m.partcode, 2, 1) = \'I\' THEN \'Import\'
                    WHEN substring(m.partcode, 2, 1) = \'L\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                'i.address',
                'c.machinename',
                'm.employeecode',
                'm.employeename',
                'm.reason'
            ])
            ->where('m.status', '<>', 'D');

        // Apply filters based on input parameters
        if (!empty($machineNo)) {
            $query->where('m.machineno', $machineNo);
        }

        if (!empty($model) && !empty($dieNo)) {
            $query->where('m.model', $model)
                ->where('m.dieno', $dieNo);
        }

        if ($partCode) {
            $query->where('m.partcode', 'ILIKE', "{$partCode}%")
                ->orWhere('m.partname', 'ILIKE', "{$partCode}%");
        }

        // Add year filter if provided
        if (!empty($year)) {
            $query->whereYear('m.exchangedatetime', $year);
        }

        $query->orderBy('m.machineno')
            ->orderBy('m.model')
            ->orderBy('m.dieno')
            ->orderBy('m.partcode');

        try {
            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching parts data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProcessNames()
    {
        try {
            $processes = DB::table('mas_presspart')
                ->select('processname')
                ->whereNotNull('processname')
                ->where('processname', '<>', '')
                ->groupBy('processname')
                ->orderBy('processname')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $processes,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving process names: ' . $e->getMessage()
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

    public function showMaster(Request $request)
    {
        // Get input parameters
        $machineNo = $request->input('machine_no');
        $model = $request->input('model');
        $dieNo = $request->input('die_no');
        $processName = $request->input('process_name');
        $partCode = $request->input('part_code');

        $query = DB::table('mas_presspart')->select();

        // Apply filters based on input parameters
        $query->where('machineno', $machineNo)
            ->where('processname', $processName)
            ->where('partcode', $partCode)
            ->where('model', $model)
            ->where('dieno', $dieNo);


        try {
            $result = $query->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Part not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching parts data',
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
            $processName = $request->input('process_name');
            $partCode = $request->input('part_code');
            $partName = $request->input('part_name');
            $category = $request->input('category');
            $companyLimit = $request->input('company_limit');
            $makerLimit = $request->input('maker_limit');
            $autoFlag = $request->input('auto_flag') ? '1' : '0';
            $qttyPerDie = $request->input('qtty_per_die');
            $drawingNo = $request->input('drawing_no');
            $note = $request->input('note');
            $exchangeDateTime = $request->input('exchange_datetime');
            $minStock = $request->input('min_stock');
            $loginUserCode = $request->input('login_user_code');
            $loginUserName = $request->input('login_user_name');
            $reason = $request->input('reason', '');

            $currentDateTime = now();

            // Check if record exists
            $exists = DB::table('mas_presspart')
                ->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo)
                ->where('processname', $processName)
                ->where('partcode', $partCode)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Record already exists'
                ], 409);
            }

            // Insert new record
            DB::table('mas_presspart')->insert([
                'machineno' => $machineNo,
                'model' => $model,
                'dieno' => $dieNo,
                'dieunitno' => $dieUnitNo,
                'processname' => $processName,
                'partcode' => $partCode,
                'partname' => $partName,
                'category' => $category,
                'companylimit' => $companyLimit,
                'makerlimit' => $makerLimit,
                'autoflag' => $autoFlag,
                'qttyperdie' => $qttyPerDie,
                'drawingno' => $drawingNo,
                'note' => $note,
                'exchangedatetime' => Carbon::parse($exchangeDateTime)->format('YmdHis'),
                'minstock' => $minStock,
                'status' => 'R',
                'employeecode' => $loginUserCode,
                'employeename' => $loginUserName,
                'updatetime' => $currentDateTime
            ]);

            // Insert into activity log
            DB::table('tbl_activity')->insert([
                'datetime' => $currentDateTime->format('YmdHis'),
                'machineno' => $machineNo,
                'model' => $model,
                'dieno' => $dieNo,
                'processname' => $processName,
                'partcode' => $partCode,
                'partname' => $partName,
                'qty' => $qttyPerDie,
                'employeecode' => $loginUserCode,
                'employeename' => $loginUserName,
                'mainform' => 'Add Data',
                'submenu' => 'Entry Master Part',
                'reason' => $reason,
                'updatetime' => $currentDateTime
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Press part created successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error creating press part',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing press part record
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $dieUnitNo = $request->input('die_unit_no');
            $processName = $request->input('process_name');
            $partCode = $request->input('part_code');
            $partName = $request->input('part_name');
            $category = $request->input('category');
            $companyLimit = $request->input('company_limit');
            $makerLimit = $request->input('maker_limit');
            $autoFlag = $request->input('auto_flag') ? '1' : '0';
            $qttyPerDie = $request->input('qtty_per_die');
            $drawingNo = $request->input('drawing_no');
            $note = $request->input('note');
            $exchangeDateTime = $request->input('exchange_datetime');
            $minStock = $request->input('min_stock');
            $loginUserCode = $request->input('login_user_code');
            $loginUserName = $request->input('login_user_name');
            $reason = $request->input('reason', '');

            $currentDateTime = now();

            // Check if record exists
            $exists = DB::table('mas_presspart')
                ->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo)
                ->where('processname', $processName)
                ->where('partcode', $partCode)
                ->exists();

            if (!$exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            // Update existing record
            DB::table('mas_presspart')
                ->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo)
                ->where('processname', $processName)
                ->where('partcode', $partCode)
                ->update([
                    'partname' => $partName,
                    'dieunitno' => $dieUnitNo,
                    'category' => $category,
                    'companylimit' => $companyLimit,
                    'makerlimit' => $makerLimit,
                    'autoflag' => $autoFlag,
                    'qttyperdie' => $qttyPerDie,
                    'drawingno' => $drawingNo,
                    'note' => $note,
                    'exchangedatetime' => Carbon::parse($exchangeDateTime)->format('YmdHis'),
                    'minstock' => $minStock,
                    'employeecode' => $loginUserCode,
                    'employeename' => $loginUserName,
                    'updatetime' => $currentDateTime
                ]);

            // Insert into activity log
            DB::table('tbl_activity')->insert([
                'datetime' => $currentDateTime->format('YmdHis'),
                'machineno' => $machineNo,
                'model' => $model,
                'dieno' => $dieNo,
                'processname' => $processName,
                'partcode' => $partCode,
                'partname' => $partName,
                'qty' => $qttyPerDie,
                'employeecode' => $loginUserCode,
                'employeename' => $loginUserName,
                'mainform' => 'Update Data',
                'submenu' => 'Entry Master Part',
                'reason' => $reason,
                'updatetime' => $currentDateTime
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Press part updated successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error updating press part',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $processName = $request->input('process_name');
            $partCode = $request->input('part_code');
            $partName = $request->input('part_name');
            $qttyPerDie = $request->input('qtty_per_die');
            $loginUserCode = $request->input('employee_code');
            $loginUserName = $request->input('employee_name');
            $reason = $request->input('reason', '');
            $currentDateTime = now();

            $affectedRows =   DB::table('mas_presspart')
                ->where('machineno', $machineNo)
                ->where('model', $model)
                ->where('dieno', $dieNo)
                ->where('partcode', $partCode)
                ->delete();

            if ($affectedRows > 0) {
                // Insert into activity log
                DB::table('tbl_activity')->insert([
                    'datetime' => $currentDateTime->format('YmdHis'),
                    'machineno' => $machineNo,
                    'model' => $model,
                    'dieno' => $dieNo,
                    'processname' => $processName,
                    'partcode' => $partCode,
                    'partname' => $partName,
                    'qty' => $qttyPerDie,
                    'employeecode' => $loginUserCode,
                    'employeename' => $loginUserName,
                    'mainform' => 'Delete Part',
                    'submenu' => 'Delete master part',
                    'reason' => $reason,
                    'updatetime' => $currentDateTime
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Press Part deleted successfully!'
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

    public function export(Request $request)
    {
        try {
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            return Excel::download(
                new PressPartExport($year, $machineNo, $model, $dieNo, $partCode),
                'press_parts.xlsx'
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pressPartMasterPartExport(Request $request)
    {
        try {
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            return Excel::download(
                new PressPartExport($year, $machineNo, $model, $dieNo, $partCode),
                'press_master_parts.xlsx'
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
