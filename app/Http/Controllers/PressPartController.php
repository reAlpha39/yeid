<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PressPartExport;
use App\Exports\PressPartMasterPartExport;
use App\Services\ActivityLogger;
use App\Traits\PermissionCheckerTrait;
use Carbon\Carbon;
use Exception;

class PressPartController extends Controller
{
    use PermissionCheckerTrait;

    public function indexParts(Request $request)
    {
        try {
            if (!$this->checkAccess(['pressShotPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

            // Basic filters
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');
            $status = $request->input('status');

            // Pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'asc');

            // If sortBy is a JSON string, decode it
            if ($sortBy && is_string($sortBy) && str_contains($sortBy, '{')) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'asc';
                } catch (Exception $e) {
                    // If JSON decode fails, use the original value
                }
            }

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
                ->where('m.exchangedatetime', 'LIKE', $year . '%');

            // Apply filters
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
                $query->where(function ($q) use ($partCode) {
                    $q->where('m.partcode', 'ILIKE', "{$partCode}%")
                        ->orWhere('m.partname', 'ILIKE', "{$partCode}%");
                });
            }

            if ($status === 'RED') {
                $query->whereRaw('CAST(COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) AS INTEGER) > CAST(COALESCE(m.companylimit, 0) AS INTEGER)');
            } elseif ($status === 'BLUE') {
                $query->whereRaw('CAST(COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) AS INTEGER) > CAST(COALESCE(m.makerlimit, 0) AS INTEGER)');
            } elseif ($status === 'YELLOW') {
                $query->whereRaw('CAST(COALESCE(m.minstock, 0) AS INTEGER) > CAST(COALESCE((
                    COALESCE(i.laststocknumber, 0) +
                    COALESCE((
                        SELECT sum(CASE
                            WHEN jobcode = \'O\' THEN -quantity
                            ELSE quantity
                        END)
                        FROM tbl_invrecord
                        WHERE partcode = i.partcode
                        AND jobdate > i.laststockdate
                    ), 0)
                ), 0) AS INTEGER)');
            }

            // Apply sorting
            if ($sortBy) {
                // Handle special cases for computed columns
                switch ($sortBy) {
                    case 'counter':
                        $query->orderBy(DB::raw('COALESCE((
                        SELECT sum(shotcount)
                        FROM tbl_presswork
                        WHERE machineno = m.machineno
                        AND model = m.model
                        AND dieno = m.dieno
                        AND dieunitno = m.dieunitno
                        AND startdatetime > m.exchangedatetime
                    ), 0)'), $sortDirection);
                        break;
                    case 'currentstock':
                        $query->orderBy(DB::raw('COALESCE(i.laststocknumber, 0) +
                        COALESCE((
                            SELECT sum(CASE
                                WHEN jobcode = \'O\' THEN -quantity
                                ELSE quantity
                            END)
                            FROM tbl_invrecord
                            WHERE partcode = i.partcode
                            AND jobdate > i.laststockdate
                        ), 0)'), $sortDirection);
                        break;
                    case 'origin':
                        $query->orderBy(DB::raw('CASE
                        WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                        WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                        ELSE \'-\'
                    END'), $sortDirection);
                        break;
                    case 'vendorname':
                        $query->orderBy(DB::raw('COALESCE(v.vendorname, \'-\')'), $sortDirection);
                        break;
                    case 'machinename':
                        $query->orderBy('c.machinename', $sortDirection);
                        break;
                    default:
                        // For regular columns, determine the table prefix
                        $prefix = in_array($sortBy, [
                            'unitprice',
                            'currency',
                            'brand',
                            'address'
                        ]) ? 'i.' : 'm.';
                        $query->orderBy($prefix . $sortBy, $sortDirection);
                }
            } else {
                // Default sorting
                $query->orderBy('m.partcode');
            }

            // Execute pagination
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'next_page_url' => $results->nextPageUrl(),
                    'prev_page_url' => $results->previousPageUrl(),
                ]
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
        try {
            if (!$this->checkAccess(['pressShotMasterPart'], 'view')) {
                return $this->unauthorizedResponse();
            }

            // Basic filters
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            // Pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'asc');

            // Handle Vuetify sorting format
            if ($sortBy && is_string($sortBy) && str_contains($sortBy, '{')) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'asc';
                } catch (Exception $e) {
                    // If JSON decode fails, use original value
                }
            }

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
                $query->where(function ($q) use ($partCode) {
                    $q->where('m.partcode', 'ILIKE', "{$partCode}%")
                        ->orWhere('m.partname', 'ILIKE', "{$partCode}%");
                });
            }

            if (!empty($year)) {
                $query->whereYear('m.exchangedatetime', $year);
            }

            // Apply sorting
            if ($sortBy) {
                // Handle special cases for computed columns and joined tables
                switch ($sortBy) {
                    case 'currentstock':
                        $query->orderBy(DB::raw('COALESCE(i.laststocknumber + COALESCE(t.total_quantity, 0), 0)'), $sortDirection);
                        break;
                    case 'supplier':
                        $query->orderBy(DB::raw('COALESCE(v.vendorname, \'-\')'), $sortDirection);
                        break;
                    case 'origin':
                        $query->orderBy(DB::raw('CASE
                        WHEN substring(m.partcode, 2, 1) = \'I\' THEN \'Import\'
                        WHEN substring(m.partcode, 2, 1) = \'L\' THEN \'Local\'
                        ELSE \'-\'
                    END'), $sortDirection);
                        break;
                    case 'machinename':
                        $query->orderBy('c.machinename', $sortDirection);
                        break;
                    case 'exchangereason':
                        $query->orderBy('e.reason', $sortDirection);
                        break;
                    default:
                        // For regular columns, determine the table prefix
                        $prefix = in_array($sortBy, [
                            'unitprice',
                            'currency',
                            'brand',
                            'address'
                        ]) ? 'i.' : 'm.';
                        $query->orderBy($prefix . $sortBy, $sortDirection);
                }
            } else {
                // Default sorting
                $query->orderBy('m.machineno')
                    ->orderBy('m.model')
                    ->orderBy('m.dieno')
                    ->orderBy('m.partcode');
            }

            // Execute pagination
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'pagination' => [
                    'total' => $results->total(),
                    'per_page' => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                    'next_page_url' => $results->nextPageUrl(),
                    'prev_page_url' => $results->previousPageUrl(),
                ]
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
            if (!$this->checkAccess(['pressShotMasterPart'], 'view')) {
                return $this->unauthorizedResponse();
            }

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
            if (!$this->checkAccess(['pressShotPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

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
        try {
            if (!$this->checkAccess(['pressShotMasterPart'], 'view')) {
                return $this->unauthorizedResponse();
            }

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
        try {
            if (!$this->checkAccess(['pressShotMasterPart'], 'create')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

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

            ActivityLogger::log(
                'press-shot-master-part',
                'insert_master_part',
                'data: ' . json_encode($request->all())
            );

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
        try {
            if (!$this->checkAccess(['pressShotMasterPart'], 'update')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

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

            ActivityLogger::log(
                'press-shot-master-part',
                'update_master_part',
                'data: ' . json_encode($request->all())
            );

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
        try {
            if (!$this->checkAccess(['pressShotMasterPart'], 'delete')) {
                return $this->unauthorizedResponse();
            }

            DB::beginTransaction();

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

            ActivityLogger::log(
                'press-shot-master-part',
                'delete_master_part',
                'data: ' . json_encode($request->all())
            );

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
            if (!$this->checkAccess(['pressShotPartList'], 'view')) {
                return $this->unauthorizedResponse();
            }

            // Get all filter parameters
            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');
            $status = $request->input('status');

            // Handle sorting parameters
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'asc');

            // If sortBy is a JSON string, decode it
            if ($sortBy && is_string($sortBy) && str_contains($sortBy, '{')) {
                try {
                    $sortData = json_decode($sortBy, true);
                    $sortBy = $sortData['key'] ?? null;
                    $sortDirection = $sortData['order'] ?? 'asc';
                } catch (Exception $e) {
                    // If JSON decode fails, use the original value
                }
            }

            ActivityLogger::log(
                'press-shot-master-part',
                'export_part_list',
                'data: ' . json_encode($request->all())
            );

            return Excel::download(
                new PressPartExport(
                    $year,
                    $machineNo,
                    $model,
                    $dieNo,
                    $partCode,
                    $status,
                    $sortBy,
                    $sortDirection
                ),
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
            if (!$this->checkAccess(['pressShotMasterPart'], 'view')) {
                return $this->unauthorizedResponse();
            }

            $year = $request->input('year');
            $machineNo = $request->input('machine_no');
            $model = $request->input('model');
            $dieNo = $request->input('die_no');
            $partCode = $request->input('part_code');

            ActivityLogger::log(
                'press-shot-master-part',
                'export_master_part',
                'data: ' . json_encode($request->all())
            );

            return Excel::download(
                new PressPartMasterPartExport($year, $machineNo, $model, $dieNo, $partCode),
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
