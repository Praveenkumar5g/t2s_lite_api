<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 09:30
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSections extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_sections";
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