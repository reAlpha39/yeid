<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_tasks';
    protected $primaryKey = 'task_id';

    protected $fillable = [
        'activity_id',
        'machine_id',
        'task_name',
        'frequency_times',
        'frequency_period',
        'start_week',
        'duration',
        'manpower_required',
        'pic',
        'cycle_time'
    ];

    public function machine()
    {
        return $this->belongsTo(MasMachine::class, 'machine_id', 'machineno');
    }

    public function activity()
    {
        return $this->belongsTo(ScheduleActivity::class, 'activity_id');
    }

    public function executions()
    {
        return $this->hasMany(ScheduleTaskItem::class, 'task_id');
    }
}
