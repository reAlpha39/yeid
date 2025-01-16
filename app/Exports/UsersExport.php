<?php

namespace App\Exports;

use App\Models\MasUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $department;
    protected $roleAccess;
    protected $status;

    public function __construct($search = null, $department = null, $roleAccess = null, $status = null)
    {
        $this->search = $search;
        $this->department = $department;
        $this->roleAccess = $roleAccess;
        $this->status = $status;
    }

    public function collection()
    {
        $query = MasUser::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'ILIKE', $this->search . '%')
                    ->orWhere('email', 'ILIKE', $this->search . '%');
            });
        }

        if ($this->department) {
            $query->where('department_id', $this->department);
        }

        if ($this->roleAccess) {
            $query->where('role_access', $this->roleAccess);
        }

        if (isset($this->status)) {
            $query->where('status', $this->status);
        }

        return $query->with('department')->get();
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
