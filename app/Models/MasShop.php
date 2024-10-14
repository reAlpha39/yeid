<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasShop extends Model
{
    // Define the table name
    protected $table = 'mas_shop';

    // Define the primary key
    protected $primaryKey = 'shopcode';

    // If shopcode is not auto-incrementing
    public $incrementing = false;

    // Define the key type since shopcode is CHAR(4)
    protected $keyType = 'string';

    // Disable timestamps if not present in the table
    public $timestamps = false;

    // Specify the fillable fields
    protected $fillable = [
        'shopcode',
        'shopname',
        'planttype',
        'countflag'
    ];
}
