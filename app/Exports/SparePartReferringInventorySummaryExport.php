<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SparePartReferringInventorySummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function collection()
    {
        $data = DB::table('tbl_invsummary as i')
            ->selectRaw("
                'I' as type,
                i.yearmonth,
                sum(coalesce(i.inbound, 0) * coalesce(m.unitprice, 0) *
                    (case m.currency
                        when 'USD' then coalesce(s.usd2idr::numeric, 1)
                        when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                        when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                        when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                        else 1
                    end)
                ) as total_inbound,
                sum(coalesce(i.outbound, 0) * coalesce(m.unitprice, 0) *
                    (case m.currency
                        when 'USD' then coalesce(s.usd2idr::numeric, 1)
                        when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                        when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                        when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                        else 1
                    end)
                ) as total_outbound,
                sum(coalesce(i.adjust, 0) * coalesce(m.unitprice, 0) *
                    (case m.currency
                        when 'USD' then coalesce(s.usd2idr::numeric, 1)
                        when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                        when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                        when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                        else 1
                    end)
                ) as total_adjust,
                sum(coalesce(i.stocknumber, 0) * coalesce(m.unitprice, 0) *
                    (case m.currency
                        when 'USD' then coalesce(s.usd2idr::numeric, 1)
                        when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                        when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                        when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                        else 1
                    end)
                ) as total_stock
            ")
            ->join('mas_inventory as m', 'i.partcode', '=', 'm.partcode')
            ->leftJoin('mas_system as s', DB::raw("substr(i.yearmonth, 1, 4)"), '=', 's.year')
            ->where(DB::raw("substr(i.yearmonth, 1, 4)"), $this->year)
            ->groupBy('i.yearmonth')
            ->orderBy('i.yearmonth')
            ->get();

        // Transform data to match table structure
        return collect([
            [
                'item' => 'Order Amount',
                'unit' => 'Jt. IDR',
                'months' => array_fill(0, 12, '-')
            ],
            [
                'item' => 'Received Amount',
                'unit' => 'Jt. IDR',
                'months' => array_map(function ($month) use ($data) {
                    $record = $data->firstWhere('yearmonth', $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                    return $record ? number_format($record->total_inbound / 1000000, 1) : '-';
                }, range(1, 12))
            ],
            [
                'item' => 'Spent Amount',
                'unit' => 'Jt. IDR',
                'months' => array_map(function ($month) use ($data) {
                    $record = $data->firstWhere('yearmonth', $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                    return $record ? number_format($record->total_outbound / 1000000, 1) : '-';
                }, range(1, 12))
            ],
            [
                'item' => 'Adjust Amount',
                'unit' => 'Jt. IDR',
                'months' => array_map(function ($month) use ($data) {
                    $record = $data->firstWhere('yearmonth', $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                    return $record ? number_format($record->total_adjust / 1000000, 1) : '-';
                }, range(1, 12))
            ],
            [
                'item' => 'Stock Amount',
                'unit' => 'Jt. IDR',
                'months' => array_map(function ($month) use ($data) {
                    $record = $data->firstWhere('yearmonth', $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                    return $record ? number_format($record->total_stock / 1000000, 1) : '-';
                }, range(1, 12))
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'ITEM',
            'UNIT',
            'JANUARI',
            'FEBRUARI',
            'MARET',
            'APRIL',
            'MEI',
            'JUNI',
            'JULI',
            'AGUSTUS',
            'SEPTEMBER',
            'OKTOBER',
            'NOVEMBER',
            'DESEMBER'
        ];
    }

    public function map($row): array
    {
        return [
            $row['item'],
            $row['unit'],
            ...$row['months']
        ];
    }
}
