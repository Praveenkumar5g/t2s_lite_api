<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 06-01-2023
 * Time: 11:40
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStudents extends Model
{
    protected $connection = "school_db";
    protected $table = "user_students";
    protected $guarded = [];
    public $timestamps = false;
}