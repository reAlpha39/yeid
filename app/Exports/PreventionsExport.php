<?php

// app/Exports/PreventionsExport.php
namespace App\Exports;

use App\Models\MasPrevention;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PreventionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasPrevention::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('preventioncode', 'ILIKE', $this->search . '%')
                ->orWhere('preventionname', 'ILIKE', $this->search . '%')
                ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
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
