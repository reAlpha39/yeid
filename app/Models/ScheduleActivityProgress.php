<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleActivityProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_activity_progress';
    protected $primaryKey = 'progress_id';

    protected $fillable = [
        'activity_id',
        'month',
        'year',
        'total_tasks',
        'completed_on_time',
        'completed_delayed',
        'not_completed'
    ];

    public function activity()
    {
        return $this->belongsTo(ScheduleActivity::class, 'activity_id');
    }
}
