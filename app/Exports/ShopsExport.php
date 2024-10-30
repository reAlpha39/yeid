<?php

namespace App\Exports;

use App\Models\MasShop;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShopsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasShop::all();
    }

    public function headings(): array
    {
        return [
            'SHOP CODE',
            'SHOP NAME',
            'PLANT TYPE',
            'COUNT FLAG'
        ];
    }

    public function map($shop): array
    {
        return [
            $shop->shopcode,
            $shop->shopname,
            $shop->planttype,
            $shop->countflag
        ];
    }
}
