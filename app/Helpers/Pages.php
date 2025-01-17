<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use App\Models\Config\Parameters;
use App\Models\Config\USessions;
use App\Models\Config\Objects;
use App\Models\Config\SeriesNumber;
use App\Models\Config\SeriesDocument;
use App\Models\Config\SeriesJurnal;
use App\Models\Setup\UserLogs;
use Illuminate\Support\Facades\Route;

class Pages
{
   public static function PageEnviroment($url)
   {
      $data = Objects::selectRaw("sysid,column_def,title as title_page,api_link,security")
         ->where('url_link', $url)
         ->first();
      return $data;
   }

    public static function Session() {
        $request = request();
        $token   = $request->header('x_jwt');
        $session = USessions::from('o_sessions as a')
        ->selectRaw("a.sign_code,a.user_sysid as sysid,a.user_name,a.ip_number,a.expired_date,a.is_locked,
        b.sysid,b.full_name,b.user_level,b.is_active,b.is_group,b.email,now() as curr_time")
        ->join('o_users as b','a.user_sysid','=','b.sysid')
        ->where('sign_code',isset($token) ? $token :'')
        ->first();

        return isset($session) ? $session : null;
    }

    public static function SeriesField($table){
        $TabelInfo = DB::table('pg_class')
        ->selectRaw("oid,relname")
        ->where('relname',isset($table) ? $table : '')
        ->first();

        if (!($TabelInfo)) {
            return 'Table name '.$table.' not found';
        }

        $SerialNumber = SeriesNumber::where('table_id',$TabelInfo->oid)->first();
        if (!($SerialNumber)) {
            $SerialNumber = new SeriesNumber();
            $SerialNumber->table_id   = $TabelInfo->oid;
            $SerialNumber->table_name = $TabelInfo->relname;
            $SerialNumber->start_number   = 1;
            $SerialNumber->increment      = 1;
            $SerialNumber->current_number = 0;
        }

        $SerialNumber->current_number++;
        $SerialNumber->save();

        return $SerialNumber->current_number;
    }

    public static function Profile(){
      $profile = DB::table('m_profile')
      ->selectRaw('name,address,url,photo,city,phone,folder_api')
      ->where('sysid',1)
      ->first();

      return $profile;
   }

   public static function DocumentSeries($prefix,$docDate){
		$realdate     = date_create($docDate);
		$year_period  = date_format($realdate, 'Y');
        $month_period = date_format($realdate, 'm');
        $data= SeriesDocument::select('prefix_code','year_period','month_period','numbering')
        ->where('prefix_code',$prefix)
        ->where('year_period',$year_period)
        ->where('month_period',$month_period)
        ->first();
        if (!($data)) {
            SeriesDocument::insert(
            ['prefix_code'=>$prefix,
            'year_period'=>$year_period,
            'month_period'=>$month_period,
            'numbering'=>0]);

            $data->refresh();
        }

        $counter=$data->numbering +1;
        SeriesDocument::where('prefix_code',$prefix)
        ->where('year_period',$year_period)
        ->where('month_period',$month_period)
        ->update(['numbering'=>$counter]);

        $year = substr($year_period, 2, 2);
        $series = $prefix . '-' . $year . $month_period . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

        return $series;
    }

    public static function GLSeries($prefix,$docDate){
		$realdate = date_create($docDate);
		$year_period = date_format($realdate, 'Y');
        $month_period = date_format($realdate, 'm');
        $data= SeriesJournal::select('series_code','fiscal_year','fiscal_month','counter')
        ->where('series_code',$prefix)
        ->where('fiscal_year',$year_period)
        ->where('fiscal_month',$month_period)
        ->first();

        if (!($data)) {
            SeriesJournal::insert(
            ['series_code'=>$prefix,
             'fiscal_year'=>$year_period,
             'fiscal_month'=>$month_period,
             'counter'=>1]);
        }
        $data->refresh();
        SeriesJournal::where('series_code',$prefix)
        ->where('fiscal_year',$year_period)
        ->where('fiscal_month',$month_period)
        ->update(['counter'=>$counter]);

        $year = substr($year_period, 2, 2);
        $series = $year . $month_period . '-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT);
        return $series;
    }

   public static function GetVariable($code,$type='C'){
        $value='';
        $data=Parameters::where('key_word',$code)
        ->first();
        if (!($data)){
            Parameters::insert([
            'key_word'=>$code,
            'key_type'=>$type,
            'key_length'=>1000
             ]);
        }
        $data->refresh();

        if ($type=='C') {
            $value = $data->key_value_nvarchar;
        } else if ($type=='I'){
            $value = $data->key_value_integer;
        }else if ($type=='N'){
            $value = (float)$data->key_value_integer;
        }else if ($type=='D'){
            $value =date_create($data->key_value_date);
        }else if ($type=='B'){
            $value =(bool)$data->key_value_boolean;
        }

        return $value;
   }

   public static function WriteVariable($code,$type,$value=''){
        $data=Parameters::where('key_word',$code)
        ->first();
        if (!($data)){
            Parameters::insert([
            'key_word'=>$code,
            'key_type'=>$type,
            'key_length'=>1000
             ]);
        }
        $rec= array();
        if ($type=='C'){
            $rec=array('key_value_nvarchar'=>$value);
        } else if ($type=='I'){
            $rec=array('key_value_integer'=>$value);
        } else if ($type=='N'){
            $rec=array('key_value_decimal'=>$value);
        } else if ($type=='D'){
            $rec=array('key_value_date'=>$value);
        } else if ($type=='B'){
            $rec=array('key_value_boolean'=>$value);
        }
        Parameters::where('key_word',$code)
        ->update($rec);
   }

    public static function ServerUrl()
    {
        $profile=Parameters::selectRaw("key_value_nvarchar")
        ->where('key_word','CONFIG_API')->first();
        $folder="";
        if ($profile){
            $folder=$profile->key_value_nvarchar;
            if (!($folder=="")){
                $folder="/".$folder;
            }
        }
        $server_name = $_SERVER['SERVER_NAME'];

        $port = (!in_array($_SERVER['SERVER_PORT'], [80, 443])) ? ":".$_SERVER['SERVER_PORT'] : "";

        $scheme = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) ?'https' :'http';

        return $scheme.'://'.$server_name.$port.$folder;
   }

   public static function LocalMonth($MonthIndex)
   {
      $months=['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

      return $months[$MonthIndex-1];
   }

   public static function Terbilang( $num ,$dec=4){
    $stext = array(
        "Nol",
        "Satu",
        "Dua",
        "Tiga",
        "Empat",
        "Lima",
        "Enam",
        "Tujuh",
        "Delapan",
        "Sembilan",
        "Sepuluh",
        "Sebelas"
    );
    $say  = array(
        "Ribu",
        "Juta",
        "Milyar",
        "Triliun",
        "Biliun", // remember limitation of float
        "--apaan---" ///setelah biliun namanya apa?
    );
    $w = "";

    if ($num <0 ) {
        $w  = "Minus ";
        //make positive
        $num *= -1;
    }

    $snum = number_format($num,$dec,",",".");
    $strnum =  explode(".",substr($snum,0,strrpos($snum,",")));
    //parse decimalnya
    $koma = substr($snum,strrpos($snum,",")+1);

    $isone = substr($num,0,1)  ==1;
    if (count($strnum)==1) {
        $num = $strnum[0];
        switch (strlen($num)) {
            case 1:
            case 2:
                if (!isset($stext[$strnum[0]])){
                    if($num<19){
                        $w .=$stext[substr($num,1)]." Belas";
                    }else{
                        $w .= $stext[substr($num,0,1)]." Puluh ".
                            (intval(substr($num,1))==0 ? "" : $stext[substr($num,1)]);
                    }
                }else{
                    $w .= $stext[$strnum[0]];
                }
                break;
            case 3:
                $w .=  ($isone ? "Seratus" : Pages::terbilang(substr($num,0,1)) .
                    " Ratus").
                    " ".(intval(substr($num,1))==0 ? "" : Pages::terbilang(substr($num,1)));
                break;
            case 4:
                $w .=  ($isone ? "Seribu" : Pages::terbilang(substr($num,0,1)) .
                    " Ribu").
                    " ".(intval(substr($num,1))==0 ? "" : Pages::terbilang(substr($num,1)));
                break;
            default:
                break;
        }
    }else{
        $text = $say[count($strnum)-2];
        $w = ($isone && strlen($strnum[0])==1 && count($strnum) <=3? "Se".strtolower($text) : Pages::terbilang($strnum[0]).' '.$text);
        array_shift($strnum);
        $i =count($strnum)-2;
        foreach ($strnum as $k=>$v) {
            if (intval($v)) {
                $w.= ' '.Pages::Terbilang($v).' '.($i >=0 ? $say[$i] : "");
            }
            $i--;
        }
    }
    $w = trim($w);
    if ($dec = intval($koma)) {
        $w .= " Koma ". Pages::Terbilang($koma);
    }
    return trim($w);
   }

   public static function Response($response,$filename='download.xlsx')
   {
      $attachment='attachment; filename="'.$filename.'"';
      $response->setStatusCode(200);
      $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      $response->headers->set('Content-Disposition', $attachment);
      $response->headers->set('Access-Control-Allow-Credentials', true);
      $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:8080');
      $response->headers->set('Access-Control-Expose-Headers', '*');
      return $response;
   }
   public static function GenerateToken(){
        $config=DB::table('o_system')->selectRaw("key_word,key_value_nvarchar")
        ->whereRaw("LEFT(key_word,3)='GPS'")
        ->get();
        $url="";
        $username="";
        $password="";
        $token = "";
        foreach ($config as $row){
          if ($row->key_word=='GPS_USERNAME') {
              $username=$row->key_value_nvarchar;
          } else if ($row->key_word=='GPS_PASSWORD') {
              $password=$row->key_value_nvarchar;
          } else if ($row->key_word=='GPS_URL') {
              $url=$row->key_value_nvarchar;
          }
        }
        $form = array(
		    'username' => $username,
			'password' => $password
        );
        $login=$url."/api_users/login";
        $respon=Pages::curl_data($login,$form);
        if ($respon['status']==true){
           $token=$respon['json']['token'];
           DB::table('o_system')->where('key_word','GPS_TOKEN')->update(['key_value_nvarchar'=>$token]);
        }
   }
   public static function GetToken(){
      $config=DB::table('o_system')
      ->select('key_value_nvarchar')
      ->where('key_word','GPS_TOKEN')
      ->first();
      if ($config){
         return $config->key_value_nvarchar;
      } else {
         return '';
      }
   }

   public static function curl_data($url,$form,$post=true,$deleted=false,$is_json=false) {
      $info['status']=true;
      $info['message']='';
      $info['data']=null;

      $ip="192.168.43.2";
      $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
      $header[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,";
      $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
      $header[] = "Cache-Control: max-age=0";
      $header[] = "Connection: keep-alive";
      $header[] = "Keep-Alive: 300";
      $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
      $header[] = "Accept-Language: en-us,en;q=0.5";
      $header[] = "Pragma: "; // browsers = blank
      $header[] = "X_FORWARDED_FOR: " . $ip;
      $header[] = "REMOTE_ADDR: " . $ip;
      $header[] = "token: 1BCF8FA77EF645229131FD65FF2F9794";
      if ($is_json==true){
         $header[] = 'Content-Type: application/json';
      }
      $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      if ($post){
         curl_setopt($ch, CURLOPT_POST, true);
      }
      if ($deleted) {
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      }
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
      if (!($form==null)){
         curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
      }
		$output = curl_exec($ch);
		if ($output==false)	{
         $info['status']=false;
			$info['message']=curl_error($ch);
		} else {
         $output = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $output);
         $info['status']=true;
         $info['json']=json_decode($output,true);
         $info['message']=json_last_error_msg();
		}
      curl_close($ch);
      return $info;
    }

    public static function convert_minutes($minute) {
      $minute_text='';
      if ($minute>1440) {
         $day=floor($minute/1440);
         $minute_text=strval($day).' hari, ';
         $minute=$minute-($day*1440);
      }
      if ($minute>60) {
         $hour=floor($minute/60);
         $minute_text=$minute_text.strval($hour).' jam, ';
         $minute=$minute-($hour*60);
      }
      $minute_text=$minute_text.strval($minute).' mnt';
      return $minute_text;
   }

}
