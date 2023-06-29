<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 02-06-2023
 * Time: 05:15
 * PayFees feestructure and payment related functions
 */
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolAcademicYears;
use App\Models\PaymentApiConfig;
use App\Models\PfSubComponents;
use App\Models\SchoolDatabase;
use App\Models\Configurations;
use App\Models\PfMixedConfig;
use App\Models\PfPaymentMode;
use App\Models\PfTransaction;
use App\Models\SchoolProfile;
use App\Models\UserStudents;
use App\Models\PfClsDetails;
use App\Models\PfStuDetails;
use App\Models\PfComponents;
use App\Models\BatchTable;
use stdClass;

class PayfeesController extends Controller
{
    public function getDb($school_id,$academic_year)
    {
        $school_profile = SchoolProfile::where('id',$school_id)->first(); //get school profile details from corresponding school
        $academic_year = $school_profile->active_academic_year;
        $config_school = SchoolDatabase::where('school_id', $school_id)->where('academic_year',$academic_year)->get()->first();
        Config::set('database.connections.school_db.host',$config_school->school_db_host);
        Config::set('database.connections.school_db.username',$config_school->school_db_user);
        Config::set('database.connections.school_db.password',$config_school->school_db_pass);
        Config::set('database.connections.school_db.database',$config_school->school_db_name);
        DB::reconnect('school_db');
    }

    public function feesStructure(Request $request){
        $user_data = auth()->user();
        $total_amount = 0;
        $today = Carbon::today();
        $today_date = $today->format("Y-m-d");
        $id = $request->get('student_id');
        $student=UserStudents::where('id',$id)->first();
        $class_config=$student->class_config;
        $fees = PfStuDetails::select('fee_stu_id','fee_comp_id','stu_id','sub_comp_id','amount','fee_end_date')->where('stu_id', $id)->get();
        if(count($fees) > 0) {
            foreach($fees as $key=>$fee){
                $fees[$key]['component_name'] = $fee->fee_comp_id != null?$fee->feesComp->comp_name:"";
                $fees[$key]['sub_component_name'] = $fee->sub_comp_id != null?$fee->feesSubComp->name:"";
                //$fee->component_name=$fee->feesComp->comp_name;
                if($today_date > $fee->fee_end_date && $fee->balanceFee() != 'NIL') {
                    $fees[$key]['overdue_days'] = Carbon::parse($fee->fee_end_date)->diffInDays($today);
                } else {
                    $fees[$key]['overdue_days'] = null;
                }
                $total_amount += $fee->amount;
                unset($fee->feesComp);
                unset($fee->feesSubComp);
            }
        }
        return response()->json(compact('total_amount','fees'));
    }   

    public function studentFees(Request $request) {
        $user_data = auth()->user();
        $this->getDb($user_data['school_profile_id'],$request->get('academic_year'));
        $student_id=$request->get('student_id');
        $config = Configurations::where('school_profile_id',$user_data['school_profile_id'])->first();
        $this_month_fees=$this->thisMonth($request);
        $advance_fees=$this->advance($request);
        $overdue_fees=$this->overdue($request);
        $amount=0;
        foreach($this_month_fees as $this_fees){
            $this_fees->isPayment = $config->fee_pay_status == 0 ? true: false;
            $amount +=(float)$this_fees->amount;
        }
        foreach($advance_fees as $ad_fees){
            $ad_fees->isPayment = false;
            $amount +=(float)$ad_fees->amount;
        }
        foreach($overdue_fees as $over_fees){
            $over_fees->isPayment = $config->fee_pay_status == 0 ? true: false;
            $amount +=(float)$over_fees->amount;
        }
        // $school_model = SchoolRegistration::where('id',$request->get('school_id'))->first();
        $inclusive_detail='';
        $api_config = PaymentApiConfig::where('school_profile_id',$user_data['school_profile_id'])->first();
        $id = $request->get('student_id');
        $student=UserStudents::where('isdfd',$id)->first();
       // dd($student);
        if(empty($student) == null){
             return response()->json(['status'=>true,'message'=>"Student Not available in this academic year!"]);
        }else{
            $payment_config_array=$this->getPaymentConfigDetails($user_data['school_profile_id'],$student->class_config);
          $payment_config = $payment_config_array['cred_object']; // Contains object of key and id of payment gateway
        //dd($payment_config);
        $api_secret=$payment_config->secret_key;  //api secret key
        $api_key=$payment_config->merchant_id;  //api key ID
        $api_config->secret_key= $api_secret;
        $api_config->merchant_id= $api_key;
        $config_status=$config->fee_pay_status;
            $past_acedemic_status=$this->checkPastAcademicFees($request);
        return response()->json(compact('this_month_fees','advance_fees','overdue_fees','amount','api_config','inclusive_detail','config_status','past_acedemic_status'));
        }
    } 

    // Fetch all academic years
    public function academicYear(){
        $user_data = auth()->user();

        $all_years=[];
        $all_years = SchoolAcademicYears::where('school_profile_id',$user_data['school_profile_id'])->pluck('academic_year')->toArray();
        return response()->json($all_years);
    }

    // student payment history
    public function paymentHistory(Request $request){
        $user_data = auth()->user();
        $batch=BatchTable::where('batch_active',1)->first();
        $fees = PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('stu_id')])->pluck('fee_stu_id')->toArray();
        $transaction = PfTransaction::select('pf_stu_id','active_status','adjusted_amount','paid_amount','trans_id','receipt_no','pf_stu_id','pf_pay_mode','paid_date')->whereIn('pf_stu_id', $fees)->where('active_status', 1)->orderBy('paid_date','DESC')->get();
        foreach($transaction as $key=>$value){
            $transaction[$key]['total_paid'] = $value->adjusted_amount != null?($value->paid_amount+$value->adjusted_amount):$value->paid_amount;
            $transaction[$key]['component_name'] = $value->student->fee_comp_id != null?$value->student->feesComp->comp_name:"";
            $transaction[$key]['sub_component_name'] = $value->student->sub_comp_id != null?$value->student->feessubComp->name:"";
            $transaction[$key]['paid_date'] = Carbon::parse($value->paid_date)->format("d-m-Y");
            //$his->fees_component=$his->student->feesComp->comp_name;
            $payment_method = PfPaymentMode::where('mode_id',$value->pf_pay_mode)->first();
            $transaction[$key]['payment_mode'] = $payment_method->mode_name;
            unset($value->student);
        }
        return response()->json($transaction);
    }

    public function receiptGenerate(Request $request, $receipt_no) {
        $user_data = auth()->user();
        $batch = BatchTable::where('batch_active',1)->first();
        $school_id = $request->get('school_id');
        $student_table = StudentTable::where('student_table_id', $request->get('stu_id'))->first();
        $pdf_name = $student_table->student_id."_".$receipt_no;
        // if(file_exists(env('ERP_FOLDER_PATH').'profile/fee_receipt/'.$pdf_name.'.pdf')) {
        //     unlink(env('ERP_FOLDER_PATH').'profile/fee_receipt/'.$pdf_name.'.pdf');
        // }
        $data = PfTransaction::where('receipt_no', $receipt_no)->whereNotIn('pf_pay_mode', [8,9])->where('active_status', 1)->get();
        if($request->get('school_id') == 105) {
            $pdf = PDF::loadView('payfees.dpsc_document', compact('data','school_id'), [], ['margin_top' => 5,'margin_left' => 3,'margin_right' => 3,'margin_bottom' =>5]);
        } else {
            $pdf = PDF::loadView('payfees.document', compact('data','school_id'));
        }
        // $pdf->save(env('ERP_FOLDER_PATH').'profile/fee_receipt/'.$pdf_name.'.pdf');
        $receipt_url = env('RESULT_IMAGE_URL').'profile/fee_receipt/'.$pdf_name.'.pdf';
        return response()->json(compact('receipt_url'));
    }

    public function thisMonth($request){
        $first_date = date('Y-m-d',strtotime('first day of this month'));
        $last_date = date('Y-m-d',strtotime('last day of this month'));
        $batch=BatchTable::where('batch_active',1)->first();
        $pending_fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id')])->whereBetween('fee_start_date', array($first_date, $last_date))->orderBy('fee_start_date')->get();
        $fees_id=[];
        foreach($pending_fees as $key=>$value){
            $receipt=PfTransaction::where(['pf_stu_id'=>$value->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount=0;
            foreach($receipt as $individual_receipt){   
                 $paid_amount +=($individual_receipt->paid_amount+$individual_receipt->adjusted_amount);
            }
            if($value->amount > $paid_amount){
                $fees_id[]=$value->fee_stu_id;
            }
        }
        $fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id'),'online_fee'=>'Y'])->whereIn('fee_stu_id',$fees_id)->whereBetween('fee_start_date', array($first_date, $last_date))->orderBy('fee_start_date')->get();
        foreach($fees as $fee){
            $receipt_detail=PfTransaction::where(['pf_stu_id'=>$fee->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount_val=0;
            foreach($receipt_detail as $indi_receipt){
                $paid_amount_val +=($indi_receipt->paid_amount+$indi_receipt->adjusted_amount);
            }
             $fee->num_amount= (float)((float)$fee->amount - (float)$paid_amount_val);
            $fee->amount= $fee->amount - $paid_amount_val;
          
            // Declare and define two dates
            $date1 = strtotime( $fee->fee_start_date);
            $date2 = strtotime($fee->fee_end_date);
            $date11 = new DateTime("now");
            $date22 = new DateTime($fee->fee_end_date);
            $interval = $date11->diff($date22);
            $status="";
            $day=$interval->days;
            if($interval->invert == 1){
                $status="Days over";
            }else{
                if($interval->days == 0){
                    $status="Last Day";
                }else{
                    $status="Days Left";
                }
            }
            $fee->days= $day;
            $fee->status=$status;
            // Formulate the Difference between two dates
            $diff = abs($date2 - $date1);
            $days=($diff/60/60/24);
               if(isset($fee->subComp)){
                $fee->component_name=$fee->feesComp->comp_name."[".$fee->subComp->name."]";
            }else{
               $fee->component_name=$fee->feesComp->comp_name; 
            }
            //$fee->component_name=$fee->feesComp->comp_name;
            unset($fee->feesComp);
        }
      return $fees;
    }

    public function advance($request){
        $first_date = date('Y-m-d',strtotime('first day of this month'));
        $last_date = date('Y-m-d',strtotime('last day of this month'));
        $batch=BatchTable::where('batch_active',1)->first();
        $pending_fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id')])->where('fee_start_date','>',$last_date)->get();
        $fees_id=[];
        foreach($pending_fees as $key=>$value){
            $receipt=PfTransaction::where(['pf_stu_id'=>$value->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount=0;
            foreach($receipt as $individual_receipt){   
               $paid_amount +=($individual_receipt->paid_amount+$individual_receipt->adjusted_amount);
            }
            if($value->amount > $paid_amount){
                $fees_id[]=$value->fee_stu_id;
            }
        }
        $fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id'),'online_fee'=>'Y'])->whereIn('fee_stu_id',$fees_id)->where('fee_start_date','>',$last_date)->orderBy('fee_start_date')->get();
        foreach($fees as $fee){
            $receipt_detail=PfTransaction::where(['pf_stu_id'=>$fee->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount_val=0;
            foreach($receipt_detail as $indi_receipt){
                $paid_amount_val +=($indi_receipt->paid_amount+$indi_receipt->adjusted_amount);
            }
            $fee->num_amount= (float)((float)$fee->amount - (float)$paid_amount_val);
            $fee->amount= $fee->amount - $paid_amount_val;
            // Declare and define two dates
            $date1 = strtotime( $fee->fee_start_date);
            $date2 = strtotime($fee->fee_end_date);
            $date11 = new DateTime("now");
            $date22 = new DateTime($fee->fee_end_date);
            $interval = $date11->diff($date22);
            $status="";
            $day=$interval->days;
            if($interval->invert == 1){
                $status="Days over";
            }else{
                if($interval->days == 0){
                    $status="Last Day";
                }else{
                    $status="Days Left";
                }
            }
            $fee->days= $day;
            $fee->status=$status;
            // Formulate the Difference between two dates
            $diff = abs($date2 - $date1);
            $days=($diff/60/60/24);
            if(isset($fee->subComp)){
                $fee->component_name=$fee->feesComp->comp_name."[".$fee->subComp->name."]";
            }else{
               $fee->component_name=$fee->feesComp->comp_name; 
            }//$fee->component_name=$fee->feesComp->comp_name;
            unset($fee->feesComp);
        }
      return $fees;
    }
    public function overdue($request){
        $first_date = date('Y-m-d',strtotime('first day of this month'));
        $last_date = date('Y-m-d',strtotime('last day of this month'));
        $batch=BatchTable::where('batch_active',1)->first();
        $pending_fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id')])->where('fee_start_date','<',$first_date)->get();
        $fees_id=[];
        foreach($pending_fees as $key=>$value){
            $receipt=PfTransaction::where(['pf_stu_id'=>$value->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount=0;
            foreach($receipt as $individual_receipt){
                $paid_amount +=($individual_receipt->paid_amount+$individual_receipt->adjusted_amount);
            }
            if($value->amount > $paid_amount){
                $fees_id[]=$value->fee_stu_id;
            }
        }
        $fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id'),'online_fee'=>'Y'])->whereIn('fee_stu_id',$fees_id)->where('fee_start_date','<',$first_date)->orderBy('fee_start_date')->get();
        foreach($fees as $fee){
            // Declare and define two dates
            $date1 = strtotime( $fee->fee_start_date);
            $date2 = strtotime($fee->fee_end_date);
            $date11 = new DateTime("now");
            $date22 = new DateTime($fee->fee_end_date);
            $interval = $date11->diff($date22);
            $status="";
            $day=$interval->days;
            if($interval->invert == 1){
                $status="Days over";
            }else{
                if($interval->days == 0){
                    $status="Last Day";
                }else{
                    $status="Days Left";
                }
            }
            $fee->days= $day;
            $fee->status=$status;
            // Formulate the Difference between two dates
            $diff = abs($date2 - $date1);
            $days=($diff/60/60/24);
            if(isset($fee->subComp)){
                $fee->component_name=$fee->feesComp->comp_name."[".$fee->subComp->name."]";
            }else{
               $fee->component_name=$fee->feesComp->comp_name; 
            }
             
            $receipt_detail=PfTransaction::where(['pf_stu_id'=>$fee->fee_stu_id,'active_status'=>'1'])->get();
            $paid_amount_val=0;
            foreach($receipt_detail as $indi_receipt){
                 $paid_amount_val += ($indi_receipt->paid_amount+$indi_receipt->adjusted_amount);
            }
            $fee->num_amount= (float)((float)$fee->amount - (float)$paid_amount_val);
           $fee->amount= $fee->amount - $paid_amount_val; 
            //$fee->component_name=$fee->feesComp->comp_name;
            unset($fee->feesComp);
        }
        return $fees;
    }

    public function getPaymentConfigDetails($school_id, $class_id)
    {
        $result_array = array();
        $result_array['cred_object'] = $result_array['receipt_object'] = "";
        $payment_api_config = PaymentApiConfig::where('school_profile_id',$school_id)->first();
        if(count($payment_api_config) == 1)
        {
            /* Set initial values as config db payment_api_config data in array */
            $result_array['cred_object'] = $payment_api_config; // Object for credentials
            $result_array['receipt_object'] = $payment_api_config; // Object for receipt
            /* If sub_pg_vendor is 'Y', the school has multiple bank accounts in the payment gateway vendor */
            if($payment_api_config->sub_pg_vendor == 'Y')
            {
                /* Fetch the record from table which has student's class */
                $pf_payment_gateway = PfPaymentGateway::whereRaw('FIND_IN_SET("'.$class_id.'",classes)')->first();
                if(count($pf_payment_gateway) == 1)
                {
                    $result_array['cred_object'] = $pf_payment_gateway;
                    /* If sub_receipt is 'Y', the school follow different receipt patterns for each bank account */
                    if($payment_api_config->sub_receipt == 'Y')
                    {
                        $result_array['receipt_object'] = $pf_payment_gateway;
                    }
                }
            }
        }
        return $result_array;
    }

    /* This function is to check past academic year pending fees */
    public function checkPastAcademicFees($request)
    {
        $user_data = auth()->user();
        try {
            $student_id = $request->get('student_id');
            $school_id = $user_data['school_profile_id'];
            $batches_array = SchoolAcademicYears::where('school_profile_id',$user_data['school_profile_id'])->pluck('academic_year')->toArray();
            $academic_year = $request->get('academic_year');
            //dd($batches_array);
            /* Remove current academic year from batch ID list */
            $key = array_search($academic_year, $batches_array);
            if (false !== $key) {
                unset($batches_array[$key]);
            }
            $academic_pending_fees = array();
            $config_school = SchoolAcademicYears::where('school_profile_id',$user_data['school_profile_id'])->where('academic_year','<',$academic_year)->whereIn('academic_year', $batches_array)->get();
            foreach($config_school as $schools){
                /* Connect to DB */
                Config::set('database.connections.school_db.host', $schools->school_db_host);
                Config::set('database.connections.school_db.username', $schools->school_db_user);
                Config::set('database.connections.school_db.password', $schools->school_db_pass);
                Config::set('database.connections.school_db.database', $schools->school_db_name);
                DB::reconnect('school_db');
                if(DB::connection('school_db')->getPdo()) {
                    /* Fetch records from pf_stu_details table for the student */
                    $pf_stu_fees = PfStuDetails::where('stu_id',$student_id)->pluck('amount','fee_stu_id')->toArray();
                    if(count($pf_stu_fees) > 0){
                        /* Total fees configured for the student */
                        $academic_total_fees = array_sum($pf_stu_fees);
                        /* pf_stu_details IDs for the student */
                        $academic_pf_stu_ids = array_keys($pf_stu_fees);
        
                        /* Total Fee paid */
                        $pf_transaction_paid = PfTransaction::select(DB::raw("SUM(paid_amount) + SUM(adjusted_amount) as total_paid"))->whereIn('pf_stu_id',$academic_pf_stu_ids)->pluck('total_paid')->toArray();
                        $academic_paid_fees = ($pf_transaction_paid[0] == "") ? 0 : $pf_transaction_paid[0];
                        /* Fees pending */
                        if($academic_total_fees > $academic_paid_fees){
                            $academic_pending_fees[$schools->academic_year] = $academic_total_fees - $academic_paid_fees;
                        }
                    }
                }
            }
        }
        /* Catch if any errors */
        catch(\Exception $e) {
            $status_message = "";
        }

        $status_message = "";
        /* If past academic year fee pending */
        if(count($academic_pending_fees) > 0){
            foreach($academic_pending_fees as $batch_year=>$pending_fee){
                $status_message = $status_message." : Academic Year - ".$batch_year.", Pending Fee - ".$pending_fee;
            }
            $status_message = "Past academic year fee still pending". $status_message.". Please switch to the respective academic year and pay them first!!!";
        }
        return $status_message;
    }
}
