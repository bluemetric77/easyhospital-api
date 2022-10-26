<?php

namespace App\Http\Controllers\Master\Inventory;

use App\Models\Master\Inventory\Inventory;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PagesHelp;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->filter;
        $limit = isset($request->limit) ? $request->limit : 100;
        $descending = $request->descending == "true";
        $sortBy = $request->sortBy;
        $group_name=isset($request->group_name) ? $request->group_name : 'MEDICAL';
        $is_active=isset($request->is_active) ? $request->is_active : false;
        if ($is_active==true) {
            $data=Inventory::selectRaw("sysid,item_code,item_code_old,item_name1,trademark")
            ->where('inventory_group',$group_name)
            ->where('is_active',true);
        } else {
            $data=Inventory::from('m_items as a')
            ->selectRaw("a.sysid,a.item_code,a.item_code_old,a.item_name1,a.mou_inventory,a.trademark,
                a.is_sales,a.is_purchase,a.is_production,a.is_material,
                a.is_active,a.update_userid,a.create_date,a.update_date,
                b.manufactur_name as  manufactur,c.supplier_name")
            ->leftjoin("m_manufactur as b","a.manufactur_sysid","=","b.sysid")
            ->leftjoin("m_supplier as c","a.prefered_vendor_sysid","=","c.sysid")
            ->where('inventory_group',$group_name);
        }
        if (!($filter == '')) {
            $filter = '%' . trim($filter) . '%';
            $data = $data->where(function ($q) use ($filter) {
                $q->where('item_code', 'ilike', $filter);
                $q->orwhere('item_code_old', 'ilike', $filter);
                $q->orwhere('item_name1', 'ilike', $filter);
                $q->orwhere('trademark', 'ilike', $filter);
            });
        }
        $data = $data->orderBy($sortBy, ($descending) ? 'desc':'asc')->paginate($limit);
        return response()->success('Success', $data);
    }

    public function destroy(Request $request){
        $sysid=isset($request->sysid) ? $request->sysid :'-1';
        $data=Inventory::find($sysid);
        if ($data) {
            DB::beginTransaction();
            try{
                PagesHelp::write_log($request,$data->sysid,$data->sysid,'Deleting recods '.$data->item_code.'-'.$data->item_name1);
                $data->delete();
                DB::commit();
                return response()->success('Success','Hapus data berhasil');
            } 
            catch(\Exception $e) {
                DB::rollback();
                return response()->error('',501,$e);
            }
        } else {
            return response()->error('',501,'Data tidak ditemukan');
        }
    }

    public function edit(Request $request){
        $sysid=isset($request->sysid) ? $request->sysid :'-1';
        $group_name=isset($request->group_name) ? $request->group_name : 'MEDICAL';
        if ($group_name=='MEDICAL'){
            $data=Inventory::from('m_items as a')
            ->selectRaw("a.sysid,a.item_code,a.item_code_old,a.item_name1,a.item_name2,a.mou_inventory,a.product_line,
            a.is_price_rounded,a.price_rounded,a.is_expired_control,a.is_sales,a.is_purchase,a.is_production,a.is_material,
            a.is_consignment,a.is_formularium,a.is_employee,a.is_inhealth,a.is_bpjs,a.is_employee,a.is_national,a.item_group_sysid,
            a.trademark,a.manufactur_sysid,a.prefered_vendor_sysid,a.is_active,a.update_userid,a.create_date,a.update_date,
            b.manufactur_name as manufactur,a.inventory_group,a.is_generic")
            ->leftjoin("m_manufactur as b","a.manufactur_sysid","=","b.sysid")
            ->where('a.sysid',$sysid)->first();
        }
        return response()->success('Success',$data);
    }

    public function store(Request $request){
        $info = $request->json()->all();
        $row = $info['data'];
        $opr = $info['operation'];
        $validator=Validator::make($row,
        [
            'item_code'=>'bail|required',
            'item_name1'=>'bail|required',
            'mou_inventory'=>'bail|required',
        ],[
            'item_code.required'=>'Kode inventory diisi',
            'item_name1.required'=>'Nama inventory diisi',
            'mou_inventory.required'=>'Satuan simpan diisi',
        ]);
        if ($validator->fails()) {
            return response()->error('',501,$validator->errors()->first());
        }
        DB::beginTransaction();
        try {
            if ($opr=='inserted'){
                $data = new Inventory();
                $data->inventory_group=$row['inventory_group'];
            } else if ($opr=='updated'){
                $data = Inventory::find($row['sysid']);
            }
            $data->item_code=$row['item_code'];
            $data->item_name1=$row['item_name1'];
            $data->item_name2=$row['item_name2'];
            $data->mou_inventory=$row['mou_inventory'];
            $data->is_price_rounded=isset($row['is_price_rounded']) ? $row['is_price_rounded'] :false;
            $data->price_rounded=isset($row['price_rounded']) ? $row['price_rounded'] :0;
            $data->manufactur_sysid=isset($row['manufactur_sysid']) ? $row['manufactur_sysid'] :-1;
            $data->prefered_vendor_sysid=isset($row['prefered_vendor_sysid']) ? $row['prefered_vendor_sysid'] :-1;
            $data->is_price_rounded=$row['is_price_rounded'];
            $data->price_rounded=$row['price_rounded'];
            $data->is_sales=$row['is_sales'];
            $data->is_purchase=$row['is_purchase'];
            $data->is_material=$row['is_material'];
            $data->is_production=$row['is_production'];
            if ($row['inventory_group']=='MEDICAL'){
                $data->is_consignment=$row['is_consignment'];
                $data->is_formularium=$row['is_formularium'];
                $data->is_inhealth=$row['is_inhealth'];
                $data->is_bpjs=$row['is_bpjs'];
                $data->is_national=$row['is_national'];
                $data->is_employee=$row['is_employee'];
                $data->is_expired_control=$row['is_expired_control'];
                $data->is_generic=$row['is_generic'];
            }
            $data->is_active=$row['is_active'];
            $data->update_userid=PagesHelp::UserID($request);
            $data->save();
            PagesHelp::write_log($request,$data->sysid,$data->dept_code,'Add/Update recods');
            DB::commit();
            return response()->success('Success','Simpan data berhasil');
        } catch(Exception $error) {
            DB::rollback();    
        }    
    }
}
