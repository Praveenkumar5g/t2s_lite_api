<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 06-1-2023
 * Time: 11:30
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStudentsMapping extends Model
{
    protected $connection = "school_db";
    protected $table = "user_students_mapping";
    protected $guarded = [];
    public $timestamps = false;
}