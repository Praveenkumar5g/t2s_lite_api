<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfTransactionOtp extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_transaction_otp";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo('App\PfTransaction','transaction_id','trans_id');
    }
	
}