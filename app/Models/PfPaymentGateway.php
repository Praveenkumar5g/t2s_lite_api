<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfPaymentGateway extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_payment_gateway";
    public $timestamps = false;
    protected $guarded = [];
}
