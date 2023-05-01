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
use App\Models\UserStudents;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Excel as ExcelExcel;

class DOBImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	// Get authorizated user details
        $user_data = auth()->user();
        foreach ($collection as $row) {
            if($row['admission_no']!= '' && $row['dob']!='')
            {
                $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['dob'])->format('Y-m-d');


                $students = UserStudents::where('admission_number',$row['admission_no'])->update(['dob'=>$dob]);
            }
        }
    }
}
