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
use App\Models\PfSubComponents;
use App\Models\SchoolDatabase;
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
        $id = $request->get('student_id');
        $student=UserStudents::where('id',$id)->first();
        $class_config=$student->class_config;
        $fees=PfClsDetails::where('class_config_id',$class_config)->get();
        foreach($fees as $fee){
            // Declare and define two dates
            $date1 = strtotime( $fee->fee_start_date);
            $date2 = strtotime($fee->fee_end_date);
            
            // Formulate the Difference between two dates
            $diff = abs($date2 - $date1);
            $days=($diff/60/60/24);
            if($days < 30){
                $fee->component_name=$fee->feesComp->comp_name." [".date('M',strtotime($fee->fee_start_date))."]";
            }else{
                $fee->component_name=$fee->feesComp->comp_name." [".date('M',strtotime($fee->fee_start_date))."-".date('M',strtotime($fee->fee_end_date))."]";
            }
            $fee->amount=number_format($fee->amount,2);
            //$fee->component_name=$fee->feesComp->comp_name;
            unset($fee->feesComp);
        }
        return response()->json($fees);
    }   

    public function studentFees(Request $request) {
        $user_data = auth()->user();
        $this->getDb($user_data['school_profile_id'],$request->get('academic_year'));
        $student_id=$request->get('student_id');
        $today = Carbon::today()->format("Y-m-d");
        $batch_table = BatchTable::where('batch_active', 1)->first();
        $total_fee = PfStuDetails::where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id])->get()->sum('amount');
        $pf_stu_ids = PfStuDetails::where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id])->pluck('fee_stu_id')->toArray();
        $component_list = PfStuDetails::where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id])->groupBy('fee_comp_id')->get();
        $pf_transaction = PfTransaction::select('trans_id', 'receipt_no', 'pf_stu_id', 'paid_amount', 'paid_date','active_status')->whereIn('pf_stu_id', $pf_stu_ids)->whereNull('pf_concession_id')->whereNull('pf_adjustment_id')->where('pf_pay_mode', '<', 8)->where('active_status', 1)->get();
        $paid_ids = PfTransaction::select('trans_id', 'receipt_no', 'pf_stu_id', 'paid_amount', 'paid_date','active_status')->whereIn('pf_stu_id', $pf_stu_ids)->whereNull('pf_concession_id')->whereNull('pf_adjustment_id')->where('active_status', 1)->pluck('pf_stu_id')->toArray();
        $outstanding_list = PfStuDetails::whereNotIn('fee_stu_id', $paid_ids)->where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id])->whereDate('fee_end_date', '<', $today)->get();
        $upcoming_list = PfStuDetails::whereNotIn('fee_stu_id', $paid_ids)->where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id])->whereDate('fee_end_date', '>=', $today)->get();
        $component_wise = $paid_fees = $outstanding_fees = $upcoming_fees = [];
        if(count($component_list) > 0) {
            foreach($component_list as $key=>$value) {
                $comp_fee = new stdClass();
                $comp_fee->amount = PfStuDetails::where(['stu_id'=>$student_id,'batch'=>$batch_table->batch_id,'fee_comp_id'=>$value->fee_comp_id])->get()->sum('amount');
                $comp_fee->comp_name = $value->compName->comp_name;
                $component_wise[] = $comp_fee;
            }
        }
        if(count($pf_transaction) > 0) {
            foreach($pf_transaction as $key=>$value) {
                $paid_fee = new stdClass();
                $paid_fee->receipt_no = $value->receipt_no;
                $paid_fee->paid_date = Carbon::parse($value->paid_date)->format("d-m-Y");
                $paid_fee->paid_amount = $value->paid_amount;
                $paid_fee->comp_name = $value->student->compName->comp_name;
                $paid_fees[] = $paid_fee;
            }
        }
        if(count($outstanding_list) > 0) {
            foreach($outstanding_list as $key=>$value) {
                $out_fee = new stdClass();
                $out_fee->comp_name = $value->compName->comp_name;
                $out_fee->amount = $value->amount;
                $today_date = Carbon::today();
                $date = Carbon::parse($value->fee_end_date);
                $out_fee->overdue_days = $date->diffInDays($today_date);
                $outstanding_fees[] = $out_fee;
            }
        }
        if(count($upcoming_list) > 0) {
            foreach($upcoming_list as $key=>$value) {
                $up_fee = new stdClass();
                $up_fee->comp_name = $value->compName->comp_name;
                $up_fee->amount = $value->amount;
                $up_fee->pay_before = Carbon::parse($value->date)->format("d-m-Y");
                $upcoming_fees[] = $up_fee;
            }
        }
        $total_upcoming_fee = $upcoming_list->sum('amount');
        $total_fee_paid = $pf_transaction->sum('paid_amount');
        $total_outstanding_fee = $outstanding_list->sum('amount');
        return response()->json(compact('total_fee','total_fee_paid','total_outstanding_fee','total_upcoming_fee','component_wise','paid_fees','outstanding_fees','upcoming_fees'));
    } 

    // Fetch all academic years
    public function academicYear(){
        $user_data = auth()->user();

        $all_years=[];
        $all_years = SchoolAcademicYears::where('school_profile_id',$user_data['school_profile_id'])->pluck('academic_year')->toArray();
        return response()->json($all_years);
    }

    // student payment history
    public function studentPaymentHistory(Request $request){
        $user_data = auth()->user();
        $batch=BatchTable::where('batch_active',1)->first();
        $fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id')])->pluck('fee_comp_id')->toArray();
        $distinct_value=array_unique($fees);
        $components=PfComponents::whereIn('comp_id',$distinct_value)->get();
        $comp=[];
        foreach($components as $key=>$value){
            $fees=PfStuDetails::where(['batch'=>$batch->batch_id,'stu_id'=>$request->get('student_id'),'fee_comp_id'=>$value->comp_id])->pluck('fee_stu_id')->toArray();
            $paid_amount=PfTransaction::whereIn('pf_stu_id',$fees)->where('active_status',1)->sum('paid_amount');  
            $adjusted_amount=PfTransaction::whereIn('pf_stu_id',$fees)->where('active_status',1)->sum('adjusted_amount');       
            $value->total_paid=$paid_amount+$adjusted_amount;
            if((int)$value->total_paid == 0){
                unset($components->$key);
                continue;
            }
            $history=PfTransaction::whereIn('pf_stu_id',$fees)->where('active_status',1)->get();
            foreach($history as $his){
                // Declare and define two dates
                $date1 = strtotime($his->student->fee_start_date);
                $date2 = strtotime($his->student->fee_end_date);
                
                // Formulate the Difference between two dates
                $diff = abs($date2 - $date1);
                $days=($diff/60/60/24);
                if($days < 30){
                    $his->fees_component=$his->student->feesComp->comp_name." [".date('M',strtotime($his->student->fee_start_date))."]";
                }else{
                   $his->fees_component=$his->student->feesComp->comp_name." [".date('M',strtotime($his->student->fee_start_date))."-".date('M',strtotime($his->student->fee_end_date))."]";
                }
                //$his->fees_component=$his->student->feesComp->comp_name;
                $his->total_amount=$his->paid_amount+$his->adjusted_amount;
                $payment_method=PfPaymentMode::where('mode_id',$his->pf_pay_mode)->first();
                $his->payment_mode=$payment_method->mode_name;
                unset($his->student);
            }
            $value->payment_history=$history;
            $comp[]=$value;
        }
        return response()->json($comp);
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
}
