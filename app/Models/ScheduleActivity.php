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
        'shop_id',
        'dept_id',
        'activity_name'
    ];

    public function shop()
    {
        return $this->belongsTo(MasShop::class, 'shop_id', 'shopcode');
    }

    public function pic()
    {
        return $this->belongsTo(MasDepartment::class, 'dept_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(ScheduleTask::class, 'activity_id');
    }
}
