<?php
/**
 * Created by PhpStorm.
 * User: Roja 
 * Date: 28-12-2022
 * Time: 10:00
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers\Version2;

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

class V2APICommunicationController extends Controller
{

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

            $notification_ids = CommunicationRecipients::where(['user_table_id'=>$userdetails->id,'user_role'=>$user->user_role])->orderBy('actioned_time','DESC')->get()->toArray();
            
            // // Chat message ids
            // $chat_ids = CommunicationRecipients::where(['user_table_id'=>$userdetails->id,'user_role'=>$user->user_role]);

            // $query_newsevent_ids = clone $chat_ids;
            // $query_homework_ids = clone $chat_ids;
            
            // $chat_ids = $chat_ids->where('communication_type',1)->whereIn('communication_id',$communication_id_list)->get()->toArray();

            // // newsevent ids
            // $newsevent_ids = $query_newsevent_ids->where('communication_type',2)->whereIn('communication_id',$newsevents_id_list)->get()->toArray();

            // // homework ids
            // $homework_ids =$query_homework_ids->where('communication_type',4)->whereIn('communication_id',$communication_id_list)->get()->toArray();

            // $notification_ids = array_merge($chat_ids,$newsevent_ids,$homework_ids);
            
            // $datesort = array_column($notification_ids,'actioned_time');
            // array_multisort($datesort, SORT_DESC, $notification_ids);
            // $notification_ids = array_unique($notification_ids,SORT_REGULAR);

            // echo '<pre>';print_r($class_messages);;exit;
            // $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get current page form url e.x. &page=1
            $currentPage = $request->page;
            $itemCollection = new Collection($notification_ids); // Create a new Laravel collection from the array data
            $perPage = 20;
            // Slice the collection to get the items to display in current page
            // $sortedCollection = $itemCollection->sortByDesc('admission_no');
            $currentPageItems = $itemCollection->values()->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
            // Create our paginator and pass it to the view
            $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

            $paginatedItems->setPath($request->url()); // set url path for generted links
            $paginatedItems->appends($request->page);

            $tempdata = $paginatedItems->toArray();
            $messages['total'] = $tempdata['total'];
            $messages['per_page'] = $tempdata['per_page'];
            $messages['current_page'] = $tempdata['current_page'];
            $messages['last_page'] = $tempdata['last_page'];
            $messages['next_page_url'] = $tempdata['next_page_url'];
            $messages['prev_page_url'] = $tempdata['prev_page_url'];
            $messages['from'] = $tempdata['from'];
            $messages['to'] = $tempdata['to'];
            $messages['data'] =[];
            
            if($currentPage > 0){

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
                    foreach ($tempdata['data'] as $key => $value) {
                        if((($value['communication_type'] == 1 || $value['communication_type'] == 4) && in_array($value['communication_id'],$communication_id_list)) || ($value['communication_type'] == 2 && in_array($value['communication_id'],$newsevents_id_list))) 
                        {
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
                                $messages['data'][$index] = ([
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
                                    $messages['data'][$index]['message']=''; 
                                else
                                    $messages['data'][$index]['message']=$message_details->chat_message; 
                                if($message_details->communication_type == 2)
                                {
                                    if(!empty($message_details))
                                    {
                                        $subject_details = AcademicSubjects::where('id',$message_details->subject_id)->get()->first();
                                        $messages['data'][$index]['subject_id'] = isset($message_details->subject_id)?$message_details->subject_id:0;
                                        $messages['data'][$index]['subject_name'] =isset($subject_details->subject_name)?$subject_details->subject_name:'';
                                        $messages['data'][$index]['short_name'] = isset( $subject_details->short_name)?$subject_details->short_name:'';
                                    }

                                }
                                else  if($value['communication_type'] == 2)
                                {
                                    $messages['data'][$index]['title'] = isset($message_details->title)?$message_details->title:0;
                                    $messages['data'][$index]['description'] =isset($message_details->description)?$message_details->description:'';
                                    $messages['data'][$index]['news_events_category'] =isset($message_details->news_events_category)?$message_details->news_events_category:'';
                                    $messages['data'][$index]['module_type'] =isset($message_details->module_type)?$message_details->module_type:'';
                                    $messages['data'][$index]['event_date'] =isset($message_details->event_date)?$message_details->event_date:null;
                                    $messages['data'][$index]['event_time'] =isset($message_details->event_time)?$message_details->event_time:null;
                                    $messages['data'][$index]['important'] =0;
                                    $messages['data'][$index]['date_time']=($message_details->published_time ==null)?$message_details->published_time:$message_details->published_time;
                                    $messages['data'][$index]['message_status'] = $message_details->status;
                                }

                                if($value['view_type'] == 1)
                                {
                                    $watched = CommunicationRecipients::select('id')->where(['message_status'=>2,'communication_id'=>$value['communication_id'],'communication_type'=>$value['communication_type']])->get()->count();
                                    $messages['data'][$index]['delivered_users'] = $message_details['delivered_users'];
                                    $messages['data'][$index]['watched'] = $watched;
                                }
                                if($message_details->message_category == 7 )
                                    $messages['data'][$index]['sub_title'] = 'Quotes';
                                if($message_details->message_category == 8 )
                                    $messages['data'][$index]['sub_title'] = 'Management Speaks';
                                if($message_details->message_category == 9 )
                                    $messages['data'][$index]['sub_title'] = 'Circular';

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
                                            
                                            $messages['data'][$index]['images'] = $images;
                                        }
                                    }
                                    else if($message_details->message_category == 6 && $message_details->communication_type == 1)
                                        $messages['data'][$index]['images']=explode(',',$message_details->chat_message); 
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
                                        
                                        $messages['data'][$index]['images'] = $images;
                                    }
                                }
                                $index++;
                            }
                        }
                    }
                }
            }
            echo json_encode(['message'=>$messages,'user_details'=>$user_details,'unreadmessages'=>$unreadmessages]);exit();        
        }
        return response()->json('No Messages');
    }

    public function user_details($userdetails)
    {
        if($userdetails->user_role == Config::get('app.Management_role'))
        {
            $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');
            $user_details = UserManagements::where(['id'=>$userdetails->user_table_id])->get()->first();
            $user_category = $management_categories[$user_details->user_category];
        }
        else if($userdetails->user_role == Config::get('app.Admin_role'))//check role and get current user id
        {
            $user_details = UserAdmin::where(['id'=>$userdetails->user_table_id])->get()->first();
            $user_category = 'Admin';
        }
        else if($userdetails->user_role == Config::get('app.Staff_role'))
        {
            $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');
            $user_details = UserStaffs::where(['id'=>$userdetails->user_table_id])->get()->first();
            $user_category = isset($user_details->user_category)?$staff_categories[$user_details->user_category]:'';
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
        if($userdetails['user_role'] == Config::get('app.Management_role'))
        {
            $management_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Management_role'))->get()->toArray(),'category_name','id');
            $user_details = UserManagements::where(['id'=>$userdetails['user_table_id']])->get()->first();
            $user_category = $management_categories[$user_details['user_category']];
        }
        else if($userdetails['user_role'] == Config::get('app.Admin_role'))//check role and get current user id
        {
            $user_details = UserAdmin::where(['id'=>$userdetails['user_table_id']])->get()->first();
            $user_category = 'Admin';
        }
        else if($userdetails['user_role'] == Config::get('app.Staff_role'))
        {
            $staff_categories = array_column(UserCategories::select('id','category_name')->where('user_role',Config::get('app.Staff_role'))->get()->toArray(),'category_name','id');
            $user_details = UserStaffs::where(['id'=>$userdetails['user_table_id']])->get()->first();
            $user_category = isset($user_details['user_category'])?$staff_categories[$user_details['user_category']]:'';
        }
        else if($userdetails['user_role'] == Config::get('app.Parent_role'))
        {
            $user_category = 'Parent';
            $user_details = UserParents::where(['id'=>$userdetails['user_table_id']])->get()->first();//fetch id from user all table to store notification triggered user
        }
        return (['user_details'=>$user_details,'user_category'=>$user_category]);
    }
}