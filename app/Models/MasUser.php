<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MasUser extends Authenticatable
{
    use SoftDeletes, HasApiTokens, HasFactory, Notifiable;

    // Specify the table name
    protected $table = 'mas_user';

    // Specify the primary key
    protected $primaryKey = 'id';

    // Define fillable attributes
    protected $fillable = [
        'name',
        'email',
        'phone',
        'nik',
        'department_id',
        'role_access',  
        'status',
        'control_access',
        'password',
        'remember_token',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Enable timestamps management (created_at, updated_at)
    public $timestamps = true;

    // Set default date format for the model
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'];

    // Define the relationship with the MasDepartment model
    public function department()
    {
        return $this->belongsTo(MasDepartment::class, 'department_id');
    }
}
