<?php

namespace App\Exports;

use App\Models\MasMachine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MachinesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $shopcode;
    protected $maker;

    public function __construct($search = null, $shopcode = null, $maker = null, $maxRows = null)
    {
        $this->search = $search;
        $this->shopcode = $shopcode;
        $this->maker = $maker;
    }

    public function collection()
    {
        $query = MasMachine::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('machineno', 'ILIKE', $this->search . '%')
                ->orWhere('machinename', 'ILIKE', $this->search . '%')
                ->orWhere('plantcode', 'ILIKE', $this->search . '%')
                ->orWhere('shopcode', 'ILIKE', $this->search . '%')
                ->orWhere('shopname', 'ILIKE', $this->search . '%');
            });
        }

        if ($this->shopcode) {
            $query->where('shopcode', $this->shopcode);
        }

        if ($this->maker) {
            $query->where('makercode', $this->maker);
        }

        return $query->get();
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
