<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 10:00
 * Import subjects data in DB 
 */
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\UserManagements;
use App\Models\UserCategories;
use App\Models\Configurations;
use App\Models\SchoolProfile;
use App\Models\SchoolUsers;
use App\Models\UserRoles;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use DB;
class ManagementsImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	// Get authorizated user details
        $user_data = auth()->user();

        if($user_data->user_role == 1)//check role and get current user id
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user

        // fetch user role id from db
        $management_role = UserRoles::where(['role_title'=>'management'])->pluck('id')->first();

        // Fetch staff category id from role
        $user_categories = array_column(UserCategories::where('user_role',$management_role)->get()->toArray(),'id','category_name');

        $profile_details = SchoolProfile::where(['id'=>$user_data->school_profile_id])->first();//Fetch school profile details 

        $usermobile_numbers= [];
        $inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($collection as $row) {
            if($row['management_person_name']!='' && $row['mobile_number']!='' && $row['designation']!='' )
            {   
                $check_exists = UserManagements::where(['mobile_number'=>$row['mobile_number']])->pluck('id')->first(); //To check given subject name is already exists in DB.
                if($check_exists == '' && !in_array($row['mobile_number'],$usermobile_numbers) ) //if no then insert 
                {
                    array_push($usermobile_numbers, $row['mobile_number']);//check mobile number already exists in array

                    //arrange staff details in array 
                    $managements_details = new UserManagements;
                    $managements_details->first_name= $row['management_person_name'];
                    $managements_details->mobile_number=$row['mobile_number'];
                    $managements_details->email_id=$row['email_address'];
                    $managements_details->user_category=isset($user_categories[$row['designation']])?$user_categories[$row['designation']]:'';
                    $managements_details->created_by=$userall_id;
                	$managements_details->created_time=Carbon::now()->timezone('Asia/Kolkata');
                    $managements_details->save();

                    $management_id = $managements_details->id; // staff id

                    // generate and update staff id in db 
                    $usermanagement_id = $profile_details['school_code'].substr($profile_details['active_academic_year'], -2).'M'.sprintf("%04s", $management_id);
                    $managements_details->user_id = $usermanagement_id;
                    $managements_details->save();

                    $user_all = new UserAll;
                    $user_all->user_table_id=$management_id;
                    $user_all->user_role=$management_role;
                    $user_all->save();

                    $schoolusers = new SchoolUsers;
                    $schoolusers->school_profile_id=$user_data->school_profile_id;
                    $schoolusers->user_id=$usermanagement_id;
                    $schoolusers->user_mobile_number=$row['mobile_number'];
                    $schoolusers->user_password=bcrypt($row['mobile_number']);
                    $schoolusers->user_email_id=$row['email_address'];
                    $schoolusers->user_role=$management_role;
                    $schoolusers->user_status=2;
                    $schoolusers->save();

                    $inserted_records++;
                }
            }
        }

        if(!empty($usermobile_numbers)) //check empty array
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['management'=>1]);
        
    }
}
