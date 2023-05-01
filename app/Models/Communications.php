<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 28-12-2022
 * Time: 10:54
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Communications extends Model
{
    protected $connection = "school_db";
    protected $table = "communications";
    protected $guarded = [];
    public $timestamps = false;
}