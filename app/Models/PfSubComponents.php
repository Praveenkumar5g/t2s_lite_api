<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PfSubComponents extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_sub_components";
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    /*public function editDelete()
    {
        $pf_cls_details = PfClsDetails::where('fee_comp_id', $this->comp_id)->get();
        $pf_stu_details = PfStuDetails::where('fee_comp_id', $this->comp_id)->get();
        if(count($pf_cls_details) > 0 || count($pf_stu_details) > 0) {
            return false;
        } else {
            return true;
        }
    }*/
}
