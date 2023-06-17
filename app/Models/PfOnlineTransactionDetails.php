<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfOnlineTransactionDetails extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_online_transaction_details";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
}