<?php

namespace App\Exports;

use App\Models\MasFactor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FactorsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasFactor::all();
    }

    public function headings(): array
    {
        return [
            'FACTOR CODE',
            'FACTOR NAME',
            'REMARK'
        ];
    }

    public function map($factor): array
    {
        return [
            $factor->factorcode,
            $factor->factorname,
            $factor->remark
        ];
    }
}
