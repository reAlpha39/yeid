<?php

namespace App\Exports;

use App\Models\MasMaker;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MakersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasMaker::all();
    }

    public function headings(): array
    {
        return [
            'MAKER CODE',
            'MAKER NAME',
            'REMARK'
        ];
    }

    public function map($maker): array
    {
        return [
            $maker->makercode,
            $maker->makername,
            $maker->remark
        ];
    }
}
