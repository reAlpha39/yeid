<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PressPartMasterPartExport implements FromCollection, WithHeadings, WithMapping
{
    protected $year;
    protected $machineNo;
    protected $model;
    protected $dieNo;
    protected $partCode;

    public function __construct($year = null, $machineNo = null, $model = null, $dieNo = null, $partCode = null)
    {
        $this->year = $year;
        $this->machineNo = $machineNo;
        $this->model = $model;
        $this->dieNo = $dieNo;
        $this->partCode = $partCode;
    }

    public function collection()
    {
        $query = DB::table('mas_presspart as m')
            ->leftJoin('mas_inventory as i', function ($join) {
                $join->whereRaw('substring(m.partcode, 1, 8) = substring(i.partcode, 1, 8)');
            })
            ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
            ->leftJoin(
                DB::raw('(SELECT exchangedatetime, reason FROM tbl_exchangework) as e'),
                'm.exchangedatetime',
                '=',
                'e.exchangedatetime'
            )
            ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
            ->leftJoin(DB::raw('(
                SELECT 
                    partcode,
                    sum(CASE WHEN jobcode = \'O\' THEN -quantity ELSE quantity END) as total_quantity
                FROM tbl_invrecord
                WHERE jobdate > (SELECT laststockdate FROM mas_inventory WHERE partcode = tbl_invrecord.partcode)
                GROUP BY partcode
            ) as t'), 'i.partcode', '=', 't.partcode')
            ->select([
                'm.machineno',
                'm.model',
                'm.dieno',
                'm.dieunitno',
                'm.processname',
                'm.partcode',
                'm.partname',
                'm.category',
                'm.companylimit',
                'm.makerlimit',
                'm.autoflag',
                'm.qttyperdie',
                'm.drawingno',
                'm.note',
                'm.exchangedatetime',
                'e.reason as exchangereason',
                'm.minstock',
                DB::raw('COALESCE(i.laststocknumber + COALESCE(t.total_quantity, 0), 0) as currentstock'),
                DB::raw('COALESCE(i.unitprice, 0) as unitprice'),
                DB::raw('COALESCE(i.currency, \'-\') as currency'),
                'i.brand',
                DB::raw('COALESCE(v.vendorname, \'-\') as supplier'),
                DB::raw('CASE
                    WHEN substring(m.partcode, 2, 1) = \'I\' THEN \'Import\'
                    WHEN substring(m.partcode, 2, 1) = \'L\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                'i.address',
                'c.machinename',
                'm.employeecode',
                'm.employeename',
                'm.reason'
            ])
            ->where('m.status', '<>', 'D');

        if (!empty($this->machineNo)) {
            $query->where('m.machineno', $this->machineNo);
        }

        if (!empty($this->model) && !empty($this->dieNo)) {
            $query->where('m.model', $this->model)
                ->where('m.dieno', $this->dieNo);
        }

        if ($this->partCode) {
            $query->where(function ($q) {
                $q->where('m.partcode', 'ILIKE', "{$this->partCode}%")
                    ->orWhere('m.partname', 'ILIKE', "{$this->partCode}%");
            });
        }

        if (!empty($this->year)) {
            $query->whereYear('m.exchangedatetime', $this->year);
        }

        return $query->orderBy('m.machineno')
            ->orderBy('m.model')
            ->orderBy('m.dieno')
            ->orderBy('m.partcode')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Machine No',
            'Model',
            'Die No',
            'Die Unit No',
            'Process Name',
            'Part Code',
            'Part Name',
            'Category',
            'Company Limit',
            'Maker Limit',
            'Auto Flag',
            'Quantity Per Die',
            'Drawing No',
            'Note',
            'Exchange Date/Time',
            'Exchange Reason',
            'Min Stock',
            'Current Stock',
            'Unit Price',
            'Currency',
            'Brand',
            'Supplier',
            'Origin',
            'Address',
            'Machine Name',
            'Employee Code',
            'Employee Name',
            'Reason'
        ];
    }

    public function map($record): array
    {
        return [
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->dieunitno,
            $record->processname,
            $record->partcode,
            $record->partname,
            $record->category,
            $record->companylimit,
            $record->makerlimit,
            $record->autoflag,
            $record->qttyperdie,
            $record->drawingno,
            $record->note,
            $record->exchangedatetime,
            $record->exchangereason,
            $record->minstock,
            $record->currentstock,
            $record->unitprice,
            $record->currency,
            $record->brand,
            $record->supplier,
            $record->origin,
            $record->address,
            $record->machinename,
            $record->employeecode,
            $record->employeename,
            $record->reason
        ];
    }
}
