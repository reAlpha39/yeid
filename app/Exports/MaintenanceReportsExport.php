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

    protected function convertApproval($approval)
    {
        $result = "";
        (intval($approval) & 16) === 16 ? ($result .= "S") : ($result .= "B");
        (intval($approval) & 32) === 32 ? ($result .= "S") : ($result .= "B");
        (intval($approval) & 64) === 64 ? ($result .= "S") : ($result .= "B");
        return $result;
    }

    public function collection()
    {
        try {
            $result = new Collection();

            // Get main data query builder
            $query = DB::table('tbl_spkrecord as s')
                ->leftJoin('mas_machine as m', 's.machineno', '=', 'm.machineno')
                ->select(
                    's.*',
                    DB::raw('COALESCE(s.planid, 0) AS planid'),
                    DB::raw('COALESCE(s.approval, 0) AS approval'),
                    DB::raw('COALESCE(s.createempcode, \'\') AS createempcode'),
                    DB::raw('COALESCE(s.createempname, \'\') AS createempname')
                );

            // Only active records filter
            // if ($this->request->input('only_active') === 'true') {
            //     $query->whereRaw('COALESCE(s.approval, 0) < 119');
            // }

            // Date filter
            if ($this->request->input('date')) {
                $query->whereRaw("TO_CHAR(s.orderdatetime, 'YYYY-MM') = ?", [$this->request->input('date')]);
            }

            // Shop code filter
            if ($this->request->input('shop_code')) {
                $query->where('s.ordershop', $this->request->input('shop_code'));
            }

            // Machine code filter
            if ($this->request->input('machine_code')) {
                $query->where('s.machineno', $this->request->input('machine_code'));
            }

            // Maintenance code filter
            if ($this->request->input('maintenance_code')) {
                $query->where('s.maintenancecode', $this->request->input('maintenance_code'));
            }

            // Order name filter
            if ($this->request->input('order_name')) {
                $query->where('s.orderempname', $this->request->input('order_name'));
            }

            // Search filter
            if ($this->request->input('search')) {
                $searchTerm = $this->request->input('search') . '%';
                $query->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("CAST(s.recordid AS TEXT) ILIKE ?", [$searchTerm])
                        ->orWhere('s.ordertitle', 'ILIKE', $searchTerm);
                });
            }

            // Status filter
            if ($this->request->input('status')) {
                $query->where(function ($query) {
                    switch ($this->request->input('status')) {
                        case 'GRAY':
                            $query->where('s.approval', '>=', 112);
                            break;
                        case 'GREEN':
                            $query->where('s.approval', '>=', 4)
                                ->where('s.approval', '<', 112);
                            break;
                        case 'YELLOW':
                            $query->where('s.approval', '<', 4)
                                ->where('s.planid', '>', 0);
                            break;
                        case 'ORANGE':
                            $query->where('s.approval', '<', 4)
                                ->where('s.planid', '=', 0);
                            break;
                        case 'WHITE':
                            $query->where(function ($q) {
                                $q->whereRaw('NOT (
                                (s.approval >= 112) OR
                                (s.approval >= 4 AND s.approval < 112) OR
                                (s.approval < 4 AND s.planid > 0) OR
                                (s.approval < 4 AND s.planid = 0)
                            )');
                            });
                            break;
                    }
                });
            }

            $query->orderBy('s.recordid', 'DESC')
                ->chunk($this->chunkSize, function ($records) use (&$result) {
                    foreach ($records as $row) {
                        $startRow = $this->rowCount + 1;

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
            $this->convertApproval($row->approval ?? 0),
            $row->status ?? '',
            $row->maintenancecode ?? '',
            $row->machineno ?? '',
            $row->createempcode ?? '',
            $row->createempname ?? '',
            $row->ordershop ?? '',
            $row->ordertitle ?? '',
            $row->startdatetime ?? '',
            $row->enddatetime ?? '',
            $row->restoredatetime ?? '',
            $row->machinestoptime ?? '',
            $row->linestoptime ?? '',
            $row->makername ?? '',
            $row->makerservice ?? '',
            $row->makerparts ?? '',
            $row->ltfactorcode ?? '',
            $row->ltfactor ?? '',
            $row->situationcode ?? '',
            $row->situation ?? '',
            $row->factorcode ?? '',
            $row->factor ?? '',
            $row->measurecode ?? '',
            $row->measure ?? '',
            $row->preventioncode ?? '',
            $row->prevention ?? '',
            $row->comments ?? '',
            $row->updatetime ?? '',
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
            'SPK NO',
            'Approval',
            'Keadaan',
            'Maintenance Code',
            'Machine No',
            'Login ID',
            'Login Name',
            'Order Shop',
            'Mengapa dan Bagaimana',
            'Wkt Mulai',
            'Wkt Selesai',
            'Wkt M.J.Prod',
            'Wkt Mesin Stop',
            'Wkt Line Stop',
            'Nama Maker',
            'Service Fee',
            'Biaya Ganti Parts',
            'Kode S.P',
            'Stop Panjang',
            'Kode U.M',
            'Uraian Masalah',
            'Kode P',
            'Penyebab',
            'Kode T.T',
            'Temporary Tindakan',
            'Kode S',
            'Solution',
            'Komentar',
            'Wkt Memperbarui',
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
