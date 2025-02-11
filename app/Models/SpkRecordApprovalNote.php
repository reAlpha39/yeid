<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpkRecordApprovalNote extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_spkrecord_approval_note';

    protected $fillable = [
        'approval_id',
        'user_id',
        'note',
        'type'
    ];

    public function approval()
    {
        return $this->belongsTo(SpkRecordApproval::class, 'approval_id');
    }

    public function user()
    {
        return $this->belongsTo(MasUser::class, 'user_id');
    }
}
