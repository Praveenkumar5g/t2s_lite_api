<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-03-2022
 * Time: 05:50
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubjectsMapping;
use App\Models\AcademicClassConfiguration;
use App\Models\CommunicationAttachments;
use App\Models\CommunicationRecipients;
use App\Models\HomeworkParentStatus;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\AcademicSubjects;
use App\Models\UserManagements;
use App\Models\Communications;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use App\Models\UserStudents;
use App\Models\UserParents;
use App\Models\UserStaffs;
use App\Models\UserGroups;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use Validator;
use Config;
use File;
use URL;
use DB;

class APIHomeworkController extends Controller
{
    // View homework for all type of users
    public function homework(Request $request)
    {
        if($request->homework_date!= '')
        {
            // Get authorizated user details
            $user = auth()->user();

            $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
            $user->save();

            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Management_role'))
                $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Staff_role'))
                $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->first();
            else if($user->user_role == Config::get('app.Parent_role'))
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

            if($user->user_role == Config::get('app.Parent_role')) //check parent login
            {  
                 $homework_details = Communications::where('actioned_time', 'like', '%' .$request->homework_date. '%')->where('communication_type',2); //get homework details based on selected date
                if($user->user_role == Config::get('app.Parent_role')) //check parent login
                {
                    $student_ids = UserStudentsMapping::where('parent',$user_table_id);
                    if($request->loginstudent_id !='')
                        $student_ids = $student_ids->where('student',$request->loginstudent_id);
                    // fetch student id for that parent
                    $student_ids = $student_ids->pluck('student')->first();
                    $group_id = '';
                    if($student_ids !='') //get classs config and group id for parent
                    {
                        $class_config = UserStudents::where('id',$student_ids)->pluck('class_config')->first();
                        $group_id = UserGroups::where('class_config',$class_config)->pluck('id')->first();
                    }
                    if($request->class_config !='') //get classs config and group id for parent
                        $group_id = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();

                    if($group_id !='')
                        $homework_details = $homework_details->where('group_id',$group_id)->where('approval_status',1);
                    else
                        return response()->json([]);

                }
                $homeworks = [];
                $homework_details = $homework_details->get()->toArray();
                if(!empty($homework_details))
                {
                    foreach ($homework_details as $key => $value) {
                        $subject_details = AcademicSubjects::where('id',$value['subject_id'])->get()->first();
                        $class_config_list = UserGroups::where(['group_type'=>2,'group_status'=>1,'id'=>$value['group_id']])->get()->first();
                        $teachingstaff = AcademicSubjectsMapping::where(['subject'=>$value['subject_id'],'class_config'=>$class_config_list->class_config])->pluck('staff')->first();
                        $staff_details = UserStaffs::select('id','first_name','profile_image')->where('id',$teachingstaff)->get()->first();
                        $images = [];
                        if(!empty($value) && $value['attachments'] == 'Y')
                        {
                            $images_list = CommunicationAttachments::where(['communication_id'=>$value['id']])->get()->toArray();
                            if(!empty($images_list))
                            {
                                foreach ($images_list as $image_key => $image_value) {
                                    $images[]= ([
                                        'image'=>$image_value['attachment_location'].''.$image_value['attachment_name'],
                                        'id'=>$image_value['id']
                                    ]);
                                }
                                
                            }
                        }
                        $homework_status_response = HomeworkParentStatus::select('status','reason')->where(['notification_id'=>$value['id'],'parent'=>$user_table_id])->get()->first();
                        $homeworks[]=([
                            'notification_id'=>$value['id'],
                            'subject_id'=>$value['subject_id'],
                            'subject_name'=>$subject_details->subject_name,
                            'subject_shortname'=>$subject_details->short_name,
                            'staff_name'=>(!empty($staff_details))?$staff_details->first_name:'',
                            'staff_id'=>(!empty($staff_details))?$staff_details->id:'',
                            'class_section'=>$class_config_list->classsectionName(),
                            'profile_image'=>(!empty($staff_details))?$staff_details->profile_image:'',
                            'class_config'=>(isset($value['group_id']) && isset($class_config_list->class_config))?$class_config_list->class_config:'',
                            'homework_date'=>!empty($value)?$value['actioned_time']:'',
                            'homework_content'=>!empty($homework_details)?$value['chat_message']:'',
                            'approval_status'=>!empty($homework_details)?$value['approval_status']:0,
                            'is_pointed'=>($request->notification_id!='' && $value['id'] == $request->notification_id)?1:0,
                            'image'=>$images,
                            'homework_status'=>!empty($homework_status_response)?$homework_status_response->status:0,
                            'homework_status_reason'=>!empty($homework_status_response)?$homework_status_response->reason:0,
                            'edited'=>$value['edited'],
                        ]);

                    }
                }

                return response()->json($homeworks);
            }
            else //remaining users
            {
                $subjects = $classteacher_details = $teachingstaff_profile_list =[];
                $subject_details = AcademicSubjectsMapping::where('class_config',$request->class_config);
                if($user->user_role == Config::get('app.Staff_role'))
                   $subject_details =  $subject_details->where('staff',$user_table_id->id);

                $subject_details =  $subject_details->get(); //fetch subject list for that correspoding mapped staffs
                if($user->user_role == Config::get('app.Staff_role'))
                {
                    $classteacher_details = AcademicClassConfiguration::where('class_teacher',$user_table_id->id)->where('id',$request->class_config)->get()->first(); //check wheter the logged in user as class teacher for that particular class
                    if(!empty($classteacher_details)) //if yes fetch all the subjects in that class
                        $subject_details = AcademicSubjectsMapping::where('class_config',$classteacher_details->id)->get();
                }

                $teachingstaff_details = UserStaffs::select('id','first_name','profile_image')->get()->toArray();//fetch staff details
                if(!empty($teachingstaff_details)) //get all staff details
                {
                    $teachingstaff_list= array_column($teachingstaff_details,'first_name','id');
                    $teachingstaff_profile_list= array_column($teachingstaff_details,'profile_image','id');
                }
                $subjects = [];
                if(!empty($subject_details))
                {
                    foreach ($subject_details as $sub_key => $sub_value) {
                        $homework_details ='';
                        $subject_details = AcademicSubjects::where('id',$sub_value->subject)->get()->first();//get subject details

                        $group_id = UserGroups::where('class_config',$sub_value->class_config)->pluck('id')->first();//fetch group details

                        $homework_details = Communications::where('actioned_time', 'like', '%' .$request->homework_date. '%')->where(['group_id'=>$group_id,'subject_id'=>$subject_details->id])->get()->first();//get homework details based on selected date

                        $classteacher_data = AcademicClassConfiguration::where('id',$request->class_config)->get()->first(); //class teacher id for that selected class

                        $classteacherdetails = UserStaffs::where('id',$classteacher_data->class_teacher)->get()->first(); //class teacher details
                        $percent = 0;
                        $completed_students = $not_completed_students = 0;
                        if(!empty($homework_details))
                        {
                            //get count of parents received homework and calcuating percentage based on completion
                            $total_parents = CommunicationRecipients::select('user_table_id')->where(['user_role'=>Config::get('app.Parent_role'),'communication_id'=>$homework_details->id])->get()->toArray();

                            if(!empty($total_parents))
                            {
                                $student_ids = UserStudents::where('class_config',$sub_value->class_config)->where('user_status',1)->pluck('id')->toArray();
                                $total_students_mapped = array_unique(UserStudentsMapping::whereIn('parent',$total_parents)->pluck('student')->toArray());
                                $total_students = array_intersect($total_students_mapped,$student_ids);
                                $count_total_student = count($total_students);
                                $completed_students = HomeworkParentStatus::where('notification_id',$homework_details->id)->where('status',1)->get()->count();
                                $not_completed_students = HomeworkParentStatus::where('notification_id',$homework_details->id)->where('status',2)->get()->count();
                                $percent = round(($completed_students/$count_total_student)*100);
                            }
                        }

                        $subjects[$sub_key]=([
                            'notification_id'=>!empty($homework_details)?$homework_details->id:0,
                            'subject_id'=>$subject_details->id,
                            'subject_name'=>$subject_details->subject_name,
                            'subject_shortname'=>$subject_details->short_name,
                            'staff_name'=>(!empty($teachingstaff_list) && array_key_exists($sub_value->staff,$teachingstaff_list))?$teachingstaff_list[$sub_value->staff]:'',
                            'staff_id'=>$sub_value->staff,
                            'classteacher_name'=>$classteacherdetails['first_name'],
                            'profile_image'=>(!empty($teachingstaff_profile_list) && array_key_exists($sub_value->staff,$teachingstaff_profile_list))?$teachingstaff_profile_list[$sub_value->staff]:'',
                            'classteacher_id'=>$classteacher_data->class_teacher,
                            'class_config'=>$sub_value->class_config,
                            'class_section'=>$classteacher_data->classsectionName(),
                            'homework_date'=>!empty($homework_details)?$homework_details->actioned_time:date('Y-m-d H:i:s'),
                            'percent'=>$percent,
                            'expires_in'=>'04h:00m:00s',
                            'completed_count'=>$completed_students,
                            'not_completed_students'=>$not_completed_students,
                            'flag'=>(!empty($classteacher_details))?'classteacher':'staff',
                            'homework_content'=>!empty($homework_details)?$homework_details->chat_message:'',
                            'approval_status'=>(!empty($homework_details) && $homework_details->approval_status>0)?$homework_details->approval_status:0,
                            'is_pointed'=>($request->notification_id!='' && !empty($homework_details) && $homework_details->id == $request->notification_id)?1:0,
                            'edited'=>!empty($homework_details)?$homework_details->edited:0,
                        ]);
                        if($user->user_role == Config::get('app.Admin_role') || $user->user_role == Config::get('app.Management_role'))
                            $subjects[$sub_key]['flag'] = 'classteacher';
                        $images = [];
                        if(!empty($homework_details) && $homework_details->attachments == 'Y')
                        {
                            $images_list = CommunicationAttachments::where(['communication_id'=>$homework_details->id])->get()->toArray();
                            if(!empty($images_list))
                            {
                                foreach ($images_list as $image_key => $image_value) {
                                    $images[]= ([
                                        'image'=>$image_value['attachment_location'].''.$image_value['attachment_name'],
                                        'id'=>$image_value['id']
                                    ]);
                                }
                                
                            }
                        }
                        $subjects[$sub_key]['images'] = $images;
                    }
                }
                return response()->json($subjects);
            }

        }
        else
            return response()->json('Homework date is required');
    }

    // Store homework in DB 
    public function store_homework(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        $group_id = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();

        // check deactivation for user
        $check_access = UserGroupsMapping::where('user_table_id',$user_table_id)->where('group_id',$group_id)->where('user_role',$user->user_role)->where('user_status',1)->pluck('id')->first();

        if($check_access == '')
            return response()->json(['message'=>'Your account is deactivated. Please contact school management for futher details']);

        // Insert communication message in notification log tables(School DB)
        if($request->notification_id!='')
        {
            $communications = Communications::where(['id'=>$request->notification_id])->first();
            $communications->updated_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->updated_by=$userall_id;
            $communications->edited =1; //0-created,1-edited
        }
        else
        {
            $communications = new Communications;
            $communications->created_by=$userall_id;
            $communications->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->subject_id = $request->subject_id;
            $communications->actioned_by=$userall_id;
            $communications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
        }
        if(isset($request->homework_date) && $request->homework_date !='')
        {
            $date = Carbon::createFromFormat('Y-m-d', $request->homework_date);
            $date->setTimeZone('Asia/Kolkata');
            $date = $date->format('Y-m-d H:i:s');
            $communications->actioned_time=$date;
        }
        $communications->chat_message=$request->message;
        if(isset($request->title))
            $communications->title=$request->title;
        $communications->distribution_type=3; //1-Class,2-Group,3-Everyone,4-Staff,5-Parent
        $communications->message_category=$request->message_category; //1-Text,2-Image with caption,3-Image Only,4-Document,5-Audio,6-Video,7-Quotes,8-Management Speaks,9-Circular,10-Study Material;
       
        $communications->group_id=$group_id;
        $communications->communication_type=2;
         // Default attachment no
        if(count($_FILES)>0)
            $communications->attachments='Y'; 
        else if($request->notification_id == '')
            $communications->attachments='N';

        $class_teacher = AcademicClassConfiguration::where('id',$request->class_config)->pluck('class_teacher')->first();
        // if($class_teacher == $user_table_id)
        //     $communications->approval_status=1;//1-Approval,2-Denied

        $communications->save();
        $notification_id = $communications->id;

        if(count($_FILES)>0)
        {
            // if($request->notification_id!='' && $request->hasfile('attachment'))
            // {
            //     $delete_attachment = CommunicationAttachments::where('communication_id',$request->notification_id)->get()->toArray();
            //     if(!empty($delete_attachment))
            //     {
            //         foreach ($delete_attachment as $delete_key => $delete_value) {
            //             if(File::exists(public_path().'/uploads/'.$delete_value['attachment_name'])){
            //                 File::delete(public_path().'/uploads/'.$delete_value['attachment_name']);
            //             }
            //         }
            //     }
            //     CommunicationAttachments::where('communication_id',$request->notification_id)->delete();
            // }
            // Insert attachment details in attachment table
            $schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile
            $path = public_path('uploads/'.$school_profile['school_code']);//

            if(!File::isDirectory($path)){ //check path already exists
                File::makeDirectory($path, 0777, true, true);
            }

            if($request->hasfile('attachment')) {
                foreach($request->file('attachment') as $file)
                {
                    $attachment = new CommunicationAttachments;
                    $attachment->communication_id = $notification_id;
                    $name = explode('.',$file->getClientOriginalName());
                    $filename = str_replace(' ', '_', $name[0]);
                    $names = $filename.time().'.'.$name[1];
                    $file->move(public_path().'/uploads/'.$school_profile['school_code'], $names);  
                    $attachment->attachment_name = $names;
                    $attachment->attachment_type = $request->attachment_type;  //1-image,2-audio,3-document,4-study material
                    $attachment->attachment_location = url('/').'/uploads/'.$school_profile['school_code'].'/';
                    $attachment->save();
                }
            }

        }
        // $user_list= $user_ids = [];
        // $user_ids =UserGroupsMapping::where('group_id',$group_id);
        // $user_ids = $user_ids->whereIn('user_role',([Config::get('app.Admin_role'),Config::get('app.Management_role')]));

        // if($class_teacher != $user_table_id)
        // {
        //     $user_list = UserGroupsMapping::where(['group_id'=>$group_id])->whereIn('user_table_id',([$class_teacher,$user_table_id]))->whereIn('user_role',[$user->user_role,Config::get('app.Staff_role')])->get()->toArray();
        // }
        // else
        // {
        //     $user_list[] =([
        //         'user_table_id'=>$user_table_id,
        //         'user_role'=>Config::get('app.Staff_role'),
        //     ]);
        // }
        
        // $user_ids = $user_ids->get()->toArray();
        // $user_list = array_merge($user_list,$user_ids);
        // if(count($user_list)>0)
        //     app('App\Http\Controllers\APICommunicationController')->insert_receipt_log($user_list,$notification_id,$user_table_id); 
        if($request->notification_id!='')
            return response()->json(['message'=>'Homework updated Successfully!...']);
        else
            return response()->json(['message'=>'Homework inserted Successfully!...']);

    }

    // Approval Status
    public function homework_approval(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        foreach ($request->notification_id as $key => $value) {
            if($value>0)
            {
                $triggered_users = $user_list = $notification_triggered_user = [];
                $communication_data = Communications::where(['id'=>$value])->get()->first();
                $class_config = UserGroups::where('id',$communication_data->group_id)->pluck('class_config')->first();
                $subject_teacher = AcademicSubjectsMapping::where('subject',$communication_data->subject_id)->where('class_config',$class_config)->pluck('staff')->first();

                $class_teacher = AcademicClassConfiguration::where('id',$class_config)->pluck('class_teacher')->first();
                // $notification_triggered_user[] = UserAll::where('id',$communication_data->created_by)->where('user_role',Config::get('app.Staff_role'))->pluck('user_table_id')->first();
                // if(!empty($notification_triggered_user))
                //     $triggered_users = array_merge($class_teacher,$notification_triggered_user);

                $user_ids = UserGroupsMapping::where('group_id',$communication_data->group_id)->whereIn('user_role',([Config::get('app.Parent_role'),Config::get('app.Admin_role'),Config::get('app.Management_role')]))->get()->toArray();
                foreach ($user_ids as $user_key => $user_value) {
                   // if((!empty($triggered_users) && !in_array($user_value['user_table_id'],$triggered_users))|| empty($triggered_users))
                        $user_list[] = $user_value;
                }
                if(!empty($subject_teacher) && !in_array($subject_teacher,$notification_triggered_user)) //check notification already triggered for subject staff or not 
                    array_push($user_list,["user_table_id"=>$subject_teacher,'user_role'=>Config::get('app.Staff_role')]);//if not, trigger notification to staff subject

                if($class_teacher!='') //insert log for class teacher
                    array_push($user_list,["user_table_id"=>$class_teacher,'user_role'=>Config::get('app.Staff_role')]);//if not, trigger notification to staff subject
                
                Communications::where('id',$value)->update(['approval_status'=>1,'delivered_users'=>count($user_list)+$communication_data->delivered_users,'approved_by'=>$userall_id,'approved_time'=>Carbon::now()->timezone('Asia/Kolkata')]);

                if(count($user_list)>0)
                    app('App\Http\Controllers\APICommunicationController')->insert_receipt_log($user_list,$communication_data->id,$user_table_id);
            }
        
        }
        return response()->json(['message'=>'Approved Successfully!...']);

    }

    // Update homwork completed not completed status
    public function update_homework_status(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();
        $student_id = UserStudentsMapping::where('parent',$user_table_id)->pluck('student')->first();

        $homework_status = HomeworkParentStatus::where('notification_id',$request->notification_id)->where('student',$request->student_id)->get()->first();
        if(empty($homework_status))
        {
            $homework_status = new HomeworkParentStatus;
            $homework_status->created_by=$userall_id;
            $homework_status->created_time=Carbon::now()->timezone('Asia/Kolkata');
        }
        else
        {
            $homework_status->updated_by=$userall_id;
            $homework_status->updated_time=Carbon::now()->timezone('Asia/Kolkata');
        }

        $homework_status->notification_id=$request->notification_id;
        $homework_status->parent=$user_table_id;
        $homework_status->status=$request->status;//1-completed,2-not completed
        $homework_status->student=$request->student_id;
        $homework_status->reason=$request->reason;
       
        $homework_status->save();


        return response()->json(['message'=>'Status noted Successfully!...']);
    }

    // Homework detail report for student
    public function homework_details_report(Request $request)
    {
        $details_report = [];
        if($request->notification_id > 0 && $request->notification_id!='') //check notification id exists or not
        {
            $check_homework_status = Communications::where('id',$request->notification_id)->where('actioned_time', 'like', '%' .$request->homework_date. '%')->get()->first(); //get notification from id and date
            if(!empty($check_homework_status))//check homework is avaiable or not
            {
                $status =HomeworkParentStatus::where('notification_id',$request->notification_id)->get();//get the homework status
                foreach ($status as $key => $value) {
                    $studentdetails = $value->studentDetails(); //fetch student related details
                    $parentdetails = $value->parentDetails(); //fetch parent related details
                    $details_report[] = ([
                        'parent_name'=>$studentdetails['first_name'],
                        'mobile_number'=>$parentdetails['mobile_number'],
                        'status'=>($value->status == 1)?'Completed':'Not Completed',
                        'reason'=>($value->reason == null)?"":$value->reason
                    ]);
                }
            }
        }
        return response()->json($details_report);
    }

    public function delete_homework_attachment(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        $delete_attachment = CommunicationAttachments::where('id',$request->id)->get()->toArray();
        if(!empty($delete_attachment))
        {
            $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile

            foreach ($delete_attachment as $delete_key => $delete_value) {
                if(File::exists(public_path().'/uploads/'.$school_profile['school_code'].'/'.$delete_value['attachment_name'])){
                    File::delete(public_path().'/uploads/'.$school_profile['school_code'].'/'.$delete_value['attachment_name']);
                }
            }
        }
        CommunicationAttachments::where('id',$request->id)->delete();

        return response()->json(['status'=>true,'message'=>'Deleted Successfully!...']);
    }

    // get homework status list
    public function list_homework_status(Request $request)
    {
        $status_list =[];
        $list = HomeworkParentStatus::where(['status' =>$request->status, 'notification_id' => $request->homework_id])->get()->toArray();
        if(!empty($list))
        {
            foreach($list as $key=>$value)
            {
                $student_name = UserStudents::where('id',$value['student'])->pluck('first_name')->first();
                $parent_name = UserParents::where('id',$value['parent'])->pluck('first_name')->first();

                $status_list[] = ([
                    'id'=>$value['id'],
                    'student_id'=>$value['student'],
                    'parent_id'=>$value['parent'],
                    'status'=>$value['status'],
                    'reason'=>$value['reason'],
                    'reported_time'=>($value['updated_time']!=null)?$value['updated_time']:$value['created_time'],
                    'student_name'=>$student_name,
                    'parent_name'=>$parent_name,

                ]);
            }
        }
        // $list = DB::table('homework_parent_status as s')->select('s.id','s.student','s.parent','s.status','s.reason','s.created_time','s.updated_time','us.first_name','p.first_name')->join('user_student as us','us.id','=','s.student')->join('user_parent as p','p.id','=','p.parent')->get()->toArray();
        return response()->json($status_list);
    }

}