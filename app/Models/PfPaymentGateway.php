<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfPaymentGateway extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_payment_gateway";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
}
