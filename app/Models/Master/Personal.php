<?php

namespace App\Models\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'm_personal';
    protected $primaryKey = 'personal_id';
    public $timestamps = false;
    const CREATED_AT = 'update_timestamp';
    const UPDATED_AT = 'update_timestamp';
    protected $guarded=[];
    protected $casts=[
       'is_active'=>'string'
    ];
}
