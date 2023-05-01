<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 04-04-2023
 * Time: 12:15
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smstemplates extends Model
{
    protected $connection = "config_db";
    protected $table = "sms_templates";
    public $timestamps = false;
}