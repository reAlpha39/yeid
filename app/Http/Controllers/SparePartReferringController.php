<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SparePartReferringInventorySummaryExport;
use App\Exports\SparePartReferringPartsCostExport;
use App\Exports\SparePartReferringMachineCostExport;
use App\Exports\SparePartReferringInventoryChangeCostExport;
use Exception;

class SparePartReferringController extends Controller
{
    public function getCostSummary(Request $request)
    {
        try {
            $year = $request->input('year');

            $costQuery = DB::table('tbl_costrecord as c')
                ->selectRaw("
                    substr(c.issuedate, 1, 6) as issue_month,
                    sum(
                        coalesce(i.qtty, 0) * coalesce(i.unitprice, 0) *
                        (case i.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_cost_idr
                ")
                ->join('tbl_costitem as i', 'c.recordid', '=', 'i.recordid')
                ->leftJoin('mas_system as s', DB::raw("substr(c.issuedate, 1, 4)"), '=', 's.year')
                ->where('c.status', 'O')
                ->where(DB::raw("substr(c.issuedate, 1, 4)"), $year)
                ->groupBy(DB::raw("substr(c.issuedate, 1, 6)"));

            $costResults = $costQuery->get();

            // Format the query results
            $formattedCostResults = [];
            foreach ($costResults as $row) {
                $month = intval(substr($row->issue_month, 4, 2)); // Extract month as integer
                $totalCostInMillions = number_format($row->total_cost_idr / 1000000, 1);

                $formattedCostResults[] = [
                    'month' => $month,
                    'total_cost_in_millions' => $totalCostInMillions,
                ];
            }


            return response()->json([
                'success' => true,
                'data' => $formattedCostResults,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch cost summary data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInventorySummary(Request $request)
    {
        try {
            $year = $request->input('year');

            $invQuery = DB::table('tbl_invsummary as i')
                ->selectRaw("
                    'I' as type,
                    i.yearmonth,
                    sum(coalesce(i.inbound, 0) * coalesce(m.unitprice, 0) *
                        (case m.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_inbound,
                    sum(coalesce(i.outbound, 0) * coalesce(m.unitprice, 0) *
                        (case m.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_outbound,
                    sum(coalesce(i.adjust, 0) * coalesce(m.unitprice, 0) *
                        (case m.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_adjust,
                    sum(coalesce(i.stocknumber, 0) * coalesce(m.unitprice, 0) *
                        (case m.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_stock
                ")
                ->join('mas_inventory as m', 'i.partcode', '=', 'm.partcode')
                ->leftJoin('mas_system as s', DB::raw("substr(i.yearmonth, 1, 4)"), '=', 's.year')
                ->where(DB::raw("substr(i.yearmonth, 1, 4)"), $year)
                ->groupBy('i.yearmonth');

            $invResults = $invQuery->get();

            // Format the query results
            $formattedInvResults = [];
            foreach ($invResults as $row) {
                $month = intval(substr($row->yearmonth, 4, 2)); // Extract month as integer

                $formattedInvResults[] = [
                    'type' => $row->type,
                    'month' => $month,
                    'inbound_in_millions' => number_format($row->total_inbound / 1000000, 1),
                    'outbound_in_millions' => number_format($row->total_outbound / 1000000, 1),
                    'adjust_in_millions' => number_format($row->total_adjust / 1000000, 1),
                    'stock_in_millions' => number_format($row->total_stock / 1000000, 1),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedInvResults,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch inventory summary data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPartsCost(Request $request)
    {
        try {
            $year = $request->input('year');

            $query =
                DB::table('tbl_invrecord as r')
                ->selectRaw("
                    m.plantcode,
                    substr(r.partcode, 2, 1) || substr(r.partcode, 4, 1) as part_code,
                    substr(r.jobdate, 1, 6) as job_month,
                    sum(coalesce(r.quantity, 0) * coalesce(r.unitprice, 0) *
                        (case r.currency
                            when 'USD' then coalesce(s.usd2idr::numeric, 1)
                            when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                            when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                            when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                            else 1
                        end)
                    ) as total_cost_idr
                ")
                ->leftJoin('mas_machine as m', 'r.machineno', '=', 'm.machineno')
                ->leftJoin('mas_system as s', DB::raw('substr(r.jobdate, 1, 4)'), '=', 's.year')
                ->where('r.jobcode', 'O')
                ->whereRaw("substr(r.jobdate, 1, 4) = ?", [$year])
                ->whereRaw("coalesce(m.plantcode, '') <> ''")
                ->groupBy('m.plantcode', DB::raw("substr(r.partcode, 2, 1) || substr(r.partcode, 4, 1)"), DB::raw("substr(r.jobdate, 1, 6)"))
                ->orderBy('m.plantcode')
                ->orderBy(DB::raw("substr(r.partcode, 2, 1) || substr(r.partcode, 4, 1)"))
                ->orderBy(DB::raw("substr(r.jobdate, 1, 6)"))
                ->get();


            $formattedResults = [];
            foreach ($query as $row) {
                $sum = number_format($row->total_cost_idr / 1000000, 1); // Convert to millions as in original code
                $month = intval(substr($row->job_month, 4, 2)); // Extract month as integer

                $formattedResults[] = [
                    'plant_code' => $row->plantcode,
                    'part_code' => $row->part_code,
                    'job_month' => $month,
                    'total_cost_idr' => $sum,
                ];
            }


            return response()->json([
                'success' => true,
                'data' => $formattedResults,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch parts cost data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getMachinesCost(Request $request)
    {
        try {
            $year = $request->input('year');
            $month = $request->input('month');
            $plantCode = $request->input('plant_code');
            $shopCode = $request->input('shop_code');
            $machineNo = $request->input('machine_no');

            $query = DB::table('tbl_invrecord')
                ->select([
                    'mas_machine.machinename',
                    'mas_machine.machineno',
                    'mas_shop.shopname',
                    'mas_machine.linecode',
                    DB::raw('SUM(
                    COALESCE(tbl_invrecord.quantity, 0) * COALESCE(tbl_invrecord.unitprice, 0) *
                    CASE tbl_invrecord.currency
                        WHEN \'USD\' THEN COALESCE(mas_system.usd2idr::numeric, 1)
                        WHEN \'JPY\' THEN COALESCE(mas_system.jpy2idr::numeric, 1)
                        WHEN \'EUR\' THEN COALESCE(mas_system.eur2idr::numeric, 1)
                        WHEN \'SGD\' THEN COALESCE(mas_system.sgd2idr::numeric, 1)
                        ELSE 1
                    END
                ) AS price')
                ])
                ->leftJoin('mas_machine', 'tbl_invrecord.machineno', '=', 'mas_machine.machineno')
                ->leftJoin(DB::raw('mas_system'), function ($join) {
                    $join->on(DB::raw('SUBSTRING(tbl_invrecord.jobdate::text, 1, 4)'), '=', 'mas_system.year');
                })
                ->leftJoin('mas_shop', 'mas_machine.shopcode', '=', 'mas_shop.shopcode')
                ->where('tbl_invrecord.jobcode', '=', 'O')
                ->whereRaw("COALESCE(mas_machine.machineno, '') <> ''");

            // Apply filters based on request parameters
            if (!empty($year) && !empty($month)) {
                $jobMonth = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
                $query->whereRaw("SUBSTRING(tbl_invrecord.jobdate::text, 1, 6) = ?", [$jobMonth]);
            } elseif (!empty($year)) {
                $query->whereRaw("SUBSTRING(tbl_invrecord.jobdate::text, 1, 4) = ?", [$year]);
            }

            if (!empty($plantCode)) {
                $query->where('mas_machine.plantcode', '=', $plantCode);
            }

            if (!empty($shopCode)) {
                $query->where('mas_machine.shopcode', '=', $shopCode);
            }

            if (!empty($machineNo)) {
                $query->where('mas_machine.machineno', 'ilike', $machineNo . '%');
            }

            $query->groupBy([
                'mas_machine.machineno',
                'mas_machine.machinename',
                'mas_machine.linecode',
                'mas_shop.shopname'
            ])
                ->orderBy('price', 'desc');

            $machines = $query->get();


            $formattedResults = [];
            foreach ($machines as $index => $machine) {

                $formattedResults[] = [
                    'index' => ($index + 1),
                    'machineno' => $machine->machineno ?? '',
                    'machinename' => $machine->machinename ?? '',
                    'shopname' => $machine->shopname ?? '',
                    'linecode' => $machine->linecode ?? '',
                    'price' => number_format($machine->price, 0) ??  '',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch parts cost data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInventoryChangeCost(Request $request)
    {
        $year = $request->input('year');
        $partCode = $request->input('part_code', '');
        $partName = $request->input('part_name', '');
        $brand = $request->input('brand', '');
        $usedFlag = $request->input('used_flag', false);
        $specification = $request->input('specification', '');
        $address = $request->input('address', '');
        $vendorCode = $request->input('vendor_code', '');
        $note = $request->input('note', '');
        $category = $request->input('category', ''); // M, F, O, J
        $limit = $request->input('limit', 100);

        try {
            // Step 1: Get max yearmonth
            $maxym = DB::table('tbl_invsummary')
                ->where('yearmonth', 'like', $year . '%')
                ->max('yearmonth');

            if (!$maxym) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ], 200);
            }

            // Step 2: Fetch inventory data with the max yearmonth
            $query = DB::table('tbl_invsummary as i')
                ->join('mas_inventory as m', 'i.partcode', '=', 'm.partcode')
                ->join('mas_system as s', DB::raw('substr(i.yearmonth, 1, 4)'), '=', 's.year')
                ->select(
                    'i.partcode',
                    'm.partname',
                    'm.specification',
                    DB::raw('COALESCE(m.unitprice, 0) as unitprice'),
                    DB::raw('CASE m.currency
                WHEN \'USD\' THEN COALESCE(s.usd2idr::numeric, 1)
                WHEN \'JPY\' THEN COALESCE(s.jpy2idr::numeric, 1)
                WHEN \'EUR\' THEN COALESCE(s.eur2idr::numeric, 1)
                WHEN \'SGD\' THEN COALESCE(s.sgd2idr::numeric, 1)
                ELSE 1
            END as exchangerate'),
                    DB::raw('COALESCE(i.stocknumber, 0) * COALESCE(m.unitprice, 0) * CASE m.currency
                WHEN \'USD\' THEN COALESCE(s.usd2idr::numeric, 1)
                WHEN \'JPY\' THEN COALESCE(s.jpy2idr::numeric, 1)
                WHEN \'EUR\' THEN COALESCE(s.eur2idr::numeric, 1)
                WHEN \'SGD\' THEN COALESCE(s.sgd2idr::numeric, 1)
                ELSE 1
            END as stockamount')
                )
                ->where('i.yearmonth', '=', $maxym);

            // Apply search filters
            if ($partCode) {
                $query->where('m.partcode', 'ilike', $partCode . '%');
            }
            if ($partName) {
                $query->where(DB::raw('upper(m.partname)'), 'ilike', '%' . strtoupper($partName) . '%');
            }
            if ($brand) {
                $query->where(DB::raw('upper(m.brand)'), 'ilike', '%' . strtoupper($brand) . '%');
            }
            if ($usedFlag) {
                $query->where('m.usedflag', 'O');
            }
            if ($specification) {
                $query->where(DB::raw('upper(m.specification)'), 'ilike', '%' . strtoupper($specification) . '%');
            }
            if ($address) {
                $query->where(DB::raw('upper(m.address)'), 'ilike', '%' . strtoupper($address) . '%');
            }
            if ($vendorCode) {
                $query->where(DB::raw('upper(m.vendorcode)'), 'ilike', '%' . strtoupper($vendorCode) . '%');
            }
            if ($note) {
                $query->where(DB::raw('upper(m.note)'), 'ilike', '%' . strtoupper($note) . '%');
            }

            // Filter by category
            if ($category) {
                $query->where('m.category', $category);
            }

            // Get the results
            $inventoryData = $query->orderByDesc('stockamount')->limit($limit)->get();

            // Step 3: For each row, get additional details
            $formattedResults = [];
            foreach ($inventoryData as $inventory) {
                $monthlyData = [];
                for ($month = 1; $month <= 12; $month++) {
                    $monthYear = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
                    $monthlyStockAmount = DB::table('tbl_invsummary as s')
                        ->join('mas_inventory as m', 's.partcode', '=', 'm.partcode')
                        ->join('mas_system as sys', DB::raw('substr(s.yearmonth, 1, 4)'), '=', 'sys.year')
                        ->where('s.partcode', '=', $inventory->partcode)
                        ->where('s.yearmonth', '=', $monthYear)
                        ->sum(DB::raw('s.stocknumber * m.unitprice * CASE m.currency
                    WHEN \'USD\' THEN COALESCE(sys.usd2idr::numeric, 1)
                    WHEN \'JPY\' THEN COALESCE(sys.jpy2idr::numeric, 1)
                    WHEN \'EUR\' THEN COALESCE(sys.eur2idr::numeric, 1)
                    WHEN \'SGD\' THEN COALESCE(sys.sgd2idr::numeric, 1)
                    ELSE 1 
                END'));
                    $monthlyData[$month] = round($monthlyStockAmount * 0.000001, 2);
                }

                $formattedResults[] = [
                    'partcode' => $inventory->partcode,
                    'partname' => $inventory->partname,
                    'specification' => $inventory->specification,
                    'unitprice' => $inventory->unitprice,
                    'exchangerate' => $inventory->exchangerate,
                    'monthly_data' => (object)$monthlyData,  // Cast array to object
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetch parts cost data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportInventorySummary(Request $request)
    {
        $year = $request->input('year');
        return Excel::download(new SparePartReferringInventorySummaryExport($year), 'inventory_summary_' . $year . '.xlsx');
    }

    public function exportPartsCost(Request $request)
    {
        $year = $request->input('year');

        return Excel::download(
            new SparePartReferringPartsCostExport($year),
            'parts_cost_' . $year . '.xlsx'
        );
    }

    public function exportMachinesCost(Request $request)
    {
        $year = $request->input('year');

        return Excel::download(
            new SparePartReferringMachineCostExport(
                $year,
                $request->input('month'),
                $request->input('plant_code'),
                $request->input('shop_code'),
                $request->input('machine_no')
            ),
            'machines_cost' . $year . '.xlsx'
        );
    }

    public function exportInventoryChangeCost(Request $request)
    {
        $filters = [
            'part_code' => $request->input('part_code'),
            'part_name' => $request->input('part_name'),
            'brand' => $request->input('brand'),
            'used_flag' => $request->input('used_flag'),
            'specification' => $request->input('specification'),
            'address' => $request->input('address'),
            'vendor_code' => $request->input('vendor_code'),
            'note' => $request->input('note'),
            'category' => $request->input('category'),
        ];

        return Excel::download(
            new SparePartReferringInventoryChangeCostExport(
                $request->input('year'),
                $filters,
                $request->input('limit', 100)
            ),
            'inventory_change_cost.xlsx'
        );
    }
}
