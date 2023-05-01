<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-01-2023
 * Time: 05:40
 * Import class,sections ,staffs,managements ,students and parents details 
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Models\AcademicClassConfiguration;
use App\Exports\MapClassesSectionsExport;
use App\Imports\MapClassesSectionsImport;
use App\Models\CommunicationAttachments;
use App\Models\CommunicationDistribution;
use App\Models\AcademicSubjectsMapping;
use App\Models\CommunicationRecipients;
use App\Models\HomeworkParentStatus;
use App\Models\UserStudentsMapping;
use App\Models\SchoolAcademicYears;
use App\Exports\MapSubjectsExport;
use App\Imports\ManagementsImport;
use App\Imports\SubDivisionImport;
use App\Imports\MapSubjectsImport;
use App\Models\AcademicDivisions;
use App\Models\UserGroupsMapping;
use App\Imports\MapStaffsImport;
use App\Models\AcademicSections;
use App\Models\AcademicSubjects;
use App\Models\Smsvendordetails;
use App\Imports\SubjectsImport;
use App\Exports\SubjectsExport;
use App\Models\MapStaffsExport;
use App\Exports\StudentsExport;
use App\Models\AcademicClasses;
use App\Models\UserManagements;
use App\Imports\StudentsImport;
use App\Imports\SectionsImport;
use App\Exports\SectionsExport;
use App\Models\UserCategories;
use App\Models\Communications;
use App\Imports\ClassesImport;
use App\Exports\ClassesExport;
use App\Models\Configurations;
use App\Imports\StaffsImport;
use App\Exports\StaffsExport;
use App\Models\SchoolProfile;
use App\Models\Smstemplates;
use Illuminate\Http\Request;
use App\Models\UserStudents;
use App\Models\UserParents;
use App\Models\UserGroups;
use App\Imports\DOBImport;
use App\Models\SchoolUsers;
use App\Models\UserStaffs;
use App\Models\UserRoles;
use App\Models\UserAdmin;
use App\Models\Appusers;
use App\Models\UserAll;
use App\Models\Smslogs;
use Carbon\Carbon;
use Validator;
use Storage;
use Config;
use File;
use URL;
use DB;


class APIConfigurationsController extends Controller
{
	// import school details in db(classes,sections,subject,map subjects,staffs,map staffs,management,student)
	public function import_configuration(Request $request) //(on-boarding)
	{
		$user_data = auth()->user();
		$school_profile = SchoolProfile::where(['id'=>$user_data->school_profile_id])->get()->first();

		// $path = public_path('uploads/'.$school_profile->school_code);

	 //    if(!File::isDirectory($path))
	 //        File::makeDirectory($path, 0777, true, true);
		    
		if(strtolower($request->update_type) == 'manual')
		{

	        if($user_data->user_role == 1)
	            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

	        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user
	    }
	    if($request->configuration_type == 'division'){ //upload division details in DB
	    	$status='insert';
			if(strtolower($request->update_type) == 'manual')
			{
				// create sub-division 
				$divisions = $request->data;
				$this->divisions($divisions,$userall_id,'manual');				
			}
			else if(strtolower($request->update_type) == 'excel') //excel upload for sub-division
				Excel::import(new SubDivisionImport,request()->file('import_file'));

			// Excel::store(new ClassesExport,'Classes.xlsx');//creating dynamic excels
   // 			File::copy(storage_path('app/Classes.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Classes.xlsx'));

   // 			Excel::store(new SubjectsExport,'Subjects.xlsx');//creating dynamic excels
   // 			File::copy(storage_path('app/Subjects.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Subjects.xlsx'));

   // 			Excel::store(new SectionsExport,'Classes.xlsx');//creating dynamic excels
   // 			File::copy(storage_path('app/Sections.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Sections.xlsx'));


			return response()->json(['message'=>'Inserted Successfully!...']);
	    }
		if($request->configuration_type == 'classes')// Import classes data into DB
		{
		    $status='insert';
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new ClassesImport,request()->file('import_file')); //import excel sheet in db
			else
			{
		        $classes = $request->data;
		        $division_id = $request->division_id;
		        foreach ($classes as $key => $value) {
		        	$class_id ='';
		        	if(!isset($value['class_id']))
		        		$class_id = AcademicClasses::where(['class_name'=>$value['class_name'],'division_id'=>$division_id])->pluck('id')->first(); //check given class name is already exists or not
		        	if(isset($value['class_id']) && $value['class_id']!='' && $class_id=='')
		        	{
		        		// updated classes along with sub-division
		        		$class_details = AcademicClasses::where(['id'=>$value['class_id'],'division_id'=>$division_id])->first();
		        		$class_details->class_name = $value['class_name'];
		        		$class_details->division_id= $division_id;
	                    $class_details->updated_by = $userall_id;
	                    $class_details->updated_time = Carbon::now()->timezone('Asia/Kolkata');
	                    $class_details->save();
	                    $class_id = $value['class_id'];
		        	}
		        	else
		        	{
		                if($class_id=='')
		                {
		                	// insert classes along with sub-division details
		                    $academicclasses = new AcademicClasses;
		                    $academicclasses->class_name = $value['class_name'];
		                    $academicclasses->division_id= $division_id;
		                    $academicclasses->created_by = $userall_id;
		                    $academicclasses->created_time = Carbon::now()->timezone('Asia/Kolkata');
		                    $academicclasses->save();

		                    $class_id = $academicclasses->id;

		                    $status = $this->map_classes_sections($userall_id); //Map classes and sections
		                }
		            }
                    if(isset($value['class_id']) && $value['class_id']!='')
                    	$status = 'edit';
		        }
		        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['classes'=>1]);

		     //    Excel::store(new StudentsExport,'Students.xlsx');//creating dynamic excels
   				// File::copy(storage_path('app/Students.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Students.xlsx'));

		        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_classes_sections'=>1]); //update completion status in configuration table
		        return response()->json(['message'=>'Inserted Successfully!...']);exit();
			}
			if($status == 'edit')
                    return response()->json(['message'=>'Updated Successfully!...']);
                else
                    return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'sections')// Import sections data into DB
		{
			$status='insert';
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new SectionsImport,request()->file('import_file'));
			else
			{
				$sections = $request->data;
				$division_id = $request->division_id;
				foreach ($sections as $key => $value) {
					$section_id = AcademicSections::where(['section_name'=>$value['section_name'],'division_id'=>$division_id])->pluck('id')->first(); //check given section name is already exists or not
		            if(!isset($value['section_id']) && $section_id=='')
		            {
		            	// updated sections along with sub-division
		                $academicsections = new AcademicSections;
		                $academicsections->section_name = $value['section_name'];
		                $academicsections->division_id = $division_id;
		                $academicsections->created_by = $userall_id;
		                $academicsections->created_time = Carbon::now()->timezone('Asia/Kolkata');
		                $academicsections->save();

		                $section_id = $academicsections->id;
		            }
		            else
		            {
		            	if($section_id=='')
		            	{
		            		// insert sections along with sub-division details
		                	$section_details = AcademicSections::where(['id'=>$value['section_id'],'division_id'=>$division_id])->first();
			        		$section_details->section_name = $value['section_name'];
			        		$section_details->division_id = $division_id;
		                    $section_details->updated_by = $userall_id;
		                    $section_details->updated_time = Carbon::now()->timezone('Asia/Kolkata');
		                    $section_details->save();
		                }
		        	}
		        	if(isset($value['section_id']) && $value['section_id']!='')
	                   	$status = 'edit';
		        }
			    	
			    Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['sections'=>1]);
	   		}

	   		// Excel::store(new MapClassesSectionsExport,'MapClassesSections.xlsx');//creating dynamic excels
   			// File::copy(storage_path('app/MapClassesSections.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'MapClassesSections.xlsx'));
		    
			if($status == 'edit')
                    return response()->json(['message'=>'Updated Successfully!...']);
                else
                    return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'mapclassessections')// Import map classes and sections data into DB
		{
			$status='insert';
			// if(strtolower($request->update_type) == 'excel')
			// 	Excel::import(new MapClassesSectionsImport,request()->file('import_file'));
			// Excel::store(new StudentsExport,'Students.xlsx');
   // 			File::copy(storage_path('app/Students.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Students.xlsx'));
			if($status == 'edit')
                    return response()->json(['message'=>'Updated Successfully!...']);
                else
                    return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'subjects')// Import Subject data into DB
		{
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new SubjectsImport,request()->file('import_file'));
			else
			{
				$subjects = $request->data;
				$division_id = $request->division_id;
				$this->subjects($subjects,$division_id,$userall_id,'manual');
			}
			// Excel::store(new MapSubjectsExport,'MapSubjects.xlsx');
   // 			File::copy(storage_path('app/MapSubjects.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'MapSubjects.xlsx'));

   // 			Excel::store(new StaffsExport,'Staffs.xlsx');
   // 			File::copy(storage_path('app/Staffs.xlsx'), public_path(env('SAMPLE_CONFIG_URL').$school_profile->school_code.'/'.'Staffs.xlsx'));

   			return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'mapsubjects')// Import class and classteacher data into DB
		{
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new MapSubjectsImport,request()->file('import_file'));
			else
			{
				$data = $request->data;//get all inputs
				if(!empty($data))//check array is empty or not
				{
					foreach ($data as $key => $value) { //process the input in loop
						$subjects = AcademicSubjectsMapping::select('id')->where(['class_config'=> $request->class_config,'subject'=>$value['subject_id']])->get()->toArray(); //fetch all data for corresponding class and subject
						if(!empty($subjects) && $value['is_checked'] == 'false') //check not empty and not checked 
						{
							$group_id = UserGroups::where('class_config',$request->class_config)->pluck('id')->first();//fetch group id to remove message dropped for that unselected subject.
					        Communications::where('group_id',$group_id)->where('subject_id',$value['subject_id'])->where('communication_type',2)->delete();//delete subject related message from homework
					        AcademicSubjectsMapping::where('id',$subjects['id'])->delete(); //delete subject mapping record
					    }
					    else if($value['is_checked'] == 'true' && empty($subjects)) //check its a new entry
					    {
					    	$subject_config = new AcademicSubjectsMapping;
		                    $subject_config->subject = $value['subject_id'];
		                    $subject_config->class_config = $request->class_config;
		                    $subject_config->created_by=$userall_id;
		                    $subject_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $subject_config->save();//insert new record if not exist already
					    }
					}
	        		Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_subjects'=>1]);

	    			return response()->json(['message'=>'Submitted Successfully!...']);
				}
				return response()->json(['message'=>"Some inputs can't be empty!..."]);
			}
			return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'staffs')// Import Staff data into DB
		{
			if(strtolower($request->update_type) == 'excel'){
				Excel::import(new StaffsImport,request()->file('import_file'));
			}

			else
			{
				$staffs = $request->data;
				$this->staff($staffs);
			}
			return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'mapstaffs')// Import class and classteacher data into DB
		{
			if(strtolower($request->update_type) == 'manual')
			{
				$user_data = auth()->user();
				$data = $request->data;
				if(!empty($data))
				{
					if($user_data->user_role == 1)
			            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

			        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

					$classconfig = $request->class_config;
					foreach ($data as $key => $value) {
						if($classconfig!='' && $value['is_checked'] == 'false' && $value['subject_id']!='' && $value['staff_id']!='')
							AcademicSubjectsMapping::where('staff',$value['staff_id'])->where('subject',$value['subject_id'])->where('class_config',$classconfig)->update(['staff'=>null,'updated_by'=>$userall_id]);
					    else if($value['is_checked'] == 'true' && $classconfig!='' && $value['subject_id']!='' && $value['staff_id']!='') 
					    	AcademicSubjectsMapping::where('class_config',$classconfig)->where('subject',$value['subject_id'])->update(['staff'=>$value['staff_id'],'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
					}
			        
				}
			    return response()->json(['message'=>'Submitted Successfully!...']);
			}
			return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else if($request->configuration_type == 'managements')// Import class and classteacher data into DB
		{
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new ManagementsImport,request()->file('import_file'));
			else
			{
				// fetch academic year
		        $academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();

		        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		        $usermobile_numbers=[];
		        foreach ($request->data as $key => $value) {
		        	$image ='';
		        	if(!empty($value['photo']))
		        	{
		        		$name = explode('.',$value['photo']->getClientOriginalName())[0];
			        	$image = $name.''.time().'.'.$value['photo']->extension();
			        	$value['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'managements'), $image);
		        	}
		        	if(isset($value['management_id']) && $value['management_id']!='')
		        		$check_exists = UserManagements::where(['id'=>$value['management_id']])->first(); //To check given subject name is already exists in DB.
		        	else
		        		$check_exists = UserManagements::where(['mobile_number'=>$value['mobile_number']])->first(); //To check given subject name is already exists in DB.

		            if(empty($check_exists) && !in_array($value['mobile_number'],$usermobile_numbers) && (!isset($value['management_id']))) //if no then insert 
		            {
			        	//save management details
			            $UserManagements = new UserManagements;
			            $UserManagements->first_name= $value['management_person_name'];
			            $UserManagements->mobile_number=$value['mobile_number'];
			            $UserManagements->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'managements/'.$image):'';
			            $UserManagements->created_by=$userall_id;
			        	$UserManagements->created_time=Carbon::now()->timezone('Asia/Kolkata');
			            $UserManagements->save();

			            $management_id =$UserManagements->id; // staff id

			            // generate and update staff id in db 
			            $usermanagement_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'M'.sprintf("%04s", $management_id);
			            $UserManagements->user_id = $usermanagement_id;
			            $UserManagements->save();

			            $user_all = new UserAll;
			            $user_all->user_table_id=$management_id;
			            $user_all->user_role=5;
			            $user_all->save();

			            $schoolusers = new SchoolUsers;
			            $schoolusers->school_profile_id=$user_data->school_profile_id;
			            $schoolusers->user_id=$usermanagement_id;
			            $schoolusers->user_mobile_number=$value['mobile_number'];
			            $schoolusers->user_password=bcrypt($value['mobile_number']);
			            $schoolusers->user_role=5;
			            $schoolusers->user_status=1;
			            $schoolusers->save();
			        }
		        }
			}
			return response()->json(['message'=>'Inserted Successfully!...']);
		}

		else if($request->configuration_type == 'students')
		{
			if(strtolower($request->update_type) == 'excel')
				Excel::import(new StudentsImport,request()->file('import_file'));
			else
		        $this->students($request->data,$userall_id,'manual',$request->class_config);
		    return response()->json(['message'=>'Inserted Successfully!...']);
		}
		else
			return response()->json(['message'=>'Invalid Inputs!...']);exit(); 
             
        return response()->json(['message'=>'Inserted Successfully!...']);
	}

	public static function divisions($data,$userall_id,$upload_type) //upload sub-division in DB (on-boarding)
	{
		$user_data = auth()->user();
		$inserted_records=0;
        $status = 'insert';
		foreach ($data as $key => $value) {
			$check_exists = AcademicDivisions::where(['division_name'=>$value['division_name']])->pluck('id')->first(); //check whether the given sub-division name already exists ro not
			if(isset($value['division_id']) && $value['division_id']!='' && $check_exists=='')
        	{
        		//if already exists update the details
        		$academic_division = AcademicDivisions::where(['id'=>$value['division_id']])->first();
        		$academic_division->division_name = $value['division_name'];
                $academic_division->updated_by = $userall_id;
                $academic_division->updated_time = Carbon::now()->timezone('Asia/Kolkata');
                $academic_division->save();
        	}
        	else
        	{
                if($check_exists=='')
                {
               	 	//insert record if new sub-division 
                    $academic_division = new AcademicDivisions;
                    $academic_division->division_name = $value['division_name'];
                    $academic_division->created_by = $userall_id;
                    $academic_division->created_time = Carbon::now()->timezone('Asia/Kolkata');
                    $academic_division->save();

                    $division_id = $academic_division->id;
                }
            }
            if(isset($value['division_id']) && $value['division_id']!='')
            	$status = 'edit';
		}

    	Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['division'=>1]);
	}
	public function map_classes_sections($userall_id) //map classes and section - on-boarding
	{
		$user_data = auth()->user(); //check authentication
		$academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();//fetch academic year
		$classes = AcademicClasses::select('id','division_id')->get()->toArray(); //get all classes id and division 
		if(!empty($classes)) //check empty
		{
			foreach ($classes as $key => $value) {
				$sections =AcademicSections::select('id')->where('division_id',$value['division_id'])->get()->toArray(); //fetch all the sections inside selected sub-division id
				if(!empty($sections))
				{
					foreach ($sections as $sec_key => $sec_value) {
						$classconfig = AcademicClassConfiguration::where(['class_id'=>$value['id'],'section_id'=>$sec_value['id']])->pluck('id')->first(); //check the configuration already exist in db
						if($classconfig=='') //not exists means,insert configuration (map)
		            	{
			        		$class_config = new AcademicClassConfiguration;
		                    $class_config->academic_year = $academicyear;
		                    $class_config->class_id = $value['id'];
		                    $class_config->section_id = $sec_value['id'];
		                    $class_config->division_id=$value['division_id'];
		                    $class_config->created_by=$userall_id;
		                    $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $class_config->save();
		                    $class_config_id = $class_config->id;
		            	}
					}
				}
	        }
	    }	
	}

	public function class_review() //check inserted classes review (on-boarding)
	{
		// Save last login in DB
        $userdata = auth()->user();
        $classes = AcademicClasses::select('id','class_name','division_id')->get(); //fetch all the classes and form in array format
        foreach ($classes as $key => $value) {
        	$class_review[]=([
        		'id'=>$value->id,
        		'class_name'=>$value->class_name,
        		'division_id'=>$value->division_id,
        		'division_name'=>$value->divisionName(),
        	]);
        }
        return response()->json($class_review);
	}

	public function class_section_review(Request $request) //check inserted classes and sections review (on-boarding)
	{
		// Save last login in DB
        $userdata = auth()->user();
        $division_id = $request->division_id;
        $class_section_review=[];
        $classessections = AcademicClassConfiguration::select('id','class_id','section_id')->where('division_id',$division_id)->get(); //fetch all mapped classes and section from table
        if(!empty($classessections))
        {
	        foreach ($classessections as $key => $value) { //formed mulit-dimenisional array along with sections
	        	$section_details = $value->sectionName();

	        	$classsection_data[$value->class_id]=([
	        		'id'=>$value->id,
	        		'class_id'=>$value->class_id,
	        		'class_name'=>$value->className(),
	        		'division_id'=>$section_details->division_id,
	        		'division_name'=>$section_details->divisionName(),
	        	]);

	        	$sections[$value->class_id][]= ([
	        		'section_id'=>$value->section_id,
	        		'section_name'=>$section_details->section_name
	        	]);
	        }
	        $index = 0;
	        foreach ($classsection_data as $class_key => $class_value) {
	        	$class_section_review[$index] = $classsection_data[$class_value['class_id']];
	        	$class_section_review[$index]['sections'] = $sections[$class_value['class_id']];
	        	$index++;
	        }
	       
	    }
	    return response()->json($class_section_review);
	}

	public function get_class_section(Request $request) //list all classes and sections for mapping(on-boarding)
	{
		$sections_list = $selected_section= [];
		$classconfig = AcademicClassConfiguration::select('section_id')->where(['class_id'=> $request->class_id,'division_id'=>$request->division_id])->get()->toArray();

		$sections = AcademicSections::select('id','section_name')->where(['division_id'=>$request->division_id])->get()->toArray();
		if(!empty($sections))
		{
			if(!empty($classconfig))
				$selected_section = array_column($classconfig, 'section_id');

			foreach ($sections as $key => $value) {
				$sections_list[] = ([
					'id'=>$value['id'],
					'section_name'=>$value['section_name'],
					'is_checked'=>in_array($value['id'], $selected_section)
				]);
			}
		}
		return response()->json($sections_list);
	}

	public function delete_class_section(Request $request) //delete unchecked mapping (on-boarding)
	{
		$data = $request->data;
		if(!empty($data))
		{
			foreach ($data as $key => $value) {
				$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'section_id'=>$value['section_id']])->get()->toArray();
				if(!empty($classconfig) && $value['is_checked'] == 'false')
				{
					$classconfig = array_column($classconfig,'id');
			        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			        UserStudents::whereIn('class_config',$classconfig)->delete();
			        AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
			        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
			    }
			    else if($value['is_checked'] == 'true' && empty($classconfig)) 
			    {
			    	$user_data = auth()->user();

			        if($user_data->user_role == 1)
			            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

			        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

			    	$academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();
			    	$class_config = new AcademicClassConfiguration;
                    $class_config->academic_year = $academicyear;
                    $class_config->class_id = $request->class_id;
                    $class_config->section_id = $value['section_id'];
                    $class_config->division_id=$request->division_id;
                    $class_config->created_by=$userall_id;
                    $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $class_config->save();
                    $class_config_id = $class_config->id;
			    }
			}
	        
		}
	    return response()->json(['message'=>'Submitted Successfully!...']);
	}

	// Get Subjects list 
	public function get_subjects(Request $request) //get all subjects list (on-boarding)
	{
		$subjectslist = $selected_subject =[];
		$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'section_id'=>$request->section_id,'division_id'=>$request->division_id])->get()->toArray();

		$subjects = AcademicSubjectsMapping::select('subject')->where(['class_config'=> $classconfig])->get()->toArray();

		$subjects_list = AcademicSubjects::select('id','subject_name')->where(['division_id'=>$request->division_id])->get()->toArray();
		if(!empty($subjects_list))
		{
			if(!empty($subjects))
				$selected_subject = array_column($subjects, 'subject');

			foreach ($subjects_list as $key => $value) {
				$subjectslist[] = ([
					'id'=>$value['id'],
					'subject_name'=>$value['subject_name'],
					'is_checked'=>in_array($value['id'], $selected_subject)
				]);
			}
		}
		return response()->json($subjectslist);
	}

	// Configuration page list
	public function configuration_list()
	{
		// Save last login in DB
        $userdata = auth()->user();

        // Fetch configuration details from DB for corresponding school
        $configurations = Configurations::where('school_profile_id',$userdata->school_profile_id)->first();
        
        // configuration details
        $configuration = ([
            'classes'=>[
            	'config'=>($configurations->classes==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Classes.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Classes.xlsx':'')
            ],
            'sections'=>[
            	'config'=>($configurations->sections==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Sections.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Sections.xlsx':'')
            ],
            'map_classes_sections'=>[
            	'config'=>($configurations->map_classes_sections==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'MapClassesSections.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'MapClassesSections.xlsx':'')
            ],
            'subjects'=>[
            	'config'=>($configurations->subjects==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Subjects.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Subjects.xlsx':''),
            ],
            'map_subjects'=>[
            	'config'=>($configurations->map_subjects==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'MapSubjects.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'MapSubjects.xlsx':''),
            ],
            'staffs'=>[
            	'config'=>($configurations->staffs==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Staffs.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Staffs.xlsx':''),
            ],
            // 'map_staffs'=>[
            // 	'config'=>($configurations->map_staffs==1)?true:false,
            // 	'excel'=>(file_exists(public_path('uploads/M.xlsx'))?env('APP_URL').'uploads/classes_sections.xlsx':''),
            // ],
            'management'=>[
            	'config'=>($configurations->management==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Management.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Management.xlsx':''),
            ],
            'students'=>[
            	'config'=>($configurations->students==1)?true:false,
            	'excel'=>(file_exists(public_path(env('SAMPLE_CONFIG_URL').'Students.xlsx'))?env('APP_URL').env('SAMPLE_CONFIG_URL').'Students.xlsx':''),
            ],
            // 'map_students'=>[
            // 	'config'=>($configurations->map_students==1)?true:false,
            // 	'excel'=>(file_exists(public_path('uploads/classes_sections.xlsx'))?env('APP_URL').'uploads/classes_sections.xlsx':''),
            // ]
        ]);

        // return token 
        return response()->json(compact('configuration'));
	}

	// Fetch classes ,sections,subjects list
	public function get_classes_sections_subjects_list()
	{
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->get()->toArray();
		return response()->json(compact('classes','sections','subjects'));
	}

	// Fetch classes list
	public function get_edit_classes_list(Request $request)
	{
		$classes = AcademicClasses::select('id','class_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('classes'));
	}

	// Fetch classes list
	public function get_edit_allsection_list(Request $request)//get all the sections with the pne single sub-division for edit in on-boarding
	{
		$sections = AcademicSections::select('id','section_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('sections'));
	}

	// Fetch management list
	public function get_edit_management_list() //get all the management for edit in on-boarding
	{
		$management_list = [];//empty array declaration
		$managements = UserManagements::select('*')->get()->toArray();//get management list
		if(!empty($managements)) //check not empty
		{
			foreach ($managements as $key => $value) {
				$management_list[] = ([
					'id'=>$value['id'],
					'management_person_name'=>$value['first_name'],
					'photo'=>$value['profile_image'],
					'mobile_number'=>$value['mobile_number'],
				]);

			}
		}
		return response()->json($management_list); //return management details 
	}

	// Fetch student list
	public function get_edit_student_list() //get all the student for edit in on-boarding
	{
		$student_list = [];//empty array declaration
		$parents = UserParents::select('id','first_name','mobile_number')->where('user_category',1)->get()->toArray();//get student list
		return response()->json($parents); //return student details 
	}

	// Fetch Sections list
	public function get_edit_sections_list()
	{
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		foreach ($sections as $key => $value) {
			$sections[$key]=$value;
			$sections[$key]['isclicked']=false;
		}
		return response()->json(compact('sections'));
	}

	public static function subjects($data,$division_id,$userall_id,$upload_type)
	{
		$user_data = auth()->user();
		$subject_list =$subject_data=[];
		$inserted_records=0;
        $status = 'insert';
        foreach ($data as $row=>$value) {

        	if($value['subject_name']!='')
        	{
        		if(isset($value['subject_id']) && $value['subject_id']!='')
        		{
        			$status = 'edit';

        			$subject_data = AcademicSubjects::where(['id'=>$value['subject_id'],'division_id'=>$division_id])->get()->first(); //To check given subject name is already exists in DB.
        			// Prepare subjects array
	                $subject_data->subject_name = $value['subject_name'];
	                $subject_data->short_name = isset($value['short_name'])?$value['short_name']:'';
	                $subject_data->division_id = $division_id;
	                $subject_data->updated_by=$userall_id;
	            	$subject_data->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	            	$subject_data->save();
        		}
        		else
        		{
        			$status = 'insert';
        			if(isset($value['division_name']) && $value['division_name'] !='')
        				$division_id = AcademicDivisions::where(['division_name'=>$value['division_name']])->pluck('id')->first();
		        	$check_exists = AcademicSubjects::where(DB::raw('lower(subject_name)'), strtolower($value['subject_name']))->where('division_id',$division_id)->pluck('id')->first(); //To check given subject name is already exists in DB.
		            if($check_exists == '' && !in_array($value['subject_name'],$subject_list)) //if no then insert 
		            {
		                array_push($subject_list, $value['subject_name']);//check mobile number already exists in array

		                // Prepare subjects array
		                $subject_data[]=([
		                    'subject_name' => $value['subject_name'],
		                    'short_name' => isset($value['short_name'])?$value['short_name']:'',
		                    'division_id' => $division_id,
		                    'created_by'=>$userall_id,
		            		'created_time'=>Carbon::now()->timezone('Asia/Kolkata')
		                ]);
		            }
		        }
	        }
        }
        if($status == 'insert')
        	AcademicSubjects::insert($subject_data); // Insert subjects data into DB
    	Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['subjects'=>1]);
	}

	// Map subjects
	public static function mapsubjects($collection,$userall_id,$academicyear,$upload_type)
	{
		$user_data = auth()->user();
		$inserted_records = 0;
		//Process each and every row ,insert all data in db
        foreach ($collection as $row) {
            if($row['class_name']!='' && $row['section_name']!='' && $row['subject_name']!='')
            {         
            	if($upload_type == 'import')
            	{          
	                $class_id = AcademicClasses::where(DB::raw('lower(class_name)'), strtolower($row['class_name']))->pluck('id')->first(); //To check given class name is already exists in DB.

	                $section_id = AcademicSections::where(DB::raw('lower(section_name)'), strtolower($row['section_name']))->pluck('id')->first(); //To check given section name is already exists in DB.

	                $subject_id = AcademicSubjects::where(DB::raw('lower(subject_name)'), strtolower($row['subject_name']))->pluck('id')->first(); //To check given subject name is already exists in DB.

	                $division_id = AcademicDivisions::where(DB::raw('lower(division_name)'), strtolower($row['division_name']))->pluck('id')->first(); //To check given subject name is already exists in DB.
	            }
	            else
	            {
	            	$class_id = $row['class_name'];
	            	$section_id = $row['section_name'];
	            	$subject_id = $row['subject_name'];
	            	$division_id = $row['division_name'];

	            }

                if($class_id != '' && $section_id!='' && $subject_id!='')
                {
                    $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id,'division_id'=>$division_id])->pluck('id')->first(); //To check given config details is already exists in DB.

                    if($class_config_id == '')
                    {
                        $class_config = new AcademicClassConfiguration;
                        $class_config->academic_year = $academicyear;
                        $class_config->class_id = $class_id;
                        $class_config->section_id = $section_id;
                        $class_config->division_id = $division_id;
                        $class_config->created_by=$userall_id;
                        $class_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                        $class_config->save();
                        $class_config_id = $class_config->id;
                    }
                    if($class_config_id!='')
                    {
                        $subject_config_id = AcademicSubjectsMapping::where(['class_config'=>$class_config_id ,'subject'=>$subject_id])->pluck('id')->first(); //To check given subject mapping is already exists in DB.
                        if($subject_config_id == '')
                        {
                            // Insert mapping in table
                            $subject_config = new AcademicSubjectsMapping;
                            $subject_config->subject = $subject_id;
                            $subject_config->class_config = $class_config_id;
                            $subject_config->created_by=$userall_id;
                            $subject_config->created_time=Carbon::now()->timezone('Asia/Kolkata');
                            $subject_config->save();
                            $inserted_records++;
                        }
                    }
                }

            }
        }

        if($inserted_records>0)
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_subjects'=>1]);
	}

	//Staffs Category
	public function get_staff_category()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',2)->get()->toArray();
		return response()->json(compact('categories'));
	}

	//Staffs Category
	public function get_staff_category_class()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',2)->get()->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->get()->toArray();
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		return response()->json(compact('categories','subjects','classes','sections'));
	}

	// Store Staff details(on-boarding)
	public function staff($data)
	{
		$user_data = auth()->user();

        if($user_data->user_role == 1)//check role and get current user id
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

        // fetch academic year
        $academicyear = SchoolAcademicYears::where(['school_profile_id'=>$user_data->school_profile_id])->pluck('academic_year')->first();

        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

        $usermobile_numbers=[];
        foreach ($data as $key => $value) {
        	$image ='';
        	if(!empty($value['photo']))//check upload photo exist or not
        	{

        		$name = explode('.',$value['photo']->getClientOriginalName())[0];
	        	$image = $name.''.time().'.'.$value['photo']->extension();
	        	$value['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'staffs'), $image);
        	}
        	$check_exists = UserStaffs::where(['mobile_number'=>$value['mobile_number']])->first(); //To check given subject name is already exists in DB.
            if(empty($check_exists) && !in_array($value['mobile_number'],$usermobile_numbers) ) //if no then insert 
            {
	        	//save staff details
	            $staffs_details = new UserStaffs;
	            $staffs_details->first_name= $value['staff_name'];
	            $staffs_details->mobile_number=$value['mobile_number'];
	            if($image!='')
	            	$staffs_details->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'staffs/'.$image):'';
	            $staffs_details->created_by=$userall_id;
	        	$staffs_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	            $staffs_details->save();

	            $staff_id =$staffs_details->id; // staff id

	            // generate and update staff id in db 
	            $userstaff_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'T'.sprintf("%04s", $staff_id);
	            $staffs_details->user_id = $userstaff_id;
	            $staffs_details->save();

	            $user_all = new UserAll;
	            $user_all->user_table_id=$staff_id;
	            $user_all->user_role=2;
	            $user_all->save();

	            $schoolusers = new SchoolUsers;
	            $schoolusers->school_profile_id=$user_data->school_profile_id;
	            $schoolusers->user_id=$userstaff_id;
	            $schoolusers->user_mobile_number=$value['mobile_number'];
	            $schoolusers->user_password=bcrypt($value['mobile_number']);
	            $schoolusers->user_role=2;
	            $schoolusers->user_status=1;
	            $schoolusers->save();
	        }
        }
        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['staffs'=>1]);
	}

	//Staffs Category
	public function get_allsubjects_list(Request $request)
	{
		$subjects = AcademicSubjects::select('id','subject_name')->where('division_id',$request->division_id)->get()->toArray();
		return response()->json(compact('subjects'));
	}

	// Add students in DB along with parents and guardian details
	public static function students($data,$userall_id,$upload_type,$class_config)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		$inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($data as $row) {
             $check_exists = UserParents::where('mobile_number',$row['mobile_number']);
            $result = $check_exists->first(); //To check given subject name is already exists in DB.
            
            // array_push($usermobile_numbers, $row['mobile_number']);//check mobile number already exists in array
            $image ='';
        	if(!empty($row['photo']))
        	{

        		$name = explode('.',$row['photo']->getClientOriginalName())[0];
	        	$image = $name.''.time().'.'.$row['photo']->extension();
	        	$row['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'students'), $image);
        	}
        	$class_config_id = null;

        	$parent=[];
            $parent_details = new UserParents;
        	$parent_details->created_by=$userall_id;
        	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $parent_details->mobile_number= $row['mobile_number'];
            $parent_details->first_name= $row['father_name'];
            $parent_details->user_status=1;//active
            $parent_details->user_category = 1;
                
                
            $parent_details->save();

            $parent_id = $parent_details->id;

            // generate and update staff id in db 
            $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
            $parent_details->user_id = $userparent_id;
            $parent_details->save(); 

            $user_all = new UserAll;
            $user_all->user_table_id=$parent_details->id;
            $user_all->user_role=Config::get('app.Parent_role');
            $user_all->save(); 

                
            $schoolusers = new SchoolUsers;

            $schoolusers->school_profile_id=$user_data->school_profile_id;
            $schoolusers->user_id=$userparent_id;
            $schoolusers->user_mobile_number=$row['mobile_number'];
            $schoolusers->user_password=bcrypt($row['mobile_number']);
            $schoolusers->user_role=Config::get('app.Parent_role');
            $schoolusers->user_status=1;
            $schoolusers->save();
        }
            
	}

	// Add managment person in DB.
	public function get_management_designation()
	{
		$categories = UserCategories::select('id','category_name')->where('user_role',5)->get()->toArray();
		return response()->json($categories);
	}

	// get classes and sections for edit
	public function get_edit_classes_sections()
	{
		$classes = AcademicClasses::select('id','class_name')->get()->toArray();
		$sections = AcademicSections::select('id','section_name')->get()->toArray();
		return response()->json(compact('classes','sections'));
	}

	// get subjects for edit
	public function get_edit_subjects(Request $request)
	{
		$subject_ids = AcademicSubjectsMapping::where('class_config',$request->class_config)->pluck('subject')->toArray();
		$subjects = AcademicSubjects::select('id','subject_name')->where('division_id',$request->division_id)->whereIn('id',$subject_ids)->get()->toArray();
		return response()->json($subjects);
	}

	// 
	public function get_staff_details(Request $request)
	{
		$subjects = AcademicSubjectsMapping::where('subject',$request->subject_id)->where('class_config',$request->class_config)->pluck('staff')->toArray();
		$all_staffs = UserStaffs::where('specialized_in',$request->subject_id)->get()->toArray();
		$staffs = [];
		if(!empty($all_staffs))
		{
			foreach ($all_staffs as $key => $value) {
				$staffs[] = ([
					'staff_name'=>$value['first_name'],
					'staff_id'=>$value['id'],
					'is_checked'=>(!empty($subjects) && in_array($value['id'],$subjects))?true:false
				]);
			}
		}
		return response()->json($staffs);
	}

	public function get_divisions()
	{
		$divisions = AcademicDivisions::select('id','division_name')->get()->toArray();
		return response()->json(compact('divisions'));
	}

	//Staffs Category
	public function get_combine_class_section_list(Request $request)
	{
		$class_sections = [];
		$class_sections_list = AcademicClassConfiguration::select('id','class_id','section_id')->where('division_id',$request->division_id)->get();
		if(!empty($class_sections_list))
		{
			foreach ($class_sections_list as $key => $value) {
				$class_sections[] = ([
					'class_section'=>$value->classsectionName(),
					'id'=>$value->id
				]);
			}
		}
		return response()->json($class_sections);
	}

	// To activate or create static groups
	public function activate_default_groups()
	{
		$user_data = auth()->user();
		$management_group = $admin_group = $staff_group = $parent_group = $teaching_staffs = $parent_class_group=[];

		if($user_data->user_role == 1)//check role and get current user id
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first();

		$group_activated = Configurations::where('school_profile_id',$user_data->school_profile_id)->pluck('default_group_activated')->first();
		if($group_activated==0)
		{
			// Create Management only group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Management Only';
	        $usergroups->group_description='This group only contains management users.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        // Fetch all managment users
			$management_users = UserManagements::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);

			$management_group=[];
			// Create Whole School group - Starts
			$usergroups = new UserGroups;
	        $usergroups->group_name='Whole School';
	        $usergroups->group_description='This group contains all users in the school.';
			$usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];
	        // Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			$admin_group=[];
			// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];
			// Fetch all Parent users
			$parent_users = UserParents::select('id','user_id')->where('user_status',1)->get()->toArray();
			foreach ($parent_users as $parent_key => $parent_value) {
				$parent_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$parent_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Parent_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($parent_group))
				UserGroupsMapping::insert($parent_group);
			$parent_group=[];
			// Create Whole School group - Ends


			// Create school internal communication group
			$usergroups = new UserGroups;
	        $usergroups->group_name='School Internal Communication';
	        $usergroups->group_description='This group contains all teaching staffs and non-teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        // Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];
			// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);

			$staff_group=[];
			// Create Academic Staff group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Academic Staffs';
	        $usergroups->group_description='This group contains only teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        /// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_category',Config::get('app.Teaching_staff'))->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];

			// Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];

			// Create Non-Teaching Staff group
			$usergroups = new UserGroups;
	        $usergroups->group_name='Non-Teaching Staffs';
	        $usergroups->group_description='This group contains only non-teaching staffs.';
	        $usergroups->group_action=1;//1-admin
	        $usergroups->group_status=1;//1-active
	        $usergroups->group_type=1;
	        $usergroups->created_by=$userall_id;
	        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
	        $usergroups->save();

	        // Get inserted recored id
	        $usergroup_id = $usergroups->id;

	        foreach ($management_users as $management_key => $management_value) {
				$management_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$management_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Management_role'),
				]);
			}

			// Insert group users in mapping table
			if(!empty($management_group))
				UserGroupsMapping::insert($management_group);
			$management_group=[];

	        /// Fetch all Staffs users
			$staff_users = UserStaffs::select('id','user_id')->where('user_category',Config::get('app.Non_teaching_staff'))->where('user_status',1)->get()->toArray();

			foreach ($staff_users as $staff_key => $staff_value) {
				$staff_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$staff_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Staff_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($staff_group))
				UserGroupsMapping::insert($staff_group);
			$staff_group=[];

			// Fetch all admin users
			$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

			foreach ($admin_users as $admin_key => $admin_value) {
				$admin_group[] = ([
					'group_id'=>$usergroup_id,
					'user_table_id'=>$admin_value['id'],
					'group_access'=>1,
					'user_role'=>Config::get('app.Admin_role'),
				]);
			}
			// Insert group users in mapping table
			if(!empty($admin_group))
				UserGroupsMapping::insert($admin_group);
			
			$admin_group=[];
			
			/*create class-sections groups -  starts*/
			$class_config = AcademicClassConfiguration::select('id','class_id','section_id','class_teacher')->get()->toArray();

			if(!empty($class_config))
			{
				$classes = array_column(AcademicClasses::select('id','class_name')->get()->toArray(),'class_name','id');
				$sections = array_column(AcademicSections::select('id','section_name')->get()->toArray(),'section_name','id');
				foreach ($class_config as $class_key => $class_value) {
					if(isset($classes[$class_value['class_id']]) && $classes[$class_value['class_id']]!='' && isset($sections[$class_value['section_id']]) && $sections[$class_value['section_id']]!='' )
					{
						$usergroups = new UserGroups;
				        $usergroups->group_name=$classes[$class_value['class_id']].' - '.$sections[$class_value['section_id']];
				        $usergroups->group_description='This group contains only parents in class '.$classes[$class_value['class_id']].' - '.$sections[$class_value['section_id']];
				        $usergroups->group_action=1;//1-admin
				        $usergroups->group_status=1;//1-active
				        $usergroups->group_type=2;
				        $usergroups->class_config=$class_value['id'];
				        $usergroups->created_by=$userall_id;
				        $usergroups->created_time=Carbon::now()->timezone('Asia/Kolkata');
				        $usergroups->save();

				        // Get inserted recored id
				        $usergroup_id = $usergroups->id;
				        if(isset($class_value['class_teacher']))
				        {
					        // Insert class teacher into group
					        $classteacher_group = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$class_value['class_teacher'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Staff_role'),
							]);
							// Insert group users in mapping table
							UserGroupsMapping::insert($classteacher_group);
							$classteacher_group=[];
						}

						 // Fetch all admin users
						$admin_users = UserAdmin::select('id','user_id')->where('user_status',1)->get()->toArray();

						foreach ($admin_users as $admin_key => $admin_value) {
							$admin_group[] = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$admin_value['id'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Admin_role'),
							]);
						}
						// Insert group users in mapping table
						if(!empty($admin_group))
							UserGroupsMapping::insert($admin_group);
						
						$admin_group=[];

						foreach ($management_users as $management_key => $management_value) {
							$management_group[] = ([
								'group_id'=>$usergroup_id,
								'user_table_id'=>$management_value['id'],
								'group_access'=>1,
								'user_role'=>Config::get('app.Management_role'),
							]);
						}

						// Insert group users in mapping table
						if(!empty($management_group))
							UserGroupsMapping::insert($management_group);
						$management_group=[];

				        // Fetch all Staffs users
						$teaching_staffs = AcademicSubjectsMapping::where('class_config',$class_value['id'])->pluck('staff')->toArray();

						if(!empty($teaching_staffs) && !empty($teaching_staffs[0]))
						{
							foreach ($teaching_staffs as $staff_key => $staff_value) {
								$teaching_staffs_group[] = ([
									'group_id'=>$usergroup_id,
									'user_table_id'=>$staff_value,
									'group_access'=>2,
									'user_role'=>Config::get('app.Staff_role'),
								]);
							}
							// Insert group users in mapping table
							if(!empty($teaching_staffs_group))
								UserGroupsMapping::insert($teaching_staffs_group);
							$teaching_staffs_group=[];
						}
						//Fetch all Parent users
						$student_ids = UserStudents::select('id')->where('class_config',$class_value['id'])->get()->toArray();

						if(!empty($student_ids))
						{
							$studentids = array_column($student_ids,'id');

							$parent_ids = UserStudentsMapping::whereIn('student',$studentids)->pluck('parent')->toArray();
							if(!empty($parent_ids))
							{
								$teaching_staffs_group = [];
								foreach ($parent_ids as $parentkey => $parentvalue) {
									$teaching_staffs_group[] = ([
										'group_id'=>$usergroup_id,
										'user_table_id'=>$parentvalue,
										'group_access'=>2,
										'user_role'=>Config::get('app.Parent_role'),
									]);
								}

								// Insert group users in mapping table
								if(!empty($teaching_staffs_group))
									UserGroupsMapping::insert($teaching_staffs_group);
								$teaching_staffs_group=[];
							}
						}
				    }
				}
			}
			/*create class-sections groups -  ends*/
			
			Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['default_group_activated'=>1]);

			return response()->json(['message'=>'Default Group Activated Successfully...']);
		}
		else
			return response()->json(['message'=>'Default Group Already Activated...']);
	}
	// Fetch group details
	public function user_group_list()
	{
		$user_data = auth()->user();
		$user_role = '';
		$school_name = SchoolProfile::where('id',$user_data->school_profile_id)->pluck('school_name')->first();
		if($user_data->user_role == Config::get('app.Admin_role'))//check role and get current user id
		{
            $userid = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
            $user_role = 'admin';
		}
        else if($user_data->user_role == Config::get('app.Management_role'))
        {
        	$userid = UserManagements::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'management';
        }
        else if($user_data->user_role == Config::get('app.Staff_role'))
        {
        	$userid = UserStaffs::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'staff';
        }
        else if($user_data->user_role == Config::get('app.Parent_role'))
        {
        	$userid = UserParents::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        	$user_role = 'parent';
        }

		$group_ids = UserGroupsMapping::select('group_id')->where('user_role',$user_data->user_role)->where('user_table_id',$userid)->groupBy('group_id')->get()->toArray();
		
		if(!empty($group_ids))
		{
			$groupids = array_column($group_ids,'group_id');
			$group_list = UserGroups::select('id','group_name','group_description')->where('group_type',1)->whereIn('id',$groupids)->get()->toArray(); //default groups
			return response()->json(compact('school_name','user_role','group_list'));
		}
		else
			return response()->json(['message'=>'No groups Configured!...']);
	}

	public function classes_group(Request $request)
	{
		$user_data = auth()->user(); //check authentication
		$class_group=[];
		$school_name = SchoolProfile::where('id',$user_data->school_profile_id)->pluck('school_name')->first();//fetch school name
		$user_category = '';
		if($user_data->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $userid = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        else if($user_data->user_role == Config::get('app.Management_role'))
        	$userid = UserManagements::where(['user_id'=>$user_data->user_id])->pluck('id')->first();
        else if($user_data->user_role == Config::get('app.Staff_role'))
        {
        	$data = UserStaffs::select('id','user_category')->where(['user_id'=>$user_data->user_id])->get()->first();
        	$userid = $data['id'];
        	$user_category = ($data['user_category']!=null && $data['user_category']!='' && $data['user_category'] == Config::get('app.Teaching_staff'))?'teaching staff':'nonteaching staff';
        }
        else if($user_data->user_role == Config::get('app.Parent_role'))
        	$userid = UserParents::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $group_ids = UserGroupsMapping::select('group_id','group_access')->where('user_role',$user_data->user_role)->where('user_table_id',$userid)->groupBy('group_id')->get()->toArray();

		if(!empty($group_ids)) //fetch school groups
		{
			$groupids = array_column($group_ids,'group_id');
			$groupaccess = array_column($group_ids,'group_access','group_id');
			$group_list = UserGroups::select('id','group_name','group_description','class_config')->where('group_type',2)->whereIn('id',$groupids);
			if($request->student_id!='')
			{
				$config_id = UserStudents::where('id',$request->student_id)->pluck('class_config')->first();
				$group_list = $group_list->where('class_config',$config_id);
			}
			$group_list = $group_list->get()->toArray(); //class groups
			foreach ($group_list as $group_key => $group_value) {
				$classteacher_name ='';
				$classteacher_id = AcademicClassConfiguration::where('id',$group_value['class_config'])->pluck('class_teacher')->first();
				if($classteacher_id!='')
					$classteacher_name = UserStaffs::where('id',$classteacher_id)->pluck('first_name')->first();
				$approval_pending = count(Communications::select('id')->where('group_id',$group_value['id'])->whereNull('approval_status')->get()->toArray());

				$parent_ids = UserGroupsMapping::where(['user_role'=>Config::get('app.Parent_role'),'group_id'=>$group_value['id']])->pluck('user_table_id')->toArray();
				$parent_online = 0;
				if(!empty($parent_ids))
				{
					$parent_user_ids = UserParents::whereIn('id',$parent_ids)->pluck('user_id')->toArray();
					$last_login_details = SchoolUsers::whereIn('user_id',$parent_user_ids)->whereDate('last_login', Carbon::today())->pluck('last_login')->toArray();
					if(!empty($last_login_details))
					{
						foreach ($last_login_details as $loginkey => $loginvalue) {

    						$newDate = strtotime(Carbon::now()->timezone('Asia/Kolkata').'-1 minutes');
							$last_login_time = strtotime($loginvalue);
							if($last_login_time>=$newDate)
							{
								$parent_online++;
							}
						}
					}
				}
				$subject_list = [];
				$get_subject_ids = AcademicSubjectsMapping::select('subject')->where('class_config',$group_value['class_config'])->get()->toArray(); //fetch all the subject ids for that corresponding class
				if(!empty($get_subject_ids))
				{
					$subject_list = AcademicSubjects::select('id','subject_name')->whereIn('id',$get_subject_ids)->get()->toArray(); //fetch all subject names from ids
					$homework_date = date("Y-m-d");
					if($request->homework_date!='')
						$homework_date = $request->homework_date;
					$approved_subject_homeworks = Communications::whereIn('subject_id',$get_subject_ids)->where('communication_type',2)->where('group_id',$group_value['id'])->where('actioned_time', 'like', '%' .$homework_date. '%')->get()->toArray();
				}

				if($groupaccess[$group_value['id']] == 1)
				{
					$classteacher_group[$group_key]['group_id'] = $group_value['id'];
					$classteacher_group[$group_key]['group_name'] = $group_value['group_name'];
					$classteacher_group[$group_key]['group_description'] = $group_value['group_description'];
					$classteacher_group[$group_key]['class_teacher'] = $classteacher_name;
					$classteacher_group[$group_key]['approval_pending'] = $approval_pending;
					$classteacher_group[$group_key]['parent_online'] = $parent_online;
					$classteacher_group[$group_key]['class_config'] = $group_value['class_config'];
					$classteacher_group[$group_key]['classteacher']=($groupaccess[$group_value['id']] == 1)?'yes':'no';
					$classteacher_group[$group_key]['total_parent_count'] =(!empty($parent_ids))?count($parent_ids):0;
					$classteacher_group[$group_key]['subject_list']= $subject_list;
					$classteacher_group[$group_key]['uploaded_homeworks_count']=(!empty($approved_subject_homeworks))?count($approved_subject_homeworks):0;


				}
				else
				{
					$staff_group[$group_key]['group_id'] = $group_value['id'];
					$staff_group[$group_key]['group_name'] = $group_value['group_name'];
					$staff_group[$group_key]['group_description'] = $group_value['group_description'];
					$staff_group[$group_key]['class_teacher'] = $classteacher_name;
					$staff_group[$group_key]['approval_pending'] = $approval_pending;
					$staff_group[$group_key]['parent_online'] = $parent_online;
					$staff_group[$group_key]['class_config'] = $group_value['class_config'];
					$staff_group[$group_key]['classteacher']=($groupaccess[$group_value['id']] == 1)?'yes':'no';
					$staff_group[$group_key]['total_parent_count'] =(!empty($parent_ids))?count($parent_ids):0;
					$staff_group[$group_key]['subject_list']= $subject_list;
					$staff_group[$group_key]['uploaded_homeworks_count']=(!empty($approved_subject_homeworks))?count($approved_subject_homeworks):0;
				}
			}
			if(!empty($classteacher_group))
				$class_group =  array_merge($class_group,$classteacher_group);

			if(!empty($staff_group))
				$class_group =  array_merge($class_group,$staff_group);

			return response()->json(compact('school_name','user_category','class_group'));
		}
		else
			return response()->json(['message'=>'No groups Configured!...']);
	}

	// Configurations tags
	public function configuration_tags()
	{
		// Save last login in DB
        $user = auth()->user();

		// Fetch configuration details from DB for corresponding school
        $configurations = Configurations::where('school_profile_id',$user->school_profile_id)->first();

        // configuration details
        $configuration = ([
        	'divisions'=>($configurations->division==1)?true:false,
            'sections'=>($configurations->sections==1)?true:false,
            'classes'=>($configurations->classes==1)?true:false,
            'map_classes_sections'=>($configurations->map_classes_sections==1)?true:false,
            'subjects'=>($configurations->subjects==1)?true:false,
            'map_subjects'=>($configurations->map_subjects==1)?true:false,
            'staffs'=>($configurations->staffs==1)?true:false,
            'map_staffs'=>($configurations->map_staffs==1)?true:false,
            'management'=>($configurations->management==1)?true:false,
            'students'=>($configurations->students==1)?true:false,
        ]);
        // return token 
        return response()->json(compact('configuration'));
	}

	// Delete division
	public function delete_division(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='') //check input exist
		{
			// Delete records 
	        AcademicSubjects::where('division_id',$request->division_id)->delete();
	        AcademicSections::where('division_id',$request->division_id)->delete();
	       	AcademicClasses::where('division_id',$request->division_id)->delete();
	        AcademicClassConfiguration::where('division_id',$request->division_id)->delete();
	        AcademicDivisions::where('id',$request->division_id)->delete();
		}
		return response()->json(['message'=>'Deleted Successfully!...']);
	}

	// Delete class
	public function delete_class(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='' && isset($request->class_id) && $request->class_id!='')
		{
			$classconfig = AcademicClassConfiguration::select('id')->where(['class_id'=> $request->class_id,'division_id'=>$request->division_id])->get()->toArray();

	       	AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
	        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			UserStudents::whereIn('class_config',$classconfig)->delete();
	        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
	        AcademicClasses::where('id',$request->class_id)->delete();
		}
		return response()->json(['message'=>'Deleted Successfully!...']);
	}

	// Delete section
	public function delete_section(Request $request)
	{
		if(isset($request->division_id) && $request->division_id!='' && isset($request->section) && $request->section!='')
		{
			$classconfig = AcademicClassConfiguration::select('id')->where(['section_id'=> $request->section_id,'division_id'=>$request->division_id])->get()->toArray();

	       	AcademicSubjectsMapping::whereIn('class_config',$classconfig)->delete();
	        CommunicationDistribution::whereIn('class_config_id',$classconfig)->delete();
			UserStudents::whereIn('class_config',$classconfig)->delete();
	        AcademicClassConfiguration::whereIn('id',$classconfig)->delete();
	        AcademicSections::where('id',$request->section_id)->delete();
		}
		return response()->json(['message'=>'Deleted Successfully!...']);
	}

	// All users list
	public function all_staff_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();

        $staff_list = UserStaffs::select('id','first_name','mobile_number','user_category')->where('user_status',1);
        if(isset($request->search) && $request->search!='')
        {
        	$category = (strpos('teaching staff',strtolower($request->search)))?Config::get('app.Teaching_staff'):((strpos('non teaching staff',strtolower($request->search)))?Config::get('app.Non-Teaching_staff'):'');
        	$staff_list = $staff_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%');
        	if($category!='')
        		$staff_list = $staff_list->orWhere('user_category', 'like', '%' . $category . '%');
        }
        $staff_list = $staff_list->get()->toArray();
        foreach ($staff_list as $key => $value) {
        	$staff_list[$key]['user_category'] = ($value['user_category'] ==Config::get('app.Teaching_staff'))?'Teaching_staff':'Non_teaching_staff';
        }
        return response()->json($staff_list);
	}

	// All users list
	public function all_parent_list(Request $request)
	{
		// Save last login in DB
        $user = auth()->user();

        $parent_list = UserParents::select('first_name','id','user_category','mobile_number');
        if(isset($request->search) && $request->search!='')
            $parent_list = $parent_list->where('first_name', 'like', '%' . $request->search . '%')->orWhere('mobile_number', 'like', '%' . $request->search . '%');
        	
        $parent_list =$parent_list->get()->toArray();
        foreach ($parent_list as $key => $value) {
        	$student_id = UserStudentsMapping::where('parent',$value['id'])->pluck('student')->toArray();
            $student_name = UserStudents::whereIn('id',$student_id)->pluck('first_name')->first();
        	$user_category = (strtolower($value['user_category']) == 1)?'F/O':'M/O';
        	$parent_list[$key]['student_name'] = ($user_category.' '.$student_name);
        	$parent_list[$key]['mobile_number'] = $value['mobile_number'];
        }
        return response()->json($parent_list);
	}

	// Store Onesignal device details in DB
    public function onesignal_store_device_details(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

        if($request->player_id !='' && $request->external_user_id!='')
        {

            if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
                $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Management_role'))
                $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Staff_role'))
                $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
            else if($user->user_role == Config::get('app.Parent_role'))
                $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();//fetch id from user all table to store notification triggered user
            $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

            $check_exists = Appusers::where('loginid',$userall_id)->get()->toArray();
            if(empty($check_exists))
            {
	            $data = ([
	                'loginid'=>$userall_id,
	                'player_id'=>($request->player_id!='')?$request->player_id:'',
	                'external_user_id'=>($request->external_user_id)?$request->external_user_id:'',
	                'device_type'=>($request->device_type)?$request->device_type:'',
	                'device_name'=>($request->device_name)?$request->device_name:'',
	                'device_version'=>($request->device_version)?$request->device_version:'',
	                'app_version'=>($request->app_version)?$request->app_version:'',
	                'login_date'=>Carbon::now()->timezone('Asia/Kolkata'),
	            ]);
            	Appusers::insert($data);
	        }
	        else
	        {
	        	Appusers::where(['loginid'=>$userall_id])->update(['player_id'=>($request->player_id!='')?$request->player_id:'','external_user_id'=>$request->external_user_id,'device_type'=>$request->device_type,'device_name'=>$request->device_name,'device_version'=>$request->device_version,'app_version'=>$request->app_version,'login_date'=>Carbon::now()->timezone('Asia/Kolkata')]);
	        }
            return response()->json('Device details registered Successfully');
        }
        else
            return response()->json('Player ID is required');
    }

    // List the subjects for class 
    public function class_subjects_list(Request $request)
    {
    	// Save last login in DB
        $userdata = auth()->user();
        $division_id = $request->division_id; //get division id input
        $class_subjects_review=[];
        $classessections = AcademicClassConfiguration::select('id','class_id','section_id','division_id')->where('division_id',$division_id)->get(); //fetch class and sections list for input division

        if(!empty($classessections)) //check whether configuration empty or not
        {
	        foreach ($classessections as $key => $value) { 
	        	// arrange necesary details in array
	        	$classsection_data[$value->id]=([
	        		'class_section_id'=>$value->id,
	        		'class_section_name'=>$value->classsectionName(),
	        		'division_id'=>$value->division_id,
	        	]);
	        	// fetch subjects for corresponding config class 
	        	$subjects_list = AcademicSubjectsMapping::where('class_config',$value->id)->get();

				foreach ($subjects_list as $subject_key => $subject_value) {
			        $subjects[$value->id][]= ([
		        		'subject_id'=>$subject_value->id,
		        		'subject_name'=>$subject_value->subjectName()
		        	]);
				}
	        }
	        $index = 0;
	        foreach ($classsection_data as $class_key => $class_value) {//merge class and subject related details
	        	$class_section_review[$index] = isset($classsection_data[$class_value['class_section_id']])?$classsection_data[$class_value['class_section_id']]:[];
	        	$class_section_review[$index]['subjects'] = isset($subjects[$class_value['class_section_id']])?$subjects[$class_value['class_section_id']]:[];
	        	$index++;
	        }
	       
	    }
	    return response()->json($class_section_review);
    }

    // fetch all staff details for onboarding process
    public function onboarding_staff_list()
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $staff_list = UserStaffs::select('id','user_id','first_name','mobile_number','profile_image')->where('user_status',1)->get()->toArray(); //fetch all the staff for listing
        return response()->json($staff_list);
    }

    // Get single user details(onboarding)
    public function onboarding_fetch_single_staff(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $check_class_teacher = AcademicClassConfiguration::where('class_teacher',$request->id)->get()->first(); //check the user is a classteacher.
		
        $staff_list = UserStaffs::select('id','user_id','first_name','mobile_number','profile_image','specialized_in','user_category','email_id')->where('user_status',1)->where('id',$request->id)->get()->first(); //fetch all the staff for listing
        $staff_list->class_teacher = 'no'; //set default values
        $staff_list->class_config = 0; 
        $staff_list->specialized_in = (int)$staff_list->specialized_in;
        if(!empty($staff_list) && isset($check_class_teacher->class_teacher)) //check not empty for class configuration details
        {
        	$staff_list->class_teacher = 'yes';
        	$staff_list->class_config = $check_class_teacher->class_teacher; 

        }
        return response()->json($staff_list);
        
    }

    // delete staff (onboarding)
    public function onboarding_delete_staff(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        AcademicSubjectsMapping::where('staff',$request->id)->update(['staff'=>null]); //update assigned staff to null.
        UserStaffs::where('id',$request->id)->delete(); //delete staff record
        return response()->json('Deleted Successfully!...');
    }

    // edit staff details
    public function onboarding_edit_staff(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();
        $staffs_details = [];
        if($request->id!='')// fetch selected staff details 
        	$staffs_details = UserStaffs::where('id',$request->id)->get()->first();

        if(!empty($staffs_details))
        {
        	$image ='';
        	if(!empty($request->photo))//check upload photo exist or not
        	{
        		$name = explode('.',$request->photo->getClientOriginalName())[0];
	        	$image = $name.''.time().'.'.$request->photo->extension();
	        	$value['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'staffs'), $image);
        	}
	        //save staff details
	        $staffs_details->first_name= $request->staff_name;
	        $staffs_details->mobile_number=$request->mobile_number;
	        if($image!='')
	        	$staffs_details->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'staffs/'.$image):'';
	        $staffs_details->email_id=$request->email_address;
	        $staffs_details->specialized_in=$request->specialized_in;
	        $staffs_details->user_category=$request->teacher_category;
	        $staffs_details->updated_by=$userall_id;
	    	$staffs_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	        $staffs_details->save();


	        $schoolusers = SchoolUsers::where('user_id',$staffs_details->user_id)->get()->first(); //update email address in common login table

            $schoolusers->user_email_id=$request->email_address;
            $schoolusers->save();

            if(strtolower($request->class_teacher) == 'yes')
             	AcademicSubjectsMapping::where('class_config',$request->class_config)->update(['staff'=>$request->id]); //assign staff to class.

            return response()->json('Staff details updated Successfully!...');
        }
        else
        {
        	$image ='';
        	if(!empty($request->photo))//check upload photo exist or not
        	{

        		$name = explode('.',$request->photo->getClientOriginalName())[0];
	        	$image = $name.''.time().'.'.$request->photo->extension();
	        	$request->photo->move(public_path(env('SAMPLE_CONFIG_URL').'staffs'), $image);
        	}

        	//save staff details
            $staffs_details = new UserStaffs;
            $staffs_details->first_name= $request->staff_name;
            $staffs_details->mobile_number=$request->mobile_number;
            if($image!='')
            	$staffs_details->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'staffs/'.$image):'';
            $staffs_details->email_id=$request->email_address;
	        $staffs_details->specialized_in=$request->specialized_in;
	        $staffs_details->user_category=$request->teacher_category;
            $staffs_details->created_by=$userall_id;
        	$staffs_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $staffs_details->save();

            $staff_id =$staffs_details->id; // staff id

            // generate and update staff id in db 
            $userstaff_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'T'.sprintf("%04s", $staff_id);
            $staffs_details->user_id = $userstaff_id;
            $staffs_details->save();

            $user_all = new UserAll;
            $user_all->user_table_id=$staff_id;
            $user_all->user_role=2;
            $user_all->save();

            $schoolusers = new SchoolUsers;
            $schoolusers->school_profile_id=$user_data->school_profile_id;
            $schoolusers->user_id=$userstaff_id;
            $schoolusers->user_mobile_number=$request->mobile_number;
            $schoolusers->user_password=bcrypt($request->mobile_number);
            $schoolusers->user_role=2;
            $schoolusers->user_status=1;
            $schoolusers->save();
        }

        Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['map_staffs'=>1]);
    }

    // Get single user details(onboarding)
    public function onboarding_fetch_single_management(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $management_list = UserManagements::select('id','user_id','first_name','mobile_number','profile_image','user_category','email_id')->where('user_status',1)->where('id',$request->id)->get()->first(); //fetch all the management for listing
        return response()->json($management_list);
        
    }

    // delete managment (onboarding)
    public function onboarding_delete_management(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        UserManagements::where('id',$request->id)->delete(); //delete staff record
        return response()->json('Deleted Successfully!...');
    }

    // delete subject (onboarding)
    public function onboarding_delete_subject(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        // reset to null with selected subject staffs
        UserStaffs::where('specialized_in',$request->id)->update(['specialized_in'=>null]);
		// Delete the class mapping to the subject record
        AcademicSubjectsMapping::where('subject',$request->id)->delete();
        // fetch subject related communication from table
        $communication_ids = Communications::where('subject_id',$request->id)->get()->toArray();
        if(!empty($communication_ids))
        {
        	// get id from fetched details
        	$ids_list = array_column($communication_ids,'id');
        	//delete subject related records from communication recipients table
        	CommunicationRecipients::whereIn('communication_id',$ids_list)->delete();
        	CommunicationAttachments::whereIn('communication_id',$ids_list)->delete();
        	CommunicationDistribution::whereIn('communication_id',$ids_list)->delete();
        	HomeworkParentStatus::whereIn('notification_id',$ids_list)->delete();
        	// delete subject related records from communication table
        	Communications::where('subject_id',$request->id)->delete();
        }
        AcademicSubjects::where('id',$request->id)->delete(); //delete staff record
        return response()->json('Deleted Successfully!...');
    }

    // edit managment details
    public function onboarding_edit_management(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();

        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();
        $management_details = [];
        if($request->id!='')// fetch selected management user details 
        	$management_details = UserManagements::where('id',$request->id)->get()->first();

        if(!empty($management_details))
        {
        	$image ='';
        	if(!empty($request->photo))//check upload photo exist or not
        	{
        		$name = explode('.',$request->photo->getClientOriginalName())[0];
	        	$image = $name.''.time().'.'.$request->photo->extension();
	        	$request->photo->move(public_path(env('SAMPLE_CONFIG_URL').'managements'), $image);
        	}
	        //save staff details
	        $management_details->first_name= $request->management_name;
	        $management_details->mobile_number=$request->mobile_number;
	        if($image!='')
	        	$management_details->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'managements/'.$image):'';
	        $management_details->email_id=$request->email_address;
	        $management_details->user_category=$request->user_category;
	        $management_details->updated_by=$userall_id;
	    	$management_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	        $management_details->save();


	        $schoolusers = SchoolUsers::where('user_id',$management_details->user_id)->get()->first(); //update email address in common login table

            $schoolusers->user_email_id=$request->email_address;
            $schoolusers->save();
       		Configurations::where('school_profile_id',$user->school_profile_id)->update(['management'=>1]);

            return response()->json('Management details updated Successfully!...');
        }
        else
       	{
       		//save management details
            $UserManagements = new UserManagements;
            $UserManagements->first_name= $value['management_person_name'];
            $UserManagements->mobile_number=$value['mobile_number'];
            $UserManagements->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'managements/'.$image):'';
            $UserManagements->email_id=$request->email_address;
	        $UserManagements->user_category=$request->user_category;
            $UserManagements->created_by=$userall_id;
        	$UserManagements->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $UserManagements->save();

            $management_id =$UserManagements->id; // staff id

            // generate and update staff id in db 
            $usermanagement_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'M'.sprintf("%04s", $management_id);
            $UserManagements->user_id = $usermanagement_id;
            $UserManagements->save();

            $user_all = new UserAll;
            $user_all->user_table_id=$management_id;
            $user_all->user_role=5;
            $user_all->save();

            $schoolusers = new SchoolUsers;
            $schoolusers->school_profile_id=$user_data->school_profile_id;
            $schoolusers->user_id=$usermanagement_id;
            $schoolusers->user_mobile_number=$value['mobile_number'];
            $schoolusers->user_password=bcrypt($value['mobile_number']);
            $schoolusers->user_role=5;
            $schoolusers->user_status=1;
            $schoolusers->save();

       		Configurations::where('school_profile_id',$user->school_profile_id)->update(['managements'=>1]);
            return response()->json('Management user added Successfully!...');
       	}
    }


    // fetch all parent details for onboarding process
    public function onboarding_parent_list()
    {
    	// Check authenticate user
        $userdata = auth()->user();

        $parent_list = UserParents::select('id','user_id','first_name','mobile_number')->where('user_status',1)->get()->toArray(); //fetch all the staff for listing
        return response()->json($parent_list);
    }

    // Onboarding for individual parent view
    public function onboarding_fetch_single_parent(Request $request)
    {
    	$student_list = $parentsdata = $students= [];//empty array declaration
		$student_list = UserStudentsMapping::select('student')->where('parent',$request->id)->first(); //fetch student details from parent mapped data
		if(!empty($student_list))
		{
			$parent_list = UserStudentsMapping::select('parent')->where('student',$student_list->student)->get()->toArray(); //fetch all parent details from student id
			$students = UserStudents::where('id',$student_list->student)->get()->first(); //get student related info
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

		$student_list = ([
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
			'guardian_mobile_number'=>isset($parentsdata[3])?$parentsdata[3]->mobile_number:'',
			'guardian_email_address'=>isset($parentsdata[3])?$parentsdata[3]->email_id:'',
			'guardian_name'=>isset($parentsdata[3])?$parentsdata[3]->first_name:'',
			'guardian_id'=>isset($parentsdata[3])?$parentsdata[3]->id:0,
			'admission_number'=>isset($students->first_name)?$students->admission_number:0,
			'roll_no'=>isset($students->roll_number)?$students->roll_number:0,
			'gender'=>isset($students->roll_number)?(Config::get('app.'.$students->gender)):0,
			'photo'=>isset($students->profile_image)?$students->profile_image:'',
			'temporary_student'=>(isset($students->profile_image) && $students->class_config == null)?'yes':'no',
			'class_section'=>isset($students->class_config)?$students->class_config:0,
		]);
		

		return response()->json($student_list); //return student details 
    }

    // delete parent (onboarding)
    public function onboarding_delete_parent(Request $request)
    {
    	// Check authenticate user
        $userdata = auth()->user();
        $studentids = UserStudentsMapping::where('parent',$request->id)->pluck('student')->toArray(); //fetch student id
        $parentids = UserStudentsMapping::whereIn('student',$studentids)->pluck('parent')->toArray(); //fetch student id
        if(count($parentids))
        	UserStudents::whereIn('id',$studentids)->delete();
        UserStudentsMapping::where('parent',$request->id)->delete(); //delete parent mapping record
        UserParents::where('id',$request->id)->delete();
        return response()->json('Deleted Successfully!...');
    }

   	// edit or insert parent (onboarding)
    public function onboarding_edit_parent(Request $request)
    {
    	// Check authenticate user
        $user = auth()->user();
		if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();

        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 
        $parent_details = $mother_details = $guradian_details = $student_details = [];

        $class_config_id = null;

        $gender = (isset($row['gender']) && strtolower($row['gender']) == 'male')?1:((isset($row['gender']) && strtolower($row['gender']) == 'female')?2:3);

        //check image exists
   		$image ='';
    	if(!empty($request->student_photo))
    	{
    		$name = explode('.',$request->student_photo->getClientOriginalName())[0];
        	$image = $name.''.time().'.'.$request->student_photo->extension();
        	$request->student_photo->move(public_path(env('SAMPLE_CONFIG_URL').'students'), $image);
    	}

        if($request->father_id>0 || $request->mother_id>0 || $request->guradian_id>0 || $request->student_id>0)// fetch check user already exists 
        {
        	if(isset($request->student_id) && $request->student_id>0)//arrange student details in array 
        		$student_details = UserStudents::where(['id'=>$request->student_id])->get()->first();
        	else
                $student_details = new UserStudents;
            // student details insert or edit into db
            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            $student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            $student_details->profile_image=public_path(env('SAMPLE_CONFIG_URL').'students/'.$image);
            $student_details->gender=$gender;
            $student_details->class_config=$request->class_config;
            $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->created_by=$userall_id;
        	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

   			$student_id = $student_details->id;

   			// generate and update student id in db 
            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
            $student_details->user_id = $userstudent_id;
            $student_details->save(); 
            
            // insert parents details
        	$father_details = UserParents::where('id',$request->father_id)->get()->first();
	        if(!empty($father_details) || $request->father_mobile_number!='')
	        {
	        	$data['photo'] = $request->father_photo;
	        	$data['first_name'] = $request->father_name;
	        	$data['mobile_number'] = $request->father_mobile_number;
	        	$data['email_address'] = $request->father_email_address;
	        	$data['user_category'] = 1;

	        	$this->edit_parent_details($data,$father_details,$student_id,$userall_id);
	        }
	        // update or insert parents details
	        $mother_details = UserParents::where('id',$request->mother_id)->get()->first();
	        if(!empty($mother_details) || $request->mother_mobile_number!='' )
	        {
	        	$data = [];
	        	$data['photo'] = $request->mother_photo;
	        	$data['first_name'] = $request->mother_name;
	        	$data['mobile_number'] = $request->mother_mobile_number;
	        	$data['email_address'] = $request->mother_email_address;
	        	$data['user_category'] = 2;

	        	$this->edit_parent_details($data,$mother_details,$student_id,$userall_id);
	        }

	        // update or insert parents details
	        $guradian_details = UserParents::where('id',$request->guradian_id)->get()->first();
	        if(!empty($guradian_details) || $request->guradian_mobile_number!='' )
	        {
	        	$data = [];
	        	$data['photo'] = $request->guradian_photo;
	        	$data['first_name'] = $request->guradian_name;
	        	$data['mobile_number'] = $request->guradian_mobile_number;
	        	$data['email_address'] = $request->guradian_email_address;
	        	$data['user_category'] = 3;

	        	$this->edit_parent_details($data,$guradian_details,$student_id,$userall_id);
	        }
	        Configurations::where('school_profile_id',$user->school_profile_id)->update(['students'=>1]);
	        return response()->json('Student and parents details updated Successfully!...');
	    }
        else
       	{  		
        	// insert student details
	        $student_details = new UserStudents;

            $student_details->first_name= $request->student_name;
            $student_details->admission_number=$request->admission_no;
            $student_details->roll_number=isset($request->roll_no)?$request->roll_no:'';
            $student_details->profile_image=public_path(env('SAMPLE_CONFIG_URL').'students/'.$image);
            $student_details->gender=$gender;
            $student_details->class_config=$request->class_config;
            $student_details->user_status=(isset($request->temporary_student) && $request->temporary_student!='' && strtolower($request->temporary_student)=='yes')?5:1;
            $student_details->created_by=$userall_id;
        	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
            $student_details->save();

   			$student_id = $student_details->id;

   			// generate and update staff id in db 
            $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
            $student_details->user_id = $userstudent_id;
            $student_details->save();
            // insert father details
            if($request->father_mobile_number!='' && $request->father_name!='')
        	{
	        	$data = [];
	        	$data['photo'] = $request->father_photo;
	        	$data['first_name'] = $request->father_name;
	        	$data['mobile_number'] = $request->father_mobile_number;
	        	$data['email_address'] = $request->father_email_address;
	        	$data['user_category'] = 1;

	        	$this->insert_parent_details($data,$student_details->id,$userall_id);
	        }
	        // insert mother details
	        if($request->mother_mobile_number!='' && $request->mother_name!='')
        	{
	        	$data = [];
	        	$data['photo'] = $request->mother_photo;
	        	$data['first_name'] = $request->mother_name;
	        	$data['mobile_number'] = $request->mother_mobile_number;
	        	$data['email_address'] = $request->mother_email_address;
	        	$data['user_category'] = 2;

	        	$this->insert_parent_details($data,$student_details->id,$userall_id);
	        }

	        // insert guradian details
	        if($request->guradian_mobile_number!='' && $request->guradian_name!='')
        	{
	        	$data = [];
	        	$data['photo'] = $request->guradian_photo;
	        	$data['first_name'] = $request->guradian_name;
	        	$data['mobile_number'] = $request->guradian_mobile_number;
	        	$data['email_address'] = $request->guradian_email_address;
	        	$data['user_category'] = 3;

	        	$this->insert_parent_details($data,$student_details->id,$userall_id);
	        }
	        Configurations::where('school_profile_id',$user->school_profile_id)->update(['students'=>1]);
	        return response()->json('Student and parents details inserted Successfully!...');
       	}
    }

    // Edit parent details dependency function - onboarding
    public function edit_parent_details($data,$details,$id,$userall_id)
    {
    	$image =$page='';
    	if(!empty($data['photo']))//check upload photo exist or not
    	{
    		$name = explode('.',$data['photo']->getClientOriginalName())[0];
        	$image = $name.''.time().'.'.$data['photo']->extension();
        	$data['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'students'), $image);
    	}
    	if(empty($details) && !isset($details->mobile_number))
    	{
    		$page = 'new';
    	    $details = new UserParents;
    	}
    	
    	$user = auth()->user();

        $profile_details = SchoolProfile::where(['id'=>$user->school_profile_id])->first();//Fetch school profile details 

        //save staff details
        $details->first_name= $data['first_name'];
        $details->mobile_number=$data['mobile_number'];
        if($image!='')
        	$details->profile_image = ($image!='')?public_path(env('SAMPLE_CONFIG_URL').'students/'.$image):'';
        $details->email_id=$data['email_address'];
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

        if($data['email_address']!='')
        {
        	$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user->school_profile_id])->get()->first();

        	if(empty($schoolusers))
            	$schoolusers = new SchoolUsers;

            $schoolusers->school_profile_id=$user->school_profile_id;
            $schoolusers->user_id=$userparent_id;
            $schoolusers->user_mobile_number=$data['mobile_number'];
            $schoolusers->user_password=bcrypt($data['mobile_number']);
            $schoolusers->user_email_id=$data['email_address'];
            $schoolusers->user_role=Config::get('app.Parent_role');
            $schoolusers->user_status=1;
            $schoolusers->save();
	    }
	    
        // mapping the student and parent
        $student_map = new UserStudentsMapping;
        $student_map->student = $id;  
        $student_map->parent = $details->id;
        $student_map->created_by = $userall_id;
        $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $student_map->save();
    }

    // create parent details dependency function -onboarding
    public function insert_parent_details($data,$id,$userall_id)
    {
    	$user_data = auth()->user();
    	$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

    	// insert parent details in db
    	$parent=[];
        $parent_details = new UserParents;
    	$parent_details->created_by=$userall_id;
    	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
        $parent_details->mobile_number= $data['mobile_number'];
        $parent_details->user_category = $data['user_category'];
        $parent_details->first_name= $data['first_name'];
        $parent_details->email_id= $data['email_address'];
        $parent_details->user_status=1;//active
        $parent_details->user_category = 1;
            
            
        $parent_details->save();

        $parent_id = $parent_details->id;

        // generate and update staff id in db 
        $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
        $parent_details->user_id = $userparent_id;
        $parent_details->save(); 

        //make an entry in user all table
        $user_all = new UserAll;
        $user_all->user_table_id=$parent_details->id;
        $user_all->user_role=Config::get('app.Parent_role');
        $user_all->save(); 
            	
        // insert record in school user table
        $schoolusers = new SchoolUsers;

        $schoolusers->school_profile_id=$user_data->school_profile_id;
        $schoolusers->user_id=$userparent_id;
        $schoolusers->user_mobile_number=$data['mobile_number'];
        $schoolusers->user_password=bcrypt($data['mobile_number']);
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



    // Add students in DB along with parents and guardian details (old need to remove)
	public static function students_excel_upload($data,$userall_id,$upload_type)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		$inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($data as $row) {
            if($row['student_name']!=''&& ($row['father_name']!='' || $row['mother_name']!='' || $row['guardian_name']!='') && ($row['father_mobile_number']!='' || $row['mother_mobile_number']!='' || $row['guardian_mobile_number']!='') )
            {  
                $check_exists = UserParents::where('mobile_number',$row['father_mobile_number']);
                if(isset($row['mother_mobile_number']))
                	$check_exists->orwhere('mobile_number',$row['mother_mobile_number']);
                if(isset($row['guardian_mobile_number']))
                	$check_exists->orwhere('mobile_number',$row['guardian_mobile_number']);
                $result = $check_exists->first(); //To check given subject name is already exists in DB.
                
                // array_push($usermobile_numbers, $row['mobile_number']);//check mobile number already exists in array
                $image ='';
	        	if(!empty($row['photo']))
	        	{

	        		$name = explode('.',$row['photo']->getClientOriginalName())[0];
		        	$image = $name.''.time().'.'.$row['photo']->extension();
		        	$row['photo']->move(public_path(env('SAMPLE_CONFIG_URL').'students'), $image);
	        	}
	        	$class_config_id = null;

	        	if($upload_type == 'import')
	        	{
                    $class_id = AcademicClasses::where('division_id',$row['division'])->where('class_name',$row['class_name'])->pluck('id')->first();
                    $section_id = AcademicSections::where('division_id',$row['division'])->where('section_name',$row['section_name'])->pluck('id')->first();
	        		if($class_id!='' && $section_id!='')
                    $class_config_id = AcademicClassConfiguration::where(['class_id'=>$class_id,'section_id'=>$section_id])->pluck('id')->first();
	        	}
	        	else
	        		$class_config_id = $row['class_section'];


            	if(isset($row['gender']) && strtolower($row['gender']) == 'male')
            		$gender = 1;
            	else if(isset($row['gender']) && strtolower($row['gender']) == 'female')
            		$gender = 2;
            	else
            		$gender = 3;

            	if(isset($row['student_id']) && $row['student_id']!='')//arrange student details in array 
            		$student_details = UserStudents::where(['id'=>$row['student_id']])->get()->first();
            	else
                    $student_details = new UserStudents;

                $student_details->first_name= $row['student_name'];
                $student_details->admission_number=$row['admission_no'];
                $student_details->roll_number=isset($row['roll_no'])?$row['roll_no']:'';
                $student_details->profile_image=public_path(env('SAMPLE_CONFIG_URL').'students/'.$image);
                $student_details->gender=$gender;
                $student_details->class_config=$class_config_id;
                $student_details->user_status=(isset($row['temporary_student']) && $row['temporary_student']!='' && strtolower($row['temporary_student'])=='yes')?5:1;
                $student_details->dob=date('Y-m-d',strtotime($row['dob']));
                $student_details->created_by=$userall_id;
            	$student_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                $student_details->save();

       			$student_id = $student_details->id; // student id

       			if(isset($row['student_id']) && $row['student_id']!='')//arrange student details in array              
       			{
       				$page = 'create';
	            	$userstudent_id = $student_details->user_id;
       			}
	            else
	            {
	            	$page = 'edit';
	            	 // generate and update staff id in db 
	                $userstudent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'S'.sprintf("%04s", $student_id);
	                $student_details->user_id = $userstudent_id;
	                $student_details->save();                  

	                
	            }


                if((isset($row['father_mobile_number']) || isset($row['father_email_address'])) && ($row['father_mobile_number']!='' || $row['father_email_address']!=''))
                {
                	$check_exists = UserParents::where('mobile_number',$row['father_mobile_number'])->first(); //To check given subject name is already exists in DB.
                	if(!empty($check_exists))
                	{
                		$student_map = new UserStudentsMapping;
	                    $student_map->student = $student_id;  
	                    $student_map->parent = $check_exists->id;
	                    $student_map->created_by = $userall_id;
	                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                    $student_map->save();
                	}
                	else
                	{
	                    $parent=[];
	                    if(isset($row['father_id']) && $row['father_id']!='')
	                    	$parent_details = $parent = UserParents::where(['id'=>$row['father_id']])->get()->first();
	                	if(empty($parent))
	                	{
	                    	$parent_details = new UserParents;
	                    	$parent_details->created_by=$userall_id;
	                    	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                	}
	                	else
	                	{
	                		$parent_details->updated_by=$userall_id;
	                    	$parent_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	                    	$userparent_id = $parent_details->user_id;
	                	}
	                    $parent_details->mobile_number= $row['father_mobile_number'];
	                    $parent_details->first_name= $row['father_name'];
	                    $parent_details->email_id= $row['father_email_address'];
	                    $parent_details->user_status=1;//active
	                    $parent_details->user_category = 1;
	                    
	                    
	                    $parent_details->save();

	                    $parent_id = $parent_details->id;

	                    if(empty($parent))
	                    {
		                    // generate and update staff id in db 
		                    $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
		                    $parent_details->user_id = $userparent_id;
		                    $parent_details->save(); 

		                    if($page == 'edit')
		                    {
			                    $user_all = new UserAll;
				                $user_all->user_table_id=$parent_id;
				                $user_all->user_role=Config::get('app.Parent_role');
				                $user_all->save(); 
		                    }

		                    $student_map = new UserStudentsMapping;
		                    $student_map->student = $student_id;  
		                    $student_map->parent = $parent_id;
		                    $student_map->created_by = $userall_id;
		                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $student_map->save();
		                }
		                $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();

	                	if(empty($schoolusers))
	                    	$schoolusers = new SchoolUsers;

	                    $schoolusers->school_profile_id=$user_data->school_profile_id;
	                    $schoolusers->user_id=$userparent_id;
	                    $schoolusers->user_mobile_number=$row['father_mobile_number'];
	                    $schoolusers->user_password=bcrypt($row['father_mobile_number']);
	                    $schoolusers->user_email_id=$row['father_email_address'];
	                    $schoolusers->user_role=Config::get('app.Parent_role');
	                    $schoolusers->user_status=1;
	                    $schoolusers->save();
                	}
                }

                if((isset($row['mother_mobile_number']) || isset($row['mother_email_address'])) && ($row['mother_mobile_number']!='' || $row['mother_email_address']!=''))
                {
                	$check_exists = UserParents::where('mobile_number',$row['mother_mobile_number'])->first(); //To check given subject name is already exists in DB.
                	if(!empty($check_exists))
                	{
                		$student_map = new UserStudentsMapping;
	                    $student_map->student = $student_id;  
	                    $student_map->parent = $check_exists->id;
	                    $student_map->created_by = $userall_id;
	                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                    $student_map->save();
                	}
                	else
                	{

	                    $parent=[];
	                    if(isset($row['mother_id']) && $row['mother_id']!='')
	                    	$parent_details = $parent = UserParents::where(['id'=>$row['mother_id']])->get()->first();

	                	if(empty($parent))
	                	{
	                    	$parent_details = new UserParents;
	                    	$parent_details->created_by=$userall_id;
	                    	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                	}
	                	else
	                	{
	                		$parent_details->updated_by=$userall_id;
	                    	$parent_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
	                    	$userparent_id = $parent_details->user_id;
	                	}
	                    $parent_details->mobile_number= $row['mother_mobile_number'];
	                    $parent_details->first_name= isset($row['mother_name'])?$row['mother_name']:'';
	                    $parent_details->email_id= $row['mother_email_address'];
	                    $parent_details->user_status=1;//active
	                    $parent_details->created_by=$userall_id;
	                    $parent_details->user_category = 2;
	                    $parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
	                    $parent_details->save();

	                    $parent_id = $parent_details->id;

	                    if(empty($parent))
	                    {
	                    	// generate and update staff id in db 
		                    $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
		                    $parent_details->user_id = $userparent_id;
		                    $parent_details->save(); 

		                    if($page == 'edit')
		                    {
			                    $user_all = new UserAll;
				                $user_all->user_table_id=$parent_id;
				                $user_all->user_role=Config::get('app.Parent_role');
				                $user_all->save(); 
		                    } 

		                    $student_map = new UserStudentsMapping;
		                    $student_map->student = $student_id;  
		                    $student_map->parent = $parent_id;
		                    $student_map->created_by = $userall_id;
		                    $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
		                    $student_map->save();    
		                }       

		                $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();

	                	if(empty($schoolusers))
	                    	$schoolusers = new SchoolUsers;

	                    $schoolusers->school_profile_id=$user_data->school_profile_id;
	                    $schoolusers->user_id=$userparent_id;
	                    $schoolusers->user_mobile_number=$row['mother_mobile_number'];
	                    $schoolusers->user_password=bcrypt($row['mother_mobile_number']);
	                    $schoolusers->user_email_id=$row['mother_email_address'];
	                    $schoolusers->user_role=Config::get('app.Parent_role');
	                    $schoolusers->user_status=1;
	                    $schoolusers->save(); 
	                }          
                }

                // if((isset($row['guardian_mobile_number']) || isset($row['guardian_email_address'])) && ($row['guardian_mobile_number']!='' || $row['guardian_email_address']!=''))
                // {
                // 	$schoolusers = SchoolUsers::where(['user_id'=>$userstudent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();
                // 	if(empty($schoolusers))
                //     	$schoolusers = new SchoolUsers;
                //     $schoolusers->school_profile_id=$user_data->school_profile_id;
                //     $schoolusers->user_id=$userstudent_id;
                //     $schoolusers->user_mobile_number=$row['guardian_mobile_number'];
                //     $schoolusers->user_email_id=$row['guardian_email_address'];
                //     $schoolusers->user_role=Config::get('app.Parent_role');
                //     $schoolusers->user_status=1;
                //     $schoolusers->save();

                //     $parent=[];
                //     if(isset($row['guardian_id']) && $row['guardian_id']!='')
                //     	$parent_details = $parent = UserParents::where(['id'=>$row['guardian_id']])->get()->first();
                // 	if(empty($parent))
                // 	{
                //     	$parent_details = new UserParents;
                //     	$parent_details->created_by=$userall_id;
                //     	$parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                // 	}
                // 	else
                // 	{
                // 		$parent_details->updated_by=$userall_id;
                //     	$parent_details->updated_time=Carbon::now()->timezone('Asia/Kolkata');
                //     	$userparent_id = $parent_details->user_id;
                // 	}
                //     $parent_details->mobile_number= $row['guardian_mobile_number'];
                //     $parent_details->first_name= $row['guardian_name'];
                //     $parent_details->email_id= $row['guardian_email_address'];
                //     $parent_details->user_category = 3;
                //     $parent_details->user_status=1;//active
                //     $parent_details->created_by=$userall_id;
                //     $parent_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                //     $parent_details->save();

                //     $parent_id = $parent_details->id;

                //     if(empty($parent))
                //     {
	               //      // generate and update staff id in db 
	               //      $userparent_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'P'.sprintf("%04s", $parent_id);
	               //      $parent_details->user_id = $userparent_id;
	               //      $parent_details->save(); 

	               //      if($page == 'edit')
	               //      {
		              //       $user_all = new UserAll;
			             //    $user_all->user_table_id=$parent_id;
			             //    $user_all->user_role=Config::get('app.Parent_role');
			             //    $user_all->save(); 
	               //      }

	               //      $student_map = new UserStudentsMapping;
	               //      $student_map->student = $student_id;  
	               //      $student_map->parent = $parent_id;
	               //      $student_map->created_by = $userall_id;
	               //      $student_map->created_time=Carbon::now()->timezone('Asia/Kolkata');
	               //      $student_map->save();   
	               //  }
	               //  $schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();

                // 	if(empty($schoolusers))
                //     	$schoolusers = new SchoolUsers;

                //     $schoolusers->school_profile_id=$user_data->school_profile_id;
                //     $schoolusers->user_id=$userparent_id;
                //     $schoolusers->user_mobile_number=$row['guardian_mobile_number'];
                //     $schoolusers->user_password=bcrypt($row['guardian_mobile_number']);
                //     $schoolusers->user_email_id=$row['guardian_email_address'];
                //     $schoolusers->user_role=Config::get('app.Parent_role');
                //     $schoolusers->user_status=1;
                //     $schoolusers->save(); 
                // }
                $inserted_records++;

            }
        }

        if($inserted_records>0) //check empty array
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['students'=>1,'map_students'=>1]);
	}

	public static function upload_config_school($data,$userall_id,$upload_type)
	{
		$user_data = auth()->user();
		$profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

		foreach ($data as $row) {
			if((isset($row['father_mobile_number']) || isset($row['father_email_address'])) && ($row['father_mobile_number']!='' || $row['father_email_address']!=''))
	        {
	        	$userparent_id=UserParents::where('mobile_number',$row['father_mobile_number'])->pluck('user_id')->first();
				$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();

		    	if(empty($schoolusers))
		        	$schoolusers = new SchoolUsers;

		        $schoolusers->school_profile_id=$user_data->school_profile_id;
		        $schoolusers->user_id=$userparent_id;
		        $schoolusers->user_mobile_number=$row['father_mobile_number'];
		        $schoolusers->user_password=bcrypt($row['father_mobile_number']);
		        $schoolusers->user_email_id=$row['father_email_address'];
		        $schoolusers->user_role=Config::get('app.Parent_role');
		        $schoolusers->user_status=1;
		        $schoolusers->save();
		    }
		    if((isset($row['mother_mobile_number']) || isset($row['mother_email_address'])) && ($row['mother_mobile_number']!='' || $row['mother_email_address']!=''))
	        {
	        	$userparent_id=UserParents::where('mobile_number',$row['mother_mobile_number'])->pluck('user_id')->first();
	        	$schoolusers = SchoolUsers::where(['user_id'=>$userparent_id,'school_profile_id'=>$user_data->school_profile_id])->get()->first();

	        	if(empty($schoolusers))
	            	$schoolusers = new SchoolUsers;

	            $schoolusers->school_profile_id=$user_data->school_profile_id;
	            $schoolusers->user_id=$userparent_id;
	            $schoolusers->user_mobile_number=$row['mother_mobile_number'];
	            $schoolusers->user_password=bcrypt($row['mother_mobile_number']);
	            $schoolusers->user_email_id=$row['mother_email_address'];
	            $schoolusers->user_role=Config::get('app.Parent_role');
	            $schoolusers->user_status=1;
	            $schoolusers->save(); 
	        }
	    }

	}

	// send welcome message to all users
	public function send_welcome_message(Request $request)
	{
		// authenticate the user
		$user = auth()->user();

		//get input of default password type and store it in school profile table for future purpose.
		if($request->default_password_type!='')
		{
			$default_password_type = strtolower($request->default_password_type);
			SchoolProfile::where('id',$user->school_profile_id)->update(['default_password_type'=>$default_password_type]);
		}
		else
		{
			$default_password_type = SchoolProfile::where('id',$user->school_profile_id)->pluck('default_password_type')->first();
			if($default_password_type == '')
				$default_password_type = 'mobile_number';
		}

		// fetch all users mobile number under role staff,parent and management
		$userslist = SchoolUsers::whereIn('user_role',[2,3,5])->where('school_profile_id',$user->school_profile_id)->get()->toArray();

		// get the common id to insert
		if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Management_role'))
            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Staff_role'))
            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->first();
        else if($user->user_role == Config::get('app.Parent_role'))
            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->first();//fetch id from user all table to store notification triggered user
        $userall_id = UserAll::where(['user_table_id'=>$user_table_id->id,'user_role'=>$user->user_role])->pluck('id')->first();
        // fetch welcome template
		$templates = Smstemplates::whereRaw('LOWER(`label_name`) LIKE ? ',['%'.trim(strtolower("welcome_message")).'%'])->where('status',1)->first();
        if(!empty($templates) && !empty($userslist)) //check empty condition
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
	      				$mapped_student = UserStudentsMapping::where('parent',$user_table_id->id)->pluck('student')->first();
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
	      				$password = date('Ymd',strtotime($student_details->dob));
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
        	
        	return (['status'=>true,'message'=>'SMS sent Successfully!...']);
        }
        else
            return (['status'=>false,'message'=>'Please configure template details!...']);
	}

	// Change mobile number
    public function change_mobile_number(Request $request)
    {
        // Get authorizated user details
        $user = auth()->user();

    	$mobile_exist = SchoolUsers::where(['id'=>$user->id,'user_id'=>$user->user_id])->get()->first();
    	
        if(!empty($mobile_exist))
        {
        	if(isset($request->pin) && $request->pin!='' && isset($request->mobile_number) && $request->mobile_number!='' )
        	{
        		$otp_exp_time = strtotime("+15 minutes",strtotime($mobile_exist->otp_gen_time));
        		$current_time = strtotime(Carbon::now()->timezone('Asia/Kolkata'));
        		if(strtotime($mobile_exist->otp_gen_time) < $current_time && $otp_exp_time > $current_time) {
        			if($user->login_otp == $request->pin) {
        				app('App\Http\Controllers\APILoginController')->saveOtpAsNull($user);

		        		$mobile_exist->user_mobile_number=$request->mobile_number;
		        		$mobile_exist->save();

		        		//Update lastest mobile number in school users table.
			        	if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
				            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();
				        else if($user->user_role == Config::get('app.Management_role'))
				            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->pluck('id')->first();
				        else if($user->user_role == Config::get('app.Staff_role'))
				            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->pluck('id')->first();
				        else if($user->user_role == Config::get('app.Parent_role'))
				            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->pluck('id')->first();

				        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//fetch id from user all table to store notification triggered user
				        if($user->user_role == Config::get('app.Management_role'))
				            $user_table_id = UserManagements::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
				        else if($user->user_role == Config::get('app.Staff_role'))
				            $user_table_id = UserStaffs::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
				        else if($user->user_role == Config::get('app.Parent_role'))
				            $user_table_id = UserParents::where(['user_id'=>$user->user_id])->update(['mobile_number'=>$request->mobile_number,'updated_by'=>$userall_id,'updated_time'=>Carbon::now()->timezone('Asia/Kolkata')]);
				       
				        return response()->json(['message'=>'Mobile number Updated Successfully!...']);
				    }
				    else
				    	 return response()->json(['status'=>false,'message'=>'Entered OTP does not matched']);
			    }
			    else {
		            app('App\Http\Controllers\APILoginController')->saveOtpAsNull($user);
		            return response()->json(['status'=>false,'message'=>'OTP is expired']);
		        }
		    }
		    else
		    {
		    	$message = app('App\Http\Controllers\APILoginController')->sendOTP($mobile_exist);
        		return response()->json(['status'=>true,'message'=>$message]);
		    }
        }
        else
        	return response()->json(['status'=>false,'message'=>"Please enter valid mobile number"]);
    }

    // Store parent details (Staff as parent)
    public function staff_as_parent(Request $request)
    {
    	// Get authorizated user details
        $user = auth()->user();

        $parent_mobile_numbers = explode(',', $request->parent_mobile_number); //split the if more than one number is given
        if(!empty($parent_mobile_numbers))
        {
        	$parent_ids = UserParents::whereIn('mobile_number',$parent_mobile_numbers)->pluck('id')->toArray();//fetch parent id based on mobile number
        	if(!empty($parent_ids))
        	{
        		$parent = implode(',',$parent_ids);
        		UserStaffs::where('user_id',$user->user_id)->update(['child_same_school'=>'Y','parent_id'=>$parent]);//store student id in staff details
        		return response()->json(['status'=>true,'message'=>'Store Successfully!...']);
        	}
        }
        else
        	return response()->json(['status'=>false,'message'=>'Please enter a mobile numbers']);
    }

    // View Stored parent details (Staff as parent)
    public function view_staff_as_parent()
    {
    	// Get authorizated user details
        $user = auth()->user();

        $staff_details = UserStaffs::select('child_same_school','parent_id')->where('user_id',$user->user_id)->get()->first();

        if(!empty($staff_details))
        {
        	if($staff_details->child_same_school == 'Y')
        	{
	        	$parent_id = explode(',', $staff_details->parent_id); //split the if more than one number is given
	        	$parent_mobile_numbers = UserParents::whereIn('id',$parent_id)->pluck('mobile_number')->toArray();//fetch parent id based on mobile number
	        	if(!empty($parent_mobile_numbers))
	        	{
	        		$parents = implode(',',$parent_mobile_numbers);
	        		//return parent mobile numbers in staff details
	        		return response()->json(['status'=>true,'staff_as_parent'=>(['child_same_school'=>$staff_details->child_same_school,'parent_mobile_number'=>$parents])]);
	        	}
        	}
        	else
        		return response()->json(['status'=>true,'staff_as_parent'=>(['child_same_school'=>$staff_details->child_same_school,'parent_mobile_number'=>''])]);
        }
        else
        	return response()->json(['status'=>false,'message'=>'Please enter a mobile numbers']);
    }

    public function importdob(Request $request)
    {
    	Excel::import(new DOBImport,request()->file('import_file'));
    	return response()->json(['status'=>true,'message'=>'Updated Successfully!...']);
    }
}