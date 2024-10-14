<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasUser extends Model
{
    use SoftDeletes;

    // Specify the table name
    protected $table = 'mas_user';

    // Specify the primary key
    protected $primaryKey = 'id';

    // Define fillable attributes
    protected $fillable = [
        'name',
        'email',
        'phone',
        'department_id',
        'role_access',
        'status',
        'control_access',
    ];

    // Enable timestamps management (created_at, updated_at)
    public $timestamps = true;

    // Set default date format for the model
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // Define the relationship with the MasDepartment model
    public function department()
    {
        return $this->belongsTo(MasDepartment::class, 'department_id');
    }
}
