<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasShop extends Model
{
    // Define the table name
    protected $table = 'HOZENADMIN.MAS_SHOP';

    // Set the primary key field
    protected $primaryKey = 'SHOPCODE';

    // Disable auto-incrementing since SHOPCODE is not an integer
    public $incrementing = false;

    // Set the key type to string since SHOPCODE is a CHAR field
    protected $keyType = 'string';

    // Disable timestamps (created_at, updated_at)
    public $timestamps = false;

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'SHOPCODE',
        'SHOPNAME',
        'PLANTTYPE',
        'COUNTFLAG'
    ];
}
