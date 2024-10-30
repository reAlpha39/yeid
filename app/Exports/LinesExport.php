<?php

namespace App\Exports;

use App\Models\MasLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LinesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasLine::all();
    }

    public function headings(): array
    {
        return [
            'LINE CODE',
            'LINE NAME',
            'SHOP'
        ];
    }

    public function map($line): array
    {
        return [
            $line->linecode,
            $line->linename,
            $line->shopcode
        ];
    }
}
