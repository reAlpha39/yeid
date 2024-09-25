<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasSituation extends Model
{
    // Specify the database table
    protected $table = 'HOZENADMIN.MAS_SITUATION';

    // Primary key field
    protected $primaryKey = 'SITUATIONCODE';

    // Disable auto-incrementing as SITUATIONCODE is not an integer
    public $incrementing = false;

    // Specify the key type
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'SITUATIONCODE',
        'SITUATIONNAME',
        'REMARK'
    ];
}

