<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 28-12-2022
 * Time: 10:00
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\AcademicClassConfiguration;
use App\Models\CommunicationDistribution;
use App\Models\CommunicationAttachments;
use App\Models\CommunicationRecipients;
use App\Models\AcademicSubjectsMapping;
use App\Models\NewsEventsAttachments;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\NotificationLogs;
use App\Models\AcademicSections;
use App\Models\AcademicSubjects;
use App\Models\AcademicClasses;
use App\Models\UserManagements;
use App\Models\ApprovalSetting;
use App\Models\Communications;
use App\Models\UserCategories;
use App\Models\UserManagement;
use App\Models\SchoolProfile;
use App\Models\UserStudents;
use App\Models\Smstemplates;
use Illuminate\Http\Request;
use App\Models\UserParents;
use App\Models\SchoolUsers;
use App\Models\UserStaffs;
use App\Models\NewsEvents;
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

class APICommunicationController extends Controller
{
    // Approval process for messages
    public function save_approval_flow(Request $request)
    {
        // Add rules to the approval process
        $validator = Validator::make($request->all(), [
            'approval' => 'required', //1- yes,2-no
        ]);
        // Validate approval
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

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

        // store approval process settings
        $approvalsettings = new ApprovalSetting;
        $approvalsettings->approval=$request->approval; //0-No,1-Yes
        $approvalsettings->user_role=$request->user_role; //1-admin,2-staff,3-parent,4-student,5-management
        $approvalsettings->user_id=$request->user_id;
        $approvalsettings->status=1;//1-Active,2-Inactive,3-delete
        $approvalsettings->created_by=$userall_id;
        $approvalsettings->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $approvalsettings->save();
        return response()->json('Settings Updated Successfully!...');
    }

    // Store Message input
    public function store_message(Request $request)
    {
        // Add rules to the Store Message form
        $validator = Validator::make($request->all(), [
            'distribution_type' => 'required', //1- Everyone,2-Staff,3-Parent
            'message_category' => 'required', //1-Text,2-Image with caption
        ]);
        // Validate form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

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

        // check deactivation for user
        $check_access = UserGroupsMapping::where('user_table_id',$user_table_id)->where('group_id',$request->group_id)->where('user_role',$user->user_role)->where('user_status',1)->pluck('id')->first();

        if($check_access == '')
            return response()->json(['message'=>'Your account is deactivated. Please contact school management for futher details']);

        // Insert communication message in notification log tables(School DB)
        $communications = new Communications;
        $communications->chat_message=$request->chat_message;
        if(isset($request->title))
            $communications->title=$request->title;
        if(isset($request->important))
            $communications->important=$request->important;
        if(isset($request->visible_to))
        {
            $visibleto_classes  = $request->visible_to; //section wise
            if($request->distribution_type == 8) // class wise
                $visibleto_classes = AcademicClassConfiguration::whereIn('class_id',$request->visible_to)->pluck('id')->toArray();
            if($request->distribution_type == 9)
            {
                $userrole = ($user->user_role == Config::get('app.Management_role'))?Config::get('app.Admin_role'):Config::get('app.Management_role');
                $visibleto_classes = UserAll::whereIn('user_table_id',$request->visible_to)->where('user_role',$userrole)->pluck('id')->toArray();
            }
            $communications->visible_to=','.implode(',',$visibleto_classes).',';
        }
        $communications->distribution_type=$request->distribution_type; //1-Class,2-Group,3-Everyone,4-Staff,5-Parent
        $communications->message_category=$request->message_category; //1-Text,2-Image with caption,3-Image Only,4-Document,5-Audio,6-Video,7-Quotes,8-Management Speaks,9-Circular,10-Study Material;
        $communications->actioned_by=$userall_id;
        $communications->created_by=$userall_id;
        $communications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
        $communications->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $communications->group_id=$request->group_id;
        $communications->communication_type=1;
        $communications->attachments='N'; // Default attachment no
        if(count($_FILES)>0)
            $communications->attachments='Y'; 

        if($user->user_role != 3 && $user->user_role != 2)
            $communications->approval_status=1;//1-Approval,2-Denied

        if(isset($request->caption_message)) 
            $communications->caption_message=$request->caption_message; //Notification Message or video link
        $communications->save();
        $notification_id = $communications->id;
        if(count($_FILES)>0)
        {
            $schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile
            $path = public_path('uploads/'.$school_profile['school_code']);//

            if(!File::isDirectory($path)){ //check path already exists
                File::makeDirectory($path, 0777, true, true);
            }

            // Insert attachment details in attachment table
            if($request->hasfile('attachment')) {
                foreach($request->file('attachment') as $file)
                {
                    $attachment = new CommunicationAttachments;
                    $attachment->communication_id = $notification_id;
                    $name = explode('.',$file->getClientOriginalName());
                    $filename = str_replace(["-",","," ","/"], '_', $name[0]);
                    $names = $filename.time().'.'.$name[1];
                    $file->move(public_path().'/uploads/'.$school_profile['school_code'], $names);
                    $attachment->attachment_name = $names;
                    $attachment->attachment_type = $request->attachment_type;  //1-image,2-audio,3-document
                    $attachment->attachment_location = url('/').'/uploads/'.$school_profile['school_code'].'/';
                    $attachment->save();
                }
            }

        }

        if($user->user_role != 3) //check user role and 3-parent goes under approval process if approval flow 'yes'
        {
            $groupid[]= $request->group_id;
            // check message_category and fetch users based on category
            $user_role = explode(',',$request->distribution_type);
            $user_list= $user_ids = [];
            foreach ($user_role as $key => $value) {
                if(isset($request->visible_to) && !empty($request->visible_to) && ( $value == 6 || $value == 8))//send message to specific classes
                {
                    $class_config = $request->visible_to;
                    if($value == 8) // class wise
                        $class_config = AcademicClassConfiguration::whereIn('class_id',$request->visible_to)->pluck('id')->toArray();
                }

                $class_config[] = UserGroups::whereIn('id',$groupid)->pluck('class_config')->first();

                foreach($class_config as $visible_key => $visible_value)
                {        
                    if(isset($request->visible_to) && !empty($request->visible_to)  && ( $value == 6 || $value == 8)) //send message to specific classes
                        $groupid[] = UserGroups::where('class_config',$visible_value)->pluck('id')->first();
                    $communicationdistribution = new CommunicationDistribution;
                    $communicationdistribution->communication_id = $notification_id;
                    $communicationdistribution->class_config_id = $visible_value;
                    $communicationdistribution->user_group_id = implode(',',$groupid);
                    $communicationdistribution->save();
                }

                if( $value == 3) // Everyone
                {
                    $group_id = $request->group_id;
                    $user_ids =UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'));

                    if($user->user_role == 2)
                    {
                        $user_ids = $user_ids->whereIn('user_role',([Config::get('app.Admin_role'),Config::get('app.Management_role')]));
                        $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray();
                    }
                    
                    $user_ids = $user_ids->get()->toArray();
                    $user_list = array_merge($user_list,$user_ids);
                }
                else if($value == 4 || $value == 5) // 5-Parent(Mother and Father) //4-staff(Teaching and non-teaching)
                {    
                    $group_id = $request->group_id;
                    if($user->user_role != 2 && ($user_role != 5 || $user_role != 4 ) )
                    {
                        // 5-Parent(Mother and Father) //4-staff(Teaching and non-teaching)
                        $user_role = ($value == 4)?([Config::get('app.Staff_role'),Config::get('app.Admin_role'),Config::get('app.Management_role')]):([Config::get('app.Parent_role'),Config::get('app.Admin_role'),Config::get('app.Management_role')]);
                        
                        $user_ids =UserGroupsMapping::select('user_table_id','user_role')->whereIn('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->whereIn('user_role',$user_role)->get()->toArray();
                    }

                    $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->whereIn('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();

                    $user_list = array_merge($user_list,$user_ids);
                }
                else if($value == 6 || $value == 8) //section wise and class wise
                {
                    $visibleto_classes  = $request->visible_to; //section wise
                    if($value == 8) // class wise
                        $visibleto_classes = AcademicClassConfiguration::whereIn('class_id',$request->visible_to)->pluck('id')->toArray();

                    $group_id = UserGroups::whereIn('class_config',$visibleto_classes)->pluck('id')->toArray();
                    $user_ids =UserGroupsMapping::select('user_table_id','user_role')->whereIn('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'));

                    if($user->user_role == 2)
                    {
                        $user_ids = $user_ids->whereIn('user_role',([Config::get('app.Admin_role'),Config::get('app.Management_role')]));
                        $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'user_status'=>Config::get('app.Group_Active')])->whereIn('group_id',$group_id)->get()->toArray();
                    }
                    
                    $user_ids = $user_ids->get()->toArray();
                    $user_list = array_merge($user_list,$user_ids);
                }
                else if($value == 7) //students
                {
                    $group_id = $request->group_id;
                    $user_role = ([Config::get('app.Admin_role'),Config::get('app.Management_role')]);
                        
                    $user_list =UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->whereIn('user_role',$user_role)->get()->toArray();
                    $message_triggered_user = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();
                    $user_list = array_merge($user_list,$message_triggered_user);

                    $parent_ids =[];
                    // if(isset($request->visible_to) && !empty($request->visible_to)  && $value == 7 && $user->user_role != 2)//send message to specific students
                    //     $parent_ids = UserStudentsMapping::whereIn('student',$request->visible_to)->pluck('parent')->toArray();
                    $parent_ids = $request->visible_to;
                    if(!empty($parent_ids))
                    {
                        $user_ids = UserGroupsMapping::select('user_table_id','user_role')->whereIn('user_table_id',$parent_ids)->where('user_role',Config::get('app.Parent_role'))->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();
                        $user_list = array_merge($user_list,$user_ids);
                    }

                }
                else if($value == 9) //from admin to management
                {
                    $group_id = $request->group_id;
                    
                    if(isset($request->visible_to) && !empty($request->visible_to)) 
                    {
                        $role[] = ($user->user_role == Config::get('app.Management_role'))?Config::get('app.Admin_role'):Config::get('app.Management_role');

                        // send to individual users
                        $user_ids = UserGroupsMapping::select('user_table_id','user_role')->whereIn('user_table_id',$request->visible_to)->where(['user_role'=>$role,'user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray();                       
                    }
                    else //send to management 
                    {
                        $role = ($user->user_role == Config::get('app.Management_role'))?([Config::get('app.Admin_role'),Config::get('app.Management_role')]):([Config::get('app.Management_role')]);
                        $user_ids =UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->whereIn('user_role',$role)->get()->toArray();
                    }
                    
                    // Send one copy to mesage triggered user
                    $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray();
                    
                    $user_list = array_merge($user_list,$user_ids);
                }
            }
        }
        if(!empty($user_list))
            $this->insert_receipt_log(array_unique($user_list, SORT_REGULAR),$notification_id,$user_table_id);

        return response()->json(['message'=>'Notification inserted Successfully!...']);
    } 

    public function insert_receipt_log($user_list,$notification_id='',$user_table_id='')
    {
        // Get authorizated user details
        $user = auth()->user();

        Communications::where('id',$notification_id)->update(['delivered_users'=>count($user_list)]);
        $existing_userids = $communication_details= $player_ids = [];
        $message = Communications::where('id',$notification_id)->get()->first();
        // Insert communication message in notification log receipt tables(School DB)
        foreach ($user_list as $key => $value) {

            $unique_userid = UserAll::where('user_table_id',$value['user_table_id'])->where('user_role',$value['user_role'])->pluck('id')->first();

            if(!in_array($unique_userid, $existing_userids)) //check the duplicate user details is exists 
            {
                $player_id = Appusers::where('loginid',$unique_userid)->pluck('player_id')->first(); //get player id for the users
                $data[] = ([
                    'communication_id'=>$notification_id,
                    'user_table_id'=>$value['user_table_id'],
                    'user_role'=>$value['user_role'],
                    'message_status'=>1,
                    'view_type'=>($value['user_table_id'] == $user_table_id && $value['user_role'] == $user->user_role)?1:2,
                    'actioned_time'=>Carbon::now()->timezone('Asia/Kolkata'),
                    'player_id'=>$player_id
                ]); //form array to store notification details

                if($player_id!='') //check player id is not empty
                {
                    // $player_ids[$value['user_role']] =$player_id;
                    $player_ids[] =$player_id;
                    if($message->group_id ==1) //check managment group and sent notification msg
                    {
                        $school_name = SchoolProfile::where('id',$user->school_profile_id)->pluck('school_name')->first();
                        $user->user_table_id = $user_table_id;
                        $details = $this->user_details($user);
                        if($message->message_category == 1 || $message->message_category == 2 || $message->message_category == 3|| $message->message_category == 7 || $message->message_category == 10)
                            $chat_message = $school_name." A new communication is available in Management Group";
                        else if($message->message_category == 4)
                            $chat_message = $details['user_details']->first_name." has sent a document in management group";
                        else if($message->message_category == 5)
                            $chat_message = $details['user_details']->first_name." has sent an audio message";
                        else if($message->message_category == 6)
                            $chat_message = $details['user_details']->first_name." has sent a video link";
                        else if($message->message_category == 8)
                            $chat_message = $details['user_details']->first_name." has talked about something";
                        else if($message->message_category == 9)
                            $chat_message = "A new circular is initiated by ". $details['user_details']->first_name;
                    }
                    else if($message->group_id ==2) //check managment group and sent notification msg
                    {
                        $school_name = SchoolProfile::where('id',$user->school_profile_id)->pluck('school_name')->first();
                        $user->user_table_id = $user_table_id;
                        $details = $this->user_details($user);
                        if($message->message_category == 1)
                            $chat_message = $details['user_details']->first_name." has sent a text message";
                        else if($message->message_category == 2 || $message->message_category == 3)
                            $chat_message = $details['user_details']->first_name." has sent a images";
                        else if($message->message_category == 7 )
                            $chat_message = $details['user_details']->first_name." has sent a quotes";
                        else if($message->message_category == 10)
                            $chat_message = $details['user_details']->first_name." has sent a study material";
                        else if($message->message_category == 4)
                            $chat_message = $details['user_details']->first_name." has sent a document";
                        else if($message->message_category == 5)
                            $chat_message = $details['user_details']->first_name." has sent an audio message";
                        else if($message->message_category == 6)
                            $chat_message = $details['user_details']->first_name." has sent a video link";
                        else if($message->message_category == 8)
                            $chat_message = $details['user_details']->first_name." has talked about something";
                        else if($message->message_category == 9)
                            $chat_message = "A new circular is initiated by ". $details['user_details']->first_name;
                    }
                    else if($message->group_id>=3) //if staff and class group notification message
                    {
                        if($message->communication_type == 4) //for birthday 
                        {
                            $role = ($user->user_role == Config::get('app.Admin_role'))?'Admin':($user->user_role == Config::get('Management_role')?"Management":"Class Teacher");
                            $chat_message = $role.' sent birthday wishes';
                        }
                        else
                        {
                            $group_name = UserGroups::where('id',$message->group_id)->pluck('group_name')->first();
                            $user->user_table_id = $user_table_id;
                            $details = $this->user_details($user);
                            $chat_message = "A new message from ".$details['user_details']->first_name." in ".$group_name;
                        }
                    }
                    else
                        $chat_message = 'A new message is avaliable';
                }
                array_push($existing_userids,$unique_userid);
            }
        }
        CommunicationRecipients::insert($data); //inserted into log

        if(!empty($player_ids))
            $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$notification_id,'chat'); //trigger pushnotification function

        if($message->approval_status == '' || $message->approval_status == null) //check message for approval process and trigger notification 
        {
            $userids = UserAll::select('user_role','id')->where('user_table_id',$value['user_table_id'])->whereIn('user_role',[Config::get('app.Admin_role'),Config::get('app.Management_role')])->pluck('id')->toArray();
            if(!empty($userids))
            {
                $player_id = Appusers::whereIn('loginid',$userids)->pluck('player_id')->toArray(); //get player id for the users
                $group_name = UserGroups::where('id',$message->group_id)->pluck('group_name')->first();
                $chat_message = "A new message is waiting for your approval in ".$group_name;
                $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_id,$notification_id,'chat');
            }
        }

    }
    // Message Visible count
    public function message_visible_count(Request $request)
    {
        $group_id = $request->group_id;
        // Get authorizated user details
        $user = auth()->user();
        $user_list = $class_list = [];
        $category='';
        $user_list = UserGroupsMapping::where('group_id',$group_id)->where('user_status',Config::get('app.Group_Active'))->get()->toArray();

        if(!empty($user_list)) 
        {
            foreach ($user_list as $key => $value) {
                if($value['user_role'] == Config::get('app.Admin_role'))//check role and get current user id
                {
                    $user_table_id = UserAdmin::where(['id'=>$value['user_table_id']])->first();
                    $category = 'Admin';
                    $keyvalue='admin';
                }
                else if($value['user_role'] == Config::get('app.Management_role'))
                {
                    $user_table_id = UserManagements::where(['id'=>$value['user_table_id']])->first();
                    $category = 'Management';
                    $keyvalue='management';
                }
                else if($value['user_role'] == Config::get('app.Staff_role'))
                {
                    $user_table_id = UserStaffs::where(['id'=>$value['user_table_id']])->first();
                    if(!empty($user_table_id))
                        $category = UserCategories::where(['id'=>$user_table_id->user_category])->pluck('category_name')->first();
                    $keyvalue='staff';
                }
                else if($value['user_role'] == Config::get('app.Parent_role'))
                {
                    $user_table_id = UserParents::where(['id'=>$value['user_table_id']])->first();//fetch id from user all table to store notification triggered user
                    if(!empty($user_table_id))
                    {
                        $config_id = UserGroups::where('id',$group_id)->pluck('class_config')->first();
                        $user_category = UserCategories::where(['id'=>$user_table_id->user_category])->pluck('category_name')->first();
                        $user_category = (strtolower($user_category) == 'father')?'F/O':((strtolower($user_category) == 'mother')?'M/O':'G/O');
                        $student_ids_list = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
                        $student_id = UserStudents::whereIn('id',$student_ids_list)->where('class_config',$config_id)->get()->toArray();
                        $student_name = implode(array_column($student_id,'first_name'));
                        $category = $user_category.' '.$student_name; //combine category and name

                        $category = $user_category.' '.$student_name;
                    }
                    $keyvalue='parent';
                }

                $user_details[$keyvalue][]=([
                    'name'=>$user_table_id->first_name,
                    'mobile_number'=>$user_table_id->mobile_number,
                    'category'=>$category,
                    'id'=>$user_table_id->id,
                ]);
            }
            
        }
        return response()->json($user_details);
        exit();
    }

    // View Message
    public function view_messages(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();
        $userid = '';
        $group_id = [];
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();

        if($user->user_role == Config::get('app.Management_role'))
        {
            $userdetails = UserManagements::where(['user_id'=>$user->user_id])->get()->first();
            $group_type = UserGroups::where(['id'=>$request->group_id])->pluck('group_type')->first();
            $group_id = ([$request->group_id]);
            if($group_type == 2)
            {
                $whole_school_group = ([2]);
                $group_id = array_merge($group_id,$whole_school_group);
            }
        }
        else if($user->user_role == Config::get('app.Staff_role'))
        {
            $group_id = ([$request->group_id]);
            $userdetails = UserStaffs::where(['user_id'=>$user->user_id])->get()->first();
        }
        else if($user->user_role == Config::get('app.Admin_role'))
        {
            $userdetails = UserAdmin::where(['user_id'=>$user->user_id])->get()->first();
            $group_type = UserGroups::where(['id'=>$request->group_id])->pluck('group_type')->first();
            $group_id = ([$request->group_id]);
            if($group_type == 2)
            {
                $whole_school_group = ([2]);
                $group_id = array_merge($group_id,$whole_school_group);
            }
        }
        else if($user->user_role == Config::get('app.Parent_role'))
        {
            $userdetails = UserParents::where(['user_id'=>$user->user_id])->get()->first();
            $group_status = UserGroupsMapping::where('user_table_id',$userdetails->id)->where('user_role',$user->user_role)->where('group_id',$request->group_id)->pluck('user_status')->first();
            if($group_status == 1)
                $group_id = ([2,$request->group_id]);
            else
                $group_id = ([$request->group_id]);
        }

        $messages=$user_details= [];
        $unreadmessages = 0;
        if(!empty($userdetails) && !empty($group_id))
        {
            $class_messages = $student_messages = $management_messages = $newsevents_id_list= [];
            $user_details = ([
                'name'=>$userdetails->first_name,
                'last_seen'=>$user->last_login
            ]);
            $groupinfo = UserGroups::where('id',$request->group_id)->first();
            $visible_to = $groupinfo->class_config;
            // only chat messages list
            $chat_id_list = Communications::whereIn('group_id',$group_id)->whereIn('distribution_type',([1,2,3,4,5]));
            
            if($user->user_role == Config::get('app.Parent_role'))
            {
                $chat_id_list =$chat_id_list->whereNull('message_status')->orWhere('message_status',2);

                $student_messages = Communications::where('group_id',$request->group_id)->Where('visible_to', 'like', '%' .$userdetails->id. ',%')->where('distribution_type',7)->where('communication_type',1)->whereNull('message_status')->orWhere('message_status',2)->pluck('id')->toArray();
            }
            else if($user->user_role == Config::get('app.Admin_role') || $user->user_role == Config::get('app.Management_role') || $user->user_role == config::get('app.Staff_role'))
            {
                $student_messages = Communications::where('group_id',$request->group_id)->where('distribution_type',7)->where('communication_type',1)->pluck('id')->toArray();
            }
            if($visible_to!='')
            {
                $class_wise = Communications::where('group_id',2)->where('visible_to','like','%,'.$visible_to.',%')->where(['distribution_type'=>8,'communication_type'=>1]);

                $section_wise = Communications::where('group_id',2)->where('visible_to','like','%,'.$visible_to.',%')->where(['distribution_type'=>6,'communication_type'=>1]);


                if($user->user_role == Config::get('app.Parent_role'))
                {
                    $class_wise = $class_wise->whereNull('message_status')->orWhere('message_status',2);
                    $section_wise = $section_wise->whereNull('message_status')->orWhere('message_status',2);
                }

                $class_wise = $class_wise->pluck('id')->toArray();

                $section_wise = $section_wise->pluck('id')->toArray();

                $class_messages = array_merge($class_wise,$section_wise);

            }
            else if($request->group_id == 2)
                $class_messages = Communications::where(['distribution_type'=>6,'communication_type'=>1])->orwhere(['distribution_type'=>8,'communication_type'=>1])->where('group_id',2)->pluck('id')->toArray();

            $groupname = strtolower(str_replace(' ', '', $groupinfo->group_name));
            if(($user->user_role == Config::get('app.Admin_role') || $user->user_role == Config::get('app.Management_role')) && $groupname == 'admin-management' && $groupinfo->group_type == 1)
            {
                $visible_to = $userdetails->id;
                $usertableid = implode(',',UserAll::where('id',explode(',',$visible_to))->pluck('user_table_id')->toArray());
                $management_messages = Communications::where('group_id',$request->group_id)->where('communication_type',1)->where('distribution_type',9);
                if($user->user_role == Config::get('app.Parent_role'))
                        $management_messages = $management_messages->whereNull('message_status')->orWhere('message_status',2);

                $management_messages = $management_messages->pluck('id')->toArray();
            }

            $chat_id_list =$chat_id_list->Where(['visible_to'=>'all','communication_type'=>1])->pluck('id')->toArray();

            $remove_duplciate_bd_alert = [];
            if($request->group_id> 5 && $groupinfo->group_type == 2)
                $remove_duplciate_bd_alert = Communications::where('group_id',2)->where('communication_type',4)->pluck('id')->toArray();
            
            // remaining chat messages list
            $remaining_id_list =  Communications::whereIn('group_id',$group_id)->where('communication_type','!=',1)->whereNotIn('id',$remove_duplciate_bd_alert);
            
            if($user->user_role == Config::get('app.Parent_role'))
                $remaining_id_list =$remaining_id_list->whereNull('message_status')->orWhere('message_status',2);

            $remaining_id_list =$remaining_id_list->pluck('id')->toArray();

            $communication_id_list = array_unique(array_merge($chat_id_list,$remaining_id_list,$class_messages,$student_messages,$management_messages));

            $get_class_config= UserGroups::where('id',$request->group_id)->pluck('class_config')->first();

            if($request->group_id == 2 || $groupinfo->group_type == 2)
            {
                $newsevents_id_list = NewsEvents::where(function($query) use ($get_class_config){
                    $query->where('visible_to','like','%,'.$get_class_config.',%')
                        ->orWhere('visible_to','all');
                });
                if($user->user_role == Config::get('app.Parent_role'))
                    $newsevents_id_list =$newsevents_id_list->where('status',1);

                $newsevents_id_list =$newsevents_id_list->pluck('id')->toArray();
            }

            // Chat message ids
            $user_common_notificationids = CommunicationRecipients::where(['user_table_id'=>$userdetails->id,'user_role'=>$user->user_role]);
            
            $chat_ids = $user_common_notificationids->where('communication_type',1)->whereIn('communication_id',$communication_id_list)->get()->toArray();

            // newsevent ids
            $newsevent_ids = $user_common_notificationids->where('communication_type',2)->whereIn('communication_id',$newsevents_id_list)->get()->toArray();

            // homework ids
            $homework_ids =$user_common_notificationids->where('communication_type',4)->whereIn('communication_id',$communication_id_list)->get()->toArray();

            $notification_ids = array_merge($chat_ids,$newsevent_ids,$homework_ids);

            $datesort = array_column($notification_ids,'actioned_time');
            array_multisort($datesort, SORT_ASC, $notification_ids);

            // echo '<pre>';print_r($class_messages);;exit;
            $read_count = CommunicationRecipients::select(DB::raw('count(*) as count'),'communication_id','communication_type')->where(['message_status'=>Config::get('app.Read')])->groupBy('communication_id','communication_type')->get()->toArray(); //get read count based on notification id.
            // $readcount_data = array_column($read_count,'count','communication_id');
            foreach($read_count as $read_key => $read_value){
                $readcount_data[$read_value['communication_type']][$read_value['communication_id']] = $read_value['count'];
            }
            if(!empty($notification_ids))
            {
                $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');

                $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');
                $unreadmessages = $index = 0;
                foreach ($notification_ids as $key => $value) {
                    $fetch_sender_id = CommunicationRecipients::select('user_table_id','user_role')->where(['view_type'=>1,'communication_id'=>$value['communication_id']])->get()->first();
                    if($value['communication_type'] == 1) //chat or homework
                        $message_details = Communications::select('*')->where(['id'=>$value['communication_id']])->get()->first();
                    else if($value['communication_type'] == 2) //news and events
                        $message_details = NewsEvents::select('*')->where(['id'=>$value['communication_id']])->get()->first();
                    if($value['message_status'] == 1)
                        $unreadmessages++;
                    $sender_details =[];
                    $user = $designation ='';
                    $designation=$user=$message_category=$message=$caption=$important='';
                    if(!empty($fetch_sender_id))
                        $sender_details = $fetch_sender_id->userDetails();
                    else if($message_details && $message_details->actioned_by!='' && $message_details->message_category == 11) //for admin to management
                    {
                        $fetch_sender_id = UserAll::where('id',$message_details->actioned_by)->first();
                        if(!empty($fetch_sender_id))
                            $sender_details = $fetch_sender_id->userDetails();
                    }

                    if($value['communication_type'] == 1 && $message_details->communication_type == 2)
                    {
                        $user = '';
                        $designation = 'Home Work of the day '.date('d-M-Y',strtotime($message_details->actioned_time));
                    }
                    else if(!empty($sender_details) && $fetch_sender_id->user_role == Config::get('app.Management_role'))
                    {
                        $user = isset($management_categories[$sender_details['user_category']])?ucfirst($sender_details['first_name'])." ".$management_categories[$sender_details['user_category']]:ucfirst($sender_details['first_name']);
                        $designation = 'Management';
                    }
                    else if(!empty($sender_details) && $fetch_sender_id->user_role == Config::get('app.Staff_role'))
                    {
                        $user = ucfirst($sender_details['first_name']);
                        $designation = $staff_categories[$sender_details['user_category']];
                    }
                    else if(!empty($sender_details) && $fetch_sender_id->user_role == Config::get('app.Admin_role'))
                    {
                        $user = ucfirst($sender_details['first_name']);
                        $designation = 'Admin';
                    }
                    else if(!empty($sender_details) && $fetch_sender_id->user_role == Config::get('app.Parent_role'))
                    {
                        $user = ucfirst($sender_details['first_name']);
                        $designation = 'F/O Test';
                    }

                    
                    if($value['communication_type'] == 1)
                    {
                        if($message_details->distribution_type==3)
                            $visibility = 'Visible to Everyone';
                        else if($message_details->distribution_type==4)
                            $visibility = 'Visible to Staffs';
                        else if($message_details->distribution_type==5)
                            $visibility = 'Visible to Parents';
                        else if($message_details->distribution_type==6 || $message_details->distribution_type==8)// 6-section wise,8-class wise 
                        {
                            $class_sections = AcademicClassConfiguration::whereIn('id',explode(',',$message_details->visible_to))->get();
                            $class_section_names = [];
                            foreach($class_sections as $class_sec_key => $class_sec_value)
                            {
                                if($class_sec_value!='')
                                {
                                    $class_section_names[] = $class_sec_value->classsectionName();
                                }
                            }
                            if(!empty($class_section_names))
                                $visibility = 'Visible to '.implode(',',$class_section_names);     
                            else
                                $visibility = 'Visible to Everyone';                
                        }
                        else if($message_details->distribution_type==7)
                        {
                            $parents_ids = explode(',',$message_details->visible_to);
                            $student_parent_names = [];
                            foreach($parents_ids as $parents_key => $parents_value)
                            {
                                if($parents_value!='')
                                {
                                    $parent_details = UserParents::where('id',$parents_value)->get()->first();
                                    $student_id = UserStudentsMapping::where(['parent'=>$parent_details->id])->pluck('student')->toArray();
                                    $student_details = UserStudents::where('id',$student_id)->get()->first();
                                    $user_category = ($parent_details->user_category == Config::get('app.Father'))?'F/O':($parent_details->user_category == Config::get('app.Mother')?'M/O':'G/O');
                                    $student_parent_names[] = $student_details->first_name.' '. $user_category.'  '.$parent_details->first_name;
                                }
                            }

                            if(!empty($student_parent_names))
                                $visibility = 'Visible to '.implode(',',$student_parent_names);     
                            else
                                $visibility = 'Visible to Everyone';                
                        } 
                        else if($message_details->distribution_type==9) //admin to managment
                        {
                            $managements = explode(',',$message_details->visible_to);
                            $management_names = [];
                            foreach($managements as $management_key => $management_value)
                            {
                                if($management_value!='')
                                {
                                    $management_details = UserManagements::where('id',$management_value)->get()->first();
                                    if(!empty($management_details))
                                        $management_names[] = $management_details->first_name;
                                }
                            }

                            if(!empty($management_names))
                                $visibility = 'Visible to '.implode(',',$management_names);     
                            else
                                $visibility = 'Visible to Everyone';                
                        }               
                  

                        $message_category='';                 
                        if($message_details->message_category == 1)
                            $message_category = 'Text';
                        else if($message_details->message_category == 2)
                            $message_category = 'ImageWithCaption';
                        else if($message_details->message_category == 3)
                            $message_category = 'Images';
                        else if($message_details->message_category == 4)
                            $message_category = 'Document';
                        else if($message_details->message_category == 5)
                            $message_category = 'Audio';
                        else if($message_details->message_category == 6)
                            $message_category = 'Video';
                        else if($message_details->message_category == 7)
                            $message_category = 'Quotes';
                        else if($message_details->message_category == 8)
                            $message_category = 'ManagementSpeaks';
                        else if($message_details->message_category == 9)
                            $message_category = 'Circular';
                        else if($message_details->message_category == 10)
                            $message_category = 'StudyMaterial';
                        else if($message_details->message_category == 11)
                            $message_category = 'Attendance'; 

                        if($message_details->communication_type == 2)
                            $message_category = 'Homework';
                    }
                    else
                    {
                        $visibility = 'Visible to Everyone';
                        $message_category = 'News & Events';
                    }
                    $exploded_config = explode(',',$message_details->visible_to);
                    if($value['communication_type'] == 1 || ($value['communication_type'] == 2 && (in_array($get_class_config,$exploded_config) || $message_details->visible_to == 'all')))
                    {    
                        $messages[$index] = ([
                            'notification_id'=>$value['communication_id'],
                            'user'=>$user,
                            'designation'=>$designation,
                            'view_type'=>$value['view_type'],//1-sender,2-Receiver
                            'message_category'=>$message_category,
                            'message_status'=>($message_details->message_status != null)?$message_details->message_status:3,//1-Deleted,2-Edited,3-active
                            'date_time'=>($message_details->approved_time ==null)?$message_details->actioned_time:$message_details->approved_time,
                            'visiblity'=>$visibility,
                            'important'=>$message_details->important,//0-no,1-yes
                            'communication_type'=>($value['communication_type'] ==1)?$message_details->communication_type:3,//1-chat,2-homework,3-news and events
                            'caption'=>$message_details->caption_message,
                            'distribution_type'=>$message_details->distribution_type,
                            'approval_status'=>($message_details->approval_status == null)?0:$message_details->approval_status,//0-waiting for approval,1-approval,2-denied
                            'read_count'=>(isset($readcount_data[$value['communication_type']]) && isset($readcount_data[$value['communication_type']][$value['communication_id']]))?$readcount_data[$value['communication_type']][$value['communication_id']]:0,
                            'edited'=>$message_details['edited'],

                        ]);
                        if($message_details->message_category == 6 && $message_details->communication_type == 1)
                            $messages[$index]['message']=''; 
                        else
                            $messages[$index]['message']=$message_details->chat_message; 
                        if($message_details->communication_type == 2)
                        {
                            if(!empty($message_details))
                            {
                                $subject_details = AcademicSubjects::where('id',$message_details->subject_id)->get()->first();
                                $messages[$index]['subject_id'] = isset($message_details->subject_id)?$message_details->subject_id:0;
                                $messages[$index]['subject_name'] =isset($subject_details->subject_name)?$subject_details->subject_name:'';
                                $messages[$index]['short_name'] = isset( $subject_details->short_name)?$subject_details->short_name:'';
                            }

                        }
                        else  if($value['communication_type'] == 2)
                        {
                            $messages[$index]['title'] = isset($message_details->title)?$message_details->title:0;
                            $messages[$index]['description'] =isset($message_details->description)?$message_details->description:'';
                            $messages[$index]['news_events_category'] =isset($message_details->news_events_category)?$message_details->news_events_category:'';
                            $messages[$index]['module_type'] =isset($message_details->module_type)?$message_details->module_type:'';
                            $messages[$index]['event_date'] =isset($message_details->event_date)?$message_details->event_date:null;
                            $messages[$index]['event_time'] =isset($message_details->event_time)?$message_details->event_time:null;
                            $messages[$index]['important'] =0;
                            $messages[$index]['date_time']=($message_details->published_time ==null)?$message_details->published_time:$message_details->published_time;
                            $messages[$index]['message_status'] = $message_details->status;
                        }

                        if($value['view_type'] == 1)
                        {
                            $watched = CommunicationRecipients::select('id')->where(['message_status'=>2,'communication_id'=>$value['communication_id'],'communication_type'=>$value['communication_type']])->get()->count();
                            $messages[$index]['delivered_users'] = $message_details['delivered_users'];
                            $messages[$index]['watched'] = $watched;
                        }
                        if($message_details->message_category == 7 )
                            $messages[$index]['sub_title'] = 'Quotes';
                        if($message_details->message_category == 8 )
                            $messages[$index]['sub_title'] = 'Management Speaks';
                        if($message_details->message_category == 9 )
                            $messages[$index]['sub_title'] = 'Circular';

                        if($value['communication_type']  == 1)
                        {
                            if($message_details->message_category == 3 || $message_details->message_category == 2 || $message_details->message_category == 4 || $message_details->message_category == 10 || $message_details->message_category == 5 )
                            {
                                $images = [];
                                $images_list = CommunicationAttachments::where(['communication_id'=>$value['communication_id']])->get()->toArray();
                                if(!empty($images_list))
                                {
                                    foreach ($images_list as $image_key => $image_value) {
                                        $images[]= $image_value['attachment_location'].''.$image_value['attachment_name'];
                                    }
                                    
                                    $messages[$index]['images'] = $images;
                                }
                            }
                            else if($message_details->message_category == 6 && $message_details->communication_type == 1)
                                $messages[$index]['images']=explode(',',$message_details->chat_message); 
                        }
                        else
                        {
                            $images = [];
                            $images_list = NewsEventsAttachments::where(['news_events_id'=>$value['communication_id']])->get()->toArray();
                            if(!empty($images_list))
                            {
                                foreach ($images_list as $image_key => $image_value) {
                                    $images[]= $image_value['attachment_location'].'/'.$image_value['attachment_name'];
                                }
                                
                                $messages[$index]['images'] = $images;
                            }
                        }
                        $index++;
                    }
                      
                }
            }
            echo json_encode(['message'=>$messages,'user_details'=>$user_details,'unreadmessages'=>$unreadmessages]);exit();        
        }
        return response()->json('No Messages');
    }

    // Delete Message
    public function delete_messages(Request $request)
    {
        $user = auth()->user();
        if($request->group_id=='')
            return response()->json(['error'=>'Group id is missing']);
        else
        {
            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_data = UserAdmin::where(['user_id'=>$user->user_id])->get()->first();
            else if($user->user_role == Config::get('app.Management_role'))
                $user_data = UserManagements::where(['user_id'=>$user->user_id])->get()->first();
            else if($user->user_role == Config::get('app.Staff_role'))
                $user_data = UserStaffs::where(['user_id'=>$user->user_id])->get()->first();
            $user_table_id = $user_data->id;
            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();
            $notification_id = $request->notification_id;
            if($request->communication_type == 3)
            {
                $communication_data = NewsEvents::where(['id'=>$notification_id])->get()->first();
                $delete_status = NewsEvents::where(['id'=>$notification_id])->update(['status'=>3,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);

            }
            else
            {

                $communication_data = Communications::where(['group_id'=>$request->group_id,'id'=>$notification_id])->get()->first();
                // foreach ($notification_id as $key => $value) {
                $delete_status = Communications::where(['group_id'=>$request->group_id,'id'=>$notification_id])->update(['message_status'=>Config::get('app.Deleted'),'deleted_by'=>$userall_id,'deleted_time'=>Carbon::now()->timezone('Asia/Kolkata')]);

                // }
            }
            $chat_message ='';
            if($request->communication_type == 3)
            {
                $module_type = ($communication_data->module_type==1)?'a News':'an Events';
                $chat_message = $user_data->first_name." has deleted ".$module_type;
            }
            else
            {

                $group_name = UserGroups::where('id',$request->group_id)->pluck('group_name')->first();
                if($user->user_role == Config::get('app.Management_role'))
                    $chat_message = $user_data->first_name." has deleted a communication in ".$group_name;
                if($user->user_role == Config::get('app.Admin_role'))
                    $chat_message = "Admin has deleted a communication in ".$group_name;
            }
            $player_ids[] = Appusers::where('loginid',$communication_data->created_by)->pluck('player_id')->first();
            if(!empty($player_ids) && $chat_message!='')
                $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$notification_id,'chat'); //trigger pushnotification function
            return response()->json(['success'=>'Deleted Successfully!...']);
        }
    }

    // Approval Status
    public function message_approval(Request $request)
    {
        // Add rules to the Store Message form
        $validator = Validator::make($request->all(), [
            'approval_status' => 'required', //1- approval,2-denied
        ]);
        // Validate form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_data = UserAdmin::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_data = UserManagements::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_data = UserStaffs::where(['user_id'=>$user->user_id])->get()->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_data = UserParents::where(['user_id'=>$user->user_id])->get()->first();//fetch id from user all table to store notification triggered user
        $user_table_id = $user_data->id;
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        // foreach ($request->notification_id as $key => $value) {
            $communication_data = Communications::where(['id'=>$request->notification_id])->get()->first();
            $player_ids[] = Appusers::where('loginid',$communication_data->created_by)->pluck('player_id')->first();
            if($request->approval_status == 1)
            {
                $notification_triggered_user = UserAll::select('user_role','user_table_id')->where(['id'=>$communication_data->created_by])->first();
                if($communication_data->distribution_type == 3) // Everyone
                    $user_ids = UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$communication_data->group_id)->where('user_status',Config::get('app.Group_Active'))->whereIn('user_role',([Config::get('app.Staff_role'),Config::get('app.Parent_role')]))->get()->toArray();
                else if($communication_data->distribution_type == 4 || $communication_data->distribution_type == 5)
                {    
                    $user_role = ($communication_data->distribution_type == 4)?2:3;
                    $user_ids = UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$communication_data->group_id)->where('user_status',Config::get('app.Group_Active'))->where('user_role',$user_role)->get()->toArray();                    
                }
                else if($communication_data->distribution_type == 6 || $communication_data->distribution_type == 8) //6-section wise , 8-class wise
                {
                    $group_ids = UserGroups::whereIn('class_config',explode(',',$communication_data->visible_to))->pluck('id')->toArray();
                    $user_ids = UserGroupsMapping::select('user_table_id','user_role')->whereIn('group_id',$group_ids)->where('user_status',Config::get('app.Group_Active'))->whereIn('user_role',([Config::get('app.Staff_role'),Config::get('app.Parent_role')]))->get()->toArray();
                }
                else if($communication_data->distribution_type == 7)
                    $user_ids = UserGroupsMapping::select('user_table_id','user_role')->where('group_id',$communication_data->group_id)->where('user_status',Config::get('app.Group_Active'))->where('user_table_id',explode(',',$communication_data->visible_to))->whereIn('user_role',([Config::get('app.Parent_role')]))->get()->toArray();
                                        
                $user_ids = array_unique($user_ids, SORT_REGULAR);


                foreach ($user_ids as $user_key => $user_value) {
                   if($user_value['user_role'] != $notification_triggered_user->user_role || $user_value['user_table_id'] != $notification_triggered_user->user_table_id)
                        $user_list[] = $user_value;
                }
                Communications::where('id',$request->notification_id)->update(['approval_status'=>$request->approval_status,'delivered_users'=>count($user_list)+$communication_data->delivered_users,'approved_by'=>$userall_id,'approved_time'=>Carbon::now()->timezone('Asia/Kolkata')]);

                if(count($user_list)>0)
                    $this->insert_receipt_log($user_list,$communication_data->id,$user_table_id);
                if($user->user_role == Config::get('app.Management_role'))
                    $chat_message = $user_data->first_name." has approved your ".Config::get('app.MessageCategories.'.$communication_data->message_category);
                if($user->user_role == Config::get('app.Admin_role'))
                    $chat_message = "Admin has approved your ".Config::get('app.MessageCategories.'.$communication_data->message_category);

                
                $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$request->notification_id,'chat'); //trigger pushnotification function

                return response()->json(['message'=>'Approved Successfully!...']);

            }
            else
            {
                Communications::where('id',$request->notification_id)->update(['approval_status'=>$request->approval_status,'approved_by'=>$userall_id,'approved_time'=>Carbon::now()->timezone('Asia/Kolkata')]);

                if($user->user_role == Config::get('app.Management_role'))
                    $chat_message = $user_data->first_name." has denied your ".Config::get('app.MessageCategories.'.$communication_data->message_category);
                if($user->user_role == Config::get('app.Admin_role'))
                    $chat_message = "Admin has denied your ".Config::get('app.MessageCategories.'.$communication_data->message_category);

                
                $delivery_details = APIPushNotificationController::SendNotification($chat_message,$player_ids,$request->notification_id,'chat'); //trigger pushnotification function

                return response()->json(['message'=>'Denied Successfully!...']);
            }
        // }

    }

    // Read count api
    public function message_read(Request $request)
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
        if($request->message_status == 2)
        {
            $communication_type = $request->communication_type == 3?2:1;
            $altercommunication_type = $request->communication_type == 3?1:2;
            $actioned_time = CommunicationRecipients::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'message_status'=>1])->where('communication_id','<=',$request->notification_id)->where('communication_type',$communication_type)->orderBy('actioned_time','DESC')->pluck('actioned_time')->first();
            if($actioned_time!='')
            {
                $get_news_events_id = CommunicationRecipients::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'message_status'=>1])->where('actioned_time','<=',$actioned_time)->where('communication_type',$altercommunication_type)->orderBy('actioned_time','DESC')->pluck('communication_id')->first();
                if($get_news_events_id!='')
                {
                    CommunicationRecipients::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'message_status'=>1])->where('communication_id','<=',$get_news_events_id)->where('communication_type',$altercommunication_type)->update(['message_status'=>$request->message_status,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
                }
            }
            CommunicationRecipients::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'message_status'=>1])->where('communication_id','<=',$request->notification_id)->where('communication_type',$communication_type)->update(['message_status'=>$request->message_status,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
        }
        else
            CommunicationRecipients::where(['communication_id'=>$request->notification_id,'user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->update(['message_status'=>$request->message_status,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
        return response()->json(['message'=>'inserted Successfully!...']);
    }   

    // Message Info
    Public function message_info(Request $request)
    {
        // Add rules to the Store Message form
        $validator = Validator::make($request->all(), [
            'group_id' => 'required', 
            'notification_id' => 'required', 
            'communication_type'=>'required',
        ]);
        // Validate form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Get authorizated user details
        $user = auth()->user();

        if($request->communication_type == 3)
            $message_details = NewsEvents::where(['id'=>$request->notification_id])->get()->first();
        else
            $message_details = Communications::where(['id'=>$request->notification_id])->get()->first();
        if(!empty($message_details))
        {
            $initated_user_details = UserAll::where(['id'=>$message_details->created_by])->get()->first();
            if(!empty($initated_user_details))
            {
                $initated_users = $this->user_details($initated_user_details);
                $approver_user_details=[];
                if($message_details->approved_by!='' && $message_details->approved_by!= null)
                {
                    $approver_users = UserAll::where(['id'=>$message_details->approved_by])->get()->first();
                    $approver_user_details = $this->user_details($approver_users);
                }
                if($message_details->message_status==Config::get('app.Deleted') && $message_details->deleted_by!='' && $message_details->deleted_by!=null)
                {
                    $deleted_users = UserAll::where(['id'=>$message_details->deleted_by])->get()->first();
                    $deleted_user_details = $this->user_details($deleted_users);
                }
               
                $message_info = ([
                    'initated_by'=>$initated_users['user_details']->first_name,
                    'initated_user_category'=>$initated_users['user_category'],
                    'initated_on'=>$message_details->created_time,
                    'approved_by'=>(count($approver_user_details)>0)?$approver_user_details['user_details']->first_name:'',
                    'approver_user_category'=>(count($approver_user_details)>0)?$approver_user_details['user_category']:'',
                    'area'=>'N/A',
                    'approved_at'=>(count($approver_user_details)>0)?$message_details->approved_time:null,
                    'deleted_by'=>(!empty($deleted_user_details))?$deleted_user_details['user_details']->first_name:'',
                    'deleted_on'=>($message_details->deleted_time!=null)?$message_details->deleted_time:null
                ]);
                echo json_encode(["message_info"=>$message_info]);exit();  
            }      
        }
        return response()->json(['status'=>false,'message'=>'No Messages']);
    }

    public function user_details($userdetails)
    {
        $user_category = '';
        $user_details = [];
        if($userdetails->user_role == Config::get('app.Management_role'))
        {
            $user_details = UserManagements::where(['id'=>$userdetails->user_table_id])->get()->first();
            if(!empty($user_details))
            {
                $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');
                $user_category = $management_categories[$user_details->user_category];
            }
        }
        else if($userdetails->user_role == Config::get('app.Admin_role'))//check role and get current user id
        {
            $user_details = UserAdmin::where(['id'=>$userdetails->user_table_id])->get()->first();
            $user_category = 'Admin';
        }
        else if($userdetails->user_role == Config::get('app.Staff_role'))
        {
            $user_details = UserStaffs::where(['id'=>$userdetails->user_table_id])->get()->first();
            if(!empty($user_details))
            {
                $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');
                $user_category = isset($user_details->user_category)?$staff_categories[$user_details->user_category]:'';
            }
        }
        else if($userdetails->user_role == Config::get('app.Parent_role'))
        {
            $user_category = 'Parent';
            $user_details = UserParents::where(['id'=>$userdetails->user_table_id])->get()->first();//fetch id from user all table to store notification triggered user
        }
        return (['user_details'=>$user_details,'user_category'=>$user_category]);
    }

    public function array_user_details($userdetails)
    {
        $user_category = '';
        $user_details = [];
        if($userdetails['user_role'] == Config::get('app.Management_role'))
        {
            $user_details = UserManagements::where(['id'=>$userdetails['user_table_id']])->get()->first();
            if(!empty($user_details))
            {
                $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');
                $user_category = $management_categories[$user_details['user_category']];                
            }
        }
        else if($userdetails['user_role'] == Config::get('app.Admin_role'))//check role and get current user id
        {
            $user_details = UserAdmin::where(['id'=>$userdetails['user_table_id']])->get()->first();
            $user_category = 'Admin';
        }
        else if($userdetails['user_role'] == Config::get('app.Staff_role'))
        {
            $user_details = UserStaffs::where(['id'=>$userdetails['user_table_id']])->get()->first();
            if(!empty($user_details))
            {
                $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');
                $user_category = isset($user_details['user_category'])?$staff_categories[$user_details['user_category']]:'';
            }
        }
        else if($userdetails['user_role'] == Config::get('app.Parent_role'))
        {
            $user_category = 'Parent';
            $user_details = UserParents::where(['id'=>$userdetails['user_table_id']])->get()->first();//fetch id from user all table to store notification triggered user
        }
        return (['user_details'=>$user_details,'user_category'=>$user_category]);
    }

    public function message_delivery_details(Request $request)
    {
        // Add rules to the Store Message form
        $validator = Validator::make($request->all(), [
            'group_id' => 'required', 
            'notification_id' => 'required', 
        ]);
        // Validate form
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Get authorizated user details
        $user = auth()->user();
        if($request->communication_type != 3)
            $message_details = Communications::where(['id'=>$request->notification_id])->get()->first();
        else if($request->communication_type == 3)
            $message_details = NewsEvents::where(['id'=>$request->notification_id])->get()->first();
        if(!empty($message_details))
        {
            $delivery_details=CommunicationRecipients::where(['communication_id'=>$request->notification_id]);
            if($request->communication_type == 3)
                $delivery_details=$delivery_details->where('communication_type',2);
            else
                $delivery_details=$delivery_details->where('communication_type',1);
            $delivery_details=$delivery_details->get();
            if(!empty($delivery_details))
            {
                $delivered_users=[];
                $index=0;
                foreach ($delivery_details as $key => $value) {
                    $class=$class_name = $section_name='';
                    $data= $this->user_details($value);
                    if($value['user_role'] == Config::get('app.Admin_role'))//check role and get current user id
                        $category = 'Admin';
                    else if($value['user_role'] == Config::get('app.Management_role'))
                        $category = 'Management';
                    else if($value['user_role'] == Config::get('app.Staff_role'))
                    {
                        $user_table_id = UserStaffs::where(['id'=>$value['user_table_id']])->first();
                        if(!empty($user_table_id))
                            $category = UserCategories::where(['id'=>$user_table_id->user_category])->pluck('category_name')->first();
                    }
                    else if($value['user_role'] == Config::get('app.Parent_role'))
                    {
                        if(!empty($data['user_details']) && isset($data['user_details']->user_id))
                        {
                            $user_table_id = UserParents::where(['user_id'=>$data['user_details']->user_id])->first();//fetch id from user all table to store notification triggered user
                            if(!empty($user_table_id))
                            {
                                $config_id = UserGroups::where('id',$request->group_id)->pluck('class_config')->first();
                                $user_category = UserCategories::where(['id'=>$user_table_id->user_category])->pluck('category_name')->first();
                                $user_category = (strtolower($user_category) == 'father')?'F/O':((strtolower($user_category) == 'mother')?'M/O':'G/O');
                                $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
                                $student_name = UserStudents::whereIn('id',$student_id)->first();

                                $category = $user_category.' '.(isset($student_name->first_name)?$student_name->first_name:'');
                                if(isset($student_name->class_config))
                                {
                                    $class_section_details = AcademicClassConfiguration::where(['id'=>$student_name->class_config])->get()->first();
                                    if(isset($class_section_details['class_id']) && $class_section_details['class_id']!='')
                                        $class_name = AcademicClasses::where('id',$class_section_details['class_id'])->pluck('class_name')->first();
                                    if(isset($class_section_details['section_id']) && $class_section_details['section_id'])
                                        $section_name = AcademicSections::where('id',$class_section_details['section_id'])->pluck('section_name')->first();
                                }
                                if($class_name != '' && $section_name!='')
                                    $class = $class_name." ".$section_name;
                                else
                                    $class = '';
                            }
                        }
                    }
                    if(!empty($data['user_details']) && isset($data['user_details']->user_id))
                    {
                        $delivered_users[$index]=([
                            'name'=>$data['user_details']->first_name,
                            'designation'=>$category,
                            'mobile_no'=>$data['user_details']->mobile_number,
                            'message_status'=>$value['message_status'],//1-delivered,2-Read,3-Actioned,
                            'view_time'=>$value['actioned_time'],
                            'class'=>$class
                        ]);
                        $index++;
                    }
                }
                echo json_encode(["delivered_users"=>$delivered_users]);exit();  
            }
            return response()->json(['status'=>false,'message'=>'No Recipients']);exit();
        }
        return response()->json(['status'=>false,'message'=>'No Messages']);
    }

    public function view_profile(Request $request)
    {
        // Get authorizated user details
        $user = $userdata = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        if($request->user_role!='' && $request->id!='')
        {
            if($request->user_role == Config::get('app.Staff_role'))
                $data = UserStaffs::where(['id'=>$request->id])->get()->first();
            else if($request->user_role == Config::get('app.Parent_role'))
                $data = UserParents::where(['id'=>$request->id])->get()->first();
             $role= $request->user_role;

            $userdata = SchoolUsers::where(['user_id'=>$data->user_id,'user_role'=>$request->user_role])->get()->first();
        }
        else
        {
            $role= $user->user_role;
            if($user->user_role == Config::get('app.Management_role'))
                $data = UserManagements::where(['user_id'=>$user->user_id])->get()->first();
            else if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $data = UserAdmin::where(['user_id'=>$user->user_id])->get()->first();
            else if($user->user_role == Config::get('app.Staff_role'))
                $data = UserStaffs::where(['user_id'=>$user->user_id])->get()->first();
            else if($user->user_role == Config::get('app.Parent_role'))
                $data = UserParents::where(['user_id'=>$user->user_id])->get()->first();
        }

        if(!empty($data))
        {
            $user_details=([
                'name'=>$data->first_name,
                'mobile_no'=>$data->mobile_number,
                'profile'=>$data->profile_image,
                "last_login"=>($userdata->last_login!=null)?$userdata->last_login:null,
            ]);

            if( $role== Config::get('app.Management_role') || $role == Config::get('app.Admin_role'))
                $user_details['designation'] = $data['user_category'];
            else if($role == Config::get('app.Staff_role'))
            {
                $user_details['designation'] = $data['user_category'];
                $user_details['dob'] = date('d-m-Y',strtotime($data['dob']));
                $user_details['doj'] = date('d-m-Y',strtotime($data['doj']));
                $user_details['employee_no'] = $data['employee_no'];
                $department_name = AcademicSubjects::where('id',$data['department'])->pluck('subject_name')->first();
                $user_details['department'] = $department_name;
                $classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id','class_teacher')->where('class_teacher',$data['id'])->get()->first();
                $user_details['class'] = (!empty($classessections))?$classessections->classsectionName():'';

            }
            else if($role == Config::get('app.Parent_role'))
            {
                $class_name =$section_name ='';
                $category = ($data['user_category']==1)?'F/O':($data['user_category']==2?'M/O':'G/O');
                if(isset($request->student_id) && $request->student_id!='')
                    $student_id = $request->student_id;
                else
                    $student_id =UserStudentsMapping::where(['parent'=>$data->id])->pluck('student')->first();
                $student_details = UserStudents::select('first_name','class_config')->where(['id'=>$student_id])->get()->first();
                $user_details['name'] = $data->first_name." ".$category." ".$student_details['first_name'];
                $user_details['dob'] = $student_details['dob'];
                $user_details['admission_number'] = $student_details['admission_number'];
                $classessections =[];
                if(isset($student_details['class_config']))
                    $classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id','class_teacher')->where('id',$student_details['class_config'])->get()->first();
                $user_details['class'] = (!empty($classessections))?$classessections->classsectionName():'';
                $user_details['class_teacher'] = (!empty($classessections))?UserStaffs::where('id',$classessections->class_teacher)->pluck('first_name')->first():'';
                $class_section_details = AcademicClassConfiguration::where(['id'=>$student_details['class_config']])->get()->first();
                if(isset($class_section_details['class_id']) && $class_section_details['class_id']!='')
                    $class_name = AcademicClasses::where('id',$class_section_details['class_id'])->pluck('class_name')->first();
                if(isset($class_section_details['section_id']) && $class_section_details['section_id'])
                    $section_name = AcademicSections::where('id',$class_section_details['section_id'])->pluck('section_name')->first();
                if($class_name != '' && $section_name!='')
                    $user_details['designation'] = $class_name." ".$section_name;
                else
                    $user_details['designation'] = null;
            }
        }
        else
        {
             $user_details=([
                'name'=>null,
                'mobile_no'=>null,
                'profile'=>null,
                "designation"=> null,
                "last_login"=>null,
                "dob"=>null,
                "doj"=>null,
                "department"=>null,
                "employee_no"=>null,
                "admission_number"=>null,
                "class"=>null,
                "class_teacher"=>null,

            ]);
        }
        
        echo json_encode($user_details);exit();
    }

    public function save_profile(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();
        $user->last_login = Carbon::now()->timezone('Asia/Kolkata');
        $user->save();
        $profile_image='';
        if($request->hasfile('profile_image')) {

            $schoolcode = $school_profile = SchoolProfile::where(['id'=>$user['school_profile_id']])->get()->first();//get school code from school profile
            $path = public_path('uploads/'.$school_profile['school_code'].'/profile_images');//

            if(!File::isDirectory($path)){ //check path already exists
                File::makeDirectory($path, 0777, true, true);
            }

            $name = explode('.',$request->file('profile_image')->getClientOriginalName());
            $names = $name[0].time().'.'.$name[1];
            $request->file('profile_image')->move(public_path().'/uploads/'.$school_profile['school_code'].'/profile_images/', $names);  
            $profile_image= url('/').'/uploads/'.$school_profile['school_code'].'/profile_images/'.$names;
        }

        $user_details = ([
            'first_name'=>$request->first_name,
            'profile_image'=>$profile_image,
        ]);
        if($user->user_role == Config::get('app.Management_role'))
            UserManagements::where('user_id',$user->user_id)->update($user_details);
        else if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            UserAdmin::where('user_id',$user->user_id)->update($user_details);
        else if($user->user_role == Config::get('app.Staff_role'))
            UserStaffs::where('user_id',$user->user_id)->update($user_details);
        else if($user->user_role == Config::get('app.Parent_role'))
            UserParents::where('user_id',$user->user_id)->update($user_details);

        return response()->json('Profile Updated Successfully!..');exit();
    }
    // approval pending list of data
    public function approval_action_required(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();
        $approval_message = $approval_data = [];
        $designation =$logged_in_staff ='';
        if($user->user_role == Config::get('app.Management_role') || $user->user_role == Config::get('app.Admin_role') || $user->user_role == Config::get('app.Staff_role'))
        {

            $approval_data = Communications::whereNull('approval_status')->whereNull('deleted_by');
            if($user->user_role == Config::get('app.Staff_role'))
            {
                $logged_in_staff = UserStaffs::where('user_id',$user->user_id)->pluck('id')->first();
                $approval_data = $approval_data->where('communication_type',2);
            }

            $approval_data =  $approval_data->orderBy('actioned_time','DESC')->get()->toArray();
        }
        if(!empty($approval_data))
        {
            $index = 0;
            foreach ($approval_data as $key => $value) {
                if(($value['communication_type'] == 2 && (date('Y-m-d',strtotime($value['actioned_time'])) >= date("Y-m-d",strtotime(Carbon::now()->timezone('Asia/Kolkata')))) ) || $value['communication_type'] == 1)
                {
                    if($value['communication_type'] == 2)
                        $fetch_sender_id = UserAll::where('id',$value['created_by'])->first(); 
                    else
                        $fetch_sender_id = CommunicationRecipients::select('user_table_id','user_role')->where(['view_type'=>1,'communication_id'=>$value['id']])->get()->first();
                    if(!empty($fetch_sender_id))
                    {
                        $sender_details = $fetch_sender_id->userDetails();
                        if(!empty($sender_details))
                        {
                            $group_details = UserGroups::select('class_config','group_name')->where('id',$value['group_id'])->get()->first();
                            if($fetch_sender_id->user_role == Config::get('app.Management_role'))
                            {
                                $user = isset($management_categories[$sender_details['user_category']])?ucfirst($sender_details['first_name'])." ".$management_categories[$sender_details['user_category']]:ucfirst($sender_details['first_name']);
                                $designation = 'Management';
                            }
                            else if($fetch_sender_id->user_role == Config::get('app.Staff_role'))
                            {
                                $user = ucfirst($sender_details['first_name']);
                                $designation='Staff';
                            }
                            else if($fetch_sender_id->user_role == Config::get('app.Admin_role'))
                            {
                                $user = ucfirst($sender_details['first_name']);
                                $designation = 'Admin';
                            }
                            else if($fetch_sender_id->user_role == Config::get('app.Parent_role'))
                            {
                                $user = ucfirst($sender_details['first_name']);
                                $user_table_id = UserParents::where(['user_id'=>$sender_details['user_details']->user_id])->first();//fetch id from user all table to store notification triggered user
                                if(!empty($user_table_id))
                                {
                                    $user_category = UserCategories::where(['id'=>$user_table_id->user_category])->pluck('category_name')->first();
                                    $user_category = (strtolower($user_category) == 'father')?'F/O':((strtolower($user_category) == 'mother')?'M/O':'G/O');
                                    $student_id = UserStudentsMapping::where(['parent'=>$user_table_id->id])->pluck('student')->toArray();
                                    $student_name = UserStudents::whereIn('id',$student_id)->pluck('first_name')->first();

                                    $designation = $user_category.' '.$student_name;
                                }
                            }

                            $message_category='';                 
                            if($value['message_category'] == 1)
                                $message_category = 'Text';
                            else if($value['message_category'] == 2)
                                $message_category = 'ImageWithCaption';
                            else if($value['message_category'] == 3)
                                $message_category = 'Images';
                            else if($value['message_category'] == 4)
                                $message_category = 'Document';
                            else if($value['message_category'] == 5)
                                $message_category = 'Audio';
                            else if($value['message_category'] == 6)
                                $message_category = 'Video';
                            else if($value['message_category'] == 7)
                                $message_category = 'Quotes';
                            else if($value['message_category'] == 8)
                                $message_category = 'ManagementSpeaks';
                            else if($value['message_category'] == 9)
                                $message_category = 'Circular';
                            else if($value['message_category'] == 10)
                                $message_category = 'StudyMaterial';

                            $approval_message[$index] = ([
                                'notification_id'=>$value['id'],
                                'user'=>$user,
                                'designation'=>$designation,
                                'communication_type'=>$value['communication_type'],
                                'message_category'=>$message_category,
                                'message'=>$value['chat_message'],
                                'group_name'=>$group_details['group_name'], 
                                'date_time'=>$value['actioned_time'],
                                'caption'=>$value['caption_message'],
                                'class'=>$group_details['class_config'],
                            ]);

                            if($value['message_category'] == 3 || $value['message_category'] == 2 || $value['message_category'] == 2 || $value['message_category'] == 4 || $value['message_category'] == 10 || $value['message_category'] == 5)
                            {
                                $images = [];
                                $images_list = CommunicationAttachments::where(['communication_id'=>$value['id']])->get()->toArray();
                                if(!empty($images_list))
                                {
                                    foreach ($images_list as $image_key => $image_value) {
                                        $images[]= $image_value['attachment_location'].''.$image_value['attachment_name'];
                                    }
                                    
                                    $approval_message[$index]['images'] = $images;
                                }
                            }
                            if($value['communication_type'] == 2)//2-homework,1-chat
                            {
                                $subject_details = AcademicSubjects::where('id',$value['subject_id'])->get()->first();
                                $teachingstaff = AcademicSubjectsMapping::where(['subject'=>$value['subject_id'],'class_config'=>$group_details['class_config']])->pluck('staff')->first();
                                $staff_details = UserStaffs::select('id','first_name')->where('id',$teachingstaff)->get()->first();
                                $classteacher = 'no';
                                if($logged_in_staff!='')
                                {
                                    $classteacher_id = AcademicClassConfiguration::Where('id',$group_details['class_config'])->pluck('class_teacher')->first();
                                    if($classteacher_id == $logged_in_staff)
                                        $classteacher = 'yes';
                                }
                                $approval_message[$index]['classteacher']=$classteacher;
                                $approval_message[$index]['subject_id']=$value['subject_id'];
                                $approval_message[$index]['subject_name']=$subject_details->subject_name;
                                $approval_message[$index]['staff_name']=(!empty($staff_details))?$staff_details->first_name:'';
                                $approval_message[$index]['staff_id']=(!empty($staff_details))?$staff_details->id:'';
                            }
                            $index++;
                        }
                    }
                }
            }
        }
        echo json_encode($approval_message);exit();
    }

    // get group participants list
    public function group_participants(Request $request)
    {
        $members_list = []; //declare empty array
        // Get authorizated user details
        $user = auth()->user();
        $player_details = array_column(Appusers::get()->toArray(),'player_id','loginid');
        $group_users = UserGroupsMapping::where('group_id',$request->group_id)->where('user_status',Config::get('app.Group_Active')); //fetch group members

        // if(isset($request->search) && $request->search!='')
        // {
        //     // $staff_list = $staff_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%');
        // }
        // else
            $group_users =$group_users->get();

        // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
        $currentPage = $request->page;
        $itemCollection = new Collection($group_users); // Create a new Laravel collection from the array data
        $perPage = 10;
        // Slice the collection to get the items to display in current page
        $sortedCollection = $itemCollection->sortBy('user_role');
        $currentPageItems = $sortedCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        $paginatedItems->setPath($request->url()); // set url path for generted links
        $paginatedItems->appends($request->page);

        if(!empty($group_users))
        {
            $tempdata = $paginatedItems->toArray();
            $members_list['total'] = $tempdata['total'];
            $members_list['per_page'] = $tempdata['per_page'];
            $members_list['current_page'] = $tempdata['current_page'];
            $members_list['last_page'] = $tempdata['last_page'];
            $members_list['next_page_url'] = $tempdata['next_page_url'];
            $members_list['prev_page_url'] = $tempdata['prev_page_url'];
            $members_list['from'] = $tempdata['from'];
            $members_list['to'] = $tempdata['to'];

            $list = ($currentPage <= 0)?$group_users:$tempdata['data'];

            foreach ($list as $key => $value) {
                $category = $app_status = $admission_no='';

                $list = $this->array_user_details($value); //fetch individual user details

                if(!empty($list['user_details']))
                {
                    if($value['user_role'] == Config::get('app.Parent_role')) //for parent fetch student details
                    {
                        $user_category = UserCategories::where(['id'=>$list['user_details']->user_category])->pluck('category_name')->first(); //fetch parent category and student name
                        $user_category = (strtolower($user_category) == 'father')?'F/O':((strtolower($user_category) == 'mother')?'M/O':'G/O');
                        $class_config = UserGroups::where('id',$request->group_id)->pluck('class_config')->first();
                        $student_ids_list = UserStudentsMapping::where(['parent'=>$list['user_details']->id])->pluck('student')->toArray();
                        $student_id = UserStudents::whereIn('id',$student_ids_list);

                        if($request->group_id!=2)
                            $student_id =$student_id->where('class_config',$class_config);

                        $student_id =$student_id->get()->toArray();

                        $student_name = implode(',',array_column($student_id,'first_name'));
                        $category = $user_category.' '.$student_name; //combine category and name
                        $admission_no = implode(',',array_column($student_id,'admission_number'));
                    }
                    //fetch id from user all table to store notification triggered user
                    $userall_id = UserAll::where(['user_table_id'=>$list['user_details']->id,'user_role'=>$value['user_role']])->pluck('id')->first();
                    $app_status = isset($player_details[$userall_id])?'Installed':'Not Installed';
                    $last_login =  SchoolUsers::where('user_id',$list['user_details']->user_id)->pluck('last_login')->first();
                    // user details
                    $members_list['data'][]=([
                        'id'=>$list['user_details']->id,
                        'name' => $list['user_details']->first_name,
                        'mobile_number' => $list['user_details']->mobile_number,
                        'designation' => ($value['user_role'] == Config::get('app.Parent_role') &&$category!='')?$category:$list['user_category'],
                        'profile_image'=>($list['user_details']->profile_image!='' && $list['user_details']->profile_image!=null)?$list['user_details']->profile_image:'',
                        'last_login'=>$last_login,
                        'app_status'=>$app_status,
                        'user_role'=>$value['user_role'],
                        'inactive_days'=>($last_login!= null)?round((time() - strtotime($last_login)) / (60 * 60 * 24)):0,
                        'admission_no'=>$admission_no
                    ]);
                }
            }
            // $key_values = array_column($members_list, 'admission_no'); 
            // array_multisort($key_values, SORT_ASC, $members_list);
        }
        return response()->json($members_list);exit();
    }

    // Fetch all the images in group
    public function image_list(Request $request)
    {
        $images_communication = Communications::select('id')->whereIn('group_id',[$request->group_id])->where('attachments','Y')->whereIn('message_category',[3,2])->get()->toArray();//fetch communication list for images
        $imageslist = []; //declare empty array
        if(!empty($images_communication)) //check communication is empty or not
        {
            $images_ids = array_column($images_communication,'id'); // apply array fun to filter id 
            foreach ($images_ids as $key => $value) { //set loop to get images
                $images = [];
                $images_list = CommunicationAttachments::where(['communication_id'=>$value])->get()->toArray();
                if(!empty($images_list))
                {
                    foreach ($images_list as $image_key => $image_value) {
                        $images= $image_value['attachment_location'].''.$image_value['attachment_name'];//set images with url
                        $imageslist[] = ([ //form array
                            'id'=>$image_value['communication_id'],
                            'image'=>$images,
                        ]);
                    }
                }
            }
        }
        echo json_encode($imageslist);exit();
                        
    }

    //resend the welcome messaage to not installed parents
    public function send_not_installed_user_welcome_message()
    {
        // Get authorizated user details
        $user = auth()->user();

        $default_password_type=SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();//get default password type

        // fetch all users mobile number under role staff,parent and management
        $userslist = SchoolUsers::whereIn('user_role',[Config::get('app.Parent_role'),Config::get('app.Management_role'),Config::get('app.Staff_role')])->where('school_profile_id',$user->school_profile_id)->whereNull('last_login')->get()->toArray();
        // echo '<pre>';print_r($userslist);exit();
        // fetch welcome template
        $templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("welcome_message")).'%'])->where('status',1)->first();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();

        if(!empty($templates) && !empty($userslist)) //check empty condition
        {
            // run the loop and trigger the sms to all users one by one.
            foreach ($userslist as $key => $value) {
                if($value['user_role'] == 3)
                {
                    if($default_password_type== '')
                        $default_password_type = 'mobile_number';
                    if($default_password_type == 'admission_number' || $default_password_type == 'dob')
                    {
                        $mapped_student = UserStudentsMapping::where('parent',$user_table_id->id)->pluck('student')->first();
                        $student_details = UserStudents::where('id',$mapped_student)->get()->first();
                    }

                    if($default_password_type == 'mobile_number')
                        $password = $value['user_mobile_number'];
                    else if($default_password_type == 'admission_number')
                        $password = $student_details->admission_number;
                    else if($default_password_type == 'dob')
                        $password = $student_details->dob;
                }
                else
                    $password = $value['user_mobile_number'];

                    // replace the mobile and password with corresponding value
                $message = str_replace("*mobileno*",$value['user_mobile_number'],$templates->message);
                $message = str_replace("*password*",$password,$message);

                // call send sms function
                $delivery_details = APISmsController::SendSMS($value['user_mobile_number'],$message,$templates->dlt_template_id);
                $status = 0;
                if(!empty($delivery_details) && isset($delivery_details['status']) && $delivery_details['status'] == 1)
                    $status = 1;
                // store log in db.
                $smslogs[] = ([
                    'sms_description'=>$message,
                    'sms_count'=>1,
                    'mobile_number'=>$value['user_mobile_number'],
                    'sent_by'=>$userall_id,
                    'status'=>$status
                ]);
                
            }
            if(!empty($smslogs))
                Smslogs::insert($smslogs); // store log in db.

            return (['status'=>true,'message'=>'Successfully Sent Welcome message!...']);
        }
        else
            return (['status'=>false,'message'=>'Successfully Sent Welcome message!...']);
    }

    // Reset password and send
    public function reset_send_sms(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        $default_password_type=SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();//get default password type

        // fetch welcome template
        $templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("welcome_message")).'%'])->where('status',1)->first();

        if($request->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['id'=>$request->id])->first();
        else if($request->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['id'=>$request->id])->first();
        else if($request->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['id'=>$request->id])->first();
        else if($request->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['id'=>$request->id])->first();//fetch id from user all table to store notification triggered user

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$request->user_role])->pluck('id')->first();

        if(!empty($templates)) //check empty condition
        {
            $schooluser = SchoolUsers::where('user_id',$user_table_id->user_id)->get()->first();
            // run the loop and trigger the sms to all users one by one.
            if($request->user_role == 3)
            {
                if($default_password_type== '')
                    $default_password_type = 'mobile_number';
                if($default_password_type == 'admission_number' || $default_password_type == 'dob')
                {
                    $mapped_student = UserStudentsMapping::where('parent',$user_table_id->id)->pluck('student')->first();
                    $student_details = UserStudents::where('id',$mapped_student)->get()->first();
                }

                if($default_password_type == 'mobile_number')
                    $password = $user_table_id->mobile_number;
                else if($default_password_type == 'admission_number')
                    $password = $student_details->admission_number;
                else if($default_password_type == 'dob')
                    $password = $student_details->dob;
            }
            else
                $password = $user_table_id->mobile_number;

            $schooluser->user_password=bcrypt($password);
            $schooluser->save();
            // replace the mobile and password with corresponding value
            $message = str_replace("*mobileno*",$user_table_id->mobile_number,$templates->message);
            $message = str_replace("*password*",$password,$message);

            // call send sms function
            $delivery_details = APISmsController::SendSMS($user_table_id->mobile_number,$message,$templates->dlt_template_id);
            $status = 0;
            if(!empty($delivery_details) && isset($delivery_details['status']) && $delivery_details['status'] == 1)
                $status = 1;
            // store log in db.
            $smslogs = ([
                'sms_description'=>$message,
                'sms_count'=>1,
                'mobile_number'=>$user_table_id->mobile_number,
                'sent_by'=>$userall_id,
                'status'=>$status
            ]);

            if(!empty($smslogs))
                Smslogs::insert($smslogs); // store log in db.

            return (['status'=>true,'message'=>'Successfully Sent Welcome message!...']);
        }
        else
            return (['status'=>false,'message'=>'Successfully Sent Welcome message!...']);
    }

    // get student list from specific group
    public function get_group_students(Request $request)
    {
        // Check authentication
        $user = auth()->user();

        if($request->group_id!='')
        {
            $student_list = [];

            $parent_ids = UserGroupsMapping::where('group_id',$request->group_id)->where('user_role',Config::get('app.Parent_role'))->pluck('user_table_id')->toArray();

            foreach ($parent_ids as $key => $value) {
                $student_ids_list = UserStudentsMapping::where('parent',$value)->pluck('student')->toArray();
                $class_config = UserGroups::where('id',$request->group_id)->pluck('class_config')->first();
                $student_details = UserStudents::select('id','first_name')->whereIn('id',$student_ids_list)->where('class_config',$class_config)->get()->first();
                if(!empty($student_details))
                {                    
                    $parent_details = UserParents::select('first_name','user_category','id')->where('id',$value)->get()->first();
                    $user_category = $parent_details->user_category == 1?'F/O':($parent_details->user_category == 2?'M/O':'G/O');
                    $student_list[] = ([
                        'id'=>$parent_details->id,
                        'name'=>$student_details->first_name.' '.$user_category.' '.$parent_details->first_name
                    ]); 
                }
            }
            return response()->json($student_list);
        }
        else
        {
            return response()->json(['status'=>false,'message'=>'Group id is missing!...']);
        }
    }
    
    // birthday student list
    public function birthday_student_list(Request $request)
    {
        // Check authentication
        $user = auth()->user();
        $student_list = [];
        $date = now();
        $text = "Dear *wardname*, the school wishes you a very happy birthday and a progressive year ahead.";
        $student_details = UserStudents::select('id','first_name','class_config','profile_image')->whereMonth('dob', '=', $date->month)->whereDay('dob', '=', $date->day);
        if($user->user_role == Config::get('app.Staff_role'))    
            $text = 'Dear *wardname*, the school wishes you a very happy birthday and a progressive year ahead.';

        if($request->class_config!='')
            $student_details =$student_details->where('class_config',$request->class_config);

        $student_details = $student_details->get()->toArray();
        $visible_to_users =[];
        $visible_to = Communications::select('visible_to')->whereDate('actioned_time', Carbon::today())->where('communication_type',4)->where('approval_status',1)->get()->toArray(); 

        if(!empty($visible_to))
            $visible_to_users = array_column($visible_to,'visible_to');

        foreach($student_details as $key => $value)
        {
            $class_sec_value = AcademicClassConfiguration::where('id',$value['class_config'])->get()->first();
            $student_list[$key] = ([
                'id'=>$value['id'],
                'first_name'=>$value['first_name'],
                'class_section'=>$class_sec_value->classsectionName(),
                'image'=>$value['profile_image'],
                'sent_status'=>in_array($value['id'].',',$visible_to_users)?1:0
            ]);
        }

        return response()->json(['text'=>$text,'student_list'=>$student_list]);

    }

    // Store birthday Message input
    public function store_birthday_message(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();
        $visible_to = $request->visible_to;

        // check deactivation for user
        $check_access = UserGroupsMapping::where('user_table_id',$user_table_id)->where('group_id',2)->where('user_role',$user->user_role)->where('user_status',1)->pluck('id')->first();

        if($check_access == '')
            return response()->json(['message'=>'Your account is deactivated. Please contact school management for futher details']);

        if($user->user_role == Config::get('app.Management_role') || $user->user_role == Config::get('app.Admin_role'))
        {
            $admincommunications = new Communications;
            $admincommunications->chat_message='Birthday wishes sent to students';
            if(isset($request->visible_to))
                $admincommunications->visible_to=','.implode(',',$visible_to).',';
            $admincommunications->distribution_type=5; //1-Class,2-Group,3-Everyone,4-Staff,5-Parent
            $admincommunications->message_category=1; //1-Text,2-Image with caption,3-Image Only,4-Document,5-Audio,6-Video,7-Quotes,8-Management Speaks,9-Circular,10-Study Material;
            $admincommunications->actioned_by=$userall_id;
            $admincommunications->created_by=$userall_id;
            $admincommunications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
            $admincommunications->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $admincommunications->group_id=2;
            $admincommunications->communication_type=4; //default 4 - birthday alert
            $admincommunications->attachments='N'; // Default attachment no
            $admincommunications->approval_status=1;//1-Approval,2-Denied

            $admincommunications->save();
            $adminnotification_id = $admincommunications->id;

            $main_group = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'user_status'=>Config::get('app.Group_Active')])->where('group_id',2)->get()->toArray(); //message copy in main group
            if(!empty($main_group))
                $this->insert_receipt_log(array_unique($main_group, SORT_REGULAR),$adminnotification_id,$user_table_id);
        }

        foreach ($visible_to as $key => $value) {
            $student_detail = UserStudents::where('id',$value)->get()->first();
            $group_id = UserGroups::where('class_config',$student_detail->class_config)->pluck('id')->first();
            $parent_id = UserStudentsMapping::where('student',$student_detail->id)->pluck('parent')->first();
            $message = str_replace("*wardname*",$student_detail->first_name,$request->chat_message);
            // Insert communication message in notification log tables(School DB)
            $communications = new Communications;
            $communications->chat_message=$message;
            if(isset($request->visible_to))
                $communications->visible_to=$value.',';
            $communications->distribution_type=5; //1-Class,2-Group,3-Everyone,4-Staff,5-Parent
            $communications->message_category=1; //1-Text,2-Image with caption,3-Image Only,4-Document,5-Audio,6-Video,7-Quotes,8-Management Speaks,9-Circular,10-Study Material;
            $communications->actioned_by=$userall_id;
            $communications->created_by=$userall_id;
            $communications->actioned_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $communications->group_id=$group_id;
            $communications->communication_type=4; //default 4 - birthday alert
            $communications->attachments='N'; // Default attachment no
            $communications->approval_status=1;//1-Approval,2-Denied

            $communications->save();
            $notification_id = $communications->id;
            
            $user_list = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray(); //for wishes for parent

            $user_ids = UserGroupsMapping::select('user_table_id','user_role')->where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role,'user_status'=>Config::get('app.Group_Active')])->where('group_id',$group_id)->get()->toArray(); //message copy for sender

            $user_list = array_merge($user_list,$user_ids);

            if(!empty($user_list))
                $this->insert_receipt_log(array_unique($user_list, SORT_REGULAR),$notification_id,$user_table_id);

        }        

        return response()->json(['message'=>'Notification inserted Successfully!...']);
    }

    // get class list
    public function get_class_list()
    {
        $classes = AcademicClasses::select('id','class_name')->get()->toArray();
        foreach ($classes as $key => $value) {
            $config_ids = AcademicClassConfiguration::where('class_id',$value)->pluck('id')->toArray();
            $group_ids = UserGroups::whereIn('class_config',$config_ids)->pluck('id')->toArray();
            $classes[$key]['total_users'] = count(UserGroupsMapping::whereIn('group_id',$group_ids)->pluck('user_table_id')->toArray());
        }
        return response()->json(compact('classes'));
    }
}