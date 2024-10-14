<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasLine extends Model
{
    // Specify the database table
    protected $table = 'mas_line';

    // Composite primary key (shopcode and linecode)
    protected $primaryKey = ['shopcode', 'linecode'];

    // Disable auto-incrementing as the primary key is not an integer
    public $incrementing = false;

    // Specify the key type for the primary keys
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'shopcode',
        'linecode',
        'linename',
        'unitprice',
        'tacttime',
        'staffnum'
    ];
}

