<?php

// app/Exports/EmployeesExport.php
namespace App\Exports;

use App\Models\MasEmployee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasEmployee::all();
    }

    public function headings(): array
    {
        return [
            'EMPLOYEE CODE',
            'EMPLOYEE NAME',
            'MLEVEL',
            'PASSWORD'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employeecode,
            $employee->employeename,
            $employee->mlevel,
            $employee->password
        ];
    }
}
