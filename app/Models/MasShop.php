<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasShop extends Model
{
    // Define the table name
    protected $table = 'HOZENADMIN.MAS_SHOP';

    // Define the primary key
    protected $primaryKey = 'SHOPCODE';

    // If SHOPCODE is not auto-incrementing
    public $incrementing = false;

    // Define the key type since SHOPCODE is CHAR(4)
    protected $keyType = 'string';

    // Disable timestamps if not present in the table
    public $timestamps = false;

    // Specify the fillable fields
    protected $fillable = [
        'SHOPCODE',
        'SHOPNAME',
        'PLANTTYPE',
        'COUNTFLAG'
    ];
}
