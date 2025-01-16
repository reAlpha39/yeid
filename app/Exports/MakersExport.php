<?php

namespace App\Exports;

use App\Models\MasMaker;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MakersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasMaker::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('makercode', 'ILIKE', $this->search . '%')
                ->orWhere('makername', 'ILIKE', $this->search . '%')
                ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'MAKER CODE',
            'MAKER NAME',
            'REMARK'
        ];
    }

    public function map($maker): array
    {
        return [
            $maker->makercode,
            $maker->makername,
            $maker->remark
        ];
    }
}
