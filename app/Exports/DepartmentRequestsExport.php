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

class DepartmentRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithBatchInserts, WithChunkReading
{
    public function query()
    {
        return DB::table('tbl_spkrecord as s')
            ->select([
                's.recordid',
                's.maintenancecode',
                's.orderdatetime',
                's.orderempname',
                's.ordershop',
                's.machineno',
                's.ordertitle',
                's.orderfinishdate',
                's.orderjobtype',
                's.orderqtty',
                's.orderstoptime',
                's.updatetime',
                's.planid',
                's.approval',
                's.createempcode',
                's.createempname',
                'm.machinename'
            ])
            ->leftJoin('mas_machine as m', 's.machineno', '=', 'm.machineno')
            ->orderBy('s.recordid', 'desc');
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
            'Record ID',
            'Machine No',
            'Order Finish Date',
            'Order Stop Time',
            'Update Time',
            'Plan ID',
            'Approval',
            'Create Emp Code',
            'Create Emp Name',
            'Order Shop'
        ];
    }

    public function map($row): array
    {
        return [
            $row->maintenancecode,
            $row->orderdatetime ? date('Y-m-d H:i:s', strtotime($row->orderdatetime)) : '',
            $row->orderempname,
            $row->ordertitle,
            $row->machinename,
            $row->orderjobtype,
            $row->orderqtty,
            $row->recordid,
            $row->machineno,
            $row->orderfinishdate ? date('Y-m-d H:i:s', strtotime($row->orderfinishdate)) : '',
            $row->orderstoptime,
            $row->updatetime ? date('Y-m-d H:i:s', strtotime($row->updatetime)) : '',
            $row->planid,
            $row->approval,
            $row->createempcode,
            $row->createempname,
            $row->ordershop
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
