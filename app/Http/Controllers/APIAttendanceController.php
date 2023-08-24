<?php
/**
 * Created by PhpStorm.
 * User: Roja 
 * Date: 23-08-2023
 * Time: 12:30
 * Validate inputs and store attendance data in DB,listing ,edit
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicClassConfiguration;
use App\Models\UserStudentsMapping;
use App\Models\NotificationLogs;
use App\Models\UserManagements;
use App\Models\UserCategories;
use App\Models\UserManagement;
use App\Models\SchoolProfile;
use App\Models\UserStudents;
use App\Models\Smstemplates;
use Illuminate\Http\Request;
use App\Models\UserParents;
use App\Models\SchoolUsers;
use App\Models\Attendance;
use App\Models\UserStaffs;
use App\Models\UserGroups;
use App\Models\UserAdmin;
use App\Models\Appusers;
use App\Models\UserAll;
use App\Models\Smslogs;
use Carbon\Carbon;
use Validator;
use Config;
use File;
use URL;
use DB;

class APIAttendanceController extends Controller
{
    // Attendance Main screen
    public function attendance_maindashboard(Request $request)
    {
        // Authentication details
        $user = auth()->user();

        $attendance_date = date("Y-m-d");

        $present_total = $absent_total = $leave_total = $present_percentage = $absent_percentage = $absent_percentage = 0;
        $session_type = 1;
        $present_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

        $absent_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

        $leave_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

        $students_count = count(UserStudents::where('user_status',Config::get('app.Group_Active'))->pluck('id')->toArray());//get students count 

        // Dashboard details
        $details = ([
            'students_count'=>$students_count,
            'present_total'=>$present_total,
            'absent_total'=>$absent_total,
            'leave_total'=>$leave_total,
            'present_percentage'=>($present_total > 0)?(($present_total/$students_count)*100):0,
            'absent_percentage'=>($absent_total > 0)?(($absent_total/$students_count)*100):0,
            'absent_percentage'=>($leave_total > 0)?(($leave_total/$students_count)*100):0,
        ]);
        echo json_encode($details);
    } 

    // Attendance list page for class and section
    public function attendance_class_section_listing(Request $request)
    {
        // Authentication details
        $user = auth()->user();


        $attendance_date = date("Y-m-d");

        $present_total = $absent_total = $leave_total = $present_percentage = $absent_percentage = $absent_percentage = 0;
        $session_type = 1;
        $present_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

        $absent_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

        $leave_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

        $students_count = count(UserStudents::where('user_status',Config::get('app.Group_Active'))->pluck('id')->toArray());//get students count 

        $left_students = $students_count - ($present_total+$absent_total+$leave_total); //student left to mark attendance
        
        // overall school attendance details
         $details = ([
            'students_count'=>$students_count,
            'present_total'=>$present_total,
            'absent_total'=>$absent_total,
            'leave_total'=>$leave_total,
            'present_percentage'=>($present_total > 0)?(($present_total/$students_count)*100):0,
            'absent_percentage'=>($absent_total > 0)?(($absent_total/$students_count)*100):0,
            'absent_percentage'=>($leave_total > 0)?(($leave_total/$students_count)*100):0,
        ]);

        $class_section_details = AcademicClassConfiguration::get();//get class and section details

        foreach($class_section_details as $class_sec_key => $class_sec_value)
        {
            $class_present_total = $class_absent_total = $class_leave_total = $class_present_percentage = $class_absent_percentage = $class_absent_percentage = 0;

            $class_present_total = count(Attendance::where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

            $class_absent_total = count(Attendance::where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

            $class_leave_total = count(Attendance::where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

            $class_students_count = count(UserStudents::where('class_config',$request->class_config)->where('user_status',Config::get('app.Group_Active'))->pluck('id')->toArray());//get students count 

            // Get individual class and section attendance details 
            $attendance[] = ([
                'class_section_name'=> $class_sec_value->classsectionName(),
                'present_total'=>$class_present_total,
                'absent_total'=>$class_absent_total,
                'leave_total'=>$class_present_total,
                'present_percentage'=>($class_present_total > 0)?(($class_present_total/$class_students_count)*100):0,
                'absent_percentage'=>($class_absent_total > 0)?(($class_absent_total/$class_students_count)*100):0,
                'absent_percentage'=>($class_leave_total > 0)?(($class_leave_total/$class_students_count)*100):0,
            ]);
        }
        echo json_encode(['left_students'=>$left_students,'school_attendance'=>$details,'attendance'=>$attendance]);
    }

    // get all student details from selected class,section
    public function get_student_list(Request $request)
    {
        // Authentication details
        $user = auth()->user();

        $student_details = UserStudents::select('first_name','id')->where('class_config',$request->class_config)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();
        
        echo json_encode($student_details);

    }

    // Store attendance date;
    public function store_attendance(Request $request)
    {
        // Authentication details
        $user = auth()->user();
        $attendance_date = date("Y-m-d");

        $attendance_records = $request->attendance_record;

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        foreach ($attendance_records as $attendance_key => $attendance_value) {
            // code...
            $attendance_entry = Attendance::where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('user_table_id',$attendance_key)->first();
            if(empty($attendance_entry))
            {
                $attendance_entry = new Attendance();
                $attendance_entry->created_by=$userall_id;
                $attendance_entry->created_time=Carbon::now()->timezone('Asia/Kolkata');
                $attendance_entry->attendance_date=date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata')));
            }
            else
            {
                $attendance_entry->updated_by=$userall_id;
                $attendance_entry->updated_time=Carbon::now()->timezone('Asia/Kolkata');
            }

            $attendance_entry->user_table_id = $attendance_key;
            $attendance_entry->class_config = $request->class_config;
            $attendance_entry->attendance_status = $attendance_value;
            if(isset($request->reason))
                $attendance_entry->reason = $request->reason;

            $attendance_entry->session_type = 1;
            $attendance_entry->save();

            if($attendance_value != 1) //trigger pushnotification for absent and leave
            {
                $parent_ids = UserStudentsMapping::where('student',$attendance_key)->pluck('parent')->toArray();
                if(!empty($parent_ids))
                {
                    $parent_details = UserParents::whereIn('id',$parent_ids)->where('user_status',Config::get('app.Group_Active'))->pluck('id')->toArray();

                    if(!empty($parent_details))
                    {
                        $player_ids =[];
                        $player_ids = Appusers::whereIn('loginid',$parent_details)->pluck('player_id')->first();

                        if(!empty($player_ids))
                        {
                            $student_name = UserStudents::where('id',$attendance_key)->pluck('first_name')->first();

                            $status = ($attendance_value == 2)?"absent":"leave";
                            $chat_message =   'Dear Parent, Your ward '.$student_name.' is '.$status.' today ('.date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata'))).')';

                            $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,NULL,'attendance'); //trigger pushnotification function
                        }
                    }
                }

            }
        }
        echo json_encode(['status'=>true,'message'=>'Attendance marked successfully!...']);
    }


    // get store attendance
    public function get_attendance(Request $request)
    {
        // Authentication details
        $user = auth()->user();
        $attendance_date = date("Y-m-d");
        $attendance_entry = Attendance::select('user_table_id as id','class_config','session_type','attendance_date','reason')->where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->get()->toArray();

        echo json_encode($attendance_entry);
    }


}