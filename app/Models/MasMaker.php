<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasMaker extends Model
{
  // Specify the database table
  protected $table = 'mas_maker';

  // Primary key field
  protected $primaryKey = 'makercode';

  // Disable auto-incrementing as makercode is not an integer
  public $incrementing = false;

  // Specify the key type
  protected $keyType = 'string';

  // Disable timestamps if the table does not have created_at and updated_at fields
  public $timestamps = false;

  // Define fillable properties
  protected $fillable = [
    'makercode',
    'makername',
    'remark'
  ];
}

