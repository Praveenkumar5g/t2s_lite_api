<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 04-04-2023
 * Time: 06:00
 * Send pushnotification to users
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
use App\Models\AppLog;
use App\Models\Onesignalkeys;

class APIPushNotificationController extends Controller
{
    
    public static function SendNotification($chat_message,$player_ids,$notification_id='',$type='')
    {
    	if(!empty($player_ids))
    	{
	    	$devices=array_chunk($player_ids,500);
	    	$keys = Onesignalkeys::get()->first();
	    	foreach ($devices as $key => $value) {
	    		$fields = array(
		            'app_id' => $keys->app_id,
		            'include_player_ids' =>$value,
		            'isAndroid' => true,		       
		            // 'headings' => array("en"=>'Checking...'),
		            'contents'=>array("en"=>$chat_message),
		            'large_icon' => '',
		            'big_picture' => '',
		        ); 

	    		$fields = json_encode($fields);

		        $ch = curl_init();
		        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Basic '.$keys->api_key));
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		        curl_setopt($ch, CURLOPT_HEADER, FALSE);
		        curl_setopt($ch, CURLOPT_POST, TRUE);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		        $response = curl_exec($ch);
		        $json = json_decode($response,true);
		        
		        $message_delivery_details[] = ([
		        	'player_id' =>implode(',',$value),
		        	'message'=> $chat_message,
		        	'message_id'=>$notification_id,
		        	'type'=>$type,
		        	'message_status'=>(!empty($json))?'success':'error'
		        ]);
	    	}
	    	AppLog::insert($message_delivery_details);
	    	return $json;
	        
	    }
    }
}
