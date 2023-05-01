<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-01-2023
 * Time: 07:40
 * Import class data in DB 
 */
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\AcademicDivisions;
use App\Models\AcademicClasses;
use App\Models\Configurations;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;

class ClassesImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	// Get authorizated user details
        $user_data = auth()->user();

        if($user_data->user_role == 1)
            $user_admin = UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_admin,'user_role'=>$user_data->user_role])->pluck('id')->first(); //fetch id from user all table to store setting triggered user
        $inserted_records=0;
        //Process each and every row ,insert all data in db
        foreach ($collection as $row) {
            if($row['class_name']!='')
            {
                $division_id = AcademicDivisions::where(['division_name'=>$row['division_name']])->pluck('id')->first();
                $class_id = AcademicClasses::where(['class_name'=>$row['class_name'],'division_id'=>$division_id])->pluck('id')->first(); 
                if($class_id=='')
                {
                    $academicclasses = new AcademicClasses;
                    $academicclasses->division_id = $division_id;
                    $academicclasses->class_name = $row['class_name'];
                    $academicclasses->created_by = $userall_id;
                    $academicclasses->created_time = Carbon::now()->timezone('Asia/Kolkata');
                    $academicclasses->save();

                    $class_id = $academicclasses->id;
                }
            }
        }

        if($inserted_records>0)
            Configurations::where('school_profile_id',$user_data->school_profile_id)->update(['classes'=>1]);
    }
}
