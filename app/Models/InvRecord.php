<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvRecord extends Model
{
    protected $table = 'tbl_invrecord';
    protected $primaryKey = 'recordid';
    public $timestamps = false;

    protected $fillable = [
        'recordid',
        'locationid',
        'jobcode',
        'jobdate',
        'jobtime',
        'partcode',
        'partname',
        'specification',
        'brand',
        'usedflag',
        'quantity',
        'unitprice',
        'currency',
        'total',
        'vendorcode',
        'note',
        'employeecode',
        'machineno',
        'machinename',
        'updatetime'
    ];
}

