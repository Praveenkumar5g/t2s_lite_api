<?php

use App\Http\Controllers\WebUserManagementController;
use App\Http\Controllers\WebSalaryReceiptController;
use App\Http\Controllers\WebLoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [WebLoginController::class, 'index']); //redirect to login
Route::post('login', [WebLoginController::class, 'login']);
Route::get('apptoweblogin', [WebLoginController::class, 'apptoweblogin']);

Route::group(['middleware' => 'auth.webcheck'], function ($router) {
    Route::get('employee/salarydetails', [WebSalaryReceiptController::class, 'index']);
    Route::get('employee/uploadsalarydetails', [WebSalaryReceiptController::class, 'upload']);
    Route::post('employee/salaryUpload', [WebSalaryReceiptController::class, 'salaryUpload']);
    Route::post('employee/getSalary_list', [WebSalaryReceiptController::class, 'getSalary_list']);
    Route::get('/employee/download_individual_payslip',[WebSalaryReceiptController::class,'download_individual_payslip']);
    Route::get('/employee/download_report',[WebSalaryReceiptController::class,'download_report']);
    Route::get('employee/sample_excel',[WebSalaryReceiptController::class,'sample_excel']);
    Route::get('logout', [WebLoginController::class, 'logout']);

    /*user Management -- starts*/
    Route::get('/usermanagement/students',[WebUserManagementController::class,'students']);
    Route::post('/usermanagement/getStudent_list',[WebUserManagementController::class,'getStudent_list']);
    Route::get('/usermanagement/addStudents',[WebUserManagementController::class,'addStudents']);
    Route::post('/usermanagement/storeStudents',[WebUserManagementController::class,'storeStudents']);
    Route::get('/usermanagement/editStudent',[WebUserManagementController::class,'editStudent']);
    Route::post('/usermanagement/updateStudent',[WebUserManagementController::class,'updateStudent']);
    Route::post('/usermanagement/checkMobileno',[WebUserManagementController::class,'checkMobileno']);
    Route::post('/usermanagement/checkAdmissionno',[WebUserManagementController::class,'checkAdmissionno']);
    Route::get('/changeDobformat',[WebUserManagementController::Class,'changeDobformat']);
    Route::post('/usermanagement/checkMobilenoExists',[WebUserManagementController::class,'checkMobilenoExists']);
    Route::get('/usermanagement/studentunmapwithparent',[WebUserManagementController::class,'studentunmapwithparent']);
    Route::get('/usermanagement/parents',[WebUserManagementController::class,'parents']);
    Route::post('/usermanagement/getParent_list',[WebUserManagementController::class,'getParent_list']);
    Route::get('/usermanagement/unmappedstudents',[WebUserManagementController::class,'unmappedstudents']);
    Route::post('/usermanagement/getunmapStudent_list',[WebUserManagementController::class,'getunmapStudent_list']);
    Route::get('/usermanagement/welcome_message',[WebUserManagementController::class,'welcome_message']);
    Route::post('usermanagement/send_welcome_message',[WebUserManagementController::class,'send_welcome_message']);
    Route::post('usermanagement/getstudents',[WebUserManagementController::class,'getstudents']);
    Route::get('/usermanagement/smslog',[WebUserManagementController::class,'smslog']);

    /*Staff*/
    Route::get('/usermanagement/staffs',[WebUserManagementController::class,'staffs']);
    Route::post('/usermanagement/getStaff_list',[WebUserManagementController::class,'getStaff_list']);
    Route::get('/usermanagement/addStaff',[WebUserManagementController::class,'addStaff']);
    Route::get('/usermanagement/subject_classes',[WebUserManagementController::class,'subject_classes']);
    Route::post('/usermanagement/checkEmployeeno',[WebUserManagementController::class,'checkEmployeeno']);
    Route::post('/usermanagement/storeStaff',[WebUserManagementController::class,'storeStaff']);
    Route::post('/usermanagement/checkStaffMobilenoexists',[WebUserManagementController::class,'checkStaffMobilenoexists']);
    Route::get('/usermanagement/editStaffdetails',[WebUserManagementController::class,'editStaffdetails']);
    Route::post('/usermanagement/updateStaff',[WebUserManagementController::class,'updateStaff']);
    Route::post('/usermanagement/checkClassteacherexists',[WebUserManagementController::class,'checkClassteacherexists']);
    Route::post('/usermanagement/checkuseraccountdetails',[WebUserManagementController::class,'checkuseraccountdetails']);
    Route::post('/usermanagement/checksubjectaccess',[WebUserManagementController::class,'checksubjectaccess']);
    /*Staff Ends*/
    /*user management -- Ends*/
});