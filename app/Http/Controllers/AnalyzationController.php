<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;

class AnalyzationController extends Controller
{
    public function analyze(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'targetTerm' => 'nullable|integer|min:1|max:11',
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
                'targetSort' => 'required|integer|min:0|max:1',
                'selectedItems.*' => 'nullable|string'
            ]);

            // Set default values
            $validatedData = array_merge([
                'outofRank' => false,
                'maxRow' => 50
            ], $validatedData);

            // Get configurations
            $targetConfig = $this->getTargetConfig($validatedData['targetItem']);
            $sumConfig = $this->getSumConfig($validatedData['targetSum']);

            if (!$targetConfig || !$sumConfig) {
                throw new Exception('Invalid configuration parameters');
            }

            // Initialize query
            $query = DB::table('tbl_spkrecord as r')
                ->join('mas_machine as mm', 'r.machineno', '=', 'mm.machineno');

            // Handle term analysis if targetTerm is provided
            if (isset($validatedData['targetTerm'])) {
                $termConfig = $this->getTermFieldConfig($validatedData['targetTerm']);
                if (!$termConfig) {
                    throw new Exception('Invalid term configuration');
                }

                // Add term field to select
                $query->addSelect(DB::raw("{$termConfig['field']} as term"));
            }

            // Add source and target fields
            $query->addSelect(DB::raw($targetConfig['sourceField']))
                ->addSelect(DB::raw($targetConfig['targetQuery']))
                ->addSelect($sumConfig['selectField']);

            // Add date filters based on term type
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

            if (isset($validatedData['targetTerm']) && in_array($validatedData['targetTerm'], [2, 3, 4, 5])) {
                // For monthly analysis, format the dates accordingly
                $query->whereRaw("SUBSTRING(occurdate::text, 1, 6) BETWEEN ? AND ?", [
                    substr($startDate, 0, 6),
                    substr($endDate, 0, 6)
                ]);
            } else {
                $query->whereBetween('occurdate', [$startDate, $endDate]);
            }

            // Add filter
            $this->addFilters($query, $validatedData);

            // Add selected items filter if provided
            if (!empty($validatedData['selectedItems'])) {
                $query->whereIn(DB::raw($targetConfig['sourceField']), $validatedData['selectedItems']);
            }

            // Add grouping
            if (isset($validatedData['targetTerm'])) {
                $termConfig = $this->getTermFieldConfig($validatedData['targetTerm']);
                $query->groupBy(
                    DB::raw($termConfig['field']),
                    DB::raw($targetConfig['groupField'])
                );

                // Add ordering for term analysis
                $sortDirection = $validatedData['targetSort'] == 1 ? 'desc' : 'asc';
                $query->orderBy(DB::raw($termConfig['field']))
                    ->orderBy(DB::raw($sumConfig['sortField']), $sortDirection);
            } else {
                // Regular grouping and ordering
                $query->groupBy(DB::raw($targetConfig['groupField']));
                $sortDirection = $validatedData['targetSort'] == 1 ? 'desc' : 'asc';
                $query->orderBy(DB::raw($sumConfig['sortField']), $sortDirection);
            }

            // Handle pagination for regular analysis
            if (!isset($validatedData['targetTerm']) && !$validatedData['outofRank']) {
                $query->limit($validatedData['maxRow']);
            }

            // $results = $query->get()->map(function ($item) {
            //     // Convert all null values to '--' in the result set
            //     return collect($item)->map(function ($value) {
            //         return $value === null ? '--' : $value;
            //     })->toArray();
            // });

            $results = $query->get()->filter(function ($item) {
                // Remove any item that has at least one null value
                return !collect($item)->contains(null);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ],
                'message' => isset($validatedData['targetTerm']) ? 'Term analysis completed successfully' : 'Analysis completed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProcessedData(Request $request)
    {
        $analysisResponse = $this->analyze($request);
        $rawData = json_decode($analysisResponse->getContent(), true)['data'];
        $method = $request->input('method');
        $seeOnly = $request->input('seeOnly', 50);
        $sort = $request->input('sort', 'DESC');
        $itemCountFields = [
            'item_count',
            'machinestop',
            'linestop',
            'repair_manhour_internal',
            'maker_manhour',
            'total_maintenance_man_hour',
            'maker_cost',
            'part_cost',
            'staff_number',
            'wkt_sebelum_pekerjaan',
            'wkt_priodical_maintenance',
            'wkt_pertanyaan',
            'wkt_siapkan',
            'wkt_penelitian',
            'wkt_menunggu_part',
            'wkt_pekerjaan_maintenance',
            'wkt_konfirm'
        ];

        if ($method === 'One Term') {
            $processedData = collect($rawData)->map(function ($item, $index) use ($seeOnly) {
                return array_merge($item, [
                    'color' => $index < $seeOnly ? $this->getRandomColor() : '#5C4646'
                ]);
            });

            if ($sort !== null) {
                $processedData = $processedData->sortBy(function ($item) use ($itemCountFields, $sort) {
                    foreach ($itemCountFields as $field) {
                        if (isset($item[$field])) {
                            return $sort === 'DESC' ? -$item[$field] : $item[$field];
                        }
                    }
                    return 0;
                });
            }

            if ($seeOnly) {
                $processedData = $processedData->take($seeOnly);
            }

            return $processedData->values()->all();
        } else {
            $terms = [];
            $codes = collect();
            $groupedData = [];
            $codeDetails = [];

            // Find the item count field from the first data item
            $itemCountField = null;
            if (!empty($rawData)) {
                $firstItem = $rawData[0];
                foreach ($itemCountFields as $field) {
                    if (isset($firstItem[$field])) {
                        $itemCountField = $field;
                        break;
                    }
                }
            }

            // Process raw data
            foreach ($rawData as $item) {
                $code = $item['code'];
                $term = $item['term'];
                $itemCount = isset($item[$itemCountField]) ? intval($item[$itemCountField]) : 0;

                if (!isset($groupedData[$term])) {
                    $groupedData[$term] = [];
                    $terms[] = $term;
                }

                $groupedData[$term][$code] = $itemCount;
                $codes->push($code);

                if (!isset($codeDetails[$code])) {
                    $codeDetails[$code] = $item;
                }
            }

            // Calculate total counts and prepare final data
            $totalCounts = [];
            foreach ($codes->unique() as $code) {
                $totalCounts[$code] = collect($terms)->sum(function ($term) use ($groupedData, $code) {
                    return $groupedData[$term][$code] ?? 0;
                });
            }

            // Create and sort the final data array
            $processedData = $codes->unique()->map(function ($code) use ($codeDetails, $totalCounts, $itemCountField) {
                return array_merge(
                    $codeDetails[$code],
                    [
                        'color' => $this->getRandomColor(),
                        $itemCountField => $totalCounts[$code]
                    ]
                );
            });

            if ($sort !== null) {
                $processedData = $processedData->sortBy($itemCountField, SORT_REGULAR, $sort === 'DESC');
            }

            if ($seeOnly) {
                $processedData = $processedData->take($seeOnly);
            }

            return $processedData->values()->all();
        }
    }

    private function getRandomColor()
    {
        $randomColor = str_pad(dechex(mt_rand(0, 16777215)), 6, '0', STR_PAD_LEFT);
        return '#' . $randomColor;
    }

    public function exportSummaryExcel(Request $request)
    {
        try {
            $data = $this->getProcessedData($request);
            $method = $request->input('method');
            $series = $request->input('series', []);
            $labels = $request->input('labels', []);
            $targetItemColumn = $request->input('targetItemColumn');

            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $dataSheet = $spreadsheet->getActiveSheet();
            $dataSheet->setTitle('Data');

            if ($method === 'One Term') {
                // One Term format
                $headers = ['CODE', Str::upper($targetItemColumn), 'SUM'];
                $dataSheet->fromArray([$headers], null, 'A1');

                // Add data rows
                $rowIndex = 2;
                foreach ($data as $row) {
                    $rowData = [
                        $row['code'],
                        $row[$request->input('targetItemFieldName')] ?? '',
                        $row[$request->input('itemCountFieldName')] ?? ''
                    ];
                    $dataSheet->fromArray([$rowData], null, 'A' . $rowIndex);
                    $rowIndex++;
                }
            } else {
                // Multiple terms format
                // Prepare headers: CODE, NAME, Period columns, SUM, AVE
                $headers = ['CODE', Str::upper($targetItemColumn)];

                // Add period columns from labels
                foreach ($labels as $label) {
                    $headers[] = $label;
                }

                // Add SUM and AVE columns
                $headers[] = 'SUM';
                $headers[] = 'AVE';

                // Write headers
                $dataSheet->fromArray([$headers], null, 'A1');

                // Prepare and write data
                $rowIndex = 2;
                $lastCol = Coordinate::stringFromColumnIndex(count($headers));

                foreach ($data as $item) {
                    // Find corresponding series data
                    $seriesData = collect($series)->first(function ($s) use ($item) {
                        return $s['name'] === $item['code'];
                    });

                    if ($seriesData) {
                        $row = [
                            $item['code'],
                            $item[$request->input('targetItemFieldName')] ?? ''
                        ];

                        // Add period values
                        foreach ($seriesData['data'] as $value) {
                            $row[] = $value;
                        }

                        // Calculate and add SUM
                        $sum = array_sum($seriesData['data']);
                        $row[] = $sum;

                        // Calculate and add AVE
                        $average = count($seriesData['data']) > 0 ? $sum / count($seriesData['data']) : 0;
                        $row[] = round($average, 2);

                        $dataSheet->fromArray([$row], null, 'A' . $rowIndex);

                        $rowIndex++;
                    }
                }

                // Auto-size columns
                foreach (range('A', $lastCol) as $col) {
                    $dataSheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Apply number format to numeric columns
                $numericStartCol = Coordinate::stringFromColumnIndex(3); // Start from first period column
                $numericEndCol = Coordinate::stringFromColumnIndex(count($headers) - 2); // Before AVE column
                $dataSheet->getStyle($numericStartCol . '2:' . $lastCol . ($rowIndex - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Add total row
                $totalRow = ['Total', ''];
                $firstDataCol = Coordinate::stringFromColumnIndex(3);
                $lastDataCol = Coordinate::stringFromColumnIndex(count($headers));

                // Calculate totals for each period
                for ($col = 3; $col <= count($headers); $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $totalRow[] = "=SUM({$colLetter}2:{$colLetter}" . ($rowIndex - 1) . ")";
                }

                $dataSheet->fromArray([$totalRow], null, 'A' . $rowIndex);

                // Style the total row
                $dataSheet->getStyle('A' . $rowIndex . ':' . $lastCol . $rowIndex)
                    ->getFont()->setBold(true);

                // Add borders
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $dataSheet->getStyle('A1:' . $lastCol . $rowIndex)->applyFromArray($styleArray);

                // Style headers
                $dataSheet->getStyle('A1:' . $lastCol . '1')
                    ->getFont()->setBold(true);

                // Freeze pane
                $dataSheet->freezePane('C2');
            }

            // Create the Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'maintenance-data-' . date('Y-m-d') . '.xlsx';

            // Save to temp file and return
            $tempFile = tempnam(sys_get_temp_dir(), 'export');
            $writer->setIncludeCharts(true);
            $writer->save($tempFile);

            return Response::download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportDetailExcel(Request $request)
    {
        try {
            $data = $this->getProcessedData($request);
            $method = $request->input('method');
            $targetIndex = $request->input('targetItem');

            $codes = [];
            foreach ($data as $item) {
                if (isset($item['code'])) {
                    $codes[] = $item['code'];
                }
            }

            $targetConfig = $this->getTargetConfig($targetIndex);
            if (!$targetConfig) {
                throw new Exception('Invalid target configuration');
            }

            $query = DB::table('tbl_spkrecord as r')
                ->join('mas_machine as mm', 'r.machineno', '=', 'mm.machineno')
                ->join('mas_shop as ms', 'r.ordershop', '=', 'ms.shopcode')
                ->leftJoin('mas_situation as msit', 'r.situationcode', '=', 'msit.situationcode')
                ->leftJoin('mas_factor as mf', 'r.factorcode', '=', 'mf.factorcode')
                ->leftJoin('mas_measure as mm2', 'r.measurecode', '=', 'mm2.measurecode')
                ->leftJoin('mas_prevention as mp', 'r.preventioncode', '=', 'mp.preventioncode')
                ->leftJoin('mas_ltfactor as ml', 'r.ltfactorcode', '=', 'ml.ltfactorcode')
                ->leftJoin('mas_maker as mmk', 'mm.makercode', '=', 'mmk.makercode')
                ->select([
                    'r.recordid',
                    'r.occurdate',
                    'r.ordershop',
                    'ms.shopname',
                    'r.machineno',
                    'mm.machinename',
                    'mm.linecode',
                    'r.situationcode',
                    'msit.situationname',
                    'r.situation',
                    'r.factorcode',
                    'mf.factorname',
                    'r.factor',
                    'r.measurecode',
                    'mm2.measurename',
                    'r.measure',
                    'r.preventioncode',
                    'mp.preventionname',
                    'r.prevention',
                    'r.ltfactorcode',
                    'ml.ltfactorname',
                    'r.ltfactor',
                    'mmk.makername',
                    'r.machinestoptime',
                    'r.linestoptime',
                    'r.staffnum',
                    'r.makerservice',
                    'r.makerparts',
                    'r.partcostsum',
                    'r.inactivesum',
                    'r.periodicalsum',
                    'r.questionsum',
                    'r.preparesum',
                    'r.checksum',
                    'r.waitsum',
                    'r.repairsum',
                    'r.confirmsum',
                    'r.makerhour',
                    'r.comments',
                    'r.ordertitle',
                    'r.updatetime',
                    DB::raw("(CASE r.maintenancecode
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
                    END) as maintenancecode"),
                    DB::raw('(r.totalrepairsum * r.staffnum) as internal_manhour'),
                    DB::raw('(r.totalrepairsum * r.staffnum + r.makerhour) as total_manhour')
                ]);

            $sourceField = str_replace(' as code', '', $targetConfig['sourceField']);

            if (strpos($sourceField, 'SUBSTRING') !== false) {
                // For cases like SUBSTRING(r.machineno, 1, 3)
                $query->whereIn(DB::raw($sourceField), $codes);
            } elseif (strpos($sourceField, "COALESCE") !== false) {
                // For cases with COALESCE
                $query->whereIn(DB::raw($sourceField), $codes);
            } else {
                // For simple column references
                $query->whereIn($sourceField, $codes);
            }

            $startDate = sprintf(
                '%d%02d01',
                $request->input('startYear'),
                intval($request->input('startMonth'))
            );

            $endDate = Carbon::create(
                $request->input('endYear'),
                $request->input('endMonth'),
                1
            )->endOfMonth()->format('Ymd');

            $query->whereBetween('r.occurdate', [$startDate, $endDate]);

            $this->addFilters($query, $request->all());

            $detailedData = $query->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Detailed Data');

            $headers = [
                'SPK NO',
                'ORDER DATE',
                'SHOP CODE',
                'SHOP NAME',
                'MACHINE NO',
                'MACHINE NAME',
                'LINE',
                'TITLE',
                'NOTE STOP PANJANG',
                'NOTE URAIAN MASALAH',
                'NOTE PENYEBAB',
                'NOTE TINDAKAN',
                'NOTE SOLUSI',
                'COMMENTS',
                'MAINTENANCE CODE',
                'KODE URAIAN MASALAH',
                'NAMA URAIAN MASALAH',
                'KODE PENYEBAB',
                'NAMA PENYEBAB',
                'KODE TINDAKAN',
                'NAMA TINDAKAN',
                'KODE SOLUSI',
                'NAMA SOLUSI',
                'KODE STOP PANJANG',
                'NAMA STOP PANJANG',
                'MAKER NAME',
                'PARTS COST[IDR]',
                'STAFF NUMBER[ORANG]',
                'INTERNAL MANHOUR[MENIT]',
                'TOTAL MANHOUR[MENIT]',
                'MACHINE STOP TIME[MENIT]',
                'LINE STOP TIME[MENIT]',
                'MAKER MANHOUR[MENIT]',
                'MAKER SERVICE FEE[IDR]',
                'MAKER PARTS FEE[IDR]',
                'WKT SEBELUM PEKERJAAN[MENIT]',
                'WKT PERIODICAL[MENIT]',
                'WKT PERTANYAAN[MENIT]',
                'WKT SIAPKAN[MENIT]',
                'WKT PENELITIAN[MENIT]',
                'WKT MENUNGGU PART[MENIT]',
                'WKT PEKERJAAN [MENIT]',
                'WKT KONFIRM[MENIT]',
                'UPDATETIME'
            ];

            $sheet->fromArray([$headers], null, 'A1');

            // Add data
            $row = 2;
            foreach ($detailedData as $record) {
                $rowData = [
                    $record->recordid,
                    $record->occurdate,
                    $record->ordershop,
                    $record->shopname,
                    $record->machineno,
                    $record->machinename,
                    $record->linecode,
                    $record->ordertitle,
                    $record->ltfactor,
                    $record->situation,
                    $record->factor,
                    $record->measure,
                    $record->prevention,
                    $record->comments,
                    $record->maintenancecode,
                    $record->situationcode,
                    $record->situationname,
                    $record->factorcode,
                    $record->factorname,
                    $record->measurecode,
                    $record->measurename,
                    $record->preventioncode,
                    $record->preventionname,
                    $record->ltfactorcode,
                    $record->ltfactorname,
                    $record->makername,
                    $record->partcostsum,
                    $record->staffnum,
                    $record->internal_manhour,
                    $record->total_manhour,
                    $record->machinestoptime,
                    $record->linestoptime,
                    $record->makerhour,
                    $record->makerservice,
                    $record->makerparts,
                    $record->inactivesum,
                    $record->periodicalsum,
                    $record->questionsum,
                    $record->preparesum,
                    $record->checksum,
                    $record->waitsum,
                    $record->repairsum,
                    $record->confirmsum,
                    $record->updatetime,
                ];
                $sheet->fromArray([$rowData], null, 'A' . $row);
                $row++;
            }

            // Apply number format to numeric columns
            $numericColumns = range(20, 36); // Columns T to AJ
            foreach ($numericColumns as $colIndex) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getStyle($colLetter . '2:' . $colLetter . ($row - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            // Auto-size columns
            $lastColumnIndex = count($headers);
            for ($i = 1;
                $i <= $lastColumnIndex;
                $i++
            ) {
                $columnLetter = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Style the header row
            $lastCol = 'AS';
            $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ]);

            // Add borders
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:' . $lastCol . ($row - 1))->applyFromArray($styleArray);

            // Freeze the top row
            $sheet->freezePane('A2');

            // Create the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'maintenance-detail-' . date('Y-m-d') . '.xlsx';

            // Save to temp file and return
            $tempFile = tempnam(sys_get_temp_dir(), 'export');
            $writer->save($tempFile);

            return Response::download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportSvg(Request $request)
    {
        try {
            // Get the SVG content from the request
            $svgContent = $request->input('svgContent');

            if (!$svgContent) {
                throw new Exception('SVG content is required');
            }

            // Create response with SVG content
            $filename = 'maintenance-chart-' . date('Y-m-d') . '.svg';

            return Response::make($svgContent, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
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

    // Helper method to add common filters
    private function addFilters($query, $params)
    {
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

        // Add numeric filters
        $this->addNumericFilters($query, $params);
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
                'sourceField' => "(COALESCE(mm.shopcode, '') || '-' || COALESCE(mm.linecode, '')) as code",
                'targetQuery' => "(COALESCE(MAX(mm.shopname), '') || '-' || COALESCE(mm.linecode, '')) as line_name",
                'groupField' => 'mm.shopcode,mm.linecode'
            ],
            3 => [
                'sourceField' => "SUBSTRING(r.machineno, 1, 3) as code",
                'targetQuery' => "SUBSTRING(r.machineno, 1, 3) as machine_header",
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
                'rawField' => 'occurdate',
                'format' => null
            ],
            2 => [
                'field' => "SUBSTRING(occurdate::text, 1, 6)",
                'rawField' => "SUBSTRING(occurdate::text, 1, 6)",
                'format' => null
            ],
            3 => [
                'field' => "SUBSTRING(occurdate::text, 1, 6)",
                'rawField' => "SUBSTRING(occurdate::text, 1, 6)",
                'format' => null
            ],
            4 => [
                'field' => "SUBSTRING(occurdate::text, 1, 6)",
                'rawField' => "SUBSTRING(occurdate::text, 1, 6)",
                'format' => null
            ],
            5 => [
                'field' => "SUBSTRING(occurdate::text, 1, 6)",
                'rawField' => "SUBSTRING(occurdate::text, 1, 6)",
                'format' => null
            ],
            6 => [
                'field' => 'analysisquarter',
                'rawField' => 'analysisquarter',
                'format' => null
            ],
            7 => [
                'field' => 'analysisquarter',
                'rawField' => 'analysisquarter',
                'format' => null
            ],
            8 => [
                'field' => 'analysisquarter',
                'rawField' => 'analysisquarter',
                'format' => null
            ],
            9 => [
                'field' => 'analysishalf',
                'rawField' => 'analysishalf',
                'format' => null
            ],
            10 => [
                'field' => 'analysishalf',
                'rawField' => 'analysishalf',
                'format' => null
            ],
            11 => [
                'field' => 'analysisterm',
                'rawField' => 'analysisterm',
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
                    $query->where('r.machinestoptime', '<=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.machinestoptime', '>', $params['numMax']);
                }
                break;
            case 2: // Line Stop Time
                if (is_numeric($params['numMin'])) {
                    $query->where('r.linestoptime', '<=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.linestoptime', '>', $params['numMax']);
                }
                break;
            case 3: // Internal
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum) <= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum) > ?', [$params['numMax']]);
                }
                break;
            case 4: // Maker
                if (is_numeric($params['numMin'])) {
                    $query->where('r.makerhour', '<=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.makerhour', '>', $params['numMax']);
                }
                break;
            case 5: // Internal + Maker
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum + r.makerhour) <= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.totalrepairsum * r.staffnum + r.makerhour) > ?', [$params['numMax']]);
                }
                break;
            case 6: // Maker Cost
                if (is_numeric($params['numMin'])) {
                    $query->whereRaw('(r.makerservice + r.makerparts) <= ?', [$params['numMin']]);
                }
                if (is_numeric($params['numMax'])) {
                    $query->whereRaw('(r.makerservice + r.makerparts) > ?', [$params['numMax']]);
                }
                break;
            case 7: // Parts Cost
                if (is_numeric($params['numMin'])) {
                    $query->where('r.partcostsum', '<=', $params['numMin']);
                }
                if (is_numeric($params['numMax'])) {
                    $query->where('r.partcostsum', '>', $params['numMax']);
                }
                break;
        }
    }
}
