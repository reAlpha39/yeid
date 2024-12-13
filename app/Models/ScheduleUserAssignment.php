<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleUserAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_user_assignments';
    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'user_id',
        'task_execution_id',
        'assigned_date'
    ];

    protected $casts = [
        'assigned_date' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(MasUser::class, 'user_id', 'id');
    }

    public function taskExecution()
    {
        return $this->belongsTo(ScheduleTaskExecution::class, 'task_execution_id', 'execution_id');
    }
}
