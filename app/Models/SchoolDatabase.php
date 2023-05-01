<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SchoolDatabase extends Authenticatable
{
    protected $connection = "config_db";
    protected $table = "school_database";
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
}