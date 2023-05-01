<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 04-04-2023
 * Time: 02:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smslogs extends Model
{
    protected $connection = "school_db";
    protected $table = "sms_logs";
    protected $guarded = [];
    public $timestamps = false;
}