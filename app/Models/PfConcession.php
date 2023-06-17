<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfConcession extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_concession";
    protected $primaryKey = 'concession_id';
    public $timestamps = false;
    protected $guarded = [];

    public function editDelete()
    {
        $pf_transaction = PfTransaction::where('pf_concession_id', $this->concession_id)->get();
        if(count($pf_transaction) > 0) {
            return false;
        } else {
            return true;
        }
    }
}
