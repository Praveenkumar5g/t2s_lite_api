<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 10:45
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Configurations extends Model
{
    protected $connection = "config_db";
    protected $table = "configuration";
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
}