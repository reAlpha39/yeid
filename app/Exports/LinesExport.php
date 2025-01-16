<?php

namespace App\Exports;

use App\Models\MasLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LinesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $shopCode;

    public function __construct($search = null, $shopCode = null)
    {
        $this->search = $search;
        $this->shopCode = $shopCode;
    }

    public function collection()
    {
        $query = MasLine::query();

        // Apply the shop code filter
        if ($this->shopCode) {
            $query->where('shopcode', $this->shopCode);
        }

        // Apply the search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('linecode', 'ILIKE', $this->search . '%')
                ->orWhere('linename', 'ILIKE', $this->search . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'LINE CODE',
            'LINE NAME',
            'SHOP'
        ];
    }

    public function map($line): array
    {
        return [
            $line->linecode,
            $line->linename,
            $line->shopcode
        ];
    }
}
