<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PressPartExport implements FromCollection, WithHeadings, WithMapping, WithChunkReading
{
    protected $year;
    protected $machineNo;
    protected $model;
    protected $dieNo;
    protected $partCode;
    protected $status;
    protected $sortBy;
    protected $sortDirection;

    public function __construct(
        $year = null,
        $machineNo = null,
        $model = null,
        $dieNo = null,
        $partCode = null,
        $status = null,
        $sortBy = null,
        $sortDirection = 'asc'
    ) {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $this->year = $year;
        $this->machineNo = $machineNo;
        $this->model = $model;
        $this->dieNo = $dieNo;
        $this->partCode = $partCode;
        $this->status = $status;
        $this->sortBy = $sortBy;
        $this->sortDirection = $sortDirection;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection()
    {
        $query = DB::table('mas_presspart as m')
            ->leftJoin('mas_inventory as i', function ($join) {
                $join->on(
                    DB::raw('substring(m.partcode, 1, 8)'),
                    '=',
                    DB::raw('substring(i.partcode, 1, 8)')
                )
                    ->whereNotNull('i.partcode');
            })
            ->leftJoin('mas_machine as c', 'm.machineno', '=', 'c.machineno')
            ->leftJoin('mas_vendor as v', 'i.vendorcode', '=', 'v.vendorcode')
            ->select([
                'c.machinename',
                'm.machineno',
                'm.model',
                'm.dieno',
                'm.dieunitno',
                'm.processname',
                'm.partcode',
                'm.partname',
                'm.category',
                DB::raw('COALESCE((
                    SELECT sum(shotcount)
                    FROM tbl_presswork
                    WHERE machineno = m.machineno
                    AND model = m.model
                    AND dieno = m.dieno
                    AND dieunitno = m.dieunitno
                    AND startdatetime > m.exchangedatetime
                ), 0) as counter'),
                'm.companylimit',
                'm.makerlimit',
                'm.qttyperdie',
                'm.drawingno',
                'm.note',
                'm.exchangedatetime',
                'm.minstock',
                DB::raw('COALESCE(i.laststocknumber, 0) +
                COALESCE((
                    SELECT sum(CASE
                        WHEN jobcode = \'O\' THEN -quantity
                        ELSE quantity
                    END)
                    FROM tbl_invrecord
                    WHERE partcode = i.partcode
                    AND jobdate > i.laststockdate
                ), 0) as currentstock'),
                'i.unitprice',
                'i.currency',
                'i.brand',
                DB::raw('COALESCE(v.vendorname, \'-\') as vendorname'),
                DB::raw('CASE
                    WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                    WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                    ELSE \'-\'
                END as origin'),
                'i.address'
            ])
            ->where('m.status', '<>', 'D');

        // Apply filters
        if ($this->year) {
            $query->where('m.exchangedatetime', 'LIKE', $this->year . '%');
        }

        if ($this->machineNo) {
            $query->where('m.machineno', $this->machineNo);
        }

        if ($this->model) {
            $query->where('m.model', $this->model);
        }

        if ($this->dieNo) {
            $query->where('m.dieno', $this->dieNo);
        }

        if ($this->partCode) {
            $query->where(function ($q) {
                $q->where('m.partcode', 'ILIKE', "{$this->partCode}%")
                    ->orWhere('m.partname', 'ILIKE', "{$this->partCode}%");
            });
        }

        // Status filters
        if ($this->status === 'RED') {
            $query->whereRaw('CAST(COALESCE((
                SELECT sum(shotcount)
                FROM tbl_presswork
                WHERE machineno = m.machineno
                AND model = m.model
                AND dieno = m.dieno
                AND dieunitno = m.dieunitno
                AND startdatetime > m.exchangedatetime
            ), 0) AS INTEGER) > CAST(COALESCE(m.companylimit, 0) AS INTEGER)');
        } elseif ($this->status === 'BLUE') {
            $query->whereRaw('CAST(COALESCE((
                SELECT sum(shotcount)
                FROM tbl_presswork
                WHERE machineno = m.machineno
                AND model = m.model
                AND dieno = m.dieno
                AND dieunitno = m.dieunitno
                AND startdatetime > m.exchangedatetime
            ), 0) AS INTEGER) > CAST(COALESCE(m.makerlimit, 0) AS INTEGER)');
        } elseif ($this->status === 'YELLOW') {
            $query->whereRaw('CAST(COALESCE(m.minstock, 0) AS INTEGER) > CAST(COALESCE((
                COALESCE(i.laststocknumber, 0) +
                COALESCE((
                    SELECT sum(CASE
                        WHEN jobcode = \'O\' THEN -quantity
                        ELSE quantity
                    END)
                    FROM tbl_invrecord
                    WHERE partcode = i.partcode
                    AND jobdate > i.laststockdate
                ), 0)
            ), 0) AS INTEGER)');
        }

        // Apply sorting
        if ($this->sortBy) {
            switch ($this->sortBy) {
                case 'counter':
                    $query->orderBy(DB::raw('COALESCE((
                        SELECT sum(shotcount)
                        FROM tbl_presswork
                        WHERE machineno = m.machineno
                        AND model = m.model
                        AND dieno = m.dieno
                        AND dieunitno = m.dieunitno
                        AND startdatetime > m.exchangedatetime
                    ), 0)'), $this->sortDirection);
                    break;
                case 'currentstock':
                    $query->orderBy(DB::raw('COALESCE(i.laststocknumber, 0) +
                        COALESCE((
                            SELECT sum(CASE
                                WHEN jobcode = \'O\' THEN -quantity
                                ELSE quantity
                            END)
                            FROM tbl_invrecord
                            WHERE partcode = i.partcode
                            AND jobdate > i.laststockdate
                        ), 0)'), $this->sortDirection);
                    break;
                case 'origin':
                    $query->orderBy(DB::raw('CASE
                        WHEN m.partcode LIKE \'_I%\' THEN \'Import\'
                        WHEN m.partcode LIKE \'_L%\' THEN \'Local\'
                        ELSE \'-\'
                    END'), $this->sortDirection);
                    break;
                case 'vendorname':
                    $query->orderBy(DB::raw('COALESCE(v.vendorname, \'-\')'), $this->sortDirection);
                    break;
                case 'machinename':
                    $query->orderBy('c.machinename', $this->sortDirection);
                    break;
                default:
                    $prefix = in_array($this->sortBy, [
                        'unitprice',
                        'currency',
                        'brand',
                        'address'
                    ]) ? 'i.' : 'm.';
                    $query->orderBy($prefix . $this->sortBy, $this->sortDirection);
            }
        } else {
            $query->orderBy('m.partcode');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'MACHINE NAME',
            'MACHINE NO',
            'MODEL',
            'DIE NO',
            'DIE UNIT NO',
            'PROCESS',
            'PART CODE',
            'PART NAME',
            'CATEGORY',
            'COUNTER',
            'COMPANY LIMIT',
            'MAKER LIMIT',
            'QTY/DIE',
            'DRAWING NO',
            'NOTE',
            'EXCHANGE DATE',
            'MIN STOCK',
            'CURRENT STOCK',
            'UNIT PRICE',
            'CURRENCY',
            'BRAND',
            'VENDOR',
            'ORIGIN',
            'ADDRESS'
        ];
    }

    public function map($record): array
    {
        return [
            $record->machinename,
            $record->machineno,
            $record->model,
            $record->dieno,
            $record->dieunitno,
            $record->processname,
            $record->partcode,
            $record->partname,
            $record->category,
            $record->counter,
            $record->companylimit,
            $record->makerlimit,
            $record->qttyperdie,
            $record->drawingno,
            $record->note,
            $record->exchangedatetime,
            $record->minstock,
            $record->currentstock,
            $record->unitprice,
            $record->currency,
            $record->brand,
            $record->vendorname,
            $record->origin,
            $record->address
        ];
    }
}
