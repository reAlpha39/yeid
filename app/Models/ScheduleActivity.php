<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedule_activities';
    protected $primaryKey = 'activity_id';

    protected $fillable = [
        'machineno',
        'activity_name'
    ];

    public function machine()
    {
        return $this->belongsTo(MasMachine::class, 'machineno', 'machineno');
    }

    public function progress()
    {
        return $this->hasMany(ScheduleActivityProgress::class, 'activity_id');
    }

    public function tasks()
    {
        return $this->hasMany(ScheduleTask::class, 'activity_id');
    }
}
