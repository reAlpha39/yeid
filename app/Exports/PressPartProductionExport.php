<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PressPartProductionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $isSummary;
    protected $targetDate;
    protected $machineNo;
    protected $model;

    public function __construct($isSummary = false, $targetDate = null, $machineNo = null, $model = null)
    {
        $this->isSummary = $isSummary;
        $this->targetDate = $targetDate;
        $this->machineNo = $machineNo;
        $this->model = $model;
    }

    public function collection()
    {
        $query = DB::table('tbl_presswork');

        if (!$this->isSummary) {
            // Detail query
            $query->select([
                'machineno',
                'model',
                'dieno',
                'dieunitno',
                'startdatetime',
                'enddatetime',
                'shotcount',
                'reason',
                'employeecode',
                'employeename',
                'updatetime'
            ])
                ->where('startdatetime', 'like', $this->targetDate . '%');

            if (!empty($this->machineNo)) {
                $query->where('machineno', explode('|', $this->machineNo)[0]);
            }

            if (!empty($this->model)) {
                $modelData = explode('-', $this->model);
                $query->where('model', $modelData[0])
                    ->when(isset($modelData[1]), function ($query) use ($modelData) {
                        return $query->where('dieno', $modelData[1]);
                    });
            }

            $query->orderByDesc('startdatetime');
        } else {
            // Summary query
            $query->selectRaw("
                '' as machineno,
                model, dieno, dieunitno,
                max(startdatetime) as startdatetime,
                max(enddatetime) as enddatetime,
                sum(shotcount) as shotcount,
                max(updatetime) as updatetime,
                '' as reason,
                '' as employeecode,
                '' as employeename
            ")
                ->groupBy('model', 'dieno', 'dieunitno')
                ->orderByDesc(DB::raw('max(startdatetime)'));
        }

        return $query->limit(1000)->get();
    }

    public function headings(): array
    {
        if (!$this->isSummary) {
            return [
                'MACHINE NO',
                'MODEL',
                'DIE NO',
                'DIE UNIT NO',
                'START DATE',
                'END DATE',
                'SHOT COUNT',
                'REASON',
                'EMPLOYEE CODE',
                'EMPLOYEE NAME',
                'UPDATE TIME'
            ];
        }

        return [
            'MACHINE NO',
            'MODEL',
            'DIE NO',
            'DIE UNIT NO',
            'LAST START DATE',
            'LAST END DATE',
            'TOTAL SHOT COUNT',
            'LAST UPDATE TIME'
        ];
    }

    public function map($record): array
    {
        if (!$this->isSummary) {
            return [
                $record->machineno,
                $record->model,
                $record->dieno,
                $record->dieunitno,
                $record->startdatetime,
                $record->enddatetime,
                $record->shotcount,
                $record->reason,
                $record->employeecode,
                $record->employeename,
                $record->updatetime
            ];
        }

        return [
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->dieunitno,
            $record->startdatetime,
            $record->enddatetime,
            $record->shotcount,
            $record->updatetime
        ];
    }
}
