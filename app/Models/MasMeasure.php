<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasMeasure extends Model
{
    // Specify the database table
    protected $table = 'mas_measure';

    // Specify the primary key
    protected $primaryKey = 'measurecode';

    // Disable auto-incrementing as the primary key is not an integer
    public $incrementing = false;

    // Specify the key type for the primary key
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'measurecode',
        'measurename',
        'remark'
    ];
}

