<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RequestToWorkshopsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DB::table('tbl_wsrrecord')
            ->select(
                'wsrid',
                'status',
                'requestdate',
                'shopname',
                'ordername',
                'title',
                'reqfinishdate',
                'asapflag',
                'deliveryplace',
                'employeename',
                'finishdate',
                'note',
                'inspector'
            );

        if (!empty($this->filters['year'])) {
            $query->where('requestdate', 'ILIKE', $this->filters['year'] . '%');
        }

        if ($this->filters['only_active'] === 'true') {
            $query->where('status', 'R');
        }

        if (!empty($this->filters['shop_code'])) {
            $query->where('shopcode', $this->filters['shop_code']);
        }

        if (!empty($this->filters['employee_code'])) {
            $query->where('employeecode', $this->filters['employee_code']);
        }

        if (!empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('title', 'ILIKE', $this->filters['search'] . '%')
                    ->orWhere('wsrid', 'ILIKE', $this->filters['search'] . '%')
                    ->orWhere('ordername', 'ILIKE', $this->filters['search'] . '%')
                    ->orWhere('employeename', 'ILIKE', $this->filters['search'] . '%')
                    ->orWhere('shopname', 'ILIKE', $this->filters['search'] . '%');
            });
        }

        return $query->orderBy('wsrid', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'REQUEST ID',
            'STATUS',
            'REQUEST DATE',
            'SHOP NAME',
            'PEMOHON',
            'TITLE',
            'REQ FINISH DATE',
            'CATEGORY',
            'DELIVERY PLACE',
            'EMPLOYEE NAME',
            'FINISH DATE',
            'NOTE',
            'INSPECTOR'
        ];
    }

    public function map($row): array
    {
        $status = match ($row->status) {
            'R' => 'REQUEST',
            'C' => 'CANCELLED',
            'F' => 'FINISH',
            default => $row->status
        };

        $category = match ($row->asapflag) {
            '1' => 'JIG',
            '2' => 'W/S',
            '3' => 'FAC',
            default => ''
        };

        return [
            $row->wsrid,
            $status,
            $row->requestdate,
            $row->shopname,
            $row->ordername,
            $row->title,
            $row->reqfinishdate,
            $category,
            $row->deliveryplace,
            $row->employeename,
            $row->finishdate,
            $row->note,
            $row->inspector
        ];
    }
}
