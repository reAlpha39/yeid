<?php

namespace App\Exports;

use App\Models\MasDepartment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepartmentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasDepartment::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'ILIKE', "{$this->search}%")
                    ->orWhere('name', 'ILIKE', "{$this->search}%");
            });
        }

        return $query->get();
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
