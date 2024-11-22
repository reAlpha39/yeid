<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PressPartExport implements FromCollection, WithHeadings, WithMapping
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
                $join->on(
                    DB::raw('substring(m.partcode, 1, 8)'),
                    '=',
                    DB::raw('substring(i.partcode, 1, 8)')
                )
                    ->whereNotNull('i.partcode');
            })
            ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
            ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
            ->select([
                'c.machinename',
                'm.machineno',
                'm.model',
                'm.dieno',
                'm.dieunitno',
                'm.processname',
                'm.partcode',
                'm.partname',
                'm.category',
                DB::raw('COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) as counter'),
                'm.companylimit',
                'm.makerlimit',
                'm.qttyperdie',
                'm.drawingno',
                'm.note',
                'm.exchangedatetime',
                'm.minstock',
                DB::raw('COALESCE(i.laststocknumber, 0) +
                COALESCE((
                    SELECT sum(CASE
                        WHEN jobcode = \'O\' THEN -quantity
                        ELSE quantity
                    END)
                    FROM tbl_invrecord
                    WHERE partcode = i.partcode
                    AND jobdate > i.laststockdate
                ), 0) as currentstock'),
                'i.unitprice',
                'i.currency',
                'i.brand',
                DB::raw('COALESCE(v.vendorname, \'-\') as vendorname'),
                DB::raw('CASE
                    WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                    WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                'i.address'
            ])
            ->where('m.status', '<>', 'D');

        if ($this->year) {
            $query->where('m.exchangedatetime', 'LIKE', $this->year . '%');
        }

        if ($this->machineNo) {
            $query->where('m.machineno', $this->machineNo);
        }

        if ($this->model) {
            $query->where('m.model', $this->model);
        }

        if ($this->dieNo) {
            $query->where('m.dieno', $this->dieNo);
        }

        if ($this->partCode) {
            $query->where(function ($q) {
                $q->where('m.partcode', 'ILIKE', "{$this->partCode}%")
                    ->orWhere('m.partname', 'ILIKE', "{$this->partCode}%");
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'MACHINE NAME',
            'MACHINE NO',
            'MODEL',
            'DIE NO',
            'DIE UNIT NO',
            'PROCESS',
            'PART CODE',
            'PART NAME',
            'CATEGORY',
            'COUNTER',
            'COMPANY LIMIT',
            'MAKER LIMIT',
            'QTY/DIE',
            'DRAWING NO',
            'NOTE',
            'EXCHANGE DATE',
            'MIN STOCK',
            'CURRENT STOCK',
            'UNIT PRICE',
            'CURRENCY',
            'BRAND',
            'VENDOR',
            'ORIGIN',
            'ADDRESS'
        ];
    }

    public function map($record): array
    {
        return [
            $record->machinename,
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->dieunitno,
            $record->processname,
            $record->partcode,
            $record->partname,
            $record->category,
            $record->counter,
            $record->companylimit,
            $record->makerlimit,
            $record->qttyperdie,
            $record->drawingno,
            $record->note,
            $record->exchangedatetime,
            $record->minstock,
            $record->currentstock,
            $record->unitprice,
            $record->currency,
            $record->brand,
            $record->vendorname,
            $record->origin,
            $record->address
        ];
    }
}
