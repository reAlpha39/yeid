<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SparePartReferringMachineCostExport implements FromCollection, WithHeadings, WithMapping
{
    protected $year;
    protected $month;
    protected $plantCode;
    protected $shopCode;
    protected $machineNo;

    public function __construct($year = null, $month = null, $plantCode = null, $shopCode = null, $machineNo = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->plantCode = $plantCode;
        $this->shopCode = $shopCode;
        $this->machineNo = $machineNo;
    }

    public function collection()
    {
        $query = DB::table('tbl_invrecord')
            ->select([
                'mas_machine.machinename',
                'mas_machine.machineno',
                'mas_shop.shopname',
                'mas_machine.linecode',
                DB::raw('SUM(
                    COALESCE(tbl_invrecord.quantity, 0) * COALESCE(tbl_invrecord.unitprice, 0) *
                    CASE tbl_invrecord.currency
                        WHEN \'USD\' THEN COALESCE(mas_system.usd2idr::numeric, 1)
                        WHEN \'JPY\' THEN COALESCE(mas_system.jpy2idr::numeric, 1)
                        WHEN \'EUR\' THEN COALESCE(mas_system.eur2idr::numeric, 1)
                        WHEN \'SGD\' THEN COALESCE(mas_system.sgd2idr::numeric, 1)
                        ELSE 1
                    END
                ) AS price')
            ])
            ->leftJoin('mas_machine', 'tbl_invrecord.machineno', '=', 'mas_machine.machineno')
            ->leftJoin(DB::raw('mas_system'), function ($join) {
                $join->on(DB::raw('SUBSTRING(tbl_invrecord.jobdate::text, 1, 4)'), '=', 'mas_system.year');
            })
            ->leftJoin('mas_shop', 'mas_machine.shopcode', '=', 'mas_shop.shopcode')
            ->where('tbl_invrecord.jobcode', '=', 'O')
            ->whereRaw("COALESCE(mas_machine.machineno, '') <> ''");

        // Apply filters based on parameters
        if (!empty($this->year) && !empty($this->month)) {
            $jobMonth = $this->year . str_pad($this->month, 2, '0', STR_PAD_LEFT);
            $query->whereRaw("SUBSTRING(tbl_invrecord.jobdate::text, 1, 6) = ?", [$jobMonth]);
        } elseif (!empty($this->year)) {
            $query->whereRaw("SUBSTRING(tbl_invrecord.jobdate::text, 1, 4) = ?", [$this->year]);
        }

        if (!empty($this->plantCode)) {
            $query->where('mas_machine.plantcode', '=', $this->plantCode);
        }

        if (!empty($this->shopCode)) {
            $query->where('mas_machine.shopcode', '=', $this->shopCode);
        }

        if (!empty($this->machineNo)) {
            $query->where('mas_machine.machineno', 'ilike', $this->machineNo . '%');
        }

        return $query->groupBy([
            'mas_machine.machineno',
            'mas_machine.machinename',
            'mas_machine.linecode',
            'mas_shop.shopname'
        ])
            ->orderBy('price', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NO.',
            'MACHINE NAME',
            'MACHINE NO',
            'SHOP',
            'LINE',
            'COST',
            'UNIT'
        ];
    }

    public function map($machine): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $machine->machinename ?? '',
            $machine->machineno ?? '',
            $machine->shopname ?? '',
            $machine->linecode ?? '',
            number_format($machine->price, 0) ?? '',
            'Rp.'
        ];
    }
}
