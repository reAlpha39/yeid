<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobProgress extends Model
{
    protected $fillable = [
        'job_type',
        'status',
        'progress',
        'total_items',
        'processed_items',
        'error_message',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'progress' => 'float',
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
}
