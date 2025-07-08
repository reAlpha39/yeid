<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Exportable;

class InventoryPartsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    use Exportable;

    protected $search;
    protected $status;
    protected $partCode;
    protected $partName;
    protected $brand;
    protected $specification;
    protected $address;
    protected $vendorCode;
    protected $note;
    protected $category;
    protected $usedFlag;
    protected $minusFlag;
    protected $orderFlag;
    protected $sortBy;
    protected $sortDirection;

    public function __construct(array $filters = [])
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $this->search = $filters['search'] ?? '';
        $this->status = $filters['status'] ?? null;
        $this->partCode = $filters['part_code'] ?? '';
        $this->partName = $filters['part_name'] ?? '';
        $this->brand = $filters['brand'] ?? '';
        $this->specification = $filters['specification'] ?? '';
        $this->address = $filters['address'] ?? '';
        $this->vendorCode = $filters['vendor_code'] ?? '';
        $this->note = $filters['note'] ?? '';
        $this->category = $filters['category'] ?? '';
        $this->usedFlag = $filters['used_flag'] ?? '0';
        $this->minusFlag = $filters['minus_flag'] ?? '0';
        $this->orderFlag = $filters['order_flag'] ?? '0';

        // Handle sortBy if it's an array or string
        if (isset($filters['sortBy']) && is_array($filters['sortBy'])) {
            $this->sortBy = $filters['sortBy']['key'] ?? null;
            $this->sortDirection = $filters['sortBy']['order'] ?? 'asc';
        } else {
            $this->sortBy = $filters['sortBy'] ?? null;
            $this->sortDirection = $filters['sortDirection'] ?? 'asc';
        }
    }

    public function query()
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

        // Apply search filter
        if ($this->search) {
            $queryBuilder->where(function ($q) {
                $q->where('m.partcode', 'ILIKE', $this->search . '%')
                    ->orWhere(DB::raw('upper(m.partname)'), 'ILIKE', strtoupper($this->search) . '%');
            });
        }

        // Status Filter
        if ($this->status === 'ORANGE') {
            $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                ->where('m.status', 'O');
        } elseif ($this->status === 'RED') {
            $queryBuilder->whereRaw('(m.laststocknumber + COALESCE(gi.sum_quantity, 0)) <= CAST(COALESCE(m.minstock, 0) AS INTEGER)')
                ->where(function ($q) {
                    $q->where('m.status', '<>', 'O')
                        ->orWhereNull('m.status');
                });
        } elseif ($this->status === 'YELLOW') {
            $queryBuilder->whereNotNull('m.posentdate')
                ->whereNotNull('m.etddate')
                ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') >= CURRENT_DATE");
        } elseif ($this->status === 'BLUE') {
            $queryBuilder->whereNotNull('m.posentdate')
                ->whereNotNull('m.etddate')
                ->whereRaw("TO_DATE(m.etddate, 'YYYYMMDD') < CURRENT_DATE");
        }

        // Apply other filters
        if (!empty($this->partCode)) {
            $queryBuilder->where(DB::raw('upper(m.partcode)'), 'ILIKE', '%' . strtoupper($this->partCode) . '%');
        }
        if (!empty($this->partName)) {
            $queryBuilder->where(DB::raw('upper(m.partname)'), 'ILIKE', '%' . strtoupper($this->partName) . '%');
        }
        if (!empty($this->brand)) {
            $queryBuilder->where(DB::raw('upper(m.brand)'), 'ILIKE', '%' . strtoupper($this->brand) . '%');
        }
        if (!empty($this->specification)) {
            $queryBuilder->where(DB::raw('upper(m.specification)'), 'ILIKE', '%' . strtoupper($this->specification) . '%');
        }
        if (!empty($this->address)) {
            $queryBuilder->where(DB::raw('upper(m.address)'), 'ILIKE', '%' . strtoupper($this->address) . '%');
        }
        if (!empty($this->vendorCode)) {
            $queryBuilder->where(DB::raw('upper(m.vendorcode)'), 'ILIKE', strtoupper($this->vendorCode) . '%');
        }
        if (!empty($this->note)) {
            $queryBuilder->where(DB::raw('upper(m.note)'), 'ILIKE', '%' . strtoupper($this->note) . '%');
        }
        if (in_array($this->category, ['M', 'F', 'J', 'O'])) {
            $queryBuilder->where('m.category', $this->category);
        }
        if ($this->usedFlag === '1') {
            $queryBuilder->where('m.usedflag', 'O');
        }
        if ($this->minusFlag === '1') {
            $queryBuilder->where(DB::raw('m.minstock'), '>', DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'));
        }
        if ($this->orderFlag === '1') {
            $queryBuilder->where(DB::raw('COALESCE(m.posentdate, \' \')'), '<>', ' ');
        }

        // Apply sorting
        if ($this->sortBy && is_string($this->sortBy)) {
            if ($this->sortBy === 'totalstock') {
                $queryBuilder->orderBy(DB::raw('m.laststocknumber + COALESCE(gi.sum_quantity, 0)'), $this->sortDirection);
            } else {
                $queryBuilder->orderBy("m.{$this->sortBy}", $this->sortDirection);
            }
        } else {
            $queryBuilder->orderBy('m.partcode');
        }

        return $queryBuilder;
    }

    public function headings(): array
    {
        return [
            'PART CODE',
            'PART NAME',
            'CATEGORY',
            'SPECIFICATION',
            'BRAND',
            'ADDRESS',
            'STOCK QUANTITY',
            'MINIMUM STOCK',
            'UNIT PRICE',
            'CURRENCY',
            'NOTE',
            'REQUEST ORDER',
            'ORDER',
            'P/O SENT',
            'ETD',
            'STATUS'
        ];
    }

    public function map($part): array
    {
        return [
            $part->partcode,
            $part->partname,
            $part->category,
            $part->specification,
            $part->brand,
            $part->address,
            $part->totalstock,
            $part->minstock,
            $part->unitprice,
            $part->currency,
            $part->note,
            $part->reqquotationdate,
            $part->orderdate,
            $part->posentdate,
            $part->etddate,
            $this->getStatus($part)
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function getStatus($part): string
    {
        // Helper function to parse stock values
        $parseStock = function ($value) {
            return is_numeric($value) ? (int)$value : 0;
        };

        $status = trim($part->status ?? '');
        $posentdate = trim($part->posentdate ?? '');
        $totalstock = $parseStock($part->totalstock);
        $minstock = $parseStock($part->minstock);

        // Check if item has PO sent date (order status)
        if ($posentdate) {
            $etddate = trim($part->etddate ?? '');
            if ($etddate) {
                try {
                    // Compare dates
                    $etd = Carbon::createFromFormat(
                        'Ymd',
                        $etddate
                    )->startOfDay();
                    $today = Carbon::now()->startOfDay();

                    return $etd->greaterThanOrEqualTo($today) ? 'YELLOW' : 'BLUE';
                } catch (\Exception $e) {
                    return 'GREEN'; // Default if date parsing fails
                }
            }
        }

        // Check stock level status
        if ($totalstock <= $minstock) {
            return $status === 'O' ? 'ORANGE' : 'RED';
        }

        return 'GREEN';
    }
}
