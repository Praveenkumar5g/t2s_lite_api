<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfStuDetails extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_stu_details";
    protected $primaryKey = 'fee_stu_id';
    public $timestamps = false;
    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo('App\Models\StudentTable','stu_id','student_table_id');
    }

    public function compName()
    {
        return $this->belongsTo('App\Models\PfComponents', 'fee_comp_id', 'comp_id');
    }
    
    public function batchDetail()
    {
        return $this->belongsTo('App\Models\BatchConfigurationTable','batch_config_id','batch_configuration_id');
    }

    public function feesComp()
    {
        return $this->belongsTo('App\Models\PfComponents','fee_comp_id','comp_id');
    }
    public function feesSubComp()
    {
        return $this->belongsTo('App\Models\PfSubComponents','sub_comp_id','id');
    }
    
    public function batchYear()
    {
        return $this->belongsTo('App\Models\BatchTable','batch','batch_id');
    }

    public function feeEditDelete()
    {
        $edit= true;
        $pf_transaction = PfTransaction::where(['pf_stu_id'=>$this->fee_stu_id,'active_status'=>1])->get();
        if(count($pf_transaction) > 0) {
            $edit = false;
        }
        return $edit;
    }

    public function feeDeleteAll()
    {
        $delete_all = true;
        $pf_stu_ids = PfStuDetails::where(['stu_id'=>$this->stu_id,'batch_config_id'=>$this->batch_config_id,'fee_comp_id'=>$this->fee_comp_id])->pluck('fee_stu_id')->toArray();
        $pf_transaction = PfTransaction::where('active_status', 1)->whereIn('pf_stu_id', $pf_stu_ids)->get();
        if(count($pf_transaction) > 0) {
            $delete_all = false;
        }
        return $delete_all;
    }

    public function concession()
    {
        $pf_transaction = PfTransaction::where(['pf_stu_id'=>$this->fee_stu_id,'active_status'=>1])->get()->sum('paid_amount');
        if($this->amount == $pf_transaction) {
            return false;
        } else {
            return true;
        }
    }

	public function adjustment()
    {
        $pf_transaction = PfTransaction::where(['pf_stu_id'=>$this->fee_stu_id,'active_status'=>1])->get()->sum('paid_amount');
        if($this->amount == $pf_transaction) {
            return false;
        } else {
            return true;
        }
    }
	
    public function paidFee()
    {
        $paid_amount = PfTransaction::where('active_status',1)->where('pf_pay_mode','<>',8)->where('pf_stu_id', $this->fee_stu_id)->get()->sum('paid_amount');
        $adjusted_amount = PfTransaction::where('active_status',1)->where('pf_pay_mode','<>',8)->where('pf_stu_id', $this->fee_stu_id)->get()->sum('adjusted_amount');
        $total_paid = $paid_amount + $adjusted_amount;
        $total_paid = $total_paid == 0?'NIL':$total_paid;
        return $total_paid;
    }

    public function concessionFee()
    {
        $paid_amount = PfTransaction::where('active_status',1)->where('pf_pay_mode',8)->where('pf_stu_id', $this->fee_stu_id)->get()->sum('paid_amount');
        return $paid_amount;
    }
	
	public function adjustmentFee()
    {
        $paid_amount = PfTransaction::where('active_status',1)->where('pf_pay_mode',10)->where('pf_stu_id', $this->fee_stu_id)->get()->sum('paid_amount');
        return $paid_amount;
    }

    public function balanceFee()
    {
        $total_fee = $this->amount;
        $paid_fee =  $this->paidFee();
        $concession=$this->concessionFee();
        $paid_fee = $paid_fee == 'NIL'?0:$paid_fee;
        $concession_fee = $concession == 'NIL'?0:$concession;
        $balance_fee = $total_fee - ($paid_fee+$concession_fee);
        $total_balance = $balance_fee == 0?'NIL':$balance_fee;
        return $total_balance;
    }
}
