<?php

namespace App\Exports;

use App\Models\MasShop;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShopsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shopCode;
    protected $shopName;

    public function __construct($shopCode = null, $shopName = null)
    {
        $this->shopCode = $shopCode;
        $this->shopName = $shopName;
    }

    public function collection()
    {
        $query = MasShop::query();

        if ($this->shopCode) {
            $query->where('shopcode', 'ILIKE', '%' . $this->shopCode . '%');
        }

        if ($this->shopName) {
            $query->orWhere('shopname', 'ILIKE', '%' . $this->shopName . '%');
        }

        return $query->get();
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
