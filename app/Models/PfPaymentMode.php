<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfPaymentMode extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_payment_mode";
    protected $primaryKey = 'mode_id';
    public $timestamps = false;
    protected $guarded = [];
}
