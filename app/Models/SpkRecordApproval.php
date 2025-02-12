<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpkRecordApproval extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_spkrecord_approval';

    protected $fillable = [
        'record_id',
        'department_id',
        'created_by',
        'pic',
        'supervisor_approved_by',
        'supervisor_approved_at',
        'manager_approved_by',
        'manager_approved_at',
        'supervisor_mtc_approved_by',
        'supervisor_mtc_approved_at',
        'manager_mtc_approved_by',
        'manager_mtc_approved_at',
        'approval_status',
    ];

    protected $casts = [
        'supervisor_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'supervisor_mtc_approved_at' => 'datetime',
        'manager_mtc_approved_at' => 'datetime',
    ];

    public function spkRecord(): BelongsTo
    {
        return $this->belongsTo(SpkRecord::class, 'record_id', 'recordid');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(MasDepartment::class, 'department_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(MasUser::class, 'created_by', 'id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(MasEmployee::class, 'pic', 'employeecode');
    }

    public function notes()
    {
        return $this->hasMany(SpkRecordApprovalNote::class, 'approval_id');
    }
}
