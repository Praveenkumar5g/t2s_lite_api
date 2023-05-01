<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 10:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSubjects extends Model
{
    protected $connection = "school_db";
    protected $table = "academic_subjects";
    protected $guarded = [];
    public $timestamps = false;
}