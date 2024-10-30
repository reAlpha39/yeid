<?php

namespace App\Exports;

use App\Models\MasDepartment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepartmentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasDepartment::all();
    }

    public function headings(): array
    {
        return [
            'DEPARTMENT CODE',
            'DEPARTMENT NAME'
        ];
    }

    public function map($department): array
    {
        return [
            $department->code,
            $department->name
        ];
    }
}
