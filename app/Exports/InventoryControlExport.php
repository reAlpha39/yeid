<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class InventoryControlExport implements FromCollection, WithHeadings, WithMapping, WithChunkReading
{
    protected $startDate;
    protected $endDate;
    protected $jobCode;
    protected $partCode;
    protected $partName;
    protected $brand;
    protected $specification;
    protected $vendorCode;
    protected $note;
    protected $usedFlag;
    protected $minusFlag;
    protected $orderFlag;
    protected $sortBy;
    protected $sortDirection;

    public function __construct(array $filters = [])
    {
        $this->startDate = $filters['start_date'] ?? '';
        $this->endDate = $filters['end_date'] ?? '';
        $this->jobCode = $filters['job_code'] ?? 'I';
        $this->partCode = $filters['part_code'] ?? '';
        $this->partName = $filters['part_name'] ?? '';
        $this->brand = $filters['brand'] ?? '';
        $this->specification = $filters['specification'] ?? '';
        $this->vendorCode = $filters['vendor_code'] ?? '';
        $this->note = $filters['note'] ?? '';
        $this->usedFlag = $filters['used_flag'] ?? '0';
        $this->minusFlag = $filters['minus_flag'] ?? '0';
        $this->orderFlag = $filters['order_flag'] ?? '0';

        // Handle sortBy if it's present
        if (isset($filters['sortBy']) && is_array($filters['sortBy'])) {
            $this->sortBy = $filters['sortBy']['key'] ?? null;
            $this->sortDirection = $filters['sortBy']['order'] ?? 'desc';
        } else {
            $this->sortBy = $filters['sortBy'] ?? null;
            $this->sortDirection = $filters['sortDirection'] ?? 'desc';
        }
    }

    public function collection()
    {
        $query = DB::table('tbl_invrecord AS i')
            ->leftJoin('mas_machine AS m', 'i.machineno', '=', 'm.machineno');

        // Add joins for minus_flag and order_flag
        if ($this->minusFlag === '1' || $this->orderFlag === '1') {
            $query->leftJoin('mas_inventory AS mi', 'i.partcode', '=', 'mi.partcode');
        }

        // Add join for minus_flag
        if ($this->minusFlag === '1') {
            $subQuery = DB::table('tbl_invrecord AS t')
                ->select('t.partcode')
                ->selectRaw('SUM(CASE WHEN t.jobcode = \'O\' THEN -t.quantity ELSE t.quantity END) as sum_quantity')
                ->leftJoin('mas_inventory AS minv', 't.partcode', '=', 'minv.partcode')
                ->where('t.jobdate', '>', DB::raw('minv.laststockdate'))
                ->groupBy('t.partcode');

            $query->leftJoinSub($subQuery, 'gi', function ($join) {
                $join->on('i.partcode', '=', 'gi.partcode');
            });
        }

        $query->select(
            'i.recordid',
            'i.jobcode',
            'i.jobdate',
            'i.jobtime',
            'i.partcode',
            'i.partname',
            'i.specification',
            'i.brand',
            'i.usedflag',
            'i.quantity',
            'i.unitprice',
            'i.currency',
            'i.total',
            'i.machineno',
            DB::raw('COALESCE(m.shopname, \'-\') AS shopname'),
            DB::raw('COALESCE(m.linecode, \'-\') AS linecode'),
            'i.employeecode',
            'i.note',
            'i.vendorcode'
        );

        $query->whereBetween('i.jobdate', [$this->startDate, $this->endDate])
            ->where('i.jobcode', $this->jobCode);

        // Apply filters
        if (!empty($this->partCode)) {
            $query->where('i.partcode', 'ILIKE', '%' . $this->partCode . '%');
        }
        if (!empty($this->partName)) {
            $query->where('i.partname', 'ILIKE', '%' . $this->partName . '%');
        }
        if (!empty($this->brand)) {
            $query->where('i.brand', 'ILIKE', '%' . $this->brand . '%');
        }
        if (!empty($this->specification)) {
            $query->where('i.specification', 'ILIKE', '%' . $this->specification . '%');
        }
        if (!empty($this->vendorCode)) {
            $query->where('i.vendorcode', $this->vendorCode);
        }
        if (!empty($this->note)) {
            $query->where('i.note', 'ILIKE', '%' . $this->note . '%');
        }
        if ($this->usedFlag === '1') {
            $query->where('i.usedflag', 'O');
        }
        if ($this->minusFlag === '1') {
            $query->whereRaw('mi.minstock > (mi.laststocknumber + COALESCE(gi.sum_quantity, 0))');
        }
        if ($this->orderFlag === '1') {
            $query->whereRaw("COALESCE(mi.posentdate, '') <> ''");
        }

        // Apply sorting
        if ($this->sortBy) {
            // Handle special cases for computed/joined columns
            switch ($this->sortBy) {
                case 'shopname':
                    $query->orderBy(DB::raw('COALESCE(m.shopname, \'-\')'), $this->sortDirection);
                    break;
                case 'linecode':
                    $query->orderBy(DB::raw('COALESCE(m.linecode, \'-\')'), $this->sortDirection);
                    break;
                default:
                    // For regular columns, determine the table prefix
                    $prefix = in_array($this->sortBy, ['shopname', 'linecode']) ? 'm.' : 'i.';
                    $query->orderBy($prefix . $this->sortBy, $this->sortDirection);
            }

            // Add secondary sort if primary sort isn't jobdate/jobtime
            if ($this->sortBy !== 'jobdate' && $this->sortBy !== 'jobtime') {
                $query->orderBy('i.jobdate', 'desc')
                    ->orderBy('i.jobtime', 'desc');
            }
        } else {
            // Default sorting
            $query->orderBy('i.jobdate', 'desc')
                ->orderBy('i.jobtime', 'desc');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'RECORD ID',
            'JOB CODE',
            'JOB DATE',
            'JOB TIME',
            'PART CODE',
            'PART NAME',
            'SPECIFICATION',
            'BRAND',
            'USED FLAG',
            'QUANTITY',
            'UNIT PRICE',
            'CURRENCY',
            'TOTAL',
            'MACHINE NO',
            'SHOP NAME',
            'LINE CODE',
            'EMPLOYEE CODE',
            'NOTE',
            'VENDOR CODE'
        ];
    }

    public function map($record): array
    {
        return [
            $record->recordid,
            $record->jobcode,
            $record->jobdate,
            $record->jobtime,
            $record->partcode,
            $record->partname,
            $record->specification,
            $record->brand,
            $record->usedflag,
            $record->quantity,
            $record->unitprice,
            $record->currency,
            $record->total,
            $record->machineno,
            $record->shopname,
            $record->linecode,
            $record->employeecode,
            $record->note,
            $record->vendorcode
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
