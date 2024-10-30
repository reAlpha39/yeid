<?php

namespace App\Exports;

use App\Models\MasVendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VendorsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasVendor::all();
    }

    public function headings(): array
    {
        return [
            'VENDOR CODE',
            'VENDOR NAME'
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->vendorcode,
            $vendor->vendorname
        ];
    }
}
