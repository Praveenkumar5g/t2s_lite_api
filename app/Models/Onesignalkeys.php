<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 05-04-2023
 * Time: 10:15
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Onesignalkeys extends Model
{
    protected $connection = "config_db";
    protected $table = "onesignal_keys";
    protected $guarded = [];
    public $timestamps = false;
}