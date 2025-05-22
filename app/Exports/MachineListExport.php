<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Query\Builder;

class MachineListExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithBatchInserts,
    WithChunkReading,
    WithStyles,
    ShouldAutoSize
{
    protected $maxMachineCount;
    protected $machineDataCache = [];
    protected $cacheLoaded = false;
    protected $chunkSize = 1000;
    protected $batchSize = 1000;

    public function __construct(int $maxMachineCount)
    {
        $this->maxMachineCount = $maxMachineCount;
    }

    public function query(): Builder
    {
        return DB::table('mas_inventory as m')
            ->select(
                'm.partcode',
                DB::raw('COALESCE(m.partname, \' \') as partname'),
                DB::raw('COALESCE(m.category, \' \') as category'),
                DB::raw('COALESCE(m.specification, \' \') as specification'),
                DB::raw('COALESCE(m.brand, \' \') as brand')
            )
            ->where('m.status', '<>', 'D')
            ->orderBy('m.partcode');
    }

    public function headings(): array
    {
        $headings = [
            'PART CODE',
            'PART NAME',
            'CATEGORY',
            'SPECIFICATION',
            'BRAND'
        ];

        // Add machine columns based on max count
        for ($i = 1; $i <= $this->maxMachineCount; $i++) {
            $headings[] = "MACHINE-{$i}";
        }

        return $headings;
    }

    public function map($part): array
    {
        // Load machine data cache if not already loaded
        if (!$this->cacheLoaded) {
            $this->loadMachineDataCache();
        }

        $row = [
            $part->partcode,
            $part->partname,
            $this->formatCategory($part->category),
            $part->specification,
            $part->brand
        ];

        // Get machines for this part
        $machines = $this->machineDataCache[$part->partcode] ?? [];

        // Add machine numbers to the row, padding with empty strings if needed
        for ($i = 0; $i < $this->maxMachineCount; $i++) {
            $row[] = $machines[$i] ?? '';
        }

        return $row;
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function styles(Worksheet $sheet)
    {
        // Get the last column letter (A, B, C, ... based on total columns)
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            5 + $this->maxMachineCount
        );

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFCCCCCC',
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            "A1:{$lastColumn}1" => [
                'alignment' => [
                    'wrapText' => true,
                ],
            ],
        ];
    }

    private function loadMachineDataCache(): void
    {
        $this->machineDataCache = [];

        // Process machine data in chunks
        DB::table('mas_invmachine')
            ->select('partcode', 'machineno')
            ->orderBy('partcode')
            ->orderBy('machineno')
            ->chunk($this->chunkSize, function ($machines) {
                foreach ($machines as $machine) {
                    if (!isset($this->machineDataCache[$machine->partcode])) {
                        $this->machineDataCache[$machine->partcode] = [];
                    }
                    $this->machineDataCache[$machine->partcode][] = $machine->machineno;
                }
            });

        $this->cacheLoaded = true;
    }

    private function formatCategory(string $category): string
    {
        switch ($category) {
            case 'M':
                return 'Machine';
            case 'F':
                return 'Facility';
            case 'J':
                return 'Jig';
            case 'O':
                return 'Other';
            default:
                return '---';
        }
    }
}
