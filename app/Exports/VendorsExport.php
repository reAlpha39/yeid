<?php

namespace App\Exports;

use App\Models\MasVendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VendorsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        return MasVendor::when($this->search, function ($query, $search) {
            return $query->where('vendorcode', 'ILIKE', "$search%")
            ->orWhere('vendorname', 'ILIKE', "$search%");
        })->get();
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
