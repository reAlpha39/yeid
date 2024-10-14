<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasSituation extends Model
{
    // Specify the database table
    protected $table = 'mas_situation';

    // Primary key field
    protected $primaryKey = 'situationcode';

    // Disable auto-incrementing as situationcode is not an integer
    public $incrementing = false;

    // Specify the key type
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'situationcode',
        'situationname',
        'remark'
    ];
}

