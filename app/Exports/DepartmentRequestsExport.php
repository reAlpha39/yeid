<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class DepartmentRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithBatchInserts, WithChunkReading
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = DB::table('tbl_spkrecord as s')
            ->leftJoin('mas_machine as m', 's.machineno', '=', 'm.machineno')
            ->select([
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
                DB::raw('COALESCE(s.createempname, \'\') AS createempname'),
            ]);

        // Only active records filter
        if ($this->request->input('only_active') === '1') {
            $query->whereRaw('COALESCE(s.approval, 0) < 119');
        }

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

        $query->orderBy('s.recordid', 'desc');

        return $query;
    }

    public function headings(): array
    {
        return [
            'SPK',
            'TGL ORDER',
            'PEMOHON',
            'JENIS PERBAIKAN',
            'MESIN',
            'JENIS PEKERJAAN',
            'JUMLAH',
            'Machine No',
            'Order Finish Date',
            'Order Stop Time',
            'Update Time',
            'Plan ID',
            'Create Emp Code',
            'Create Emp Name',
            'Order Shop',
            'STATUS'
        ];
    }

    protected function getStatus($row): string
    {
        if ($row->approval >= 112) {
            return 'GRAY';
        } elseif ($row->approval >= 4 && $row->approval < 112) {
            return 'GREEN';
        } elseif ($row->approval < 4 && $row->planid > 0) {
            return 'YELLOW';
        } elseif ($row->approval < 4 && $row->planid == 0) {
            return 'ORANGE';
        } else {
            return 'WHITE';
        }
    }

    public function map($row): array
    {
        return [
            $row->recordid,
            $row->orderdatetime ? date('Y-m-d H:i:s', strtotime($row->orderdatetime)) : '',
            $row->orderempname,
            $row->maintenancecode,
            $row->machinename,
            $row->orderjobtype,
            $row->orderqtty,
            $row->machineno,
            $row->orderfinishdate ? date('Y-m-d H:i:s', strtotime($row->orderfinishdate)) : '',
            $row->orderstoptime,
            $row->updatetime ? date('Y-m-d H:i:s', strtotime($row->updatetime)) : '',
            $row->planid,
            $row->createempcode,
            $row->createempname,
            $row->ordershop,
            $this->getStatus($row)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
