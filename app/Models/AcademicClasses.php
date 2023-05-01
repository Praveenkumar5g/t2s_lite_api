<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicClasses extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_classes";
    protected $guarded = [];
    public $timestamps = false;

    public function divisionName()
    {
    	$divisionname =AcademicDivisions::where(['id'=>$this->division_id])->pluck('division_name')->first();
        if($divisionname!= '') 
            return $divisionname;
        else
            return "";
    }
}