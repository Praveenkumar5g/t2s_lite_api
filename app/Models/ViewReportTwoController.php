<?php

namespace App\Http\Controllers\ReportCard;

ini_set('max_execution_time', '500');
ini_set('pcre.backtrack_limit', '5000000');

use Illuminate\Http\Request;
use App\BatchTable;
use App\BatchConfigurationTable;
use App\RcConfiguration;
use App\RcMarksCategory;
use App\SubjectSet;
use App\SubjectTable;
use App\RcExamList;
use App\StudentTable;
use App\RcStuMarks;
use App\RcOtherSubjects;
use Response;
use View;
use PDF;
use Zipper;
use File;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinalExport;

class ViewReportTwoController extends Controller
{
    public function index(Request $request)
    {
        $class_section_list = [];
        $rc_stu_marks = RcStuMarks::where('type_val', 1)->groupBy('configuration_id')->pluck('configuration_id')->toArray();
        $rc_config_mapping = RcConfiguration::whereIn('id', $rc_stu_marks)->groupBy('batch_config_id')->pluck('batch_config_id')->toArray();
        $active_batch = BatchTable::where('batch_active', 1)->first();
        
        $batch_config_table = BatchConfigurationTable::whereIn('batch_configuration_id', $rc_config_mapping)->where('batch',$active_batch->batch_id)->get();
        if(count($batch_config_table) > 0) {
            foreach($batch_config_table as $key=>$value) {
                $class_section_list[$value->batch_configuration_id] = $value->classSection();
            }
        }
        return view('ReportCard.ViewReportTwo.admin', compact('class_section_list'));
    }

    public function getExam(Request $request)
    {
		$rc_configuation_exam_ids = RcConfiguration::where('batch_config_id', $request->class_section)->groupBy('exam_id')->pluck('exam_id')->toArray();
        $exam_ids = RcStuMarks::where(['student_id'=>$request->student_id,'type_val'=>1])->groupBy('exam_id')->pluck('exam_id')->toArray();
        $exam_list = RcExamList::whereIn('id', $exam_ids)->whereIn('id', $rc_configuation_exam_ids)->pluck('exam_name','id')->toArray();
		if(in_array(6, array_keys($exam_list))) {
			$exam_list[8] = "FINAL REPORT";
		}
        return view('ReportCard.ViewReportTwo.exam', compact('exam_list'));
    }

    public function getStudent(Request $request)
    {
        $student_list = [];
        $all_student_ids = StudentTable::where('batch_config_id', $request->class_section)->pluck('student_table_id')->toArray();
        $rc_student_marks = RcStuMarks::whereIn('student_id', $all_student_ids)->pluck('student_id')->toArray();
        $student_table = StudentTable::whereIn('status',([1,2]))->whereIn('student_table_id', $rc_student_marks)->get();
        if(count($student_table) > 0) {
            foreach($student_table as $key=>$value) {
                $student_list[$value->student_table_id] = $value->roll_no."-".$value->first_name." ".$value->last_name." - ".$value->admission_id;
            }
        }
        return view('ReportCard.ViewReportTwo.student', compact('student_list'));
    }

    public function viewReport(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->class_section;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
        if($exam_id == 6 && $is_final_report == 1) {
            return view('ReportCard.ViewReportTwo.multiView', compact('exam_id', 'batch_config_id', 'student_id', 'is_final_report'));
        } else {
            return view('ReportCard.ViewReportTwo.singleView', compact('exam_id', 'batch_config_id', 'student_id', 'is_final_report'));
        }
    }

    public function pdfDownload(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
        if($exam_id == 6 && $is_final_report == 1) {
            $content = View::make('ReportCard.ViewReportTwo.indexMulti', compact('exam_id','batch_config_id','student_id','is_final_report'))->render();
        } else {
            $content = View::make('ReportCard.ViewReportTwo.index', compact('exam_id','batch_config_id','student_id','is_final_report'))->render();
        }
        $student_table = StudentTable::where('student_table_id',$student_id)->first();
        $student_name_with_space = $student_table->first_name.$student_table->last_name;
        $student_name_with_dots = str_replace(" ","",$student_name_with_space);
        $student_name = str_replace(".","",$student_name_with_dots);
        $batch_config_with_space = $student_table->classId->class_name.$student_table->sectionId->section_name;
        $batch_config = str_replace(" ","",$batch_config_with_space);
        $exam_list = RcExamList::where('id', $exam_id)->first();
        $exam_name = str_replace(" ","",$exam_list->exam_name);
        $pdf_name = $student_name."_".$exam_name."_".$batch_config;
        // Set the name of the text file
        $filename = $pdf_name.'.txt';
        
        // Set headers necessary to initiate a download of the textfile, with the specified name
        $headers = array(
            'Content-Type' => 'plain/txt',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            //'Content-Length' => sizeof($content),
        );
        return Response::make($content, 200, $headers);
    }

    
    public function excelReport(Request $request){
         $batch_config_table = BatchConfigurationTable::where('batch_configuration_id', $request->batch_config_id)->first();
         $batch_config_id=$request->batch_config_id;
         //return view('ReportCard.ViewReportTwo.ajaxExport', compact('batch_config_id','batch_config_table'));
        
        return Excel::download(new FinalExport($request), 'final_report.xlsx');
    }
    public function pdfClassDownload(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $students=StudentTable::where('batch_config_id', $batch_config_id)->where('status',1)->get();
        $path = public_path().'/'.$batch_config_id;
        if (! File::exists($path)) {
            File::makeDirectory($path);
        }
        foreach ($students as $student){
            $student_id=$student->student_table_id;
            if($exam_id == 6) {
                $content = View::make('ReportCard.ViewReportTwo.indexMulti', compact('exam_id','batch_config_id','student_id'))->render();
            } else {
                $content = View::make('ReportCard.ViewReportTwo.index', compact('exam_id','batch_config_id','student_id'))->render();
            }
            $student_model=StudentTable::where('student_table_id',$student->student_table_id)->first();
            // Set the name of the text file
            $filename = 'Reportcard-'.$student_model->first_name.'.txt';
            File::put($batch_config_id.'/'.$filename,$content);
        }
        $files = glob(public_path($batch_config_id.'/*'));
        Zipper::make('reportcard.zip')->add($files);
        return response()->download(public_path('reportcard.zip'));
    }

    public function pdf(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
        if($exam_id == 6 && $is_final_report == 1) {
            $pdf = PDF::loadView('ReportCard.ViewReportTwo.pdfFinal', compact('exam_id','batch_config_id','student_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        } else {
            $pdf = PDF::loadView('ReportCard.ViewReportTwo.pdf', compact('exam_id','batch_config_id','student_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        }
        $pdf->showImageErrors = true;
        
        $student_table = StudentTable::where('student_table_id',$student_id)->first();
        $student_name_with_space = $student_table->first_name.$student_table->last_name;
        $student_name_with_dots = str_replace(" ","",$student_name_with_space);
        $student_name = str_replace(".","",$student_name_with_dots);
        $batch_config_with_space = $student_table->classId->class_name.$student_table->sectionId->section_name;
        $batch_config = str_replace(" ","",$batch_config_with_space);
        $exam_list = RcExamList::where('id', $exam_id)->first();
        $exam_name = str_replace(" ","",$exam_list->exam_name);
        $pdf_name = $student_name."_".$exam_name."_".$batch_config.".pdf";
        return $pdf->stream($pdf_name);
    }

    public function multiPdf(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
        $pdf = PDF::loadView('ReportCard.ViewReportTwo.multipdf', compact('exam_id','batch_config_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        return $pdf->stream('report_card.pdf');
    }
	
	public function pdfEmpty(Request $request)
    {
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
        if($exam_id == 6 && $is_final_report == 1) {
            $pdf = PDF::loadView('ReportCard.ViewReportTwo.pdfFinalEmpty', compact('exam_id','batch_config_id','student_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        } else {
            $pdf = PDF::loadView('ReportCard.ViewReportTwo.pdfEmpty', compact('exam_id','batch_config_id','student_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        }
        $student_table = StudentTable::where('student_table_id',$student_id)->first();
        $student_name_with_space = $student_table->first_name.$student_table->last_name;
        $student_name_with_dots = str_replace(" ","",$student_name_with_space);
        $student_name = str_replace(".","",$student_name_with_dots);
        $batch_config_with_space = $student_table->classId->class_name.$student_table->sectionId->section_name;
        $batch_config = str_replace(" ","",$batch_config_with_space);
        $exam_list = RcExamList::where('id', $exam_id)->first();
        $exam_name = str_replace(" ","",$exam_list->exam_name);
        $pdf_name = $student_name."_".$exam_name."_".$batch_config.".pdf";
        return $pdf->stream($pdf_name);
    }

    public function multiPdfEmpty(Request $request)
    {
        // dd($request);exit();
        $exam_id = $request->exam_id;
        $batch_config_id = $request->batch_config_id;
        $student_id = $request->student_id;
		$is_final_report = $request->is_final_report;
		if($exam_id == 6 && $is_final_report == 1) {
            $pdf = PDF::loadView('ReportCard.ViewReportTwo.multipdfFinalEmpty', compact('exam_id','batch_config_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        } else {
			$pdf = PDF::loadView('ReportCard.ViewReportTwo.multipdfEmpty', compact('exam_id','batch_config_id','is_final_report'),[],['margin_top' => 2,'margin_left' => 1,'margin_right' => 1,'margin_bottom' =>2]);
        }
		
		return $pdf->stream('report_card.pdf');
    }
}
