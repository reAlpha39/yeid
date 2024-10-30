<?php

// app/Exports/SituationsExport.php
namespace App\Exports;

use App\Models\MasSituation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SituationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasSituation::all();
    }

    public function headings(): array
    {
        return [
            'SITUATION CODE',
            'SITUATION NAME',
            'REMARK'
        ];
    }

    public function map($situation): array
    {
        return [
            $situation->situationcode,
            $situation->situationname,
            $situation->remark
        ];
    }
}
