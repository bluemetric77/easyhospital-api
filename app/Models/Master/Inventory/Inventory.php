<?php

namespace App\Models\Master\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'm_items';
    protected $primaryKey = 'sysid';
    public $timestamps = true;
    const CREATED_AT = 'create_date';
    const UPDATED_AT = 'update_date';
    protected $guarded =[];
    protected $casts = [
        'is_price_rounded'=>'string',
        'is_expired_control'=>'string',
        'is_sales'=>'string',
        'is_purchase'=>'string',
        'is_production'=>'string',
        'is_material'=>'string',
        'is_cosignment'=>'string',
        'is_formularium'=>'string',
        'is_employee'=>'string',
        'is_inhealth'=>'string',
        'is_bpjs'=>'string',
        'is_national'=>'string',
        'cogs'=>'float',
        'hna'=>'float',
        'het_price'=>'float',
        'on_hand'=>'float',
        'on_hand_unit'=>'float',
        'is_active'=>'string',
        'conversion'=>'float'
    ];
}
