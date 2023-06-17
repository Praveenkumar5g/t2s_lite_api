<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PfLateFeeConfig extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_late_fee_config";
    protected $guarded = [];

    public function component()
    {
        return $this->belongsTo('App\PfComponents', 'pf_components_id', 'comp_id');
    }

    public function classTable()
    {
        return $this->belongsTo('App\ClassTable', 'class_table_id', 'class_id');
    }
}
