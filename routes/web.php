<?php

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
});