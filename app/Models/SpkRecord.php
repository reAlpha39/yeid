<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpkRecord extends Model
{
    protected $table = 'tbl_spkrecord';
    protected $primaryKey = 'recordid';
    public $timestamps = true;

    protected $fillable = [
        'yokotenkai',
        'waitsum',
        'updatetime',
        'totalrepairsum',
        'status',
        'startdatetime',
        'staffnum',
        'situationcode',
        'situation',
        'restoreddatetime',
        'repairsum',
        'recordid',
        'questionsum',
        'preventioncode',
        'prevention',
        'preparesum',
        'planid',
        'periodicalsum',
        'partcostsum',
        'ordertitle',
        'orderstoptime',
        'ordershop',
        'orderqtty',
        'orderjobtype',
        'orderfinishdate',
        'orderempname',
        'orderempcode',
        'orderdatetime',
        'occurdate',
        'measurecode',
        'measure',
        'makerservice',
        'makerparts',
        'makername',
        'makerhour',
        'makercode',
        'maintenancecode',
        'machinestoptime',
        'machineno',
        'machinename',
        'ltfactorcode',
        'ltfactor',
        'linestoptime',
        'inactivesum',
        'factorcode',
        'factor',
        'enddatetime',
        'createempname',
        'createempcode',
        'confirmsum',
        'comments',
        'checksum',
        'approval',
        'analysisterm',
        'analysisquarter',
        'analysishalf',
    ];

    public function approval(): HasOne
    {
        return $this->hasOne(SpkRecordApproval::class, 'record_id', 'recordid');
    }
}
