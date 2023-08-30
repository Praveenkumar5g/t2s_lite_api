<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 05:15
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
use App\Models\SchoolUsers;
use Carbon\Carbon;
use App\Models\Configurations;
use Config;
use App\Models\UserParents;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\SchoolProfile;
use App\Models\SchoolDatabase;
use App\Models\UserStudents;
use App\Models\Smstemplates;
use DB;
use App\Models\UserAll;
use App\Models\Smslogs;
use App\Models\UserAdmin;
use App\Models\UserManagements;
use App\Models\UserStaffs;
use App\Models\UserGroups;

class APILoginController extends Controller
{
    public function login(Request $request)
    {
        // Add rules to the login form
        $validator = Validator::make($request->all(), [
            'user_email_id' => 'required_without_all:user_mobile_number',
            'user_mobile_number' => 'required_without_all:user_email_id',
            // 'password'=> 'required',
            'user_role'=>'required', //1-admin,2-staff,3-parent,4-student,5-management
        ]);
        // Validate login form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if(isset($request->user_email_id))
            $credentials = $request->only('user_email_id', 'password','user_role');
        else
            $credentials = $request->only('user_mobile_number', 'password','user_role');

        if($request->user_role == Config::get('app.Management_role') && $request->school_profile_id !='' && $request->password=='')
        {
            $user = SchoolUsers::where('user_role',$request->user_role)->where('user_mobile_number',$request->user_mobile_number)->where('school_profile_id',$request->school_profile_id)->where('user_status',1)->get()->first();
            try {
                // Generate token if credentails are valid else thrown error message.
                if (! $token = Auth::login($user)) 
                {
                    if($user->user_status != '' && $user->user_status != 1)
                        return response()->json(['status'=>false,'error' => 'Your is account deactived!..']);
                    else
                        return response()->json(['error' => 'Invalid credentials'], 401);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not generate token'], 500);
            }
            
        }
        else
        {
            if($request->user_role == Config::get('app.Management_role') && $request->school_profile_id !='' && $request->password!='')
                $credentials['school_profile_id'] = $request->school_profile_id;
            else if($request->user_role == Config::get('app.Parent_role') && $request->school_profile_id !='' && $request->password!='')
                $credentials['school_profile_id'] = $request->school_profile_id;
            $user_status_all = SchoolUsers::where('user_role',$request->user_role)->where('user_mobile_number',$request->user_mobile_number)->get()->toArray();
            $credentials['user_status'] = 1;
            $flag=$user_status=0;
            $token='';
            foreach ($user_status_all as $key => $value) {
                $credentials['user_id'] = $value['user_id'];
                $user_status = $value['user_status'];
                if (! $token = Auth::attempt($credentials))     
                    $flag =0;
                else
                {
                    $flag=1;
                    break;
                }
            }
            try {
                // Generate token if credentails are valid else thrown error message.
                if ($token == '' && $flag ==0) {
                    if($user_status != '' && $user_status != 1)
                        return response()->json(['status'=>false,'error' => 'Your is account deactived!..']);
                    else
                        return response()->json(['error' => 'Invalid credentials'], 401);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not generate token'], 500);
            }
        }
        
        // Save last login in DB
        $user = auth()->user();
        $user->new_user = 0;
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $loginstudent_id =0;
        $userid = '';
        if($user->user_role == Config::get('app.Parent_role'))
        {
            $school_profile = SchoolProfile::where('id',$user->school_profile_id)->first(); //get school profile details from corresponding school
            $academic_year = $school_profile->active_academic_year;
            $config_school = SchoolDatabase::where('school_id', $user->school_profile_id)->where('academic_year',$academic_year)->get()->first();
            Config::set('database.connections.school_db.host',$config_school->school_db_host);
            Config::set('database.connections.school_db.username',$config_school->school_db_user);
            Config::set('database.connections.school_db.password',$config_school->school_db_pass);
            Config::set('database.connections.school_db.database',$config_school->school_db_name);
            DB::reconnect('school_db');

            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();
            $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->first();
            $loginstudent_id = $student_id;
        }
        $userid = $user->user_id;
        // return token  
        return response()->json(compact('token','loginstudent_id','userid'));
    }

    // Change password for user
    public function change_password(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();
        // Get current password and new password
        $current_password = $request->input('current_password');
        $new_password = $request->input('new_password');
        
        // Check current password matches with our record
        if (!Hash::check($current_password, $user->user_password)) {
            return response()->json(['status'=>false,'error' => 'Invalid Currect Password']);
        }
        else{
            // Save new password in DB
            $user->user_password = bcrypt($new_password);
            $user->save();
        }
        return response()->json(['status'=>true,'message'=>'Password Updated Successfully!...']);
    }

    // Forgot password
    public function forgot_password(Request $request)
    {
        // Add rules to the forgot password
        $validator = Validator::make($request->all(), [
            'email' => 'required_without_all:mobile_no',
            'mobile_no' => 'required_without_all:email',
        ]);
        // Validate forgot password
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        // Check mobile no / email id was exist or not
        $mobile_exist = SchoolUsers::where('user_mobile_number',$request->mobile_no);
        if($request->email!='')
            $mobile_exist = $mobile_exist->orWhere('user_email_id',$request->email);
        if($request->user_role!='')
            $mobile_exist = $mobile_exist->where('user_role',$request->user_role);
        $mobile_exist = $mobile_exist->first();

        if(!empty($mobile_exist)>0)
        {
            // Redirect to Send OTP function
            $message = $this->sendOTP($mobile_exist);
            return response()->json(['status'=>true,'message'=>$message]);
        }
        else
            return response()->json(['status'=>false,'message'=>"Please enter valid mobile number"]);
    }

    // Send OTP to user
    public function sendOTP($user)
    {
        $digits = 4;
        $otp = rand(pow(10, $digits-1), pow(10, $digits)-1); //generate 4 digit otp
        $this->saveOtp($user, $otp); //save OTP in DB
        $mail_response = (($user->user_email_id != null)||($user->user_email_id != ''))?$this->sendMail($user, $otp):'Email not available'; //Send OTP to email
        $mobile_response = (($user->user_mobile_number != null)||($user->user_mobile_number!= ''))?$this->sendMessage($user, $otp):'Mobile number not available'; //Send OTP to user mobile no
        return $mobile_response;
    }

    // Save OTP in school user DB
    public function saveOtp($data, $otp)
    {
        // Update OTP and expire time in DB
        $data->login_otp = $otp;
        $data->otp_gen_time = Carbon::now()->timezone('Asia/Kolkata');
        $data->save();
    }

    // Send OTP to mail
    public function sendMail($user, $otp)
    {
        $email = $user->user_email_id;
        $data = array('email' => $email);
        return 'OTP sent to Mail ID '.$email;
    }

    // Send OTP to the mobile no
    public function sendMessage($user, $otp)
    {
        $mobile_no = $user->user_mobile_number;
        $mobile_no_list[]=$mobile_no;
        $templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("login_otp")).'%'])->where('status',1)->first();

        if(!empty($templates))
            $message = str_replace("*OTP*",$otp,$templates->message);
        else
            return (['status'=>false,'message'=>'Please configure template details!...']);

        $delivery_details = APISmsController::SendSMS($mobile_no_list,$message,$templates->dlt_template_id);
        $status = 0;
        if(!empty($delivery_details) && isset($delivery_details['status']) && $delivery_details['status'] == 1)
            $status = 1;
        $school_profile = SchoolProfile::where('id',$user->school_profile_id)->first(); //get school profile details from corresponding school
        $academic_year = $school_profile->active_academic_year;
        $config_school = SchoolDatabase::where('school_id', $user->school_profile_id)->where('academic_year',$academic_year)->get()->first();
        Config::set('database.connections.school_db.host',$config_school->school_db_host);
        Config::set('database.connections.school_db.username',$config_school->school_db_user);
        Config::set('database.connections.school_db.password',$config_school->school_db_pass);
        Config::set('database.connections.school_db.database',$config_school->school_db_name);
        DB::reconnect('school_db');

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        $smslogs[] = ([
            'sms_description'=>$message,
            'sms_count'=>1,
            'mobile_number'=>$mobile_no,
            'sent_by'=>$userall_id,
            'status'=>$status
        ]);
        if(!empty($smslogs))
                Smslogs::insert($smslogs); // store log in db.
        if(!empty($delivery_details) && $delivery_details['status'] == true)
            return 'OTP sent to Mobile number '.$mobile_no;
        else
            return $delivery_details['message'];
    }

    // Change OTP to null
    public function saveOtpAsNull($user)
    {
        $user->login_otp = null;
        $user->otp_gen_time = null;
        $user->save();
    }

    // Reset the password to user id
    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'pin' => 'required|digits_between:4,4',
            'user_email_id' => 'required_without_all:user_mobile_number',
            'user_mobile_number' => 'required_without_all:user_email_id',
            'user_role'=>'required',
        ]);    
        $current_time = strtotime(Carbon::now()->timezone('Asia/Kolkata'));
        if($request->user_email_id!='')
            $cond=(['user_email_id'=>$request->user_email_id,'user_role'=>$request->user_role]);
        else
            $cond=(['user_mobile_number'=>$request->user_mobile_number,'user_role'=>$request->user_role]);

        $user = SchoolUsers::where($cond)->first();
        $otp_exp_time = strtotime("+15 minutes",strtotime($user->otp_gen_time));

        if(strtotime($user->otp_gen_time) < $current_time && $otp_exp_time > $current_time) {
            if($user->login_otp == $request->pin) {
                if($request->user_password!='')
                {
                    $this->saveOtpAsNull($user);
                    $user->user_password = bcrypt($request->user_password);
                    $user->save();
                    if($user->user_mobile_number!='')
                        $credentials = (['user_mobile_number'=>$user->user_mobile_number,'password'=>$request->user_password,'user_role'=>$user->user_role]);
                    else
                        $credentials = (['user_email_id'=>$user->user_email_id,'password'=>$request->user_password,'user_role'=>$user->user_role]);
                    try {
                        // Generate token if credentails are valid else thrown error message.
                        if (! $token = Auth::attempt($credentials)) 
                            return response()->json(['error' => 'Invalid credentials'], 401);
                    } catch (JWTException $e) 
                    {
                        return response()->json(['error' => 'Could not generate token'], 500);
                    }
                    
                    // Save last login in DB
                    $user = auth()->user();
                    $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
                    $user->save();
                    
                    return response()->json(['status'=>true,'message'=>'Password reset success','token'=>$token]);
                }
                else
                    return response()->json(['status'=>true,'message'=>'OTP matched']);
            }
            else
                return response()->json(['status'=>false,'message'=>'Entered OTP does not matched']);
        }
        else {
            $this->saveOtpAsNull($user);
            return response()->json(['status'=>false,'message'=>'OTP is expired']);
        }
    }

    // To get Sibiling details
    public function get_siblings_details()
    {
        // Get authorizated user details
        $user = auth()->user();

        $siblingstudent_details=[];
        if($user->user_role == Config::get('app.Parent_role'))
        {
            $school_profile = SchoolProfile::where('id',$user->school_profile_id)->first(); //get school profile details from corresponding school
            $academic_year = $school_profile->active_academic_year;
            $config_school = SchoolDatabase::where('school_id', $user->school_profile_id)->where('academic_year',$academic_year)->get()->first();
            Config::set('database.connections.school_db.host',$config_school->school_db_host);
            Config::set('database.connections.school_db.username',$config_school->school_db_user);
            Config::set('database.connections.school_db.password',$config_school->school_db_pass);
            Config::set('database.connections.school_db.database',$config_school->school_db_name);
            DB::reconnect('school_db');

            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();
            $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->first();
            $loginstudent_id = $student_id;

            $siblingstudent_ids = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
            $siblingstudent_details =[];
            if(!empty($siblingstudent_ids))
            {
                foreach ($siblingstudent_ids as $key => $value) {
                    $student_list = UserStudents::select('id','first_name','gender','class_config')->where(['id'=>$value])->where('user_status',1)->orwhere('user_status',3)->get()->first();
                    if(!empty($student_list) && $student_list != null)
                        $siblingstudent_details[] = $student_list;
                }
            }
            $parent_details = ([
                'name'=>$user_table_id->first_name,
                'mobile_no'=>$user_table_id->mobile_number
            ]);
        }

        return response()->json(compact('siblingstudent_details','parent_details'));
    }

    // Reset the password to user id
    public function OTPlogin(Request $request)
    {
        $this->validate($request, [
            'pin' => 'required|digits_between:4,4',
            'user_email_id' => 'required_without_all:user_mobile_number',
            'user_mobile_number' => 'required_without_all:user_email_id',
            'user_role'=>'required', 
        ]);    
        $current_time = strtotime(Carbon::now()->timezone('Asia/Kolkata'));
        if($request->user_email_id!='')
            $cond=(['user_email_id'=>$request->user_email_id,'user_role'=>$request->user_role]);
        else
            $cond=(['user_mobile_number'=>$request->user_mobile_number,'user_role'=>$request->user_role]);

        $user = SchoolUsers::where($cond)->first();
        $otp_exp_time = strtotime("+15 minutes",strtotime($user->otp_gen_time));

        if(strtotime($user->otp_gen_time) < $current_time && $otp_exp_time > $current_time) {
            if($user->login_otp == $request->pin) {
                $this->saveOtpAsNull($user);
                // if($user->user_mobile_number!='')
                //     $credentials = (['user_mobile_number'=>$user->user_mobile_number,'password'=>$user->user_mobile_number,'user_role'=>$user->user_role]);
                // else
                //     $credentials = (['user_email_id'=>$user->user_email_id,'password'=>$user->user_mobile_number,'user_role'=>$user->user_role]);
                try {
                    // Generate token if credentails are valid else thrown error message.
                    if (! $token = Auth::login($user)) 
                        return response()->json(['error' => 'Invalid credentials'], 401);
                } catch (JWTException $e) 
                {
                    return response()->json(['error' => 'Could not generate token'], 500);
                }
                
                // Save last login in DB
                $user = auth()->user();
                $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
                $user->save();
                $loginstudent_id =0;
                $userid = '';
                if($user->user_role == Config::get('app.Parent_role'))
                {
                    $school_profile = SchoolProfile::where('id',$user->school_profile_id)->first(); //get school profile details from corresponding school
                    $academic_year = $school_profile->active_academic_year;
                    $config_school = SchoolDatabase::where('school_id', $user->school_profile_id)->where('academic_year',$academic_year)->get()->first();
                    Config::set('database.connections.school_db.host',$config_school->school_db_host);
                    Config::set('database.connections.school_db.username',$config_school->school_db_user);
                    Config::set('database.connections.school_db.password',$config_school->school_db_pass);
                    Config::set('database.connections.school_db.database',$config_school->school_db_name);
                    DB::reconnect('school_db');

                    $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();
                    $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->first();
                    $loginstudent_id = $student_id;
                }
                $userid = $user->user_id;
                // return token 
                return response()->json(compact('token','loginstudent_id','userid'));
            }
            else
                return response()->json(['status'=>false,'message'=>'Entered OTP does not matched']);
        }
        else {
            $this->saveOtpAsNull($user);
            return response()->json(['status'=>false,'message'=>'OTP Expired']);
        }
    }

    // swap profile and refresh the token
    public function swap_profile(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();
        $userdetails = SchoolUsers::where('user_id',$request->user_id)->get()->first();
        $token =  Auth::login($userdetails);
        return response()->json(compact('token'));
    }

    // get all school for single management users
    public function get_school(Request $request)
    {
        $school_names = [];

        if($request->user_role !='')
        {
            // get school profile id
            $school_list = SchoolUsers::where('user_mobile_number',$request->mobile_number)->where('user_role',$request->user_role)->where('user_status',1)->GroupBy('school_profile_id')->pluck('school_profile_id')->toArray();

            if(!empty($school_list)) //check empty or not
                $school_names = SchoolProfile::select('id','school_name')->whereIn('id',$school_list)->get()->toArray();//get all schools list

            return response()->json($school_names);//return school details
        }
        else
            return response()->json(['status'=>false,'message'=>'Role is required']);
    }

    // Change the user status to active /deactive
    public function user_status_change(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        // Connect School DB
        $school_profile = SchoolProfile::where('id',$user->school_profile_id)->first(); //get school profile details from corresponding school
        $academic_year = $school_profile->active_academic_year;
        $config_school = SchoolDatabase::where('school_id', $user->school_profile_id)->where('academic_year',$academic_year)->get()->first();
        Config::set('database.connections.school_db.host',$config_school->school_db_host);
        Config::set('database.connections.school_db.username',$config_school->school_db_user);
        Config::set('database.connections.school_db.password',$config_school->school_db_pass);
        Config::set('database.connections.school_db.database',$config_school->school_db_name);
        DB::reconnect('school_db');

        if($request->mobile_number!='')
        {
            $schoolusers = SchoolUsers::where('user_mobile_number',$request->mobile_number)->where('user_role',$request->user_role)->where('school_profile_id',$user->school_profile_id)->get()->first();
            if($request->group_id == '' && (strtolower($request->app_deactivation)=='yes' || $request->status == 1))
            {
                // update the status
                $schoolusers->user_status=$request->status;
                $schoolusers->save();
            }

            $user_table_id = $this->get_user_table_id($schoolusers);
            if($request->group_id == '' && (strtolower($request->app_deactivation)=='yes'  || $request->status == 1) && !empty($user_table_id))
            {
                
                $user_table_id->user_status=$request->status;
                $user_table_id->save();
            }
            if(!empty($user_table_id))// Update status to all the groups 
                $groups = UserGroupsMapping::where(['user_table_id'=>$user_table_id->id,'user_role'=>$request->user_role]);

            if($request->group_id != '')
                $groups = $groups->where('group_id',$request->group_id);
            $groups = $groups->update(['user_status'=>$request->status]);

        }
        else if($request->student_id !='' && $request->user_role == 4)
        {
            $parent_list = UserStudentsMapping::where('student',$request->student_id)->pluck('parent')->toArray();
            if(!empty($parent_list))
            {
                $student_class = UserStudents::where('id',$request->student_id)->pluck('class_config')->first();
                $student_group_id = UserGroups::where('class_config',$student_class)->pluck('id')->first();
                $student_list = UserStudentsMapping::where('parent',$parent_list[0])->where('student','!=',$request->student_id)->pluck('student')->toArray();
                if(strtolower($request->app_deactivation)=='no')
                    UserStudents::where('id',$request->student_id)->update(['user_status'=>3]);
                else if($request->status == 1 || strtolower($request->app_deactivation)=='yes')
                    UserStudents::where('id',$request->student_id)->update(['user_status'=>$request->status]);

                foreach($parent_list as $parent_value)
                {
                    $parent_user_id = UserParents::where('id',$parent_value)->pluck('user_id')->first();
                    $schoolusers = SchoolUsers::where('user_id',$parent_user_id)->where('user_role',Config::get('app.Parent_role'))->get()->first();
                    $user_table_id = $this->get_user_table_id($schoolusers);
                    if($request->group_id == '' && empty($student_list) && strtolower($request->app_deactivation)=='yes')
                    {
                        // update the status
                        $schoolusers->user_status=$request->status;
                        $schoolusers->save();
                        $user_table_id->user_status=$request->status;
                        $user_table_id->save();
                    }
                    else if($request->status == 1)
                    {
                        // update the status
                        $schoolusers->user_status=$request->status;
                        $schoolusers->save();
                        $user_table_id->user_status=$request->status;
                        $user_table_id->save();
                    }
                   
                    // Update status to all the groups 
                    $groups = UserGroupsMapping::where(['user_table_id'=>$user_table_id->id,'user_role'=>Config::get('app.Parent_role')]);

                    if($request->group_id != '' && $student_group_id!='')
                        $groups = $groups->where('group_id',$request->group_id)->orWhere('group_id',$student_group_id);
                    if($request->group_id == '' && $student_group_id!='')
                        $groups = $groups->where('group_id',$student_group_id);
                    $groups = $groups->update(['user_status'=>$request->status]);
                }
            } 
        }
        
        if($request->group_id == '')
            $message = ($request->status == 1)?'User account activated Successfully':'User account deactivated Successfully';
        else
            $message = ($request->status == 1)?'Group activated Successfully':'Group deactivated Successfully';

        return response()->json(['status'=>true,'message'=>$message]);
    }

    public static function get_user_table_id($user)
    {
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->get()->first();//fetch id from user all table to store notification triggered user
        return $user_table_id;
    }
}
