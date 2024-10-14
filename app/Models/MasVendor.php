<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasVendor extends Model
{
    // Define the correct schema and table
    protected $table = 'mas_vendor';

    // Define the primary key
    protected $primaryKey = 'vendorcode';

    // vendorcode is not auto-incremented
    public $incrementing = false;

    // Disable timestamps if the table doesn't have 'created_at' or 'updated_at'
    public $timestamps = false;

    protected $fillable = [
        'vendorcode',
        'vendorname',
    ];

    // Since vendorcode is a string
    protected $keyType = 'string';
}
