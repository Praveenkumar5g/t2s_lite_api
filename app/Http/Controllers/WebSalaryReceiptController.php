<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 24-05-2023
 * Time: 05:30
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Excel as ExcelExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\EmployeeSalaryDetails;
use App\Http\Controllers\Controller;
use App\Models\UserManagements;
use App\Models\SchoolDatabase;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use App\Models\SchoolUsers;
use App\Models\UserStaffs;
use App\Models\UserAdmin;
use App\Models\UserAll;
use Carbon\Carbon;
use DataTables;
use Session;
use Config;
use PDF;
use DB;

class WebSalaryReceiptController extends Controller
{    
    public function index()
    {
        return view('SalaryReceipt.list');
    }

    public function upload()
    {
        return view('SalaryReceipt.upload');
    }

    public function salaryUpload(Request $request)
    {
        $user = Session::get('user_data');
        // get the common id to insert
        if($user->user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_table_id = UserAdmin::where(['user_id'=>$user->user_id])->pluck('id')->first();

        $userall_id = UserAll::where(['user_table_id'=>$user_table_id,'user_role'=>$user->user_role])->pluck('id')->first();//get common id 

        $filedetails = $request->file('salary');

        try{
            $spreadsheet = IOFactory::load($filedetails->getRealPath());//get the information and data 
            $success_rows = $error_rows = 0;
            $errors  = [];

            $sheet = $spreadsheet->setActiveSheetIndex(0); 
            $row_limit    = $sheet->getHighestDataRow(); //get total rows count
            $column_limit = $sheet->getHighestDataColumn();//get total column count
            $row_range    = range( 2, $row_limit ); //set row range
            $column_range = range( 'A', $column_limit ); //set column range
            $startcount = 2; //inital value to start
            $index =0;
            $data = array();

            $split_date = explode('/',$request->month);
            $month = $split_date[0];
            $year = $split_date[1];
            $date = date('d');
            $salary_date = $year.'-'.$month.'-'.$date;
            $error='';
            foreach ( $row_range as $row ) {
                $employee_no = $sheet->getCell('A'.$row)->getCalculatedValue();
                $staffs = UserStaffs::select('id','user_id')->where('employee_no',$employee_no)->get()->first();
                if(!empty($staffs))
                {
                    $user_table_id = $staffs->id;
                    $user_role = Config::get('app.Staff_role');
                    $user_id = $staffs->user_id;
                }
                else
                {
                    $managements = UserManagements::select('id','user_id')->where('employee_no',$employee_no)->get()->first();
                    if(!empty($managements))
                    {
                        $user_table_id = $managements->id;
                        $user_role = Config::get('app.Management_role');
                        $user_id = $managements->user_id;
                    }
                }

                if($user_table_id !='' && $user_role!='')
                    $user_all_id = UserAll::where('user_table_id',$user_table_id)->where('user_role',$user_role)->pluck('id')->first();

                if($user_all_id!='' && $user_id!='' && $salary_date!='')
                {
                    $user_salary = ([
                        'user_id'=>$user_id,
                        'user_all_id'=>$user_all_id,
                        'month'=>$salary_date,
                        'employee_no'=>$employee_no,
                        'user_role'=>$user_role,
                        'pfacc_no'=> $sheet->getCell('B'.$row)->getCalculatedValue(),
                        'uan'=> $sheet->getCell('C'.$row)->getCalculatedValue(),
                        'esi'=> $sheet->getCell('D'.$row)->getCalculatedValue(),
                        'actual'=> $sheet->getCell('F'.$row)->getCalculatedValue(),
                        'earned_wages'=> $sheet->getCell('G'.$row)->getCalculatedValue(),
                        'basic_da'=> $sheet->getCell('H'.$row)->getCalculatedValue(),
                        'hra'=> $sheet->getCell('I'.$row)->getCalculatedValue(),
                        'ot'=> $sheet->getCell('J'.$row)->getCalculatedValue(),
                        'employer'=> $sheet->getCell('K'.$row)->getCalculatedValue(),
                        'employee'=> $sheet->getCell('L'.$row)->getCalculatedValue(),
                        'llp'=> $sheet->getCell('M'.$row)->getCalculatedValue(),
                        'advance'=> $sheet->getCell('N'.$row)->getCalculatedValue(),
                        'net'=> $sheet->getCell('O'.$row)->getCalculatedValue(),
                        'working_days'=> $sheet->getCell('P'.$row)->getCalculatedValue(),
                        'pf'=> $sheet->getCell('Q'.$row)->getCalculatedValue(),
                        'lop'=> $sheet->getCell('R'.$row)->getCalculatedValue(),
                        'created_by'=>$userall_id,
                        'created_time'=>Carbon::now()->timezone('Asia/Kolkata'),
                    ]);
                    EmployeeSalaryDetails::insert($user_salary);
                    $staffs = [];
                }
                else
                {
                    if($error != '')
                        $error.=' , ';
                    $error.= $row;
                }

            }
        } catch (Exception $e) {
           return back()->with('error',$error);
        }
        if($error!='')
            return back()->with('error',"Invalid Employee No's in line no - ".$error);

        return back()->with('success','Uploaded Successfully');
    }


    // get salary list details
    public function getSalary_list(Request $request)
    {
        if ($request->ajax()) {
            $data = EmployeeSalaryDetails::select('id','user_id','pfacc_no','month','actual','user_all_id','employee_no','user_role');
            if($request->employee_no!='')
                $data = $data->where('employee_no', 'like', '%' .$request->employee_no. '%');
            if($request->role!='')
                $data = $data->where('user_role', 'like', '%' .$request->role. '%');
            if($request->pfaccno!='')
                $data = $data->where('pfacc_no', 'like', '%' .$request->pfaccno. '%');
            if($request->month!='')
            {
                $split_date = explode('/',$request->month);
                $month = $split_date[0];
                $year = $split_date[1];
                $start_date = '01-'.$month.'-'.$year;
                $end_date = '31-'.$month.'-'.$year;
                $data = $data->where('month','>=',date("Y-m-d",strtotime($start_date)))->Where('month','<=',date("Y-m-d",strtotime($end_date)));
            }

            $data = $data->orderBy('created_time','desc')->get()->toArray();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_name', function($row){
                    //fetch id from user all table to store notification triggered user
                    if($row['user_role'] == Config::get('app.Admin_role'))//check role and get current user id
                        $user_table_id = UserAdmin::where(['employee_no'=>$row['employee_no']])->get()->first();
                    else if($row['user_role'] == Config::get('app.Management_role'))
                        $user_table_id = UserManagements::where(['employee_no'=>$row['employee_no']])->get()->first();
                    else if($row['user_role'] == Config::get('app.Staff_role'))
                        $user_table_id = UserStaffs::where(['employee_no'=>$row['employee_no']])->get()->first();
                    $employee_name = $user_table_id->first_name;
                    
                    return $employee_name;
                })
                ->addColumn('actual', function($row){
                    return number_format($row['actual'],2);
                })
                ->addColumn('month', function($row){
                    $time=strtotime($row['month']);
                    $month=date("F",$time).' - '.date("Y",$time);
                    
                    return $month;
                })
                ->addColumn('payslip', function($row){
                    $actionBtn = '<a href="'.url('employee/download_individual_payslip?id='.$row['id']).'" target="_blank" class="edit btn btn-success btn-sm"><i class="fas fa-download nav-icon"></i></a>';
                    return $actionBtn;
                })
                ->rawColumns(['month','employee_name','payslip','actual'])
                ->make(true);
        }
    }

    public function download_individual_payslip(Request $request)
    {
        // retreive all records from db
        $data = EmployeeSalaryDetails::where('id',$request->id)->get()->first();

        $time=strtotime($data->month);
        $month=date("F",$time).' - '.date("Y",$time);

        $user_role = SchoolUsers::where('user_id',$data->user_id)->pluck('user_role')->first();

        //fetch id from user all table to store notification triggered user
        if($user_role == Config::get('app.Admin_role'))//check role and get current user id
            $user_data = UserAdmin::where(['user_id'=>$data->user_id])->get()->first();
        else if($user_role == Config::get('app.Management_role'))
            $user_data = UserManagements::where(['user_id'=>$data->user_id])->get()->first();
        else if($user_role == Config::get('app.Staff_role'))
            $user_data = UserStaffs::where(['user_id'=>$data->user_id])->get()->first();

        $school_name = SchoolProfile::where('id',Session::get('user_data')->school_profile_id)->pluck('school_name')->first();

        $payslip = ([
            'school_name'=>$school_name,
            'month'=>$month,
            'name'=>$user_data->first_name,
            'employee_no'=>$data->employee_no,
            'account_no'=>$data->pfacc_no,
            'uan'=>$data->uan,
            'esi'=>$data->esi,
            'department'=>$user_data->department,
            'designation'=>$user_data->designation,
            'doj'=>$user_data->doj,
            'ctc'=>$data->actual,
            'working_ctc'=>$data->earned_wages,
            'paid_days'=>$data->working_days,
            'basic_da'=>$data->basic_da,
            'hra'=>($data->hra)?$data->hra:0,
            'ot'=>($data->ot)?$data->ot:0,
            'employer'=>($data->employer)?$data->employer:0,
            'employee'=>($data->employee)?$data->employee:0,
            'llp'=>($data->llp>0)?$data->llp:0,
            'advance'=>($data->advance>0)?$data->advance:0,
            'net'=>$data->net,
            'pf'=>$data->pf,
            'lop'=>($data->lop)?$data->lop:0,
            'net_words'=>($data->net>0)?$this->numberTowords($data->net):0
        ]);
        $pdf = PDF::loadView('SalaryReceipt.payslip', $payslip);
        // download PDF file with download method
        return $pdf->download('Payslip.pdf');
    }

    public function numberTowords($num)
    { 
        $ones = array(1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five", 6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen" );

        $tens = array( 1 => "Ten",2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty", 6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety" ); 

        $hundreds = array( "Hundred", "Thousand", "Million", "Billion", "Trillion", "Quadrillion" ); //limit t quadrillion 

        $num = number_format($num,2,".",","); 
        $num_arr = explode(".",$num); 
        $wholenum = $num_arr[0]; 
        $decnum = $num_arr[1]; 
        $whole_arr = array_reverse(explode(",",$wholenum)); 
        krsort($whole_arr); 
        $rettxt = ""; 
        foreach($whole_arr as $key => $i){ 
            if($i < 20){ 
                $rettxt .= $ones[$i]; 
            }elseif($i < 100){ 
                $rettxt .= $tens[substr($i,0,1)]; 
                $rettxt .= " ".$ones[substr($i,1,1)]; 
            }else{ 
                $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0]; 
                if(substr($i,1,1) > 0)
                    $rettxt .= " ".$tens[substr($i,1,1)]; 
                if(substr($i,4,1) > 0)
                    $rettxt .= " ".$ones[substr($i,2,1)]; 
            } 
            if($key > 0){ 
                $rettxt .= " ".$hundreds[$key]." "; 
            } 
        } 
        if($decnum > 0){ 
            $rettxt .= " and "; 
            if($decnum < 20){ 
                $rettxt .= $ones[$decnum]; 
            }elseif($decnum < 100){ 
                $rettxt .= $tens[substr($decnum,0,1)]; 
                $rettxt .= " ".$ones[substr($decnum,1,1)]; 
            } 
        } 
        return $rettxt; 
    } 

    public function download_report(Request $request)
    {
        $data = EmployeeSalaryDetails::select('id','user_id','pfacc_no','month','actual','user_all_id','employee_no','user_role');
        if($request->employee_no!='')
            $data = $data->where('employee_no', 'like', '%' .$request->employee_no. '%');
        if($request->role!='')
            $data = $data->where('user_role', 'like', '%' .$request->role. '%');
        if($request->pfaccno!='')
            $data = $data->where('pfacc_no', 'like', '%' .$request->pfaccno. '%');
        if($request->month!='')
        {
            $split_date = explode('/',$request->month);
            $month = $split_date[0];
            $year = $split_date[1];
            $start_date = '01-'.$month.'-'.$year;
            $end_date = '31-'.$month.'-'.$year;
            $data = $data->where('month','>=',date("Y-m-d",strtotime($start_date)))->Where('month','<=',date("Y-m-d",strtotime($end_date)));
        }

        $data = $data->orderBy('created_time','desc')->get()->toArray();
        // return view('SalaryReceipt.download_report', ['data'=>$data]);

        $pdf = PDF::loadView('SalaryReceipt.download_report', ['data'=>$data]);

        return $pdf->download('Salary Report.pdf');
    }

    public function sample_excel()
    {
        $file_path = '/assets/sampleexcels/salary_calculation.xlsx';
        return response()->download($file_path,'salary_calculation.xlsx');
    }

}