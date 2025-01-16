<?php

namespace App\Exports;

use App\Models\MasFactor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FactorsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasFactor::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('factorcode', 'ILIKE', $this->search . '%')
                    ->orWhere('factorname', 'ILIKE', $this->search . '%')
                    ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
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
