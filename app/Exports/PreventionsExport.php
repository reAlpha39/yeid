<?php

// app/Exports/PreventionsExport.php
namespace App\Exports;

use App\Models\MasPrevention;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PreventionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasPrevention::all();
    }

    public function headings(): array
    {
        return [
            'PREVENTION CODE',
            'PREVENTION NAME',
            'REMARK'
        ];
    }

    public function map($prevention): array
    {
        return [
            $prevention->preventioncode,
            $prevention->preventionname,
            $prevention->remark
        ];
    }
}
