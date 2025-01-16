<?php

namespace App\Exports;

use App\Models\MasSystem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CurrencyExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        $query = MasSystem::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('year', 'ILIKE', $this->search . '%')
                    ->orWhere('usd2idr', 'ILIKE', $this->search . '%')
                    ->orWhere('jpy2idr', 'ILIKE', $this->search . '%')
                    ->orWhere('eur2idr', 'ILIKE', $this->search . '%')
                    ->orWhere('sgd2idr', 'ILIKE', $this->search . '%');
            });
        }

        return $query->orderByDesc('year')->get();
    }

    public function headings(): array
    {
        return [
            'YEAR',
            'USD2IDR',
            'JPY2IDR',
            'EUR2IDR',
            'SGD2IDR'
        ];
    }

    public function map($currency): array
    {
        return [
            $currency->year,
            $currency->usd2idr,
            $currency->jpy2idr,
            $currency->eur2idr,
            $currency->sgd2idr
        ];
    }
}
