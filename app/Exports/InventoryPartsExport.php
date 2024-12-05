<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryPartsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DB::table('mas_inventory as m')
            ->select(
                'm.partcode',
                'm.partname',
                'm.category',
                'm.specification',
                'm.brand',
                'm.eancode',
                'm.usedflag',
                'm.vendorcode',
                'm.address',
                'm.unitprice',
                'm.currency',
                DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                'm.minstock',
                'm.minorder',
                'm.orderpartcode',
                'm.noorderflag',
                'm.laststocknumber',
                DB::raw("COALESCE(m.status, '-') as status"),
                DB::raw("COALESCE(m.noorderflag, '0') as noorderflag"),
                DB::raw("COALESCE(m.note, 'N/A') as note"),
                DB::raw("COALESCE(m.reqquotationdate, ' ') as reqquotationdate"),
                DB::raw("COALESCE(m.orderdate, ' ') as orderdate"),
                DB::raw("COALESCE(m.posentdate, ' ') as posentdate"),
                DB::raw("COALESCE(m.etddate, ' ') as etddate")
            )
            ->leftJoin(DB::raw('(
                select
                    t.partcode,
                    sum(case
                        when t.jobcode = \'O\' then -t.quantity
                        when t.jobcode = \'I\' then t.quantity
                        when t.jobcode = \'A\' then t.quantity
                        else 0 end) as sum_quantity
                from tbl_invrecord as t
                left join mas_inventory as minv on t.partcode = minv.partcode
                where t.updatetime > minv.updatetime
                group by t.partcode
            ) as gi'), 'm.partcode', '=', 'gi.partcode')
            ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
            ->where('m.status', '<>', 'D')
            ->orderBy('partcode')
            ->get();
    }

    public function headings(): array
    {
        return [
            'PART CODE',
            'PART NAME',
            'CATEGORY',
            'SPECIFICATION',
            'BRAND',
            'ADDRESS',
            'STOCK QUANTITY',
            'MINIMUM STOCK',
            'UNIT PRICE',
            'CURRENCY',
            'NOTE',
            'REQUEST ORDER',
            'ORDER',
            'P/O SENT',
            'ETD',
        ];
    }

    public function map($part): array
    {
        return [
            $part->partcode,
            $part->partname,
            $part->category,
            $part->specification,
            $part->brand,
            $part->address,
            $part->totalstock,
            $part->minstock,
            $part->unitprice,
            $part->currency,
            $part->note,
            $part->reqquotationdate,
            $part->orderdate,
            $part->posentdate,
            $part->etddate
        ];
    }
}
