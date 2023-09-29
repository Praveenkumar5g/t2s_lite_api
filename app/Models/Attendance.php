<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 03-01-2023
 * Time: 04:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $connection = "school_db";
    protected $table = "attendance";
    protected $guarded = [];
    public $timestamps = false;
}