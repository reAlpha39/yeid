<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasMachine extends Model
{
    // Specify the database table
    protected $table = 'mas_machine';

    // Primary key field
    protected $primaryKey = 'machineno';

    // Disable auto-incrementing as machineno is not an integer
    public $incrementing = false;

    // Specify the key type
    protected $keyType = 'string';

    // Disable timestamps if the table does not have created_at and updated_at fields
    public $timestamps = false;

    // Define fillable properties
    protected $fillable = [
        'machineno',
        'machinename',
        'plantcode',
        'shopcode',
        'shopname',
        'linecode',
        'modelname',
        'makercode',
        'makername',
        'serialno',
        'machineprice',
        'currency',
        'purchaseroot',
        'installdate',
        'note',
        'status',
        'rank',
        'updatetime'
    ];
}
