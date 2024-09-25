<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasMachine extends Model
{
    // Specify the database table
    protected $table = 'HOZENADMIN.MAS_MACHINE';

    // Primary key field
    protected $primaryKey = 'MACHINENO';

    // Disable auto-incrementing as MACHINENO is not an integer
    public $incrementing = false;

    // Specify the key type
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'MACHINENO',
        'MACHINENAME',
        'PLANTCODE',
        'SHOPCODE',
        'SHOPNAME',
        'LINECODE',
        'MODELNAME',
        'MAKERCODE',
        'MAKERNAME',
        'SERIALNO',
        'MACHINEPRICE',
        'CURRENCY',
        'PURCHASEROOT',
        'INSTALLDATE',
        'NOTE',
        'STATUS',
        'RANK',
        'UPDATETIME'
    ];
}

