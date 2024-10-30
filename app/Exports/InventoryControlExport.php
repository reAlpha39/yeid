<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryControlExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $jobCode;
    protected $vendorCode;
    protected $currency;
    protected $search;

    public function __construct($startDate = '20240101', $endDate = '20240101', $jobCode = 'I', $vendorCode = null, $currency = null, $search = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->jobCode = $jobCode;
        $this->vendorCode = $vendorCode;
        $this->currency = $currency;
        $this->search = $search;
    }

    public function collection()
    {
        $query = DB::table('tbl_invrecord AS i')
            ->leftJoin('mas_machine AS m', 'i.machineno', '=', 'm.machineno')
            ->select(
                'i.partcode',
                'i.jobdate',
                'i.vendorcode as vendor',
                'i.currency',
                'i.quantity',
                'i.total'
            )
            ->whereBetween('i.jobdate', [$this->startDate, $this->endDate])
            ->where('i.jobcode', $this->jobCode);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('i.partcode', 'ILIKE', "{$this->search}%")
                    ->orWhere('i.partname', 'ILIKE', "{$this->search}%");
            });
        }

        if ($this->vendorCode) {
            $query->where('i.vendorcode', $this->vendorCode);
        }

        if ($this->currency) {
            $query->where('i.currency', $this->currency);
        }

        $query->orderBy('i.jobdate', 'desc')
            ->orderBy('i.jobtime', 'desc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'PART',
            'DATE',
            'VENDOR',
            'UNIT PRICE',
            'QTY',
            'TOTAL PRICE'
        ];
    }

    public function map($record): array
    {
        return [
            $record->partcode,
            $record->jobdate,
            $record->vendor,
            $record->currency,
            $record->quantity,
            $record->total
        ];
    }
}
