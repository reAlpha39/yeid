<?php

// app/Exports/SituationsExport.php
namespace App\Exports;

use App\Models\MasSituation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SituationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasSituation::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('situationcode', 'ILIKE', $this->search . '%')
                    ->orWhere('situationname', 'ILIKE', $this->search . '%')
                    ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'SITUATION CODE',
            'SITUATION NAME',
            'REMARK'
        ];
    }

    public function map($situation): array
    {
        return [
            $situation->situationcode,
            $situation->situationname,
            $situation->remark
        ];
    }
}
