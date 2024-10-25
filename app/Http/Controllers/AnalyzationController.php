<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class AnalyzationController extends Controller
{
    public function analyze(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'targetItem' => 'required|integer|min:0|max:10',
                'targetSum' => 'required|integer|min:0|max:16',
                'startYear' => 'required|digits:4',
                'startMonth' => 'required|min:1|max:12',
                'endYear' => 'required|digits:4',
                'endMonth' => 'required|min:1|max:12',
                'tdivision' => 'nullable|string',
                'section' => 'nullable|string',
                'line' => 'nullable|string',
                'machineNo' => 'nullable|string',
                'situation' => 'nullable|string',
                'measures' => 'nullable|string',
                'factor' => 'nullable|string',
                'factorLt' => 'nullable|string',
                'preventive' => 'nullable|string',
                'machineMaker' => 'nullable|string',
                'numItem' => 'nullable|integer|min:1|max:7',
                'numMin' => 'nullable|numeric',
                'numMax' => 'nullable|numeric',
                'outofRank' => 'nullable|boolean',
                'maxRow' => 'nullable|integer|min:1',
                'targetSort' => 'required|integer|min:0|max:1'
            ]);

            // Set default values for optional parameters
            $params = array_merge([
                'outofRank' => false,
                'maxRow' => 50
            ], $validatedData);

            $query = $this->createAnalyzeSQL($params);
            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Analysis completed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createAnalyzeSQL($params)
    {
        // Set default values for optional parameters
        $params = array_merge([
            'outofRank' => false,
            'maxRow' => 50,
            'tdivision' => null,
            'section' => null,
            'line' => null,
            'machineNo' => null,
            'situation' => null,
            'measures' => null,
            'factor' => null,
            'factorLt' => null,
            'preventive' => null,
            'machineMaker' => null,
            'numItem' => null,
            'numMin' => null,
            'numMax' => null,
        ], $params);

        $sourceFieldName = "";
        $targetNameQuery = "";
        $groupFieldName = "";
        $sortFieldName = "";

        // Convert VB's Select Case to PHP switch for target item selection
        switch ($params['targetItem']) {
            case 0:
                $sourceFieldName = "maintenancecode";
                $targetNameQuery = DB::raw("(CASE maintenancecode 
                    WHEN '01' THEN 'UM' 
                    WHEN '02' THEN 'BM' 
                    WHEN '03' THEN 'TBC'
                    WHEN '04' THEN 'TBA' 
                    WHEN '05' THEN 'PvM' 
                    WHEN '06' THEN 'FM' 
                    WHEN '07' THEN 'CM'
                    WHEN '08' THEN 'CHECK' 
                    WHEN '09' THEN 'LAYOUT' 
                    ELSE '--' 
                END) as nama");
                $groupFieldName = "r.maintenancecode";
                break;
            case 1:
                $sourceFieldName = "ordershop";
                $targetNameQuery = DB::raw("(SELECT COALESCE(shopname,'') FROM mas_shop ms1 WHERE ms1.shopcode = r.ordershop) as shop");
                $groupFieldName = "r.ordershop";
                break;
            case 2:
                $sourceFieldName = DB::raw("(mm.shopcode || '-' || mm.linecode) AS code");
                $targetNameQuery = DB::raw("(MAX(mm.shopname) || '-' || mm.linecode) as line_name");
                $groupFieldName = "mm.shopcode,mm.linecode";
                break;
            case 3:
                $sourceFieldName = DB::raw("SUBSTRING(r.machineno, 1, 3) AS code");
                $targetNameQuery = DB::raw("SUBSTRING(r.machineno, 1, 3) as machineheader");
                $groupFieldName = DB::raw("SUBSTRING(r.machineno, 1, 3)");
                break;
            case 4:
                $sourceFieldName = "r.machineno AS code";
                $targetNameQuery = DB::raw("(SELECT machinename FROM mas_machine mm1 WHERE mm1.machineno = r.machineno) as machine_no");
                $groupFieldName = "r.machineno";
                break;
            case 5:
                $sourceFieldName = "situationcode AS code";
                $targetNameQuery = DB::raw("(SELECT situationname FROM mas_situation ms2 WHERE ms2.situationcode = r.situationcode) as uraian_masalah");
                $groupFieldName = "r.situationcode";
                break;
            case 6:
                $sourceFieldName = "factorcode AS code";
                $targetNameQuery = DB::raw("(SELECT factorname FROM mas_factor mf1 WHERE mf1.factorcode = r.factorcode) as penyebab");
                $groupFieldName = "r.factorcode";
                break;
            case 7:
                $sourceFieldName = "measurecode AS code";
                $targetNameQuery = DB::raw("(SELECT measurename FROM mas_measure mm2 WHERE mm2.measurecode = r.measurecode) as tindakan");
                $groupFieldName = "r.measurecode";
                break;
            case 8:
                $sourceFieldName = "preventioncode AS code";
                $targetNameQuery = DB::raw("(SELECT preventionname FROM mas_prevention mv1 WHERE mv1.preventioncode = r.preventioncode) as solution");
                $groupFieldName = "r.preventioncode";
                break;
            case 9:
                $sourceFieldName = "ltfactorcode AS code";
                $targetNameQuery = DB::raw("(SELECT ltfactorname FROM mas_ltfactor mf2 WHERE mf2.ltfactorcode = r.ltfactorcode) as stop_panjang");
                $groupFieldName = "r.ltfactorcode";
                break;
            case 10:
                $sourceFieldName = "mm.makercode AS code";
                $targetNameQuery = DB::raw("(SELECT COALESCE(makername,'') FROM mas_maker mm1 WHERE mm1.makercode = mm.makercode) as maker_name");
                $groupFieldName = "mm.makercode";
                break;
        }

        // Initialize query builder
        $query = DB::table('tbl_spkrecord as r')
            ->join('mas_machine as mm', 'r.machineno', '=', 'mm.machineno');

        // Handle pagination if needed
        if (!$params['outofRank']) {
            $query->limit($params['maxRow']);
        }

        // Select fields based on target sum selection
        switch ($params['targetSum']) {
            case 0: // COUNT
                $sortFieldName = "count(r.recordid)";
                $query->addSelect(DB::raw("count(r.recordid) as item_count"));
                break;
            case 1: // MACHINE STOP TIME
                $sortFieldName = "sum(COALESCE(r.machinestoptime,0))";
                $query->select(DB::raw("round(CAST(sum(COALESCE(r.machinestoptime,0)) as numeric), 2) as machinestop"));
                break;
            case 2: // LINE STOP TIME
                $sortFieldName = "sum(COALESCE(r.linestoptime,0))";
                $query->select(DB::raw("round(CAST(sum(COALESCE(r.linestoptime,0)) as numeric), 2) as linestop"));
                break;
            case 3: // INTERNAL MAINTENANCE MANHOUR
                $sortFieldName = "sum(COALESCE(r.totalrepairsum*r.staffnum,0))";
                $query->select(DB::raw("round(CAST(sum(COALESCE(r.totalrepairsum*r.staffnum,0))*0.016666 as numeric), 2) as repair_manhour_internal"));
                break;
            case 4: // MAKER MAINTENANCE MANHOUR
                $sortFieldName = "sum(COALESCE(r.makerhour,0))";
                $query->select(DB::raw("round(CAST(sum(COALESCE(r.makerhour,0))*0.016666 as numeric), 2) as maker_manhour"));
                break;
            case 5: // TOTAL MANHOUR
                $sortFieldName = "round(CAST((sum(COALESCE(r.totalrepairsum*r.staffnum,0)) + sum(COALESCE(r.makerhour,0)))*0.016666 as numeric), 2)";
                $query->select(DB::raw("round(CAST((sum(COALESCE(r.totalrepairsum*r.staffnum,0)) + sum(COALESCE(r.makerhour,0)))*0.016666 as numeric), 2) as total_maintenance_man_hour"));
                break;
            case 6: // MAKER COST
                $sortFieldName = "sum(COALESCE(r.makerservice,0))+sum(COALESCE(r.makerparts,0))";
                $query->select(DB::raw("sum(COALESCE(r.makerservice,0))+sum(COALESCE(r.makerparts,0)) as maker_cost"));
                break;
            case 7: // PARTS COST
                $sortFieldName = "sum(COALESCE(r.partcostsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.partcostsum,0)) as part_cost"));
                break;
            case 8: // STAFF NUMBER
                $sortFieldName = "sum(COALESCE(r.staffnum,0))";
                $query->select(DB::raw("sum(COALESCE(r.staffnum,0)) as staff_number"));
                break;
            case 9: // WKT SEBELUM PEKERJAAN
                $sortFieldName = "sum(COALESCE(r.inactivesum,0))";
                $query->select(DB::raw("sum(COALESCE(r.inactivesum,0)) as wkt_sebelum_pekerjaan"));
                break;
            case 10: // WKT PRIODICAL MAINTENANCE
                $sortFieldName = "sum(COALESCE(r.periodicalsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.periodicalsum,0)) as wkt_priodical_maintenance"));
                break;
            case 11: // WKT PERTANYAAN
                $sortFieldName = "sum(COALESCE(r.questionsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.questionsum,0)) as wkt_pertanyaan"));
                break;
            case 12: // WKT SIAPKAN
                $sortFieldName = "sum(COALESCE(r.preparesum,0))";
                $query->select(DB::raw("sum(COALESCE(r.preparesum,0)) as wkt_siapkan"));
                break;
            case 13: // WKT PENELITIAN
                $sortFieldName = "sum(COALESCE(r.checksum,0))";
                $query->select(DB::raw("sum(COALESCE(r.checksum,0)) as wkt_penelitian"));
                break;
            case 14: // WKT MENUNGGU PART
                $sortFieldName = "sum(COALESCE(r.waitsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.waitsum,0)) as wkt_menunggu_part"));
                break;
            case 15: // WKT PEKERJAAN MAINTENANCE
                $sortFieldName = "sum(COALESCE(r.repairsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.repairsum,0)) as wkt_pekerjaan_maintenance"));
                break;
            case 16: // WKT KONFIRM
                $sortFieldName = "sum(COALESCE(r.confirmsum,0))";
                $query->select(DB::raw("sum(COALESCE(r.confirmsum,0)) as wkt_konfirm"));
                break;
        }

        // Add source field and target name query to select
        $query->addSelect(DB::raw($sourceFieldName));
        $query->addSelect($targetNameQuery);

        // Format start date: YYYYMMDD
        $startDate = sprintf(
            '%d%02d01',
            $params['startYear'],
            intval($params['startMonth'])
        );

        // Format end date: YYYYMMDD (last day of month)
        $endDate = Carbon::createFromDate(
            $params['endYear'],
            $params['endMonth'],
            1
        )->endOfMonth()->format('Ymd');

        $query->whereBetween('occurdate', [$startDate, $endDate]);


        // Add additional filters
        if (!empty($params['tdivision'])) {
            $query->where('r.maintenancecode', $params['tdivision']);
        }
        if (!empty($params['section'])) {
            $query->where('r.ordershop', $params['section']);
        }
        if (!empty($params['line'])) {
            $query->where('mm.linecode', $params['line']);
        }
        if (!empty($params['machineNo'])) {
            $query->where('r.machineno', 'like', $params['machineNo'] . '%');
        }
        if (!empty($params['situation'])) {
            $query->where('r.situationcode', $params['situation']);
        }
        if (!empty($params['measures'])) {
            $query->where('r.measurecode', $params['measures']);
        }
        if (!empty($params['factor'])) {
            $query->where('r.factorcode', $params['factor']);
        }
        if (!empty($params['factorLt'])) {
            $query->where('r.ltfactorcode', $params['factorLt']);
        }
        if (!empty($params['preventive'])) {
            $query->where('r.preventioncode', $params['preventive']);
        }
        if (!empty($params['machineMaker'])) {
            $query->where('mm.makercode', $params['machineMaker']);
        }

        // Add numeric range filters if specified
        if (!empty($params['numItem'])) {
            switch ($params['numItem']) {
                case 1: // WKT MACHINE STOP
                    if (is_numeric($params['numMin'])) {
                        $query->where('r.machinestoptime', '>=', $params['numMin']);
                    }
                    if (is_numeric($params['numMax'])) {
                        $query->where('r.machinestoptime', '<', $params['numMax']);
                    }
                    break;
                case 2: // WKT LINE STOP
                    if (is_numeric($params['numMin'])) {
                        $query->where('r.linestoptime', '>=', $params['numMin']);
                    }
                    if (is_numeric($params['numMax'])) {
                        $query->where('r.linestoptime', '<', $params['numMax']);
                    }
                    break;
                case 3: // INTERNAL
                    if (is_numeric($params['numMin'])) {
                        $query->whereRaw('(r.totalrepairsum * r.staffnum) >= ?', [$params['numMin']]);
                    }
                    if (is_numeric($params['numMax'])) {
                        $query->whereRaw('(r.totalrepairsum * r.staffnum) < ?', [$params['numMax']]);
                    }
                    break;
                    // Add other numeric range cases...
            }
        }

        // Add grouping
        $query->groupBy(DB::raw($groupFieldName));

        // Add sorting
        $sortDirection = $params['targetSort'] < 1 ? 'desc' : 'asc';
        $query->orderByRaw("{$sortFieldName} {$sortDirection}");

        // Handle pagination if needed
        if (!$params['outofRank']) {
            $query->limit($params['maxRow']);
        }

        return $query;
    }
}
