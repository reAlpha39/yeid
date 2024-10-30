<?php

namespace App\Exports;

use App\Models\MasMeasure;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeasuresExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasMeasure::all();
    }

    public function headings(): array
    {
        return [
            'MEASURE CODE',
            'MEASURE NAME',
            'REMARK'
        ];
    }

    public function map($measure): array
    {
        return [
            $measure->measurecode,
            $measure->measurename,
            $measure->remark
        ];
    }
}
