<?php

namespace App\Exports;

use App\Models\MasUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MasUser::with('department')->get();
    }

    public function headings(): array
    {
        return [
            'FULL NAME',
            'EMAIL ADDRESS',
            'DEPARTMENT',
            'ROLE ACCESS',
            'STATUS'
        ];
    }

    public function map($user): array
    {
        // Convert status to readable format
        $status = $user->status ? 'Active' : 'Inactive';

        return [
            $user->name,
            $user->email,
            $user->department ? $user->department->name : '-',
            $user->role_access,
            $status
        ];
    }
}
