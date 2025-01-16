<?php

// app/Exports/EmployeesExport.php
namespace App\Exports;

use App\Models\MasEmployee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasEmployee::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('employeecode', 'ILIKE', "{$this->search}%")
                    ->orWhere('employeename', 'ILIKE', "{$this->search}%")
                    ->orWhere('mlevel', 'ILIKE', "{$this->search}%")
                    ->orWhere('password', 'ILIKE', "{$this->search}%");
            });
        }

        return $query->get();
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
