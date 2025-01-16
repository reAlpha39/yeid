<?php

namespace App\Exports;

use App\Models\MasMeasure;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeasuresExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasMeasure::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('measurecode', 'ILIKE', $this->search . '%')
                ->orWhere('measurename', 'ILIKE', $this->search . '%')
                ->orWhere('remark', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'MEASURE CODE',
            'MEASURE NAME',
            'REMARK'
        ];
    }

    public function map($measure): array
    {
        return [
            $measure->measurecode,
            $measure->measurename,
            $measure->remark
        ];
    }
}
