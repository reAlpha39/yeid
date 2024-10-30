<?php

namespace App\Exports;

use App\Models\MasSystem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CurrencyExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasSystem::all();
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
