<?php

namespace App\Exports;

use App\Models\SpkRecord;
use App\Models\MasUser;
use App\Models\MasDepartment;
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
    protected const MTC_DEPARTMENT = 'MTC';
    protected $request;
    protected $chunkSize = 1000;
    protected $rowGroups = [];
    protected $rowCount = 1; // Start after header

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function convertStatus($status)
    {
        $result = "";
        switch ($status) {
            case 'pending':
                $result = 'PENDING';
                break;
            case 'partially_approved':
                $result = 'PARTIALLY APPROVED';
                break;
            case 'approved':
                $result = 'APPROVED';
                break;
            case 'revision':
                $result = 'REVISION';
                break;
            case 'revised':
                $result = 'REVISED';
                break;
            case 'draft':
                $result = 'DRAFT';
                break;
            case 'finish':
                $result = 'FINISH';
                break;
            case 'rejected':
                $result = 'REJECTED';
                break;
        }

        return $result;
    }

    public function collection()
    {
        try {
            $result = new Collection();

            $user = MasUser::findOrFail(auth()->user()->id);
            $department = MasDepartment::find($user->department_id);
            $isMtcDepartment = $department->code === self::MTC_DEPARTMENT;

            // Build base query using Eloquent
            $query = SpkRecord::with(['approvalRecord' => function ($query) {
                $query->with([
                    'department:id,code,name'
                ]);
            }])
                ->leftJoin('mas_machine as m', 'tbl_spkrecord.machineno', '=', 'm.machineno')
                ->select([
                    'tbl_spkrecord.*',
                    'm.machinename',
                    DB::raw('COALESCE(tbl_spkrecord.planid, 0) AS planid'),
                    DB::raw('COALESCE(tbl_spkrecord.approval, 0) AS approval'),
                    DB::raw('COALESCE(tbl_spkrecord.createempcode, \'\') AS createempcode'),
                    DB::raw('COALESCE(tbl_spkrecord.createempname, \'\') AS createempname')
                ]);

            // Date filter
            if ($this->request->filled('date')) {
                $query->whereRaw("TO_CHAR(tbl_spkrecord.orderdatetime, 'YYYY-MM') = ?", [$this->request->date]);
            }

            // Shop code filter
            if ($this->request->filled('shop_code')) {
                $query->where('tbl_spkrecord.ordershop', $this->request->shop_code);
            }

            // Machine code filter
            if ($this->request->filled('machine_code')) {
                $query->where('tbl_spkrecord.machineno', $this->request->machine_code);
            }

            // Maintenance code filter
            if ($this->request->filled('maintenance_code')) {
                $query->where('tbl_spkrecord.maintenancecode', $this->request->maintenance_code);
            }

            // Order name filter
            if ($this->request->filled('order_name')) {
                $query->where('tbl_spkrecord.orderempname', $this->request->order_name);
            }

            // Search filter
            if ($this->request->filled('search')) {
                $searchTerm = $this->request->search . '%';
                $query->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("CAST(tbl_spkrecord.recordid AS TEXT) ILIKE ?", [$searchTerm])
                        ->orWhere('tbl_spkrecord.ordertitle', 'ILIKE', $searchTerm);
                });
            }

            // Status filter
            if ($this->request->filled('status')) {
                $query->whereHas('approvalRecord', function ($q) {
                    switch ($this->request->status) {
                        case 'PENDING':
                            $q->where('approval_status', 'pending');
                            break;
                        case 'PARTIALLY APPROVED':
                            $q->where('approval_status', 'partially_approved');
                            break;
                        case 'APPROVED':
                            $q->where('approval_status', 'approved');
                            break;
                        case 'REVISION':
                            $q->where('approval_status', 'revision');
                            break;
                        case 'REVISED':
                            $q->where('approval_status', 'revised');
                            break;
                        case 'DRAFT':
                            $q->where('approval_status', 'draft');
                            break;
                        case 'FINISH':
                            $q->where('approval_status', 'finish');
                            break;
                        case 'REJECTED':
                            $q->where('approval_status', 'rejected');
                            break;
                    }
                });
            }

            // Approved only filter
            if ($this->request->filled('approved_only')) {
                $query->where(function ($q) {
                    $q->whereHas('approvalRecord', function ($sq) {
                        $sq->whereIn('approval_status', ['approved', 'finish']);
                    })->orWhereDoesntHave('approvalRecord');
                });
            }

            if ($this->request->filled('need_approval_only')) {
                $query->whereHas('approvalRecord', function ($q) use ($user, $isMtcDepartment) {
                    if ($user->role_access === '2' && !$isMtcDepartment) {
                        $q->whereNull('supervisor_approved_by')
                        ->whereIn('approval_status', ['pending', 'partially_approved', 'revised']);
                    } elseif ($user->role_access === '3' && !$isMtcDepartment) {
                        $q->whereNull('manager_approved_by');
                    } elseif ($user->role_access === '2' && $isMtcDepartment) {
                        $q->whereNull('supervisor_mtc_approved_by')
                        ->whereIn('approval_status', ['pending', 'partially_approved', 'revised']);
                    } elseif ($user->role_access === '3' && $isMtcDepartment) {
                        $q->whereNull('manager_mtc_approved_by');
                    }
                });
            }

            $query->orderByDesc('tbl_spkrecord.recordid')
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
            $this->convertStatus($row->approvalRecord->approval_status ?? ''),
            // $row->status ?? '',
            $row->maintenancecode ?? '',
            $row->machineno ?? '',
            $row->approvalRecord->createdBy->id ?? '',
            $row->approvalRecord->createdBy->name ?? '',
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
            'Status',
            // 'Keadaan',
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
