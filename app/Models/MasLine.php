<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasLine extends Model
{
    // Specify the database table
    protected $table = 'HOZENADMIN.MAS_LINE';

    // Composite primary key (SHOPCODE and LINECODE)
    protected $primaryKey = ['SHOPCODE', 'LINECODE'];

    // Disable auto-incrementing as the primary key is not an integer
    public $incrementing = false;

    // Specify the key type for the primary keys
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'SHOPCODE',
        'LINECODE',
        'LINENAME',
        'UNITPRICE',
        'TACTTIME',
        'STAFFNUM'
    ];
}
