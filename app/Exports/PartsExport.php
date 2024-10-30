<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PartsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DB::table('mas_inventory as m')
            ->select(
                'm.partcode',
                'm.partname',
                'm.category',
                DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0) as totalstock'),
                'm.minstock',
                'm.unitprice as unitprices'
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
            'STOCK QUANTITY',
            'MINIMUM STOCK',
            'UNIT PRICE'
        ];
    }

    public function map($part): array
    {
        return [
            $part->partcode,
            $part->partname,
            $part->category,
            $part->totalstock,
            $part->minstock,
            $part->unitprices
        ];
    }
}
