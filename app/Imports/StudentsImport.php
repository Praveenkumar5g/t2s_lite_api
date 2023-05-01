<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 05-01-2023
 * Time: 10:00
 * Import Students data in DB 
 */
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\APIConfigurationsController;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use DB;
class StudentsImport implements ToCollection, WithHeadingRow
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

        APIConfigurationsController::students_excel_upload($collection,$userall_id,'import');
        
    }
}
