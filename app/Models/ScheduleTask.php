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
        'dept_id',
        'frequency_times',
        'frequency_period',
        'start_week',
        'duration',
        'manpower_required',
        'cycle_time',
        'year'
    ];

    public function machine()
    {
        return $this->belongsTo(MasMachine::class, 'machine_id', 'machineno');
    }

    public function pic()
    {
        return $this->belongsTo(MasDepartment::class, 'dept_id', 'id');
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
