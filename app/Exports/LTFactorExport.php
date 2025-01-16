<?php

namespace App\Exports;

use App\Models\MasLTFactor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LTFactorExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasLTFactor::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('ltfactorcode', 'ILIKE', $this->search . '%')
                ->orWhere('ltfactorname', 'ILIKE', $this->search . '%')
                ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
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
