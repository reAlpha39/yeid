<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleTaskExecution extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_task_execution';
    protected $primaryKey = 'execution_id';

    protected $fillable = [
        'task_id',
        'scheduled_date',
        'status',
        'completion_date'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completion_date' => 'date'
    ];

    public function task()
    {
        return $this->belongsTo(ScheduleTask::class, 'task_id');
    }

    public function userAssignments()
    {
        return $this->hasMany(ScheduleUserAssignment::class, 'task_execution_id');
    }
}
