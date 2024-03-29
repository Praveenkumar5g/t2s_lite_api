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
use App\Models\UserGroupsMapping;
use App\Models\NotificationLogs;
use App\Models\UserManagements;
use App\Models\UserCategories;
use App\Models\UserManagement;
use App\Models\Communications;
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

        $attendance_date = date('Y-m-d',strtotime(Carbon::now()->timezone('Asia/Kolkata')));

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
            'present_percentage'=>($present_total > 0)?round(($present_total/$students_count)*100):0,
            'absent_percentage'=>($absent_total > 0)?round(($absent_total/$students_count)*100):0,
            'leave_percentage'=>($leave_total > 0)?round(($leave_total/$students_count)*100):0,
        ]);
        echo json_encode($details);
    } 

    // Attendance list page for class and section
    public function attendance_class_section_listing(Request $request)
    {
        // Authentication details
        $user = auth()->user();


        $attendance_date = date('Y-m-d',strtotime(Carbon::now()->timezone('Asia/Kolkata')));

        $present_total = $absent_total = $leave_total = $present_percentage = $absent_percentage = $absent_percentage = 0;
        $session_type = 1;
        $student_ids = UserStudents::where('user_status',1)->pluck('id')->toArray();

        $present_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->whereIn('user_table_id',$student_ids)->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

        $absent_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->whereIn('user_table_id',$student_ids)->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

        $leave_total = count(Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->whereIn('user_table_id',$student_ids)->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

        $students_count = count(UserStudents::where('user_status',Config::get('app.Group_Active'))->where('user_status',1)->pluck('id')->toArray());//get students count 

        $left_students = $students_count - ($present_total+$absent_total+$leave_total); //student left to mark attendance
        
        // overall school attendance details
         $details = ([
            'students_count'=>$students_count,
            'present_total'=>$present_total,
            'absent_total'=>$absent_total,
            'leave_total'=>$leave_total,
            'present_percentage'=>($present_total > 0)?round(($present_total/$students_count)*100):0,
            'absent_percentage'=>($absent_total > 0)?round(($absent_total/$students_count)*100):0,
            'leave_percentage'=>($leave_total > 0)?round(($leave_total/$students_count)*100):0,
        ]);

        $class_section_details = AcademicClassConfiguration::get();//get class and section details

        foreach($class_section_details as $class_sec_key => $class_sec_value)
        {
            $class_present_total = $class_absent_total = $class_leave_total = $class_present_percentage = $class_absent_percentage = $class_absent_percentage = 0;

            $student_ids = UserStudents::where('class_config',$class_sec_value->id)->where('user_status',1)->pluck('id')->toArray();

            $class_present_total = count(Attendance::where('class_config',$class_sec_value->id)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->where('attendance_status',1)->whereIn('user_table_id',$student_ids)->pluck('id')->toArray());

            $class_absent_total = count(Attendance::where('class_config',$class_sec_value->id)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->whereIn('user_table_id',$student_ids)->where('attendance_status',2)->pluck('id')->toArray());

            $class_leave_total = count(Attendance::where('class_config',$class_sec_value->id)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('session_type',1)->whereIn('user_table_id',$student_ids)->where('attendance_status',3)->pluck('id')->toArray());

            $class_students_count = count(UserStudents::where('class_config',$class_sec_value->id)->where('user_status',Config::get('app.Group_Active'))->where('user_status',1)->pluck('id')->toArray());//get students count  

            // Get individual class and section attendance details 
            $attendance[] = ([
                'config_id'=>$class_sec_value->id,
                'class_section_name'=> $class_sec_value->classsectionName(),
                'present_total'=>$class_present_total,
                'absent_total'=>$class_absent_total,
                'leave_total'=>$class_leave_total,
                'present_percentage'=>($class_present_total > 0)?round(($class_present_total/$class_students_count)*100):0,
                'absent_percentage'=>($class_absent_total > 0)?round(($class_absent_total/$class_students_count)*100):0,
                'leave_percentage'=>($class_leave_total > 0)?round(($class_leave_total/$class_students_count)*100):0,
            ]);
        }
        echo json_encode(['left_students'=>$left_students,'school_attendance'=>$details,'attendance'=>$attendance]);
    }

    // get all student details from selected class,section
    public function get_student_list(Request $request)
    {
        // Authentication details
        $user = auth()->user();

        $student_details = UserStudents::select('first_name','id','class_config','admission_number','roll_number','profile_image')->where('class_config',$request->class_config)->where('user_status',Config::get('app.Group_Active'))->get();

        foreach($student_details as $key=> $value)
        {
            $present_total = $absent_total = $leave_total = $present_percentage = $absent_percentage = $absent_percentage = 0;

            $present_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

            $absent_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

            $leave_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

            $total_days = $present_total+$absent_total+$leave_total;

            $mapped_parents = UserStudentsMapping::where('student',$value->id)->pluck('parent')->toArray();

            $parents = UserParents::whereIn('id',$mapped_parents)->where('user_status',1)->get()->toArray();

            $student_details[$key]['class_section_name'] = $value->classsectionName();
            if(!empty($parents))
            {
                foreach($parents as $parent_key => $parent_value)
                {
                    $student_details[$key]['father_name'] = '';
                    $student_details[$key]['mother_name'] = '';
                    $student_details[$key]['guardian_name'] = '';
                    $student_details[$key]['father_mobile_no'] = 0;
                    $student_details[$key]['mother_mobile_no'] = 0;
                    $student_details[$key]['guardian_mobile_no'] = 0;

                    if($parent_value['user_category'] == Config::get('app.Father'))
                    {
                        $student_details[$key]['father_name'] = $parent_value['first_name'];
                        $student_details[$key]['father_mobile_no'] = $parent_value['mobile_number'];
                    }

                    if($parent_value['user_category'] == Config::get('app.Mother'))
                    {
                        $student_details[$key]['mother_name'] = $parent_value['first_name'];
                        $student_details[$key]['mother_mobile_no'] = $parent_value['mobile_number'];
                    }

                    if($parent_value['user_category'] == Config::get('app.Guardian'))
                    {
                        $student_details[$key]['guardian_name'] = $parent_value['first_name'];
                        $student_details[$key]['guardian_mobile_no'] = $parent_value['mobile_number'];
                    }
                }
            }


            $student_details[$key]['present_total'] = $present_total;
            $student_details[$key]['absent_total'] = $absent_total;
            $student_details[$key]['leave_total'] = $leave_total;
            $student_details[$key]['present_percentage'] = ($present_total > 0)?round(($present_total/$total_days)*100):0;
            $student_details[$key]['absent_percentage'] = ($absent_total > 0)?round(($absent_total/$total_days)*100):0;
            $student_details[$key]['leave_percentage'] = ($leave_total > 0)?round(($leave_total/$total_days)*100):0;
        }
        
        echo json_encode($student_details);

    }

    // Store attendance date;
    public function store_attendance(Request $request)
    {
        // Authentication details
        $user = auth()->user();
        $attendance_date = date('Y-m-d',strtotime(Carbon::now()->timezone('Asia/Kolkata')));

        $attendance_records = $request->attendance_records;
        $old_attendance_status ='';
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        $groupid = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();

        // check deactivation for user
        $check_access = UserGroupsMapping::where('user_table_id',$user_table_id)->where('group_id',$groupid)->where('user_role',$user->user_role)->where('user_status',1)->pluck('id')->first();

        if($check_access == '')
            return response()->json(['message'=>'Your account is deactivated. Please contact school management for futher details']);
        
        foreach ($attendance_records as $attendance_key => $attendance_value) {
            // code...
            $attendance_entry = $check_entry = Attendance::where('attendance_date', 'like', '%' .$attendance_date. '%')->where('user_table_id',$attendance_key)->first();

            if(empty($attendance_entry))
            {
                $attendance_entry = new Attendance();
                $attendance_entry->created_by=$userall_id;
                $attendance_entry->created_time=Carbon::now()->timezone('Asia/Kolkata');
                $attendance_entry->attendance_date=date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata')));
            }
            else
            {
                $old_attendance_status = $attendance_entry->attendance_status;
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

            $chat_message= '';
            $parent_ids = UserStudentsMapping::where('student',$attendance_key)->pluck('parent')->toArray();
            if(!empty($parent_ids))
            {
                $parent_details = UserParents::whereIn('id',$parent_ids)->where('user_status',Config::get('app.Group_Active'))->pluck('id')->toArray();
                if(!empty($parent_details))
                {
                    $student_name = UserStudents::where('id',$attendance_key)->pluck('first_name')->first();
                    $player_ids =[];
                    $player_ids = Appusers::whereIn('loginid',$parent_details)->pluck('player_id')->toArray();
                }
            }
            if($attendance_value != 1) //trigger pushnotification for absent and leave
            {

                $status = ($attendance_value == 2)?"absent":"leave";
                $chat_message = 'Dear Parent, Your ward '.$student_name.' is '.$status.' today ('.date("d-m-Y",strtotime(Carbon::now()->timezone('Asia/Kolkata'))).')';
            }
            else if($old_attendance_status != 1 && $attendance_value == 1 && $old_attendance_status !='')
                $chat_message = 'Dear Parent, Your ward '.$student_name.' is present today ('.date("d-m-Y",strtotime(Carbon::now()->timezone('Asia/Kolkata'))).')';
            
            $group_details = UserGroups::select('id','group_name')->where('class_config',$request->class_config)->first();

            $group_id = $group_details->id;
            // Send nottification to parents
            if(!empty($parent_ids) && $chat_message!='')
            {
                $communications = new Communications;
                $communications->chat_message=$chat_message;
                $communications->visible_to=','.$attendance_key.',';
                $communications->distribution_type=5; //5-parent
                $communications->message_category=11; // 11-attendance
                $communications->actioned_by=$userall_id;
                $communications->created_by=$userall_id;
                $communications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
                $communications->created_time=Carbon::now()->timezone('Asia/Kolkata');
                $communications->group_id=$group_id;
                $communications->communication_type=1;
                $communications->approval_status=1;
                $communications->save();
                $notification_id = $communications->id;

                $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_role'=>Config::get('app.Parent_role'),'user_status'=>Config::get('app.Group_Active')])->whereIn('user_table_id',$parent_ids)->where('group_id',$group_id)->get()->toArray();

                if(!empty($user_list))
                    app('App\Http\Controllers\APICommunicationController')->insert_receipt_log(array_unique($user_list, SORT_REGULAR),$notification_id,$user_table_id);
                if(!empty($player_ids))
                {
                    $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$notification_id,'attendance'); //trigger pushnotification function
                }
            }

        }

        // send notifications to class teacher
        $classteacher = AcademicClassConfiguration::Where('id',$request->class_config)->pluck('class_teacher')->first();

        if(empty($check_entry))
        {
            $user_list=[];
            $communications = new Communications;
            $communications->chat_message='Today ('.date("d-m-Y",strtotime(Carbon::now()->timezone('Asia/Kolkata'))).') attendance marked for class '.$group_details->group_name;
            $communications->distribution_type=5; //5-parent
            $communications->message_category=11; // 11-attendance
            $communications->actioned_by=$userall_id;
            $communications->created_by=$userall_id;
            $communications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->group_id=$group_id;
            $communications->communication_type=1;
            $communications->approval_status=1;
            $communications->save();
            $notification_id = $communications->id;

            $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_role'=>Config::get('app.Staff_role'),'user_status'=>Config::get('app.Group_Active')])->where('user_table_id',$classteacher)->where('group_id',$group_id)->get()->toArray();

            $user_ids = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();
            $user_list = array_merge($user_list,$user_ids);
            $user_ids = [];

            $user_ids = UserGroupsMapping::select('user_table_id','user_role')->whereIn('user_role',([Config::get('app.Admin_role'),Config::get('app.Management_role')]))->where(['user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray();

            $user_list = array_merge($user_list,$user_ids);

            if(!empty($user_list))
                app('App\Http\Controllers\APICommunicationController')->insert_receipt_log(array_unique($user_list, SORT_REGULAR),$notification_id,$user_table_id);
        }
        echo json_encode(['status'=>true,'message'=>'Attendance marked successfully!...']);
    }


    // get store attendance
    public function get_attendance(Request $request)
    {
        // Authentication details
        $user = auth()->user();
        $attendance_date = date('Y-m-d',strtotime(Carbon::now()->timezone('Asia/Kolkata')));
        $attendance_entry = Attendance::select('user_table_id as id','class_config','session_type','attendance_date','reason')->where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->get()->toArray();

        $student_details = UserStudents::select('first_name','id','class_config','admission_number','roll_number','profile_image')->where('class_config',$request->class_config)->where('user_status',Config::get('app.Group_Active'))->get();

        foreach($student_details as $key=>$value)
        {
            $attendance_entry = Attendance::select('user_table_id as id','class_config','session_type','attendance_date','reason','attendance_status')->where('class_config',$request->class_config)->where('attendance_date', 'like', '%' .$attendance_date. '%')->where('user_table_id',$value->id)->get()->first();

            $present_total = $absent_total = $leave_total = $present_percentage = $absent_percentage = $absent_percentage = 0;

            $present_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',1)->pluck('id')->toArray());

            $absent_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',2)->pluck('id')->toArray());

            $leave_total = count(Attendance::where('class_config',$request->class_config)->where('user_table_id',$value->id)->where('session_type',1)->where('attendance_status',3)->pluck('id')->toArray());

            $total_days = $present_total+$absent_total+$leave_total;

            $mapped_parents = UserStudentsMapping::where('student',$value->id)->pluck('parent')->toArray();

            $parents = UserParents::whereIn('id',$mapped_parents)->where('user_status',1)->get()->toArray();

            $student_details[$key]['class_section_name'] = $value->classsectionName();
            if(!empty($parents))
            {
                foreach($parents as $parent_key => $parent_value)
                {
                    $student_details[$key]['father_name'] = '';
                    $student_details[$key]['mother_name'] = '';
                    $student_details[$key]['guardian_name'] = '';
                    $student_details[$key]['father_mobile_no'] = 0;
                    $student_details[$key]['mother_mobile_no'] = 0;
                    $student_details[$key]['guardian_mobile_no'] = 0;

                    if($parent_value['user_category'] == Config::get('app.Father'))
                    {
                        $student_details[$key]['father_name'] = $parent_value['first_name'];
                        $student_details[$key]['father_mobile_no'] = $parent_value['mobile_number'];
                    }

                    if($parent_value['user_category'] == Config::get('app.Mother'))
                    {
                        $student_details[$key]['mother_name'] = $parent_value['first_name'];
                        $student_details[$key]['mother_mobile_no'] = $parent_value['mobile_number'];
                    }

                    if($parent_value['user_category'] == Config::get('app.Guardian'))
                    {
                        $student_details[$key]['guardian_name'] = $parent_value['first_name'];
                        $student_details[$key]['guardian_mobile_no'] = $parent_value['mobile_number'];
                    }
                }
            }
            $student_details[$key]['total_days'] = $total_days;
            $student_details[$key]['present_total'] = $present_total;
            $student_details[$key]['absent_total'] = $absent_total;
            $student_details[$key]['leave_total'] = $leave_total;
            $student_details[$key]['present_percentage'] = ($present_total > 0)?round(($present_total/$total_days)*100):0;
            $student_details[$key]['absent_percentage'] = ($absent_total > 0)?round(($absent_total/$total_days)*100):0;
            $student_details[$key]['leave_percentage'] = ($leave_total > 0)?round(($leave_total/$total_days)*100):0;

            if(!empty($attendance_entry))
            {
                $student_details[$key]['session_type'] = $attendance_entry->session_type; 
                $student_details[$key]['attendance_date'] = $attendance_entry->attendance_date; 
                $student_details[$key]['reason'] = $attendance_entry->reason; 
                $student_details[$key]['attendance_status'] = $attendance_entry->attendance_status;    
            }
            else
            {
                $student_details[$key]['session_type'] = 1; 
                $student_details[$key]['attendance_date'] = $attendance_date; 
                $student_details[$key]['reason'] = ''; 
                $student_details[$key]['attendance_status'] = 0;
            }
        }

        echo json_encode($student_details);
    }

}