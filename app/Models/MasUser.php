<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasUser extends Model
{
    // Specify the table name
    protected $table = 'HOZENADMIN.MAS_USER';

    // Specify the primary key
    protected $primaryKey = 'ID';

    // Indicate if the primary key is auto-incrementing
    public $incrementing = true;

    // Specify the key type for the primary key
    protected $keyType = 'int';

    // Define fillable attributes
    protected $fillable = [
        'FULLNAME',
        'EMAIL',
        'PHONE',
        'DEPARTMENT',
        'ROLEACCESS',
        'STATUS',
        'CONTROLACCESS',
    ];

    // Disable timestamps if not needed
    public $timestamps = false;

    // Specify that CONTROLACCESS will be handled as a JSON column
    protected $casts = [
        'CONTROLACCESS' => 'array',
    ];
}

