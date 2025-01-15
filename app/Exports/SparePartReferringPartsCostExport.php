<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SparePartReferringPartsCostExport implements FromCollection, WithHeadings, WithEvents
{
    protected $year;
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
    protected $rows = [
        ['type' => 'Import', 'category' => 'M'],
        ['type' => 'Local', 'category' => 'M'],
        ['type' => 'Import', 'category' => 'J'],
        ['type' => 'Local', 'category' => 'J'],
        ['type' => 'Import', 'category' => 'F'],
        ['type' => 'Local', 'category' => 'F'],
        ['type' => 'Import', 'category' => 'O'],
        ['type' => 'Local', 'category' => 'O']
    ];

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function collection()
    {
        $rawData = DB::table('tbl_invrecord as r')
            ->selectRaw("
                m.plantcode,
                substr(r.partcode, 2, 1) || substr(r.partcode, 4, 1) as part_code,
                substr(r.jobdate, 1, 6) as job_month,
                sum(coalesce(r.quantity, 0) * coalesce(r.unitprice, 0) *
                    (case r.currency
                        when 'USD' then coalesce(s.usd2idr::numeric, 1)
                        when 'JPY' then coalesce(s.jpy2idr::numeric, 1)
                        when 'EUR' then coalesce(s.eur2idr::numeric, 1)
                        when 'SGD' then coalesce(s.sgd2idr::numeric, 1)
                        else 1
                    end)
                ) as total_cost_idr
            ")
            ->leftJoin('mas_machine as m', 'r.machineno', '=', 'm.machineno')
            ->leftJoin('mas_system as s', DB::raw('substr(r.jobdate, 1, 4)'), '=', 's.year')
            ->where('r.jobcode', 'O')
            ->whereRaw("substr(r.jobdate, 1, 4) = ?", [$this->year])
            ->whereRaw("coalesce(m.plantcode, '') <> ''")
            ->groupBy('m.plantcode', DB::raw("substr(r.partcode, 2, 1) || substr(r.partcode, 4, 1)"), DB::raw("substr(r.jobdate, 1, 6)"))
            ->orderBy('m.plantcode')
            ->get();

        $formattedData = collect();
        $groupedByPlant = $rawData->groupBy('plantcode');

        foreach ($groupedByPlant as $plantCode => $plantData) {
            // Add headers for each plant
            $formattedData->push([
                'Plant Code: ' . $plantCode,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ]);

            // Add column headers
            $formattedData->push([
                'IMPORT/LOCAL',
                'CAT',
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
            ]);

            // Add data rows
            foreach ($this->rows as $row) {
                $rowData = ['', '', ''];
                $rowData[0] = $row['type'];
                $rowData[1] = $row['category'];
                $rowData[2] = 'Jt. IDR';

                $typePrefix = $row['type'] === 'Import' ? 'I' : 'L';
                $partCode = $typePrefix . $row['category'];

                for ($month = 1; $month <= 12; $month++) {
                    $monthStr = sprintf("%06d", $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                    $entry = $plantData->first(function ($item) use ($partCode, $monthStr) {
                        return $item->part_code === $partCode && $item->job_month === $monthStr;
                    });

                    $rowData[] = $entry ? number_format($entry->total_cost_idr / 1000000, 1) : '-';
                }

                $formattedData->push($rowData);
            }

            // Add subtotal row
            $subtotalRow = ['Sub Total', '', 'Jt. IDR'];
            for ($month = 1; $month <= 12; $month++) {
                $monthStr = sprintf("%06d", $this->year . str_pad($month, 2, '0', STR_PAD_LEFT));
                $monthData = $plantData->filter(function ($item) use ($monthStr) {
                    return $item->job_month === $monthStr;
                });

                if ($monthData->isEmpty()) {
                    $subtotalRow[] = '-';
                } else {
                    $total = $monthData->sum(function ($item) {
                        // return round($item->total_cost_idr / 1000000, 3);
                        return number_format($item->total_cost_idr / 1000000, 1);
                    });
                    $subtotalRow[] = number_format(round($total, 1), 1);
                }
            }
            $formattedData->push($subtotalRow);

            // Add empty row between plants
            $formattedData->push(['', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
        }

        return $formattedData;
    }

    public function headings(): array
    {
        return []; // Headers are handled in collection()
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestRow();
                $lastColumn = 'O'; // Assuming 15 columns (A to O)

                // Style for all cells
                $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Find and style plant headers and subtotal rows
                for ($row = 1; $row <= $lastRow; $row++) {
                    $value = $sheet->getCell('A' . $row)->getValue();

                    if (str_starts_with($value, 'Plant Code:')) {
                        // Plant header styling
                        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E0E0E0'],
                            ],
                        ]);
                        $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
                    } elseif ($value === 'Sub Total') {
                        // Subtotal row styling
                        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9F9F9'],
                            ],
                        ]);
                    }
                }

                // Auto-size columns
                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Center align all cells except totals
                $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
