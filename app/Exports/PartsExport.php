<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PartsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $queryBuilder = DB::table('mas_inventory as m')
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
                    sum(case when t.jobcode = \'O\' then -t.quantity else t.quantity end) as sum_quantity
                from tbl_invrecord as t
                left join mas_inventory as minv on t.partcode = minv.partcode
                where t.jobdate > minv.laststockdate
                group by t.partcode
            ) as gi'), 'm.partcode', '=', 'gi.partcode')
            ->leftJoin('mas_vendor as v', 'm.vendorcode', '=', 'v.vendorcode')
            ->where('m.status', '<>', 'D');

        // Apply filters
        if (!empty($this->filters['search'])) {
            $queryBuilder->where(function ($q) {
                $q->where('m.partcode', 'ILIKE', $this->filters['search'] . '%')
                    ->orWhere(DB::raw('upper(m.partname)'), 'ILIKE', strtoupper($this->filters['search']) . '%');
            });
        }

        // Status Filter
        if (!empty($this->filters['status'])) {
            switch ($this->filters['status']) {
                case 'ORANGE':
                    $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                        ->where('m.status', 'O');
                    break;
                case 'RED':
                    $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                        ->where(function ($q) {
                            $q->where('m.status', '<>', 'O')
                                ->orWhereNull('m.status');
                        });
                    break;
                case 'YELLOW':
                    $queryBuilder->whereNotNull('m.posentdate')
                        ->whereNotNull('m.etddate')
                        ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') >= CURRENT_DATE");
                    break;
                case 'BLUE':
                    $queryBuilder->whereNotNull('m.posentdate')
                        ->whereNotNull('m.etddate')
                        ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') < CURRENT_DATE");
                    break;
            }
        }

        if (!empty($this->filters['part_code'])) {
            $queryBuilder->where(function ($q) {
                $q->where('m.partcode', $this->filters['part_code'])
                    ->orWhere(DB::raw('upper(m.partname)'), strtoupper($this->filters['part_code']));
            });
        }

        if (!empty($this->filters['brand'])) {
            $queryBuilder->where(DB::raw('upper(m.brand)'), 'ILIKE', strtoupper($this->filters['brand']) . '%');
        }

        if (!empty($this->filters['used_flag'])) {
            $queryBuilder->where('m.usedflag', 'O');
        }

        if (!empty($this->filters['specification'])) {
            $queryBuilder->where(DB::raw('upper(m.specification)'), 'ILIKE', strtoupper($this->filters['specification']) . '%');
        }

        if (!empty($this->filters['address'])) {
            $queryBuilder->where(DB::raw('upper(m.address)'), 'ILIKE', $this->filters['address'] . '%');
        }

        if (!empty($this->filters['vendor_code'])) {
            $queryBuilder->where(DB::raw('upper(m.vendorcode)'), 'ILIKE', strtoupper($this->filters['vendor_code']) . '%');
        }

        if (!empty($this->filters['note'])) {
            $queryBuilder->where(DB::raw('upper(m.note)'), 'ILIKE', strtoupper($this->filters['note']) . '%');
        }

        if (!empty($this->filters['category']) && in_array($this->filters['category'], ['M', 'F', 'J', 'O'])) {
            $queryBuilder->where('m.category', $this->filters['category']);
        }

        if (!empty($this->filters['vendor_name_cmb'])) {
            $queryBuilder->where('m.vendorcode', $this->filters['vendor_name_cmb']);
        } elseif (!empty($this->filters['vendor_name_text'])) {
            $queryBuilder->where(DB::raw('upper(v.vendorname)'), 'ILIKE', strtoupper($this->filters['vendor_name_text']) . '%');
        }

        if (!empty($this->filters['minus_flag'])) {
            $queryBuilder->where(DB::raw('m.minstock'), '>', DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'));
        }

        if (!empty($this->filters['order_flag'])) {
            $queryBuilder->where(DB::raw('COALESCE(m.posentdate, \' \')'), '<>', ' ');
        }

        // Sorting
        if (!empty($this->filters['sortBy'])) {
            $sortDirection = $this->filters['sortDirection'] ?? 'asc';
            if ($this->filters['sortBy'] === 'totalstock') {
                $queryBuilder->orderBy(DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'), $sortDirection);
            } else {
                $queryBuilder->orderBy("m.{$this->filters['sortBy']}", $sortDirection);
            }
        } else {
            $queryBuilder->orderBy('m.partcode');
        }

        return $queryBuilder->get();
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
            $part->unitprice
        ];
    }
}
