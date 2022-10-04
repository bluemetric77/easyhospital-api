<?php

namespace App\Http\Controllers\Config;

use App\Models\Config\Parameters;
use App\Models\Config\Sites;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function profiles(){
        $company = Parameters::select('keyword','value_string')
         ->whereRaw("LEFT(keyword,7) = 'COMPANY'")
         ->get();
         foreach ($company as $row) {
            $profile[strtolower($row->keyword)]=$row->value_string;
         }
        return response()->success('Success',$profile);
    }

   public function getSites(Request $request){
        $all=isset($request->all) ? $request->all : '0';
        $data=Sites::selectRaw("sysid,site_name")
             ->where('is_active','1')->get();
        if ($all=='1') {
            $allcode=array();
            $allcode['sysid']=-1;
            $allcode['site_name']='SEMUA';
            $data[]=$allcode ;
        }
        return response()->success('Success',$data);
    }
}
