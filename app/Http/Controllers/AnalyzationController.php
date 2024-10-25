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

    public function termAnalyze(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'targetTerm' => 'required|integer|min:1|max:11',
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
                'targetSort' => 'nullable|integer|min:0|max:1',
                'selectedItems.*' => 'nullable|string'
            ]);

            // Get configurations
            $termConfig = $this->getTermFieldConfig($validatedData['targetTerm']);
            $targetConfig = $this->getTargetConfig($validatedData['targetItem']);
            $sumConfig = $this->getSumConfig($validatedData['targetSum']);

            if (!$termConfig || !$targetConfig || !$sumConfig) {
                throw new Exception('Invalid configuration parameters');
            }

            // Initialize query
            $query = DB::table('tbl_spkrecord as r')
                ->join('mas_machine as mm', 'r.machineno', '=', 'mm.machineno');

            // Add term field
            $query->addSelect(DB::raw("{$termConfig['field']} as term"));

            // Add source and target fields
            $query->addSelect(DB::raw($targetConfig['sourceField']))
                ->addSelect(DB::raw($targetConfig['targetQuery']))
                ->addSelect($sumConfig['selectField']);

            // Add date filters
            $startDate = sprintf(
                '%d%02d01',
                $validatedData['startYear'],
                intval($validatedData['startMonth'])
            );

            $endDate = Carbon::create(
                $validatedData['endYear'],
                $validatedData['endMonth'],
                1
            )->endOfMonth()->format('Ymd');

            $query->whereBetween('occurdate', [$startDate, $endDate]);

            // Add division filter
            if (!empty($validatedData['tdivision'])) {
                $query->where('r.maintenancecode', $validatedData['tdivision']);
            }

            // Add section filter
            if (!empty($validatedData['section'])) {
                $query->where('r.ordershop', $validatedData['section']);
            }

            // Add line filter
            if (!empty($validatedData['line'])) {
                $query->where('mm.linecode', $validatedData['line']);
            }

            // Add machine number filter
            if (!empty($validatedData['machineNo'])) {
                $query->where('r.machineno', 'like', $validatedData['machineNo'] . '%');
            }

            // Add situation filter
            if (!empty($validatedData['situation'])) {
                $query->where('r.situationcode', $validatedData['situation']);
            }

            // Add measures filter
            if (!empty($validatedData['measures'])) {
                $query->where('r.measurecode', $validatedData['measures']);
            }

            // Add factor filter
            if (!empty($validatedData['factor'])) {
                $query->where('r.factorcode', $validatedData['factor']);
            }

            // Add LT factor filter
            if (!empty($validatedData['factorLt'])) {
                $query->where('r.ltfactorcode', $validatedData['factorLt']);
            }

            // Add preventive filter
            if (!empty($validatedData['preventive'])) {
                $query->where('r.preventioncode', $validatedData['preventive']);
            }

            // Add machine maker filter
            if (!empty($validatedData['machineMaker'])) {
                $query->where('mm.makercode', $validatedData['machineMaker']);
            }

            // Add numeric filters
            $this->addNumericFilters($query, $validatedData);

            // Add selected items filter if provided
            if (!empty($validatedData['selectedItems'])) {
                $query->whereIn(DB::raw($targetConfig['sourceField']), $validatedData['selectedItems']);
            }

            // Add grouping
            $query->groupBy(
                DB::raw($termConfig['field']),
                DB::raw($targetConfig['groupField'])
            );

            // Add ordering
            $sortDirection = ($validatedData['targetSort'] ?? 0) < 1 ? 'desc' : 'asc';
            $query->orderBy(DB::raw($termConfig['field']))
                ->orderBy(DB::raw($sumConfig['sortField']), $sortDirection);

            $results = $query->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ],
                'message' => 'Term analysis completed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing term analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper method for getting formatted parameters from UI inputs
    private function getFormattedParameter($text, $separator = '|')
    {
        if (empty($text)) {
            return null;
        }
        $parts = explode($separator, $text);
        return $parts[0] ?? null;
    }

    private function getTargetConfig($targetIndex)
    {
        $configs = [
            0 => [
                'sourceField' => 'maintenancecode as code',
                'targetQuery' => "(CASE maintenancecode 
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
                END) as nama",
                'groupField' => 'r.maintenancecode'
            ],
            1 => [
                'sourceField' => 'ordershop as code',
                'targetQuery' => "(SELECT COALESCE(shopname,'') FROM mas_shop ms1 WHERE ms1.shopcode = r.ordershop) as shop",
                'groupField' => 'r.ordershop'
            ],
            2 => [
                'sourceField' => "(mm.shopcode || '-' || mm.linecode) as code",
                'targetQuery' => "(MAX(mm.shopname) || '-' || mm.linecode) as line_name",
                'groupField' => 'mm.shopcode,mm.linecode'
            ],
            3 => [
                'sourceField' => "SUBSTRING(r.machineno, 1, 3) as code",
                'targetQuery' => "SUBSTRING(r.machineno, 1, 3) as machineheader",
                'groupField' => "SUBSTRING(r.machineno, 1, 3)"
            ],
            4 => [
                'sourceField' => 'r.machineno as code',
                'targetQuery' => "(SELECT machinename FROM mas_machine mm1 WHERE mm1.machineno = r.machineno) as machine_no",
                'groupField' => 'r.machineno'
            ],
            5 => [
                'sourceField' => 'situationcode as code',
                'targetQuery' => "(SELECT situationname FROM mas_situation ms2 WHERE ms2.situationcode = r.situationcode) as uraian_masalah",
                'groupField' => 'r.situationcode'
            ],
            6 => [
                'sourceField' => 'factorcode as code',
                'targetQuery' => "(SELECT factorname FROM mas_factor mf1 WHERE mf1.factorcode = r.factorcode) as penyebab",
                'groupField' => 'r.factorcode'
            ],
            7 => [
                'sourceField' => 'measurecode as code',
                'targetQuery' => "(SELECT measurename FROM mas_measure mm2 WHERE mm2.measurecode = r.measurecode) as tindakan",
                'groupField' => 'r.measurecode'
            ],
            8 => [
                'sourceField' => 'preventioncode as code',
                'targetQuery' => "(SELECT preventionname FROM mas_prevention mv1 WHERE mv1.preventioncode = r.preventioncode) as solution",
                'groupField' => 'r.preventioncode'
            ],
            9 => [
                'sourceField' => 'ltfactorcode as code',
                'targetQuery' => "(SELECT ltfactorname FROM mas_ltfactor mf2 WHERE mf2.ltfactorcode = r.ltfactorcode) as stop_panjang",
                'groupField' => 'r.ltfactorcode'
            ],
            10 => [
                'sourceField' => 'mm.makercode as code',
                'targetQuery' => "(SELECT COALESCE(makername,'') FROM mas_maker mm1 WHERE mm1.makercode = mm.makercode) as maker_name",
                'groupField' => 'mm.makercode'
            ]
        ];

        return $configs[$targetIndex] ?? null;
    }

    private function getSumConfig($sumIndex)
    {
        $configs = [
            0 => [
                'sortField' => 'item_count',
                'selectField' => DB::raw('count(r.recordid) as item_count')
            ],
            1 => [
                'sortField' => 'sum(COALESCE(r.machinestoptime,0))',
                'selectField' => DB::raw('round(CAST(sum(COALESCE(r.machinestoptime,0)) as numeric)) as machinestop')
            ],
            2 => [
                'sortField' => 'sum(COALESCE(r.linestoptime,0))',
                'selectField' => DB::raw('round(CAST(sum(COALESCE(r.linestoptime,0)) as numeric)) as linestop')
            ],
            3 => [
                'sortField' => 'sum(COALESCE(r.totalrepairsum*r.staffnum,0))',
                'selectField' => DB::raw('round(CAST(sum(COALESCE(r.totalrepairsum*r.staffnum,0))*0.016666 as numeric), 2) as repair_manhour_internal')
            ],
            4 => [
                'sortField' => 'sum(COALESCE(r.makerhour,0))',
                'selectField' => DB::raw('round(CAST(sum(COALESCE(r.makerhour,0))*0.016666 as numeric), 2) as maker_manhour')
            ],
            5 => [
                'sortField' => 'round(CAST((sum(COALESCE(r.totalrepairsum*r.staffnum,0)) + sum(COALESCE(r.makerhour,0)))*0.016666 as numeric), 2)',
                'selectField' => DB::raw('round(CAST((sum(COALESCE(r.totalrepairsum*r.staffnum,0)) + sum(COALESCE(r.makerhour,0)))*0.016666 as numeric), 2) as total_maintenance_man_hour')
            ],
            6 => [
                'sortField' => 'sum(COALESCE(r.makerservice,0))+sum(COALESCE(r.makerparts,0))',
                'selectField' => DB::raw('sum(COALESCE(r.makerservice,0))+sum(COALESCE(r.makerparts,0)) as maker_cost')
            ],
            7 => [
                'sortField' => 'sum(COALESCE(r.partcostsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.partcostsum,0)) as part_cost')
            ],
            8 => [
                'sortField' => 'sum(COALESCE(r.staffnum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.staffnum,0)) as staff_number')
            ],
            9 => [
                'sortField' => 'sum(COALESCE(r.inactivesum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.inactivesum,0)) as wkt_sebelum_pekerjaan')
            ],
            10 => [
                'sortField' => 'sum(COALESCE(r.periodicalsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.periodicalsum,0)) as wkt_priodical_maintenance')
            ],
            11 => [
                'sortField' => 'sum(COALESCE(r.questionsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.questionsum,0)) as wkt_pertanyaan')
            ],
            12 => [
                'sortField' => 'sum(COALESCE(r.preparesum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.preparesum,0)) as wkt_siapkan')
            ],
            13 => [
                'sortField' => 'sum(COALESCE(r.checksum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.checksum,0)) as wkt_penelitian')
            ],
            14 => [
                'sortField' => 'sum(COALESCE(r.waitsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.waitsum,0)) as wkt_menunggu_part')
            ],
            15 => [
                'sortField' => 'sum(COALESCE(r.repairsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.repairsum,0)) as wkt_pekerjaan_maintenance')
            ],
            16 => [
                'sortField' => 'sum(COALESCE(r.confirmsum,0))',
                'selectField' => DB::raw('sum(COALESCE(r.confirmsum,0)) as wkt_konfirm')
            ]
        ];

        return $configs[$sumIndex] ?? null;
    }

    private function getTermFieldConfig($termIndex)
    {
        $configs = [
            1 => [
                'field' => 'occurdate',
                'format' => null
            ],
            2 => [
                'field' => DB::raw("SUBSTRING(occurdate, 1, 6)"),
                'format' => null
            ],
            3 => [
                'field' => DB::raw("SUBSTRING(occurdate, 1, 6)"),
                'format' => null
            ],
            4 => [
                'field' => DB::raw("SUBSTRING(occurdate, 1, 6)"),
                'format' => null
            ],
            5 => [
                'field' => DB::raw("SUBSTRING(occurdate, 1, 6)"),
                'format' => null
            ],
            6 => [
                'field' => 'analysisquarter',
                'format' => null
            ],
            7 => [
                'field' => 'analysisquarter',
                'format' => null
            ],
            8 => [
                'field' => 'analysisquarter',
                'format' => null
            ],
            9 => [
                'field' => 'analysishalf',
                'format' => null
            ],
            10 => [
                'field' => 'analysishalf',
                'format' => null
            ],
            11 => [
                'field' => 'analysisterm',
                'format' => null
            ]
        ];

        return $configs[$termIndex] ?? $configs[1];
    }

    private function addNumericFilters($query, $params)
    {
        if (!isset($params['numItem']) || !is_numeric($params['numItem'])) {
            return;
        }

        switch ($params['numItem']) {
            case 1: // Machine Stop Time
                if (is_numeric($params['numMin'])) {
                    $query->where('r.machinestoptime', '>=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.machinestoptime', '<', $params['numMax']);
                }
                break;
            case 2: // Line Stop Time
                if (is_numeric($params['numMin'])) {
                    $query->where('r.linestoptime', '>=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.linestoptime', '<', $params['numMax']);
                }
                break;
            case 3: // Internal
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum) >= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum) < ?', [$params['numMax']]);
                }
                break;
            case 4: // Maker
                if (is_numeric($params['numMin'])) {
                    $query->where('r.makerhour', '>=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.makerhour', '<', $params['numMax']);
                }
                break;
            case 5: // Internal + Maker
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum + r.makerhour) >= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum + r.makerhour) < ?', [$params['numMax']]);
                }
                break;
            case 6: // Maker Cost
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.makerservice + r.makerparts) >= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.makerservice + r.makerparts) < ?', [$params['numMax']]);
                }
                break;
            case 7: // Parts Cost
                if (is_numeric($params['numMin'])) {
                    $query->where('r.partcostsum', '>=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.partcostsum', '<', $params['numMax']);
                }
                break;
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
                $sourceFieldName = DB::raw("(mm.shopcode || '-' || mm.linecode) as code");
                $targetNameQuery = DB::raw("(MAX(mm.shopname) || '-' || mm.linecode) as line_name");
                $groupFieldName = "mm.shopcode,mm.linecode";
                break;
            case 3:
                $sourceFieldName = DB::raw("SUBSTRING(r.machineno, 1, 3) AS code");
                $targetNameQuery = DB::raw("SUBSTRING(r.machineno, 1, 3) AS machineheader");
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
