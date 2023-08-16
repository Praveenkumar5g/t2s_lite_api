<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 16-06-2023
 * Time: 04:00
 * Validate inputs ,create ,edit and list the users.
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\AcademicClassConfiguration;
use App\Models\AcademicSubjectsMapping;
use App\Models\UserStudentsMapping;
use App\Models\UserGroupsMapping;
use App\Models\AcademicDivisions;
use App\Models\AcademicSubjects;
use App\Models\UserManagements;
use App\Models\AcademicClasses;
use App\Models\AcademicSections;
use App\Models\SchoolDatabase;
use App\Models\UserCategories;
use App\Models\SchoolProfile;
use App\Models\UserStudents;
use App\Models\Smstemplates;
use Illuminate\Http\Request;
use App\Models\SchoolUsers;
use App\Models\UserParents;
use App\Models\UserStaffs;
use App\Models\UserGroups;
use App\Models\UserAdmin;
use App\Models\Appusers;
use App\Models\Smslogs;
use App\Models\UserAll;
use Carbon\Carbon;
use DataTables;
use Session;
use Config;
use File;
use PDF;
use DB;

class WebUserManagementController extends Controller
{    
    public function students()
    {
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        return view('UserManagement.list',$data);
    }

    // get list of students details
    public function getStudent_list(Request $request)
    {
        $user_list =[];
        if ($request->ajax()) {
            $data = UserStudents::join('user_students_mapping as p', 'user_students.id', '=', 'p.student')->leftjoin('user_parents as up', 'up.id', '=', 'p.parent');

            if($request->name!='')
                $data = $data->where('user_students.first_name', 'like', '%' .$request->name. '%')->orwhere('up.first_name', 'like', '%' .$request->name. '%');
           if($request->class_section!='')
                $data = $data->where('user_students.class_config',$request->class_section);

            if($request->admission_no!='')
                $data = $data->where('user_students.admission_number', 'like', '%' .$request->admission_no. '%');
            if($request->mobile_no!='')
                $data = $data->where('up.mobile_number', 'like', '%' .$request->mobile_no. '%');
           
            $data = $data->orderBy('user_students.created_time','desc')->get(['user_students.id','user_students.user_id','user_students.first_name as student_name','user_students.roll_number','user_students.admission_number', 'p.parent', 'up.mobile_number','up.user_category','user_students.class_config','user_students.user_status','up.first_name as parent_name','user_students.dob']);
            $checked_records = [];
            foreach ($data as $key => $value) {            
                $user_list[$value->id]['id']=$value->id;
                $user_list[$value->id]['user_id']=$value->user_id;
                $user_list[$value->id]['student_name']=$value->student_name;
                $user_list[$value->id]['roll_no']=$value->roll_number;
                $user_list[$value->id]['admission_number']=$value->admission_number;
                $user_list[$value->id]['dob']=date('d-m-Y',strtotime($value->dob));
                $user_list[$value->id]['class_config_id']=$value->class_config;
                $user_list[$value->id]['class_section']=$value->classsectionName();
                $user_list[$value->id]['student_status']=$value->user_status;
                if(!in_array($value->user_id,$checked_records))
                {
                    $user_list[$value->id]['father_name'] = '-';
                    $user_list[$value->id]['father_id'] = '-';
                    $user_list[$value->id]['father_mobile_no'] = '-';
                    $user_list[$value->id]['mother_name'] = '-';
                    $user_list[$value->id]['mother_id'] = '-';
                    $user_list[$value->id]['mother_mobile_no'] = '-';
                    $user_list[$value->id]['guardian_name'] = '-';
                    $user_list[$value->id]['guardian_id'] = '-';
                    $user_list[$value->id]['guardian_mobile_no'] = '-';
                }
                if($value->user_category == Config::get('app.Father'))
                {
                    $user_list[$value->id]['father_name'] = $value->parent_name;
                    $user_list[$value->id]['father_id'] = $value->parent;
                    $user_list[$value->id]['father_mobile_no'] = $value->mobile_number;
                }
                if($value->user_category == Config::get('app.Mother'))
                {
                    $user_list[$value->id]['mother_name'] = $value->parent_name;
                    $user_list[$value->id]['mother_id'] = $value->parent;
                    $user_list[$value->id]['mother_mobile_no'] = $value->mobile_number;
                }
                if($value->user_category == Config::get('app.Guardian'))
                {
                    $user_list[$value->id]['guardian_name'] = $value->parent_name;
                    $user_list[$value->id]['guardian_id'] = $value->parent;
                    $user_list[$value->id]['guardian_mobile_no'] = $value->mobile_number;
                }
                array_push($checked_records,$value->user_id);
                
            }
            return Datatables::of($user_list)->addIndexColumn()
                ->addColumn('edit_student', function($row){
                    $actionBtn = '<a href="'.url('usermanagement/editStudent?id='.$row['id']).'" class="edit btn btn-success btn-sm"><i class="fas fa-edit nav-icon"></i></a>';
                    return $actionBtn;
                })
                ->addColumn('unmap', function($row){
                    $actionBtn = ' - ';
                    if($row['father_id']!='' || $row['mother_id']!='' || $row['guardian_id']!='')
                    {
                        $parent_id = ($row['father_id']!='')?$row['father_id']:(($row['mother_id']!='')?$row['mother_id']:$row['guardian_id']);
                        if($parent_id!='')
                        {
                            $check_sibilings = UserStudentsMapping::where('parent',$parent_id)->where('student','!=',$row['id'])->pluck('id')->first();
                            if($check_sibilings!= '')
                                $actionBtn = '<a href="javascript:void(0);" data-id='.$row['id'].' class="exit btn btn-success btn-sm unmapping" id="unmap"><i class="fas fa-sign-out-alt nav-icon" style="color:white"></i></a>';
                        }
                    }
                    
                    return $actionBtn;  
                })
                ->rawColumns(['edit_student','unmap'])
                ->make(true);
        }
    }   

    // Redirect to add student page
    public function addStudents(Request $request)
    {
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        return view('UserManagement.addstudents',$data);
    }

    // Store students 
    public function storeStudents(Request $request)
    {
        if(!empty($request->all()))
        {
            $user = Session::get('user_data');
            $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
            // get the common id to insert
            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

            $profile_image_path ='';
            if(!empty($request->profile_image))
            {
                $path = public_path('uploads/'.$profile_details['school_code'].'/profile_images');//

                if(!File::isDirectory($path)){ //check path already exists
                    File::makeDirectory($path, 0777, true, true);
                }
                $filedata =  $request->file('profile_image');
                $name = explode('.',$filedata->getClientOriginalName())[0];
                $image = $name.''.time().'.'.$filedata->extension();
                $filename = str_replace(["-",","," ","/"], '_', $image);
                $filedata->move(public_path().'/uploads/'.$profile_details['school_code'].'/profile_images', $filename);
                $profile_image_path =url('/').'/uploads/'.$profile_details['school_code'].'/profile_images/'.$filename;
            }

            // insert student details
            $student_details = new UserStudents;
            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            if(isset($request->roll_no))
                $student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            if(!empty($profile_image_path) && !empty($request->profile_image))
                $student_details->profile_image=$profile_image_path;
            $student_details->gender=$request->gender;
            $student_details->class_config=$request->class_section;
            $student_details->dob=date('Y-m-d',strtotime($request->dob));
            // $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->user_status=1;
            $student_details->created_by=$userall_id;
            $student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

            $student_id = $student_details->id;
            $password = '';
            // generate and update staff id in db 
            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
            $student_details->user_id = $userstudent_id;
            $student_details->save();
            
            if($profile_details['default_password_type'] == 'admission_number')
                $password = bcrypt($request->admission_no);
            else if($profile_details['default_password_type'] == 'dob')
                $password = bcrypt(date('dmY',strtotime($request->dob)));

            $group_id = UserGroups::where('class_config',$request->class_section)->pluck('id')->first();
            // insert father details
            if($request->father_mobile_number!='' && $request->father_name!='')
            {
                $data = [];
                $data['photo'] = '';
                $data['first_name'] = $request->father_name;
                $data['mobile_number'] = $request->father_mobile_number;
                $data['email_address'] = $request->father_email_address;
                $data['user_category'] = 1;

                if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';

                if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                    $password = bcrypt($request->father_mobile_number);
                
                $this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);
            }
            // insert mother details
            if($request->mother_mobile_number!='' && $request->mother_name!='')
            {
                $data = [];
                $data['photo'] = '';
                $data['first_name'] = $request->mother_name;
                $data['mobile_number'] = $request->mother_mobile_number;
                $data['email_address'] = $request->mother_email_address;
                $data['user_category'] = 2;

                if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';

                if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                    $password = bcrypt($request->mother_mobile_number);

                $this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);
            }

            // insert guardian details
            if($request->guardian_mobile_number!='' && $request->guardian_name!='')
            {   
                $data = [];
                $data['photo'] = '';
                $data['first_name'] = $request->guardian_name;
                $data['mobile_number'] = $request->guardian_mobile_number;
                $data['email_address'] = $request->guardian_email_address;
                $data['user_category'] = 9;

                if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';

                if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                    $password = bcrypt($request->guardian_mobile_number);

                $this->insert_parent_details($data,$student_details->id,$userall_id,$group_id,$password);
            }
            return back()->with('success','Inserted Successfully');
        }
        return back()->with('error','Invalid Inputs');
    }

    // create parent details dependency function -onboarding
    public function insert_parent_details($data,$id,$userall_id,$group_id,$password)
    {
        $user_data = Session::get('user_data');
        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 
        $profile_image_path ='';
        if(!empty($data['photo']))//check upload photo exist or not
        {
            $path = public_path('uploads/'.$profile_details['school_code'].'/profile_images');//

            if(!File::isDirectory($path)){ //check path already exists
                File::makeDirectory($path, 0777, true, true);
            }
            $name = explode('.',$data['photo']->getClientOriginalName())[0];
            $image = $name.''.time().'.'.$data['photo']->extension();
            $filename = str_replace(["-",","," ","/"], '_', $image);
            $data['photo']->move(public_path().'/uploads/'.$profile_details['school_code'].'/profile_images', $filename);
            $profile_image_path =url('/').'/uploads/'.$profile_details['school_code'].'/profile_images/'.$filename;
        }

        // insert parent details in db
        $parent=[];

        $parent_details = $new_record = UserParents::where('mobile_number',$data['mobile_number'])->get()->first();
        if(!empty($parent_details))
        {
            $parent_details->updated_by = $userall_id;
            $parent_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
        }
        else
        {
            $parent_details = new UserParents;
            $parent_details->created_by = $userall_id;
            $parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
        }

        if($profile_image_path!='')
            $parent_details->profile_image = ($profile_image_path!='')?$profile_image_path:'';
        $parent_details->mobile_number= $data['mobile_number'];
        $parent_details->user_category = $data['user_category'];
        $parent_details->first_name= $data['first_name'];
        $parent_details->email_id= $data['email_address'];
        $parent_details->user_status=1;//active
            
            
        $parent_details->save();

        $parent_id = $parent_details->id;

        if(empty($new_record))
        {
            // generate and update staff id in db 
            $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
            $parent_details->user_id = $userparent_id;
            $parent_details->save(); 
        }
        else
            $userparent_id = $parent_details->user_id;

        // add into group
        if($group_id!='')
        {
            $check_exists = UserGroupsMapping::where(['group_id'=>2,'user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1])->get()->first();
            if(empty($check_exists))
                UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
            UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$parent_id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
        }
        
        if(empty($new_record))
        {
            //make an entry in user all table
            $user_all = new UserAll;
            $user_all->user_table_id=$parent_details->id;
            $user_all->user_role=Config::get('app.Parent_role');
            $user_all->save(); 
        }
        $schoolusers = $check_exists_login = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();
        if(empty($schoolusers))
            $schoolusers = new SchoolUsers;

        $schoolusers->school_profile_id=$user_data->school_profile_id;
        $schoolusers->user_id=$userparent_id;
        $schoolusers->user_mobile_number=$data['mobile_number'];
        if($password!='' && empty($check_exists_login))
            $schoolusers->user_password=$password;
        $schoolusers->user_email_id=$data['email_address'];
        $schoolusers->user_role=Config::get('app.Parent_role');
        $schoolusers->user_status=1;
        $schoolusers->save();

        // mapping the student and parent
        $student_map = new UserStudentsMapping;
        $student_map->student = $id;  
        $student_map->parent = $parent_id;
        $student_map->created_by = $userall_id;
        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $student_map->save();
    }

    public function editStudent(Request $request)
    {
        $user_data = Session::get('user_data');
        $student_list = $parentsdata = $students= [];//empty array declaration
        // $parents_list = UserStudentsMapping::select('parent')->where('student',$request->id)->first(); //fetch student details from parent mapped data
        if($request->id!='')
        {
            $parent_list = UserStudentsMapping::select('parent')->where('student',$request->id)->get()->toArray(); //fetch all parent details from student id
            $students = UserStudents::where('id',$request->id)->get()->first(); //get student related info
            $parents = array_column($parent_list,'parent'); //pick parent id alone
            foreach ($parents as $parent_key => $parent_value) { //form array with parent details
                $parent_data = UserParents::where('id',$parent_value)->get()->first();
                $parentsdata[$parent_data->user_category] = $parent_data; 
            }
        }
        else if($request->id!='')
        {
            $parent_data = UserParents::where('id',$request->id)->get()->first();
            $parentsdata[$parent_data->user_category] = $parent_data; 
        }

        $data['student_list'] = ([
            'student_id'=>isset($students->id)?$students->id:0,
            'student_name'=>isset($students->first_name)?$students->first_name:'',
            'father_mobile_number'=>isset($parentsdata[1])?$parentsdata[1]->mobile_number:'',
            'father_email_address'=>isset($parentsdata[1])?$parentsdata[1]->email_id:'',
            'father_name'=>isset($parentsdata[1])?$parentsdata[1]->first_name:'',
            'father_id'=>isset($parentsdata[1])?$parentsdata[1]->id:0,
            'mother_mobile_number'=>isset($parentsdata[2])?$parentsdata[2]->mobile_number:'',
            'mother_email_address'=>isset($parentsdata[2])?$parentsdata[2]->email_id:'',
            'mother_name'=>isset($parentsdata[2])?$parentsdata[2]->first_name:'',
            'mother_id'=>isset($parentsdata[3])?$parentsdata[3]->id:0,
            'guardian_mobile_number'=>isset($parentsdata[9])?$parentsdata[9]->mobile_number:'',
            'guardian_email_address'=>isset($parentsdata[9])?$parentsdata[9]->email_id:'',
            'guardian_name'=>isset($parentsdata[9])?$parentsdata[9]->first_name:'',
            'guardian_id'=>isset($parentsdata[9])?$parentsdata[9]->id:0,
            'admission_number'=>isset($students->first_name)?$students->admission_number:'',
            'roll_no'=>isset($students->roll_number)?$students->roll_number:'',
            'dob'=>isset($students->dob)?date('d-m-Y',strtotime($students->dob)):null,
            'employee_no'=>isset($students->employee_no)?$students->employee_no:'',
            'gender'=>$students->gender,
            'photo'=>isset($students->profile_image)?$students->profile_image:'',
            'temporary_student'=>(isset($students->profile_image) && $students->class_config == null)?'yes':'no',
            'class_section'=>isset($students->class_config)?$students->class_config:0,
        ]);
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        
        return view('UserManagement.editstudent',$data);
    }

    // update student details
    public function updateStudent(Request $request)
    {
        if(!empty($request->all()))
        {
            $user = Session::get('user_data');
            $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
            // get the common id to insert
            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

            $profile_image_path ='';
            if(!empty($request->profile_image))
            {
                $path = public_path('uploads/'.$profile_details['school_code'].'/profile_images');//

                if(!File::isDirectory($path)){ //check path already exists
                    File::makeDirectory($path, 0777, true, true);
                }
                if($request->old_image!='')
                {
                    $exploded_value = explode('/',$request->old_image);
                    $image_path = public_path('/uploads/'.$profile_details['school_code'].'/profile_images/'.$exploded_value[count($exploded_value)-1]);
                    if(File::exists($image_path)) {
                        echo unlink($image_path);
                    }
                }
                $filedata =  $request->file('profile_image');
                $name = explode('.',$filedata->getClientOriginalName())[0];
                $image = $name.''.time().'.'.$filedata->extension();
                $filename = str_replace(["-",","," ","/"], '_', $image);
                $request->profile_image->move(public_path().'/uploads/'.$profile_details['school_code'].'/profile_images', $filename);
                $profile_image_path =url('/').'/uploads/'.$profile_details['school_code'].'/profile_images/'.$filename;
            }
            $group_id =$old_group_id=$new_group_id='';
            if(isset($request->student_id) && $request->student_id>0)//arrange student details in array
            {

                $student_details = UserStudents::where(['id'=>$request->student_id])->get()->first();
                $old_group_id = UserGroups::where('class_config',$student_details->class_config)->pluck('id')->first();
                if($student_details->class_config != $request->class_config)
                {
                    $new_group_id = UserGroups::where('class_config',$request->class_section)->pluck('id')->first();
                }
            } 
            else
                $student_details = new UserStudents;

            // student details insert or edit into db
            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            $student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            if(!empty($profile_image_path) && !empty($request->profile_image))
                $student_details->profile_image=$profile_image_path;
            if(isset($request->gender))
                $student_details->gender=$request->gender;
            if(isset($request->dob))
                $student_details->dob=date('Y-m-d',strtotime($request->dob));
            $student_details->class_config=$request->class_section;
            // $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->user_status=1;
            $student_details->updated_by=$userall_id;
            $student_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

            $student_id = $student_details->id;

            // add into group
            if($new_group_id!='' && ($old_group_id != $new_group_id) && $request->student_id!='')
            {
                $parent_ids = UserStudentsMapping::where('student',$request->student_id)->pluck('parent')->toArray();
                UserGroupsMapping::where(['group_id'=>$old_group_id,'user_role'=>Config::get('app.Parent_role')])->whereIn('user_table_id',$parent_ids)->delete();
                foreach ($parent_ids as $key => $parent_value) {
                    UserGroupsMapping::insert(['group_id'=>$new_group_id,'user_table_id'=>$parent_value,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
                }
            }
            $new_user = '';
            if(($request->father_id == 0 && $request->father_mobile_number!=''))
                $new_user = 'yes';
            if(($request->mother_id == 0 && $request->mother_mobile_number!=''))
                $new_user = 'yes';
            if(($request->guardian_id == 0 && $request->guardian_mobile_number!=''))
                $new_user = 'yes';

            $password ='';
            if((isset($request->password_update) && $request->password_update!='') ||$new_user!='')
            {
                if($profile_details['default_password_type'] == 'admission_number')
                    $password = bcrypt($request->admission_no);
                else if($profile_details['default_password_type'] == 'dob')
                    $password = bcrypt(date('dmY',strtotime($request->dob)));
            }

            // insert parents details
            if(isset($request->father_id))
            {
                $father_details = UserParents::where('id',$request->father_id)->get()->first();
                if(!empty($father_details) || $request->father_mobile_number!='')
                {
                    $data['photo'] = $profile_image_path;
                    $data['first_name'] = $request->father_name;
                    $data['mobile_number'] = $request->father_mobile_number;
                    $data['email_address'] = $request->father_email_address;
                    $data['user_category'] = 1;
                    if($new_user == '' && $password =='')
                    {
                        $check_user_ids = SchoolUsers::where(['user_id'=>$father_details->user_id,'school_profile_id'=>$user->school_profile_id])->get()->first();
                        if(empty($check_user_ids))
                        {
                            $schoolusers = SchoolUsers::where(['user_mobile_number'=>$request->father_mobile_number,'school_profile_id'=>$user->school_profile_id])->get()->first();
                            if(empty($schoolusers))
                            {
                                if($profile_details['default_password_type'] == 'admission_number')
                                    $password = bcrypt($request->admission_no);
                                else if($profile_details['default_password_type'] == 'dob')
                                    $password = bcrypt(date('dmY',strtotime($request->dob)));
                                else
                                    $password = bcrypt($request->father_mobile_number);
                            }
                        }
                    }
                    if((isset($request->password_update) && $request->password_update!='') ||$new_user!='')
                    {
                        if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';
                        if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                            $password = bcrypt($request->father_mobile_number);
                    }
                    $this->edit_parent_details($data,$father_details,$student_id,$userall_id,$old_group_id,$new_group_id,$password,$request->father_id);
                }
            }
            // update or insert parents details
            if(isset($request->mother_id))
            {
                $mother_details = UserParents::where('id',$request->mother_id)->get()->first();
                if(!empty($mother_details) || $request->mother_mobile_number!='' )
                {
                    $data = [];
                    $data['photo'] = $profile_image_path;
                    $data['first_name'] = $request->mother_name;
                    $data['mobile_number'] = $request->mother_mobile_number;
                    $data['email_address'] = $request->mother_email_address;
                    $data['user_category'] = 2;
                    
                    if($new_user == '' && $password =='')
                    {
                        $check_user_ids = SchoolUsers::where(['user_id'=>$mother_details->user_id,'school_profile_id'=>$user->school_profile_id])->get()->first();
                        if(empty($check_user_ids))
                        {
                            $schoolusers = SchoolUsers::where(['mobile_number'=>$request->fathemother_mobile_numberr_mobile_number,'school_profile_id'=>$user->school_profile_id])->get()->first();
                            if(empty($schoolusers))
                            {
                                if($profile_details['default_password_type'] == 'admission_number')
                                    $password = bcrypt($request->admission_no);
                                else if($profile_details['default_password_type'] == 'dob')
                                    $password = bcrypt(date('dmY',strtotime($request->dob)));
                                else
                                    $password = bcrypt($request->mother_mobile_number);
                            }
                        }
                    }

                    if((isset($request->password_update) && $request->password_update!='') || $new_user!='')
                    {
                        if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';
                        if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                            $password = bcrypt($request->mother_mobile_number);
                    }
                    $this->edit_parent_details($data,$mother_details,$student_id,$userall_id,$old_group_id,$new_group_id,$password,$request->mother_id);
                }
            }

            if(isset($request->guardian_id))
            { 
            // update or insert parents details
                $guardian_details = UserParents::where('id',$request->guardian_id)->get()->first();
                if(!empty($guardian_details) || $request->guardian_mobile_number!='' )
                {
                    $data = [];
                    $data['photo'] = $profile_image_path;
                    $data['first_name'] = $request->guardian_name;
                    $data['mobile_number'] = $request->guardian_mobile_number;
                    $data['email_address'] = $request->guardian_email_address;
                    $data['user_category'] = 9;
                    if($new_user == '' && $password =='')
                    {
                        $check_user_ids = SchoolUsers::where(['user_id'=>$guardian_details->user_id,'school_profile_id'=>$user->school_profile_id])->get()->first();
                        if(empty($check_user_ids))
                        {
                            $schoolusers = SchoolUsers::where(['mobile_number'=>$request->guardian_mobile_number,'school_profile_id'=>$user->school_profile_id])->get()->first();
                            if(empty($schoolusers))
                            {
                                if($profile_details['default_password_type'] == 'admission_number')
                                    $password = bcrypt($request->admission_no);
                                else if($profile_details['default_password_type'] == 'dob')
                                    $password = bcrypt(date('dmY',strtotime($request->dob)));
                                else
                                    $password = bcrypt($request->guardian_mobile_number);
                            }
                        }
                    }
                    if((isset($request->password_update) && $request->password_update!='') || $new_user!='')
                    {   
                        if($profile_details['default_password_type'] != 'admission_number' && $profile_details['default_password_type'] != 'dob')
                            $password ='';

                        if($profile_details['default_password_type'] == 'mobile_number' || $password == '')
                            $password = bcrypt($request->guardian_mobile_number);
                    }
                    $this->edit_parent_details($data,$guardian_details,$student_id,$userall_id,$old_group_id,$new_group_id,$password,$request->guardian_id);
                }
            }
            return back()->with('success','Updated Successfully');
        }
        return back()->with('error','Invalid Inputs');
    }

    // Edit parent details dependency function - onboarding
    public function edit_parent_details($data,$details,$id,$userall_id,$old_group_id,$new_group_id,$password,$old_parent_id)
    {
        $image =$page='';
        $user = Session::get('user_data');
        $profile_image_path ='';
        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        if(!empty($data['photo']))//check upload photo exist or not
        {
            $profile_image_path = $data['photo'];
        //     $path = public_path('uploads/'.$profile_details['school_code'].'/students');//

        //     if(!File::isDirectory($path)){ //check path already exists
        //         File::makeDirectory($path, 0777, true, true);
        //     }
        //     $name = explode('.',$data['photo']->getClientOriginalName())[0];
        //     $image = $name.''.time().'.'.$data['photo']->extension();
        //     $filename = str_replace(["-",","," ","/"], '_', $image);
        //     $data['photo']->move(public_path().'/uploads/'.$profile_details['school_code'].'/students', $filename);
        //     $profile_image_path =url('/').'/uploads/'.$profile_details['school_code'].'/students/'.$filename;
        }
        if(empty($details) && !isset($details->mobile_number))
        {
            $page = 'new';
            $details = new UserParents;
        }

        if(!empty($details) && $details->mobile_number != $data['mobile_number'])
        {
            $check_old_groups = UserStudentsMapping::where('student',$id)->where('parent',$old_parent_id)->get()->first();
            if(!empty($check_old_groups))
                UserStudentsMapping::where('student',$id)->where('parent',$old_parent_id)->delete();  

            $check_old_class = UserStudents::where('id',$id)->pluck('class_config')->first();
            if($check_old_class!='')
            {
                $check_other_child = UserStudentsMapping::where('parent',$old_parent_id)->pluck('student')->toArray();
                if(!empty($check_other_child))
                {
                    $check_same_class = UserStudents::whereIn('id',$check_other_child)->where('id','!=',$id)->where('class_config',$check_old_class)->get()->first();
                    if(empty($check_same_class))
                    {
                        $group_id = $new_group_id!=''?$new_group_id:$old_group_id;
                        UserGroupsMapping::where('user_table_id',$old_parent_id)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->delete();
                    }

                }
                else
                    UserGroupsMapping::where('user_table_id',$old_parent_id)->where('user_role',Config::get('app.Parent_role'))->delete();
            }      
        }
        
        //save parent details
        if($data['first_name']!='')
            $details->first_name= $data['first_name'];
        if($data['mobile_number']!='')
            $details->mobile_number=$data['mobile_number'];
        if($profile_image_path!='')
            $details->profile_image = ($profile_image_path!='')?$profile_image_path:'';
        if($data['email_address']!='')
            $details->email_id=$data['email_address'];
        if($data['user_category']!='')
            $details->user_category = $data['user_category'];
        $details->updated_by=$userall_id;
        $details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
        $details->save();
        $parent_id = $details->id;
        if($page!='')
        {
            // generate and update staff id in db 
            $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
            $details->user_id = $userparent_id;
            $details->save();     

            $user_all = new UserAll;
            $user_all->user_table_id=$parent_id;
            $user_all->user_role=Config::get('app.Parent_role');
            $user_all->save(); 

        }
        else
            $userparent_id = $details->user_id;

        if($data['email_address']!='' || $data['mobile_number']!='')
        {
            $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user->school_profile_id])->get()->first();

            if(empty($schoolusers))
                $schoolusers = new SchoolUsers;

            $schoolusers->school_profile_id=$user->school_profile_id;
            $schoolusers->user_id=$userparent_id;
            $schoolusers->user_mobile_number=$data['mobile_number'];
            if($password!='')
                $schoolusers->user_password=$password;
            $schoolusers->user_email_id=$data['email_address'];
            $schoolusers->user_role=Config::get('app.Parent_role');
            $schoolusers->user_status=1;
            $schoolusers->save();
        }
        
        if($page!='')
        {
            // mapping the student and parent
            $student_map = new UserStudentsMapping;
            $student_map->student = $id;  
            $student_map->parent = $details->id;
            $student_map->created_by = $userall_id;
            $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_map->save();
            
            UserGroupsMapping::insert(['group_id'=>$new_group_id,'user_table_id'=>$details->id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
            
            $check_exists = UserGroupsMapping::where(['group_id'=>2,'user_table_id'=>$details->id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1])->get()->first();
            if(empty($check_exists))
                UserGroupsMapping::insert(['group_id'=>2,'user_table_id'=>$details->id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);
        }
        else
        {
            $check_old_groups = UserStudentsMapping::where('student',$id)->where('parent',$old_parent_id)->get()->first();
            if(!empty($check_old_groups))
                UserStudentsMapping::where('student',$id)->where('parent',$old_parent_id)->delete();
            $check_old_class = UserStudents::where('id',$id)->pluck('class_config')->first();
            if($check_old_class!='')
            {
                $check_other_child = UserStudentsMapping::where('parent',$old_parent_id)->pluck('student')->toArray();
                if(!empty($check_other_child))
                {
                    $check_same_class = UserStudents::whereIn('id',$check_other_child)->where('id','!=',$id)->where('class_config',$check_old_class)->get()->first();
                    if(empty($check_same_class))
                    {
                        $group_id = $new_group_id!=''?$new_group_id:$old_group_id;
                        UserGroupsMapping::where('user_table_id',$old_parent_id)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->delete();
                    }

                }
                else
                    UserGroupsMapping::where('user_table_id',$old_parent_id)->where('user_role',Config::get('app.Parent_role'))->delete();
            }


            $check_exists = UserStudentsMapping::where('student',$id)->where('parent',$details->id)->pluck('id')->first();

            if($check_exists=='')
            {
                // mapping the student and parent
                $student_map = new UserStudentsMapping;
                $student_map->student = $id;  
                $student_map->parent = $details->id;
                $student_map->created_by = $userall_id;
                $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
                $student_map->save();
            }
            $group_id = $new_group_id!=''?$new_group_id:$old_group_id;
            UserGroupsMapping::insert(['group_id'=>$group_id,'user_table_id'=>$details->id,'user_role'=>Config::get('app.Parent_role'),'user_status'=>1,'group_access'=>2]);

        }
    }

    public function checkMobileno(Request $request)
    {
        $check_exists = UserParents::where('mobile_number',$request->mobile_no);
        if($request->status == 'father')
            $check_exists = $check_exists->whereIn('user_category',([Config::get('app.Mother'),Config::get('app.Guardian')]));
        if($request->status == 'mother')
            $check_exists = $check_exists->whereIn('user_category',([Config::get('app.Father'),Config::get('app.Guardian')]));
        if($request->status == 'guardian')
            $check_exists = $check_exists->whereIn('user_category',([Config::get('app.Mother'),Config::get('app.Father')]));

        if(isset($request->id)!='')
            $check_exists = $check_exists->where('id','!=',$request->id);

        $check_exists = $check_exists->get()->first();

        if(!empty($check_exists))
            echo 'false';
        else
            echo 'true';
    }

    public function checkAdmissionno(Request $request)
    {
        $check_exists = UserStudents::where('admission_number',$request->admission_no);
        
        if(isset($request->id)!='')
            $check_exists = $check_exists->where('id','!=',$request->id);

        $check_exists = $check_exists->get()->first();

        if(!empty($check_exists))
            echo 'false';
        else
            echo 'true';
    }

    public function changeDobformat()
    {
        $user = Session::get('user_data');

        $default_password_type = SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();
           
        // fetch all users mobile number under role staff,parent and management
        $userslist = SchoolUsers::whereIn('user_role',[3])->where('school_profile_id',$user->school_profile_id)->get()->toArray();
        // get the common id to insert
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();
        // fetch welcome template
        if(!empty($userslist)) //check empty condition
        {
            // remove duplicate mobile numbers;
            // $userslist = array_unique($userslist);

            // run the loop and trigger the sms to all users one by one.
            foreach ($userslist as $key => $value) {
                if($value['user_role'] == 3)
                {
                    $schoolusers = SchoolUsers::where(['user_role'=>$value['user_role'],'school_profile_id'=>$user->school_profile_id,'id'=>$value['id']]);
                    if($default_password_type == 'admission_number' || $default_password_type == 'dob')
                    {
                        $parent_id = UserParents::where('user_id',$value['user_id'])->pluck('id')->first();
                        $mapped_student = UserStudentsMapping::where('parent',$parent_id)->pluck('student')->first();
                        $student_details = UserStudents::where('id',$mapped_student)->get()->first();
                    }

                    if($default_password_type == 'mobile_number')
                    {
                        $password = $value['user_mobile_number'];
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($value['user_mobile_number'])]);
                    }
                    else if($default_password_type == 'admission_number')
                    {
                        $password = $student_details->admission_number;
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($student_details->admission_number)]);
                    }
                    else if($default_password_type == 'dob')
                    {
                        $password = date('dmY',strtotime($student_details->dob));
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($password)]);
                    }
                }
            }
            
            echo '<prE>';print_r(['status'=>true,'message'=>'SMS sent Successfully!...']);
        }
        else
            echo '<prE>';print_r(['status'=>false,'message'=>'Please configure template details!...']);
    }

    public function checkMobilenoexists(Request $request)
    {
        $flag = 0;
        $tag = '';
        if($request->father_mobile_number!='' && $flag == 0)
        {
            $tag = 'father';
            $flag = UserParents::where('mobile_number',$request->father_mobile_number)->where('user_category',Config::get('app.Father'));
            if(isset($request->father_id)!='')
                $flag = $flag->where('id','!=',$request->father_id);

            $flag = $flag->pluck('id')->first();
        }

        if($request->mother_mobile_number!='' && $flag == 0)
        {
            $tag = 'mother';
            $flag = UserParents::where('mobile_number',$request->father_mobile_number)->where('user_category',Config::get('app.Mother'));
            if(isset($request->mother_id)!='')
                $flag = $flag->where('id','!=',$request->mother_id);

            $flag = $flag->pluck('id')->first();
        }

        if($request->guardian_mobile_number!='' && $flag == 0)
        {
            $tag = 'guardian';
            $flag = UserParents::where('mobile_number',$request->father_mobile_number)->where('user_category',Config::get('app.Guardian'));
            if(isset($request->mother_id)!='')
                $flag = $flag->where('id','!=',$request->guardian_id);

            $flag = $flag->pluck('id')->first();
        }

        echo json_encode(['status'=>$flag,'tag'=>$tag]);
    }

    // unmap users
    public function studentunmapwithparent(Request $request)
    {
        
        $get_parents = UserStudentsMapping::where('student',$request->id)->pluck('parent')->toArray();
        $check_old_class = UserStudents::where('id',$request->id)->pluck('class_config')->first();
        if($check_old_class!='')
        {
            foreach ($get_parents as $key => $value) {
                $check_other_child = UserStudentsMapping::where('parent',$value)->pluck('student')->toArray();
                if(!empty($check_other_child))
                {
                    $check_same_class = UserStudents::whereIn('id',$check_other_child)->where('id','!=',$request->id)->where('class_config',$check_old_class)->get()->first();
                    if(empty($check_same_class))
                    {
                        $group_id = UserGroups::where('class_config',$check_old_class)->pluck('id')->first();
                        UserGroupsMapping::where('user_table_id',$value)->where('group_id',$group_id)->where('user_role',Config::get('app.Parent_role'))->delete();
                    }
                }
                else
                    UserGroupsMapping::where('user_table_id',$value)->where('user_role',Config::get('app.Parent_role'))->delete();
            }
        }
        if(!empty($get_parents))
            UserStudentsMapping::where('student',$request->id)->delete();
        echo true;
    }
    // parent list
    public function parents()
    {
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        return view('UserManagement.parentlist',$data);
    }

    // get list of students details
    public function getParent_list(Request $request)
    {
        $user_list =[];
        if ($request->ajax()) {
            $data = UserParents::join('user_students_mapping as p', 'user_parents.id', '=', 'p.parent')->leftjoin('user_students as s', 's.id', '=', 'p.student');

            if($request->name!='')
                $data = $data->where('s.first_name', 'like', '%' .$request->name. '%')->orwhere('up.first_name', 'like', '%' .$request->name. '%');
           if($request->class_section!='')
                $data = $data->where('s.class_config',$request->class_section);

            if($request->admission_no!='')
                $data = $data->where('s.admission_number', 'like', '%' .$request->admission_no. '%');
            if($request->mobile_no!='')
                $data = $data->where('user_parents.mobile_number', 'like', '%' .$request->mobile_no. '%');
           
            $data = $data->get(['user_parents.id','user_parents.user_id','s.first_name as student_name', 'p.parent', 'user_parents.mobile_number','user_parents.user_category','s.class_config','user_parents.first_name as parent_name','s.dob']);
            $checked_records = [];
            foreach ($data as $key => $value) {        
                $user_list[$value->id]['id']=$value->id;    
                $user_list[$value->id]['user_id']=$value->user_id;
                $user_list[$value->id]['parent_name']=$value->parent_name;
                $user_list[$value->id]['mobile_number']=$value->mobile_number;
                $user_list[$value->id]['class_section']=$value->classsectionName();
                if(!in_array($value->user_id,$checked_records))
                {
                    $user_list[$value->id]['dob1'] = '-';
                    $user_list[$value->id]['dob2'] = '-';
                    $user_list[$value->id]['dob3'] = '-';
                }
                if($user_list[$value->id]['dob1']=='-')
                    $user_list[$value->id]['dob1'] = date('d-m-Y',strtotime($value->dob));
                else if($user_list[$value->id]['dob2']=='-')
                    $user_list[$value->id]['dob2'] = date('d-m-Y',strtotime($value->dob));
                else if($user_list[$value->id]['dob3']=='-')
                    $user_list[$value->id]['dob3'] = date('d-m-Y',strtotime($value->dob));
                
                array_push($checked_records,$value->user_id);
                
            }
            return Datatables::of($user_list)->addIndexColumn()->make(true);
        }
    }  

    public function unmappedstudents()
    {
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        return view('UserManagement.unmapstudentlist',$data);
    }

    // get list of students details
    public function getunmapStudent_list(Request $request)
    {
        $user_list =[];
        if ($request->ajax()) {
            $data = UserStudents::select('id','user_id','first_name as student_name','roll_number','admission_number','class_config','user_status','dob');

            if($request->name!='')
                $data = $data->where('first_name', 'like', '%' .$request->name. '%');
           if($request->class_section!='')
                $data = $data->where('class_config',$request->class_section);

            if($request->admission_no!='')
                $data = $data->where('admission_number', 'like', '%' .$request->admission_no. '%');
           
            $data = $data->orderBy('created_time','desc')->get();
            $checked_records = [];

            foreach ($data as $key => $value) {  

                $check_unmapped = UserStudentsMapping::where('student',$value->id)->pluck('id')->first();
                if($check_unmapped=='')
                {
                    $user_list[$value->id]['id']=$value->id;
                    $user_list[$value->id]['user_id']=$value->user_id;
                    $user_list[$value->id]['student_name']=$value->student_name;
                    $user_list[$value->id]['roll_no']=$value->roll_number;
                    $user_list[$value->id]['admission_number']=$value->admission_number;
                    $user_list[$value->id]['dob']=date('d-m-Y',strtotime($value->dob));
                    $user_list[$value->id]['class_config_id']=$value->class_config;
                    $user_list[$value->id]['class_section']=$value->classsectionName();
                    $user_list[$value->id]['student_status']=$value->user_status;
                }
            }

            return Datatables::of($user_list)->addIndexColumn()
                ->addColumn('edit_student', function($row){
                    $actionBtn = '<a href="'.url('usermanagement/editStudent?id='.$row['id']).'" class="edit btn btn-success btn-sm"><i class="fas fa-edit nav-icon"></i></a>';
                    return $actionBtn;
                })
                
                ->rawColumns(['edit_student'])
                ->make(true);
        }
    } 

    
    // send welcome message to users
    public function welcome_message(Request $request)
    {
        $data['managements'] = UserManagements::select('id','first_name')->where('user_status',1)->get()->toArray();
        $data['staffs'] = UserStaffs::select('id','first_name')->where('user_status',1)->get()->toArray();
        $data['parents'] = UserStudents::select('id','first_name')->where('user_status',1)->get()->toArray();
        $data['class_configs'] = AcademicClassConfiguration::select('academic_class_configuration.id',DB::raw("CONCAT(c.class_name,'-',s.section_name) as class_section"))->join('academic_classes as c', 'c.id', '=', 'academic_class_configuration.class_id')->join('academic_sections as s', 's.id', '=', 'academic_class_configuration.section_id')->get()->toArray();
        return view('UserManagement.welcome_message',$data);
    }

    // send welcome message to users
    public function send_welcome_message(Request $request)
    {
        $user = Session::get('user_data');
        $userslist = [];
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();

        $templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("welcome_message")).'%'])->where('status',1)->first();
        if($request->distribution_type == 1)
        {
            // fetch all users mobile number under role staff,parent and management
            $userslist = SchoolUsers::where('school_profile_id',$user->school_profile_id)->where('new_user',1);
            if($request->role=='all')
                $userslist = $userslist->whereIn('user_role',[Config::get('app.Management_role'),Config::get('app.Parent_role'),Config::get('app.Staff_role')]);
            else
            {
                $role[] = $request->role;
                $userslist = $userslist->whereIn('user_role',$role);
            }
            $userslist = $userslist->get()->toArray();
            if(!empty($userslist))
            {

                $newsusers_id = array_column($userslist,'user_id');
                SchoolUsers::where('user_id',$newsusers_id)->where('school_profile_id',$user['school_profile_id'])->update(['new_user'=>0]);
            }
        }
        else if($request->distribution_type == 2)
        {
            $staffs_list = $managements_list = $parents_list = $school_list = $userslist = [];
            $app_installed_users_ids = Appusers::where('active_status',1)->pluck('loginid')->toArray();
            $staffs = UserAll::whereNotIn('id',$app_installed_users_ids)->where('user_role',Config::get('app.Staff_role'))->pluck('id')->toArray();

            $managements = UserAll::whereNotIn('id',$app_installed_users_ids)->where('user_role',Config::get('app.Management_role'))->pluck('id')->toArray();

            $parents = UserAll::whereNotIn('id',$app_installed_users_ids)->where('user_role',Config::get('app.Parent_role'))->pluck('id')->toArray();
            
            if($request->role=='all')
            {
                $staffs_list = UserStaffs::whereIn('id',$staffs)->where('user_status',1)->pluck('user_id')->toArray();
                $parents_list = UserParents::whereIn('id',$parents)->where('user_status',1)->pluck('user_id')->toArray();
                $managements_list = UserManagements::whereIn('id',$managements)->where('user_status',1)->pluck('user_id')->toArray();
            }
            else
            {
                if($request->role == 2 && !empty($staffs))
                    $staffs_list = UserStaffs::whereIn('id',$staffs)->where('user_status',1)->pluck('user_id')->toArray();
                if($request->role == 3 && !empty($parents))
                    $parents_list = UserParents::whereIn('id',$parents)->where('user_status',1)->pluck('user_id')->toArray();
                if($request->role == 5 && !empty($managements))
                    $managements_list = UserManagements::whereIn('id',$managements)->where('user_status',1)->pluck('user_id')->toArray();
            }
            $school_list = array_merge($school_list,$staffs_list,$parents_list,$managements_list);
            
            if(!empty($school_list))
                    $userslist = SchoolUsers::whereIn('user_id',$school_list)->where('school_profile_id',$user->school_profile_id)->get()->toArray();
            
        }
        else if($request->distribution_type == 3)
        {
            $staffs = $managements = $parents = $school_list = $userslist = [];
            if($request->role == 2 && $request->staffs!='')
                $staffs = UserStaffs::whereIn('id',$request->staffs)->where('user_status',1)->pluck('user_id')->toArray();
            if($request->role == 3 && $request->students!='')
            {
                $parentid = UserStudentsMapping::whereIn('student',$request->students)->pluck('parent')->toArray();
                if(!empty($parentid))
                    $parents = UserParents::whereIn('id',$parentid)->where('user_status',1)->pluck('user_id')->toArray();
            }
            if($request->role == 5 && $request->managements!='')
                $managements = UserManagements::whereIn('id',$request->managements)->where('user_status',1)->pluck('user_id')->toArray();

            $school_list = array_merge($staffs,$parents,$managements);
            if(!empty($school_list))
                $userslist = SchoolUsers::whereIn('user_id',$school_list)->where('school_profile_id',$user->school_profile_id)->get()->toArray();
        }
        else if($request->distribution_type == 4)
        {
            $userslist = SchoolUsers::where('school_profile_id',$user->school_profile_id);
            if($request->role=='all')
            {
                // fetch all users mobile number under role staff,parent and management
                $userslist = $userslist->whereIn('user_role',[Config::get('app.Management_role'),Config::get('app.Parent_role'),Config::get('app.Staff_role')]);
            }
            else
            {
                $role[]= $request->role;
                // fetch all users mobile number under role staff,parent and management
                $userslist = $userslist->whereIn('user_role',$role);
            }

            $userslist = $userslist->get()->toArray();
        }

        if(!empty($templates) && !empty($userslist)) //check empty condition
        {
            foreach ($userslist as $key => $value) {
                if($value['user_role'] == 3)
                {
                    $schoolusers = SchoolUsers::where(['user_role'=>$value['user_role'],'school_profile_id'=>$user->school_profile_id,'id'=>$value['id']]);

                    $default_password_type = SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();
                    if($default_password_type == '')
                        $default_password_type = 'mobile_number';

                    if($default_password_type == 'admission_number' || $default_password_type == 'dob')
                    {
                        $parent_id = UserParents::where('user_id',$value['user_id'])->pluck('id')->first();
                        $mapped_student = UserStudentsMapping::where('parent',$parent_id)->pluck('student')->first();
                        $student_details = UserStudents::where('id',$mapped_student)->get()->first();
                    }

                    if($default_password_type == 'mobile_number')
                    {
                        $password = $value['user_mobile_number'];
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($value['user_mobile_number'])]);
                    }
                    else if($default_password_type == 'admission_number')
                    {
                        $password = $student_details->admission_number;
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($student_details->admission_number)]);
                    }
                    else if($default_password_type == 'dob')
                    {
                        $password = date('dmY',strtotime($student_details->dob));
                        $schoolusers = $schoolusers->update(['user_password'=>bcrypt($password)]);
                    }
                }
                else
                    $password =$value['user_mobile_number'];


                    // replace the mobile and password with corresponding value
                $message = str_replace("*mobileno*",$value['user_mobile_number'],$templates->message);
                $message = str_replace("*password*",$password,$message);

                // call send sms function
                $delivery_details = APISmsController::SendSMS($value['user_mobile_number'],$message,$templates->dlt_template_id);

                // store log in db.
                $status = 0;
                if(!empty($delivery_details) && isset($delivery_details['status']) && $delivery_details['status'] == 1)
                    $status = 1;
                $smslogs = ([
                    'sms_description'=>$message,
                    'sms_count'=>1,
                    'mobile_number'=>$value['user_mobile_number'],
                    'sent_by'=>$userall_id,
                    'status'=>$status
                ]);
                if(!empty($smslogs))
                    Smslogs::insert($smslogs); // store log in db.
            }
            return back()->with('success','SMS sent Successfully!...');
        }
        else
        {
            if(empty($userslist))
                return back()->with('error','No Users!...');
            else
                return back()->with('error','Please configure template details!...');
        }
    }

    public function getstudents(Request $request)
    {
        $parents= [];
        $parents = UserStudents::select('id','first_name')->where('user_status',1)->whereIn('class_config',$request->classes)->get()->toArray();
        echo json_encode($parents);
    }

}