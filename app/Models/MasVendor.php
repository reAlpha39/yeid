<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasVendor extends Model
{
    protected $table = 'HOZENADMIN.MAS_VENDOR'; // Define the correct schema and table
    protected $primaryKey = 'VENDORCODE'; // Define the primary key
    public $incrementing = false; // VENDORCODE is not auto-incremented
    public $timestamps = false; // Disable timestamps if the table doesn't have 'created_at' or 'updated_at'

    protected $fillable = [
        'VENDORCODE',
        'VENDORNAME',
    ];

    protected $keyType = 'string'; // Since VENDORCODE is a string
}
