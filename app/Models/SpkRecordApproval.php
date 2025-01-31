<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkRecordApproval extends Model
{
    protected $table = 'tbl_spkrecord_approval';

    protected $fillable = [
        'record_id',
        'department_id',
        'supervisor_approved_by',
        'supervisor_approved_at',
        'manager_approved_by',
        'manager_approved_at',
        'supervisor_mtc_approved_by',
        'supervisor_mtc_approved_at',
        'manager_mtc_approved_by',
        'manager_mtc_approved_at',
        'approval_status',
        'notes'
    ];

    protected $casts = [
        'supervisor_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'supervisor_mtc_approved_at' => 'datetime',
        'manager_mtc_approved_at' => 'datetime',
        'notes' => 'array'
    ];

    public function spkRecord(): BelongsTo
    {
        return $this->belongsTo(SpkRecord::class, 'record_id', 'recordid');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(MasDepartment::class);
    }
}
