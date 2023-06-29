<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 29/06/2023
 * Time: 10:58 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentApiConfig extends Model
{
    protected $connection = "config_db";
    protected $table = "payment_api_config";
    public $timestamps = false;
    protected $guarded = [];

}
