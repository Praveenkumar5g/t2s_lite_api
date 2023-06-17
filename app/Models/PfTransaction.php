<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PfTransaction extends Model
{
    protected $connection = "school_db";
    protected $table = "pf_transaction";
    protected $primaryKey = 'trans_id';
    public $timestamps = false;
    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo('App\Models\PfStuDetails','pf_stu_id','fee_stu_id');
    }
	
	public function concession()
	{
		return $this->belongsTo('App\Models\PfConcession','pf_concession_id','concession_id');
	}
	
	public function adjustment()
	{
		return $this->belongsTo('App\Models\PfAdjustment','pf_adjustment_id','id');
	}
	
	public function paymentMode()
	{
		return $this->belongsTo('App\Models\PfPaymentMode','pf_pay_mode','mode_id');
	}
	
	public function pfStuDetails()
	{
		return $this->hasOne('App\Models\PfStuDetails','fee_stu_id','pf_stu_id');
	}
	
	public function loginTable()
	{
		return $this->belongsTo('App\Models\LoginTable', 'created_by', 'login_id');
	}
    
    public function batchConfigId()
    {
        return $this->belongsTo('App\Models\BatchConfigurationTable', 'batch_config_id', 'batch_configuration_id');
    }
    
    public function PaymentGateway()
    {
        return $this->belongsTo('App\Models\PfPaymentGateway', 'gateway_id', 'id');
    }
}
