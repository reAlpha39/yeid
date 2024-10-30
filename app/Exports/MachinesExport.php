<?php

namespace App\Exports;

use App\Models\MasMachine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MachinesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasMachine::all();
    }

    public function headings(): array
    {
        return [
            'MACHINE NO',
            'MACHINE NAME',
            'STATUS',
            'PLANT',
            'SHOP',
            'LINE',
            'MODEL',
            'MAKER'
        ];
    }

    public function map($machine): array
    {
        return [
            $machine->machineno,
            $machine->machinename,
            $machine->status,
            $machine->plantcode,
            $machine->shopcode,
            $machine->linecode,
            $machine->modelname,
            $machine->makername
        ];
    }
}
