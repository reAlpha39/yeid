<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Http\Request;

class MaintenanceReportsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    protected $request;
    protected $chunkSize = 1000;
    protected $rowGroups = [];
    protected $rowCount = 1; // Start after header

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        try {
            $result = new Collection();

            // Get main data query builder
            $query = DB::table('tbl_spkrecord as s')
                ->leftJoin('mas_machine as m', 's.machineno', '=', 'm.machineno')
                ->select(
                    's.recordid',
                    's.maintenancecode',
                    's.orderdatetime',
                    's.orderempname',
                    's.ordershop',
                    's.machineno',
                    'm.machinename',
                    's.ordertitle',
                    's.orderfinishdate',
                    's.orderjobtype',
                    's.orderqtty',
                    's.orderstoptime',
                    's.updatetime',
                    DB::raw('COALESCE(s.planid, 0) AS planid'),
                    DB::raw('COALESCE(s.approval, 0) AS approval'),
                    DB::raw('COALESCE(s.createempcode, \'\') AS createempcode'),
                    DB::raw('COALESCE(s.createempname, \'\') AS createempname')
                );

            // Apply filters
            if ($this->request->input('only_active') == 'true') {
                $query->whereRaw('COALESCE(s.approval, 0) < 119');
            }

            if ($this->request->input('date')) {
                $date = $this->request->input('date');
                $query->whereRaw("TO_CHAR(s.orderdatetime, 'YYYY-MM') = ?", [$date]);
            }

            if ($this->request->input('search')) {
                $search = $this->request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('s.recordid', 'LIKE', "%$search%")
                        ->orWhere('s.maintenancecode', 'LIKE', "%$search%")
                        ->orWhere('s.orderempname', 'LIKE', "%$search%")
                        ->orWhere('s.ordershop', 'LIKE', "%$search%")
                        ->orWhere('s.machineno', 'LIKE', "%$search%")
                        ->orWhere('m.machinename', 'LIKE', "%$search%")
                        ->orWhere('s.ordertitle', 'LIKE', "%$search%");
                });
            }

            // Process in chunks
            $query->orderBy('s.recordid', 'DESC')
                ->chunk($this->chunkSize, function ($records) use (&$result) {
                    foreach ($records as $row) {
                        $startRow = $this->rowCount + 1;

                        // Get all work details
                        $workDetails = DB::table('tbl_work')
                            ->select(
                                'workid',
                                'staffname',
                                'inactivetime',
                                'periodicaltime',
                                'questiontime',
                                'preparetime',
                                'checktime',
                                'waittime',
                                'repairtime',
                                'confirmtime'
                            )
                            ->where('recordid', $row->recordid)
                            ->orderBy('workid')
                            ->get();

                        // Get all part details
                        $partDetails = DB::table('tbl_part')
                            ->select(
                                'partid',
                                'partcode',
                                'partname',
                                'specification',
                                'brand',
                                'qtty',
                                'price',
                                'currency',
                                'isstock'
                            )
                            ->where('recordid', $row->recordid)
                            ->orderBy('partid')
                            ->get();

                        $maxCount = max($workDetails->count(), $partDetails->count(), 1);

                        for ($i = 0; $i < $maxCount; $i++) {
                            $workDetail = $workDetails[$i] ?? null;
                            $partDetail = $partDetails[$i] ?? null;
                            $result->push($this->createRow($row, $workDetail, $partDetail));
                            $this->rowCount++;
                        }

                        // Store row group information if we have multiple rows
                        if ($maxCount > 1) {
                            $endRow = $this->rowCount;
                            $this->rowGroups[] = [
                                'start' => $startRow,
                                'end' => $endRow
                            ];
                        }
                    }
                });

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function createRow($row, $workDetail = null, $partDetail = null)
    {
        return [
            $row->recordid ?? '',
            $row->maintenancecode ?? '',
            $row->orderdatetime ?? '',
            $row->orderempname ?? '',
            $row->ordershop ?? '',
            $row->machineno ?? '',
            $row->machinename ?? '',
            $row->ordertitle ?? '',
            $row->orderfinishdate ?? '',
            $row->orderjobtype ?? '',
            $row->orderqtty ?? '',
            $row->orderstoptime ?? '',
            $row->updatetime ?? '',
            $row->planid ?? 0,
            $row->approval ?? 0,
            $row->createempcode ?? '',
            $row->createempname ?? '',
            // Work Details
            $workDetail?->workid ?? '',
            $workDetail?->staffname ?? '',
            $workDetail?->inactivetime ?? 0,
            $workDetail?->periodicaltime ?? 0,
            $workDetail?->questiontime ?? 0,
            $workDetail?->preparetime ?? 0,
            $workDetail?->checktime ?? 0,
            $workDetail?->waittime ?? 0,
            $workDetail?->repairtime ?? 0,
            $workDetail?->confirmtime ?? 0,
            // Part Details
            $partDetail?->partid ?? '',
            $partDetail?->partcode ?? '',
            $partDetail?->partname ?? '',
            $partDetail?->specification ?? '',
            $partDetail?->brand ?? '',
            $partDetail?->qtty ?? 0,
            $partDetail?->price ?? 0,
            $partDetail?->currency ?? '',
            $partDetail?->isstock ?? 0
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->rowGroups as $group) {
                    // Merge cells for main data columns (A to Q)
                    foreach (range('A', 'Q') as $column) {
                        $event->sheet->mergeCells(sprintf(
                            '%s%d:%s%d',
                            $column,
                            $group['start'],
                            $column,
                            $group['end']
                        ));
                    }

                    // Add borders to the merged group
                    // $event->sheet->getStyle(sprintf('A%d:AK%d', $group['start'], $group['end']))
                    //     ->getBorders()->getAllBorders()->setBorderStyle('thin');
                }

                // Center align all merged cells
                $event->sheet->getStyle(sprintf('A2:Q%d', $this->rowCount))
                    ->getAlignment()->setVertical('center');
            }
        ];
    }

    public function headings(): array
    {
        return [
            'Record ID',
            'Maintenance Code',
            'Order DateTime',
            'Order Employee Name',
            'Order Shop',
            'Machine No',
            'Machine Name',
            'Order Title',
            'Order Finish Date',
            'Order Job Type',
            'Order Quantity',
            'Order Stop Time',
            'Update Time',
            'Plan ID',
            'Approval',
            'Create Employee Code',
            'Create Employee Name',
            // Work Details Headers
            'Work ID',
            'Staff Name',
            'Inactive Time',
            'Periodical Time',
            'Question Time',
            'Prepare Time',
            'Check Time',
            'Wait Time',
            'Repair Time',
            'Confirm Time',
            // Part Details Headers
            'Part ID',
            'Part Code',
            'Part Name',
            'Specification',
            'Brand',
            'Quantity',
            'Price',
            'Currency',
            'Is Stock'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ];
    }
}