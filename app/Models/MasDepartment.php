<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasDepartment extends Model
{
    use SoftDeletes;

    // Specify the table name
    protected $table = 'mas_department';

    // Specify the primary key
    protected $primaryKey = 'id';

    // Define fillable attributes
    protected $fillable = [
        'code',
        'name',
    ];

    // Enable timestamps management (created_at, updated_at)
    public $timestamps = true;

    // Set default date format for the model
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}

