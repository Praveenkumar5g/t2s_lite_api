<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 20-11-2023
 * Time: 12:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientConfiguration extends Model
{
    protected $connection = "config_db";
    protected $table = "client_configuration";
    protected $guarded = [];
    public $timestamps = false;
}