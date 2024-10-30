<?php

namespace App\Exports;

use App\Models\MasLTFactor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LTFactorExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasLTFactor::all();
    }

    public function headings(): array
    {
        return [
            'LTFACTOR CODE',
            'LTFACTOR NAME',
            'REMARK'
        ];
    }

    public function map($ltfactor): array
    {
        return [
            $ltfactor->ltfactorcode,
            $ltfactor->ltfactorname,
            $ltfactor->remark
        ];
    }
}
