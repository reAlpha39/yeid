<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasDepartment extends Model
{
    // Specify the database table
    protected $table = 'HOZENADMIN.MAS_DEPARTMENT';

    // Specify the primary key
    protected $primaryKey = 'DEPARTMENTCODE';

    // Disable auto-incrementing as the primary key is not an integer
    public $incrementing = false;

    // Specify the key type for the primary key
    protected $keyType = 'string';

    // Define fillable properties
    protected $fillable = [
        'DEPARTMENTCODE',
        'DEPARTMENTID',
        'DEPARTMENTNAME',
    ];

    // Disable timestamps as the table does not use Laravel's default timestamps
    public $timestamps = false;
}

