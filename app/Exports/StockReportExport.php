<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class StockReportExport implements FromCollection, WithHeadings, WithStyles, WithMapping, WithBatchInserts, WithChunkReading
{
    use Exportable;

    protected $chunkSize = 1000;
    protected $batchSize = 1000;

    public function __construct() {}

    public function collection()
    {
        $collection = new Collection();
        foreach ($this->getQuery()->cursor() as $record) {
            $collection->push($record);
        }
        return $collection;
    }

    private function getQuery()
    {
        // Get movement data aggregated
        $movementSubquery = DB::table('tbl_invrecord as t')
            ->select('t.partcode')
            ->selectRaw('SUM(CASE WHEN t.jobcode = \'O\' THEN -t.quantity WHEN t.jobcode = \'I\' THEN t.quantity ELSE 0 END) as normal_movement')
            ->selectRaw('SUM(CASE WHEN t.jobcode = \'A\' THEN t.quantity ELSE 0 END) as adjust_movement')
            ->join('mas_inventory as mi', function ($join) {
                $join->on('t.partcode', '=', 'mi.partcode')
                    ->whereRaw('t.jobdate > mi.laststockdate');
            })
            ->groupBy('t.partcode');

        // Main inventory table
        $query = DB::table('mas_inventory as m')
            ->select(
                'm.partcode',
                'm.partname',
                'm.category',
                'm.specification',
                'm.brand',
                'm.usedflag',
                'm.address',
                'm.unitprice',
                'm.currency',
                DB::raw('m.laststocknumber + COALESCE(t.normal_movement, 0) as theory_qtty'),
                DB::raw('m.laststocknumber + COALESCE(t.adjust_movement, 0) as adjusted_qtty'),
                DB::raw('m.unitprice * (m.laststocknumber + COALESCE(t.adjust_movement, 0)) as adjusted_price')
            )
            ->leftJoinSub($movementSubquery, 't', function ($join) {
                $join->on('m.partcode', '=', 't.partcode');
            })
            ->where('m.status', '<>', 'D');

        return $query;
    }

    public function map($row): array
    {
        $category = '';
        switch ($row->category) {
            case 'M':
                $category = 'Machine';
                break;
            case 'F':
                $category = 'Facility';
                break;
            case 'J':
                $category = 'Jig';
                break;
            case 'O':
                $category = 'Other';
                break;
            default:
                $category = '---';
        }

        return [
            $row->partcode,
            $row->partname,
            $category,
            $row->specification,
            $row->brand,
            $row->usedflag,
            $row->address,
            number_format($row->unitprice, 2, '.', ','),
            $row->currency,
            number_format($row->theory_qtty, 2, '.', ','),
            number_format($row->adjusted_qtty, 2, '.', ','),
            number_format($row->adjusted_price, 2, '.', ',')
        ];
    }

    public function headings(): array
    {
        return [
            'Part Code',
            'Part Name',
            'Category',
            'Specification',
            'Brand',
            'Used',
            'Address',
            'Unit Price',
            'Currency',
            'Theory Quantity',
            'Adjusted Quantity',
            'Adjusted Price'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'J' => ['font' => ['bold' => true]],
            'K' => ['font' => ['bold' => true]], // Adjusted quantity column
            'L' => ['font' => ['bold' => true]], // Adjusted price column
        ];
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }
}
