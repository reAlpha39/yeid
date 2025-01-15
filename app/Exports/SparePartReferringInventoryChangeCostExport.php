<?php

namespace App\Exports;

use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SparePartReferringInventoryChangeCostExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize
{
    protected $year;
    protected $filters;
    protected $limit;
    protected $months = [
        1 => 'JANUARI',
        2 => 'FEBRUARI',
        3 => 'MARET',
        4 => 'APRIL',
        5 => 'MEI',
        6 => 'JUNI',
        7 => 'JULI',
        8 => 'AGUSTUS',
        9 => 'SEPTEMBER',
        10 => 'OKTOBER',
        11 => 'NOVEMBER',
        12 => 'DESEMBER'
    ];

    public function __construct($year, $filters = [], $limit = 100)
    {
        $this->year = $year;
        $this->filters = $filters;
        $this->limit = $limit;
    }

    public function collection()
    {
        try {
            // Get max yearmonth
            $maxym = DB::table('tbl_invsummary')
                ->where('yearmonth', 'like', $this->year . '%')
                ->max('yearmonth');

            if (!$maxym) {
                return collect([]);
            }

            // Build base query
            $query = DB::table('tbl_invsummary as i')
                ->join('mas_inventory as m', 'i.partcode', '=', 'm.partcode')
                ->join('mas_system as s', DB::raw('substr(i.yearmonth, 1, 4)'), '=', 's.year')
                ->select(
                    'i.partcode',
                    'm.partname',
                    'm.specification',
                    DB::raw('COALESCE(m.unitprice, 0) as unitprice'),
                    DB::raw('CASE m.currency
                        WHEN \'USD\' THEN COALESCE(s.usd2idr::numeric, 1)
                        WHEN \'JPY\' THEN COALESCE(s.jpy2idr::numeric, 1)
                        WHEN \'EUR\' THEN COALESCE(s.eur2idr::numeric, 1)
                        WHEN \'SGD\' THEN COALESCE(s.sgd2idr::numeric, 1)
                        ELSE 1
                    END as exchangerate'),
                    DB::raw('COALESCE(i.stocknumber, 0) * COALESCE(m.unitprice, 0) * CASE m.currency
                        WHEN \'USD\' THEN COALESCE(s.usd2idr::numeric, 1)
                        WHEN \'JPY\' THEN COALESCE(s.jpy2idr::numeric, 1)
                        WHEN \'EUR\' THEN COALESCE(s.eur2idr::numeric, 1)
                        WHEN \'SGD\' THEN COALESCE(s.sgd2idr::numeric, 1)
                        ELSE 1
                    END as stockamount')
                )
                ->where('i.yearmonth', '=', $maxym);

            // Apply filters
            if (!empty($this->filters['part_code'])) {
                $query->where('m.partcode', 'ilike', $this->filters['part_code'] . '%');
            }
            if (!empty($this->filters['part_name'])) {
                $query->where(DB::raw('upper(m.partname)'), 'ilike', '%' . strtoupper($this->filters['part_name']) . '%');
            }
            if (!empty($this->filters['brand'])) {
                $query->where(DB::raw('upper(m.brand)'), 'ilike', '%' . strtoupper($this->filters['brand']) . '%');
            }
            if (!empty($this->filters['used_flag'])) {
                $query->where('m.usedflag', 'O');
            }
            if (!empty($this->filters['specification'])) {
                $query->where(DB::raw('upper(m.specification)'), 'ilike', '%' . strtoupper($this->filters['specification']) . '%');
            }
            if (!empty($this->filters['address'])) {
                $query->where(DB::raw('upper(m.address)'), 'ilike', '%' . strtoupper($this->filters['address']) . '%');
            }
            if (!empty($this->filters['vendor_code'])) {
                $query->where(DB::raw('upper(m.vendorcode)'), 'ilike', '%' . strtoupper($this->filters['vendor_code']) . '%');
            }
            if (!empty($this->filters['note'])) {
                $query->where(DB::raw('upper(m.note)'), 'ilike', '%' . strtoupper($this->filters['note']) . '%');
            }
            if (!empty($this->filters['category'])) {
                $query->where('m.category', $this->filters['category']);
            }

            $inventoryData = $query->orderByDesc('stockamount')
                ->limit($this->limit)
                ->get();

            // Add monthly data to each record
            foreach ($inventoryData as $inventory) {
                $monthlyData = [];
                for ($month = 1; $month <= 12; $month++) {
                    $monthYear = $this->year . str_pad($month, 2, '0', STR_PAD_LEFT);
                    $monthlyStockAmount = $this->getMonthlyStockAmount($inventory->partcode, $monthYear);
                    $monthlyData[$month] = round($monthlyStockAmount * 0.000001, 2);
                }
                $inventory->monthly_data = (object)$monthlyData;
            }

            return collect($inventoryData);
        } catch (Exception $e) {
            throw new Exception('Error fetching inventory data: ' . $e->getMessage());
        }
    }

    protected function getMonthlyStockAmount($partCode, $monthYear)
    {
        return DB::table('tbl_invsummary as s')
            ->join('mas_inventory as m', 's.partcode', '=', 'm.partcode')
            ->join('mas_system as sys', DB::raw('substr(s.yearmonth, 1, 4)'), '=', 'sys.year')
            ->where('s.partcode', '=', $partCode)
            ->where('s.yearmonth', '=', $monthYear)
            ->sum(DB::raw('s.stocknumber * m.unitprice * CASE m.currency
                WHEN \'USD\' THEN COALESCE(sys.usd2idr::numeric, 1)
                WHEN \'JPY\' THEN COALESCE(sys.jpy2idr::numeric, 1)
                WHEN \'EUR\' THEN COALESCE(sys.eur2idr::numeric, 1)
                WHEN \'SGD\' THEN COALESCE(sys.sgd2idr::numeric, 1)
                ELSE 1 
            END'));
    }

    public function headings(): array
    {
        $baseHeadings = [
            'PART CODE',
            'PART NAME',
            'SPECIFICATION'
        ];

        // Add month headings with "JT. RP" suffix
        foreach ($this->months as $month) {
            $baseHeadings[] = $month . "\n(JT. RP)";
        }

        return $baseHeadings;
    }

    public function map($inventory): array
    {
        $row = [
            $inventory->partcode,
            $inventory->partname,
            $inventory->specification
        ];

        // Add monthly data
        for ($month = 1; $month <= 12; $month++) {
            $row[] = $inventory->monthly_data->{$month};
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // Style for the entire sheet
        $sheet->getStyle('A1:O' . ($sheet->getHighestRow()))->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);

        // Style for headers
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Set row height for header to accommodate two lines
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Style for the PART CODE column
        $sheet->getStyle('A2:A' . $sheet->getHighestRow())->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT
            ]
        ]);

        // Style for the PART NAME column
        $sheet->getStyle('B2:B' . $sheet->getHighestRow())->applyFromArray([
            'alignment' => [
                'wrapText' => true
            ]
        ]);

        // Style for the SPECIFICATION column
        $sheet->getStyle('C2:C' . $sheet->getHighestRow())->applyFromArray([
            'alignment' => [
                'wrapText' => true
            ]
        ]);

        // Style for numeric columns (monthly data)
        $sheet->getStyle('D2:O' . $sheet->getHighestRow())->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT
            ],
            'numberFormat' => [
                'formatCode' => '#,##0.00'
            ]
        ]);

        // Set columns width
        $sheet->getColumnDimension('A')->setWidth(20); // PART CODE column
        $sheet->getColumnDimension('B')->setWidth(40); // PART NAME column
        $sheet->getColumnDimension('C')->setWidth(40); // SPECIFICATION column
        foreach (range('D', 'O') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15); // Month columns
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
