<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasSystem extends Model
{
    // Specify the database table
    protected $table = 'HOZENADMIN.MAS_SYSTEM';

    // Specify the primary key
    protected $primaryKey = 'YEAR';

    // Disable auto-incrementing as the primary key is not an integer
    public $incrementing = false;

    // Specify the key type for the primary key
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'YEAR',
        'USD2IDR',
        'JPY2IDR',
        'EUR2IDR',
        'SGD2IDR'
    ];
}
