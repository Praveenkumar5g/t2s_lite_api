<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfAdjustment extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_adjustment";
    public $timestamps = false;
    protected $guarded = [];

    public function editDelete()
    {
        $pf_transaction = PfTransaction::where('pf_adjustment_id', $this->id)->get();
        if(count($pf_transaction) > 0) {
            return false;
        } else {
            return true;
        }
    }
}
