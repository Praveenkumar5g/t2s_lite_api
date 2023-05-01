<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 27-02-2023
 * Time: 16:35
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicDivisions extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_division";
    protected $guarded = [];
    public $timestamps = false;
}