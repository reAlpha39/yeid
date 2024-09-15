<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasMachine extends Model
{
    protected $table = 'mas_machine';
    protected $primaryKey = 'machineno';
    public $timestamps = false;

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

