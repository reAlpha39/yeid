<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PressPartHistoryActivityExport implements FromCollection, WithHeadings, WithMapping
{
    protected $targetDate;
    protected $machineNo;
    protected $model;
    protected $dieNo;
    protected $search;

    public function __construct($targetDate = null, $machineNo = null, $model = null, $dieNo = null, $search = null)
    {
        $this->targetDate = $targetDate;
        $this->machineNo = $machineNo;
        $this->model = $model;
        $this->dieNo = $dieNo;
        $this->search = $search;
    }

    public function collection()
    {
        $query = DB::table('tbl_activity')
            ->select([
                'datetime',
                'machineno',
                'model',
                'dieno',
                'processname',
                'partcode',
                'partname',
                'qty',
                'employeecode',
                'employeename',
                'mainform',
                'submenu',
                'reason',
                'updatetime'
            ])
            ->where('datetime', 'like', "{$this->targetDate}%");

        if ($this->machineNo) {
            $query->where('machineno', $this->machineNo);
        }

        if ($this->model) {
            $query->where('model', $this->model);
        }

        if ($this->dieNo) {
            $query->where('dieno', $this->dieNo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('processname', 'ilike', "{$this->search}%")
                    ->orWhere('partcode', 'ilike', "{$this->search}%")
                    ->orWhere('partname', 'ilike', "{$this->search}%")
                    ->orWhere('qty', 'ilike', "{$this->search}%")
                    ->orWhere('employeecode', 'ilike', "{$this->search}%")
                    ->orWhere('employeename', 'ilike', "{$this->search}%")
                    ->orWhere('mainform', 'ilike', "{$this->search}%")
                    ->orWhere('submenu', 'ilike', "{$this->search}%")
                    ->orWhere('reason', 'ilike', "{$this->search}%")
                    ->orWhere('updatetime', 'ilike', "{$this->search}%");
            });
        }

        $query->orderBy('updatetime', 'desc')
            ->orderBy('model')
            ->orderBy('dieno')
            ->orderBy('partcode');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'DATE TIME',
            'MACHINE NO',
            'MODEL',
            'DIE NO',
            'PROCESS',
            'PART CODE',
            'PART NAME',
            'QTY',
            'EMPLOYEE CODE',
            'EMPLOYEE NAME',
            'MAIN FORM',
            'SUB MENU',
            'REASON',
            'UPDATE TIME'
        ];
    }

    public function map($record): array
    {
        return [
            $record->datetime,
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->processname,
            $record->partcode,
            $record->partname,
            $record->qty,
            $record->employeecode,
            $record->employeename,
            $record->mainform,
            $record->submenu,
            $record->reason,
            $record->updatetime
        ];
    }
}
