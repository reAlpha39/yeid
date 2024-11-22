<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PressPartExchangeExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $targetDate;
    protected $machineNo;
    protected $model;
    protected $dieNo;
    protected $partCode;

    public function __construct($targetDate = null, $machineNo = null, $model = null, $dieNo = null, $partCode = null, $search = null)
    {
        $this->targetDate = $targetDate;
        $this->machineNo = $machineNo;
        $this->model = $model;
        $this->dieNo = $dieNo;
        $this->partCode = $partCode;
        $this->search = $search;
    }

    public function collection()
    {
        $query = DB::table('tbl_exchangework')
            ->select([
                'exchangedatetime',
                'machineno',
                'model',
                'dieno',
                'dieunitno',
                'processname',
                'partcode',
                'partname',
                'exchangeqtty',
                'exchangeshotno',
                'serialno',
                'reason',
                'employeecode',
                'employeename'
            ])
            ->where('exchangedatetime', 'like', $this->targetDate . '%');

        if (!empty($this->search)) {
            $query->where(function ($subQuery) {
                $subQuery->where('model', 'ILIKE', "{$this->search}%")
                    ->orWhere('dieunitno', 'ILIKE', "{$this->search}%")
                    ->orWhere('processname', 'ILIKE', "{$this->search}%")
                    ->orWhere('partname', 'ILIKE', "{$this->search}%")
                    ->orWhere('exchangeqtty', 'ILIKE', "{$this->search}%")
                    ->orWhere('exchangeshotno', 'ILIKE', "{$this->search}%")
                    ->orWhere('serialno', 'ILIKE', "{$this->search}%")
                    ->orWhere('reason', 'ILIKE', "{$this->search}%")
                    ->orWhere('employeecode', 'ILIKE', "{$this->search}%")
                    ->orWhere('employeename', 'ILIKE', "{$this->search}%")
                    ->orWhere('partcode', 'ILIKE', "{$this->search}%");
            });
        }

        if (!empty($this->machineNo)) {
            $query->where('machineno', $this->machineNo);
        }

        if (!empty($this->model)) {
            $query->where('model', $this->model);
        }

        if (!empty($this->dieNo)) {
            $query->where('dieno', $this->dieNo);
        }

        if (!empty($this->partCode)) {
            $query->where('partcode', 'like', $this->partCode . '%');
        }

        return $query->orderByDesc('exchangedatetime')
            ->limit(1000)
            ->get();
    }

    public function headings(): array
    {
        return [
            'EXCHANGE DATE',
            'MACHINE NO',
            'MODEL',
            'DIE NO',
            'DIE UNIT NO',
            'PROCESS',
            'PART CODE',
            'PART NAME',
            'EXCHANGE QTY',
            'EXCHANGE SHOT',
            'SERIAL NO',
            'REASON',
            'EMPLOYEE CODE',
            'EMPLOYEE NAME'
        ];
    }

    public function map($record): array
    {
        return [
            $record->exchangedatetime,
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->dieunitno,
            $record->processname,
            $record->partcode,
            $record->partname,
            $record->exchangeqtty,
            $record->exchangeshotno,
            $record->serialno,
            $record->reason,
            $record->employeecode,
            $record->employeename
        ];
    }
}
