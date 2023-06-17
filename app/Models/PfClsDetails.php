<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 05-06-2023
 * Time: 05:00
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfClsDetails extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_cls_details";
    protected $primaryKey = 'fee_cls_id';
    protected $guarded = [];
    public $timestamps = false;

    public function batchDetail()
    {
        return $this->belongsTo('App\Models\BatchConfigurationTable','batch_config_id','batch_configuration_id');
    }

    public function feesComp()
    {
        return $this->belongsTo('App\Models\PfComponents','fee_comp_id','comp_id');
    }

	public function subComp()
	{
		return $this->belongsTo('App\Models\PfSubComponents', 'sub_comp_id', 'id');
	}

    public function feeEditDelete()
    {
        $edit= true;
        $pf_stu_ids = PfStuDetails::where(['fee_cls_id'=>$this->fee_cls_id,'batch_config_id'=>$this->batch_config_id,'fee_comp_id'=>$this->fee_comp_id])->pluck('fee_stu_id')->toArray();
        $pf_transaction = PfTransaction::where('active_status', 1)->whereIn('pf_stu_id', $pf_stu_ids)->get();
        if(count($pf_transaction) > 0) {
            $edit = false;
        }
        return $edit;
    }

    public function feeDeleteAll()
    {
        $delete_all = true;
        $pf_stu_ids = PfStuDetails::where(['batch_config_id'=>$this->batch_config_id,'fee_comp_id'=>$this->fee_comp_id])->pluck('fee_stu_id')->toArray();
        $pf_transaction = PfTransaction::where('active_status', 1)->whereIn('pf_stu_id', $pf_stu_ids)->get();
        if(count($pf_transaction) > 0) {
            $delete_all = false;
        }
        return $delete_all;
    }

    public function feeOverride()
    {
        $override = false;
        $pf_stu_details = PfStuDetails::where(['fee_cls_id'=>$this->fee_cls_id,'batch_config_id'=>$this->batch_config_id,'fee_comp_id'=>$this->fee_comp_id,'unique_fee'=>1])->get();
        if(count($pf_stu_details) > 0) {
            $override = true;
        }
        return $override;
    }
}
