<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 04-04-2023
 * Time: 12:00
 * Validate and generate token in login controller
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use JWTFactory;
use JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use App\Models\Smsvendordetails;

class APISmsController extends Controller
{
    
    public static function SendSMS($mobile_no_list,$message,$dlt_template_id)
    {
    	if(is_array($mobile_no_list))
    		$to_no = implode(',',$mobile_no_list);
    	else
    		$to_no =$mobile_no_list;
    	$vendor_details =Smsvendordetails::get()->first();
    	if(!empty($vendor_details) && !empty($to_no))
    	{
			$encurl = "http://alerts.solutionsinfini.com/api/v3/index.php?method=sms&api_key=".$vendor_details->sms_pass."&to=".$to_no."&sender=".$vendor_details->sender_name."&message=".urlencode($message)."&format=json&custom=1,2&flash=0&unicode=auto&template_id=".$dlt_template_id;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $encurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec($ch);
			curl_close($ch);
			return (['status'=>true]);
			
		}
		else
			return (['status'=>false,'message'=>'Please configure vendor details!...']);
    }
}
