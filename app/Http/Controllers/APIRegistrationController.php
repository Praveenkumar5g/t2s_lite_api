<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 05:15
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use JWTFactory;
use JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolUsers;
use App\Models\SchoolProfile;
use Illuminate\Support\Facades\Hash;
use DB;
use App\Models\SchoolDatabase;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Illuminate\Support\Facades\Config;
use App\Models\SchoolAcademicYears;
use App\Models\Configurations;

class APIRegistrationController extends Controller
{
    public function register(Request $request)
    {
        // // Validate the input details
        // $request->validate([
        //     'school_name'=>'required',
        //     'name' => 'required',
        //     'email' => 'required|email',
        //     'user_mobile_number' => 'required',
        //     'password' => 'required',
        //     'designation'=>'required',
        //     'academic_year'=>'required',
        // ]);
        // // Check mobile no / email id already exist
        $mobile_exist = SchoolUsers::where('user_mobile_number',$request->user_mobile_number);
        if($request->email!='')
            $mobile_exist = $mobile_exist->orWhere('user_email_id',$request->email);
        $mobile_exist = $mobile_exist->get()->toArray();
        if(count($mobile_exist)>0)
            return response()->json(['error' => 'Mobile Number/ Email already exist']);

        // Get all the input details and stored in variable 'data'
        $data = $request->all();

        if(!isset($request->school_code))
        {
            $words = explode(" ", $request->school_name);
            $acronym = "";

            foreach ($words as $w) {
              $acronym .= mb_substr($w, 0, 1);
            }
            $data['school_code'] = strtoupper(mb_substr($acronym,0,4));
            if(strlen($data['school_code'])<4)
            {
                $count = 4-strlen($data['school_code']);
                $data['school_code'] = $data['school_code'].''.mb_substr('SCHOOL',0,$count);
            }
            $check_exists = SchoolProfile::where('school_code',$data['school_code'])->first();
            if(!empty($check_exists))
            {
                $str_result = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

                $data['school_code'] = substr(str_shuffle($str_result),0, 4);
            }
        }
        // Insert school related data in "school_profile" table(config DB)
        $schoolprofile = new SchoolProfile;
        $schoolprofile->school_code=$data['school_code'];
        $schoolprofile->school_name=$data['school_name'];
        $schoolprofile->active_academic_year=$data['academic_year'];
        $schoolprofile->contact_person_name = $data['name'];
        $schoolprofile->contact_person_designation = $data['designation'];
        $schoolprofile->contact_mobile_number = $data['user_mobile_number'];
        $schoolprofile->contact_email_id = $data['email'];
        $schoolprofile->save();
        $school_profile_id = $schoolprofile->id;

        // Create entry for academic year along with school profile id
        $schoolacademicyears=new SchoolAcademicYears;
        $schoolacademicyears->school_profile_id=$school_profile_id;
        $schoolacademicyears->academic_year=$data['academic_year'];
        $schoolacademicyears->save();

        // Create entry in config DB
        $schoolusers=new SchoolUsers;
        $schoolusers->school_profile_id=$school_profile_id;
        $userid = $data['school_code'].substr($data['academic_year'], -2).'a0001';
        $schoolusers->user_id=$userid;
        $schoolusers->user_mobile_number=$data['user_mobile_number'];
        $schoolusers->user_email_id=$data['email'];
        $schoolusers->user_password=bcrypt($data['password']);
        $schoolusers->user_role=Config::get('app.Admin_role');
        $schoolusers->user_status=1;
        $schoolusers->save();

        // create entry empty configuration for new school; 
        $configurations = new Configurations;
        $configurations->school_profile_id=$school_profile_id;
        $configurations->save();


        // Generate token and return token
        $credentials = $request->only('user_mobile_number', 'password');
        try {
            if (! $token = Auth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid Credentails']);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Unable to generate token']);
        }

        return response()->json(compact('token','configurations'));
    }

    // Create profile for schools
    public static function create_profile()
    {
        // Get authorizated user details
        $data = auth()->user();

        //fetch school profile details
        $school_profile = SchoolProfile::where(['id'=>$data['school_profile_id']])->get()->first();

        $check_school_db = SchoolDatabase::where(['school_id'=>$data['school_profile_id']])->get()->first();
        if(!empty($school_profile)>0 && empty($check_school_db))
        {
            // Create DB for school
            $db_name = 'lite_t2s_'.$school_profile['school_code'].'_'.$school_profile['active_academic_year'];
            $check_school_db_exists = SchoolDatabase::where(['school_db_name'=>$db_name])->get()->first();
            if(!empty($check_school_db_exists))
            {
                $seed = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ'); // and any other characters
                shuffle($seed); // probably optional since array_is randomized; this may be redundant
                $rand = '';
                foreach (array_rand($seed, 4) as $k) $rand .= $seed[$k];
                $db_name = 'lite_t2s_'.$rand.'_'.$school_profile['active_academic_year'];
                SchoolProfile::where(['id'=>$data['school_profile_id']])->update(['school_code',$rand]);
                $check_school_db_exists = SchoolDatabase::where(['school_db_name'=>$db_name])->get()->first();
            }
            $query="CREATE DATABASE IF NOT EXISTS ".$db_name;
            $output=DB::select($query);

            // $command = "sudo -u rxj0001 -S /usr/bin/mysqladmin --login-path=Lite_Admin -p create ".$db_name;
            // $output = exec($command);
            $config_model=new SchoolDatabase;
            $config_model->school_id=$data['school_profile_id'];
            $config_model->school_db_host= 'localhost';//'t2slive-rds.c2j0o56fpven.ap-south-1.rds.amazonaws.com';

            // Copy empty tables from empty DB to newly created DB
            if($_SERVER['HTTP_HOST'] == 'localhost')// For local
            {
                $config_model->school_db_user='root';
                $config_model->school_db_pass='';
                $command = 'C:\xampp\mysql\bin\mysqldump.exe -h localhost -u root lite_t2s_uat_empty | C:\xampp\mysql\bin\mysql.exe -h localhost -u root '.$db_name;
            }
            else// For live
            {

                $config_model->school_db_user='t2sliteeditor';
                $config_model->school_db_pass='T2S#2023@editxxLMADES##';
                $command = 'mysqldump -h localhost -P 3306 --skip-triggers --set-gtid-purged=OFF --no-tablespaces -u t2sliteeditor -pT2S#2023@editxxLMADES## lite_t2s_empty | mysql -h localhost -P 3306 -u t2sliteeditor -pT2S#2023@editxxLMADES## '.$db_name;
                // $command = 'mysqldump -h t2slive-rds.c2j0o56fpven.ap-south-1.rds.amazonaws.com -P 3306 -u t2sliteeditor -pT2S#2023@editxxLMADES## lite_t2s_empty | mysql -h t2slive-rds.c2j0o56fpven.ap-south-1.rds.amazonaws.com -P 3306 -u t2sliteeditor -pT2S#2023@editxxLMADES## '.$db_name;
            }

            $config_model->school_db_name=$db_name;
            $config_model->academic_year=$school_profile['active_academic_year'];
            $config_model->save();
            
            $output = exec($command);

            // Fetch school DB connection details
            $config_db_details = SchoolDatabase::where('id',$data['school_profile_id'])->first();
            // print_r($school_profile);exit();
            //Connect School DB
            APIRegistrationController::dbConnection($data['school_profile_id'],$school_profile['active_academic_year']);

            // Create admin entry for that corresponding school
            $useradmin=new UserAdmin;
            $useradmin->first_name=$school_profile['contact_person_name'];
            $useradmin->user_id = $data['user_id'];
            $useradmin->mobile_number=$data['user_mobile_number'];
            $useradmin->save();
            $userid = $useradmin->id;

            // Create admin entry for that corresponding school
            $userall=new UserAll;
            $userall->user_table_id=$useradmin->id;
            $userall->user_role=1;
            $userall->save();

            return response()->json(['message'=>'Profile Created Successfully!...']);
        }
        else if(!empty($check_school_db))
            return response()->json(['status'=>true,'error' => 'Profile already created.']);

        return response()->json(['message'=>'No school data']);
    }

    // School DB connection
    public static function dbConnection($school_id,$academic_year)
    {
        $config_school = SchoolDatabase::where('school_id', $school_id)->where('academic_year',$academic_year)->get()->first();
        Config::set('database.connections.school_db.host',$config_school->school_db_host);
        Config::set('database.connections.school_db.username',$config_school->school_db_user);
        Config::set('database.connections.school_db.password',$config_school->school_db_pass);
        Config::set('database.connections.school_db.database',$config_school->school_db_name);
        DB::reconnect('school_db');
    }
}
