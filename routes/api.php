<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIConfigurationsController;
use App\Http\Controllers\APICommunicationController;
use App\Http\Controllers\APIRegistrationController;
use App\Http\Controllers\APINewsEventsController;
use App\Http\Controllers\APIHomeworkController;
use App\Http\Controllers\APILoginController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\PayfeesController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Clear application cache:
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return 'Application cache has been cleared';
});

//Clear route cache:
Route::get('/route-cache', function() {
    Artisan::call('route:cache');
    return 'Routes cache has been cleared';
});

//Clear config cache:
Route::get('/config-cache', function() {
    Artisan::call('config:cache');
    return 'Config cache has been cleared';
}); 

// Clear view cache:
Route::get('/view-clear', function() {
    Artisan::call('view:clear');
    return 'View cache has been cleared';
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/demo', function () {
    dd(1);
});

Route::get('/user/welcome', [WelcomeController::class, 'welcome']);
Route::post('/user/register' , [APIRegistrationController::class,'register']);

Route::post('/user/login' , [APILoginController::class,'login']);
Route::post('/user/otplogin',[APILoginController::class,'OTPlogin']);
Route::post('/user/get_school',[APILoginController::class,'get_school']);//get school for single management user

Route::post('/user/forgot_password' , [APILoginController::class,'forgot_password']);
Route::post('/user/resetPassword' , [APILoginController::class,'resetPassword']);
Route::group(['middleware' => 'auth.check','prefix' => 'user'], function ($router) {
    Route::post('/change_password', [APILoginController::class, 'change_password']); 
    Route::get('/create_profile', [APIRegistrationController::class, 'create_profile']); //Create DB for each school
    Route::get('/get_siblings_details',[APILoginController::class,'get_siblings_details']);
});

Route::group(['middleware' => 'auth.connect','prefix' => 'user'], function ($router) {
    Route::post('/onesignal_store_device_details',[APIConfigurationsController::class,'onesignal_store_device_details']);//fetch onesignal details
    Route::post('/change_mobile_number',[APIConfigurationsController::class,'change_mobile_number']);//change mobile number for parent
    Route::post('/send_welcome_message',[APIConfigurationsController::class,'send_welcome_message']);//send welcome message to all users.
    Route::get('/configuration_list',[APIConfigurationsController::class,'configuration_list']); //list configuration to check what are the configuration steps completed along with sample.
    Route::get('/configuration_tags',[APIConfigurationsController::class,'configuration_tags']);//list configuration to check what are the configuration steps completed
    Route::post('/import_configuration',[APIConfigurationsController::class,'import_configuration']);//import manual upload data into DB.
    Route::post('/upload_configuration',[APIConfigurationsController::class,'upload_configuration']);//import configuration data through excel.
    Route::post('/user_status_change',[APILoginController::class,'user_status_change']);//change the user status
    
    Route::get('/send_not_installed_user_welcome_message',[APICommunicationController::class,'send_not_installed_user_welcome_message']);//resend the welcome messaage to not installed parents
    Route::post('/reset_send_sms',[APICommunicationController::class,'reset_send_sms']);//Reset and send sms
    Route::post('/importdob',[APIConfigurationsController::class,'importdob']);
    Route::get('/users_count',[APIConfigurationsController::class,'users_count']); // users count list
    
    Route::get('/get_classes_sections_subjects_list',[APIConfigurationsController::class,'get_classes_sections_subjects_list']);
    Route::get('/get_staff_category_class',[APIConfigurationsController::class,'get_staff_category_class']);
    Route::get('/get_management_designation',[APIConfigurationsController::class,'get_management_designation']);
    Route::post('/get_classes_list',[APIConfigurationsController::class,'get_edit_classes_list']);
    Route::post('/get_edit_allsection_list',[APIConfigurationsController::class,'get_edit_allsection_list']);
    Route::get('/get_edit_staff_list',[APIConfigurationsController::class,'get_edit_staff_list']);
    Route::get('/get_edit_management_list',[APIConfigurationsController::class,'get_edit_management_list']);
    Route::get('/get_edit_student_list',[APIConfigurationsController::class,'get_edit_student_list']);
    
    Route::get('/get_sections_list',[APIConfigurationsController::class,'get_edit_sections_list']);
    Route::get('/get_edit_sections_list',[APIConfigurationsController::class,'get_edit_sections_list']);
    Route::get('/get_divisions',[APIConfigurationsController::class,'get_divisions']);
    Route::get('/class_review',[APIConfigurationsController::class,'class_review']);
    Route::post('/class_section_review',[APIConfigurationsController::class,'class_section_review']);
    Route::post('/get_class_section',[APIConfigurationsController::class,'get_class_section']);
    Route::post('/get_combine_class_section_list',[APIConfigurationsController::class,'get_combine_class_section_list']);
    Route::post('/get_allsubjects_list',[APIConfigurationsController::class,'get_allsubjects_list']);
    Route::post('/get_subjects',[APIConfigurationsController::class,'get_subjects']);
    Route::post('/get_staff_details',[APIConfigurationsController::class,'get_staff_details']);

    Route::get('/get_edit_classes_sections',[APIConfigurationsController::class,'get_edit_classes_sections']);
    Route::post('/get_edit_subjects',[APIConfigurationsController::class,'get_edit_subjects']);
    Route::post('/all_staff_list',[APIConfigurationsController::class,'all_staff_list']);
    Route::post('/all_parent_list',[APIConfigurationsController::class,'all_parent_list']);
    Route::post('/all_student_list',[APIConfigurationsController::class,'all_student_list']);
    Route::post('/all_admin_list',[APIConfigurationsController::class,'all_admin_list']);
    Route::post('/all_management_list',[APIConfigurationsController::class,'all_management_list']);
    Route::post('/class_subjects_list',[APIConfigurationsController::class,'class_subjects_list']);
    Route::get('/onboarding_staff_list',[APIConfigurationsController::class,'onboarding_staff_list']);
    Route::post('/onboarding_fetch_single_staff',[APIConfigurationsController::class,'onboarding_fetch_single_staff']);
    Route::post('/onboarding_edit_staff',[APIConfigurationsController::class,'onboarding_edit_staff']);
    Route::post('/onboarding_fetch_single_management',[APIConfigurationsController::class,'onboarding_fetch_single_management']);
    Route::post('/onboarding_edit_management',[APIConfigurationsController::class,'onboarding_edit_management']);
    Route::post('/onboarding_fetch_single_parent',[APIConfigurationsController::class,'onboarding_fetch_single_parent']);
    Route::get('/onboarding_parent_list',[APIConfigurationsController::class,'onboarding_parent_list']);
    Route::post('/onboarding_edit_parent',[APIConfigurationsController::class,'onboarding_edit_parent']);
    Route::post('/check_admission_unique',[APIConfigurationsController::class,'check_admission_unique']);
    /*Delete Configuration*/
    Route::post('/delete_division',[APIConfigurationsController::class,'delete_division']);
    Route::post('/delete_class',[APIConfigurationsController::class,'delete_class']);
    Route::post('/delete_section',[APIConfigurationsController::class,'delete_section']);
    Route::post('/delete_class_section',[APIConfigurationsController::class,'delete_class_section']);
    Route::post('/onboarding_delete_subject',[APIConfigurationsController::class,'onboarding_delete_subject']);
    Route::post('/onboarding_delete_staff',[APIConfigurationsController::class,'onboarding_delete_staff']);
    Route::post('/onboarding_delete_management',[APIConfigurationsController::class,'onboarding_delete_management']);
     Route::post('/onboarding_delete_parent',[APIConfigurationsController::class,'onboarding_delete_parent']);
    /*Delete Configuration*/

    Route::get('/activate_default_groups',[APIConfigurationsController::class,'activate_default_groups']);
    Route::get('/user_group_list',[APIConfigurationsController::class,'user_group_list']);
    Route::get('/classes_group',[APIConfigurationsController::class,'classes_group']);
    Route::post('/staff_as_parent',[APIConfigurationsController::class,'staff_as_parent']);//Staff as parent
    Route::get('/view_staff_as_parent',[APIConfigurationsController::class,'view_staff_as_parent']);//View Staff as parent
    
    Route::post('/approval_process', [APICommunicationController::class, 'approval_process']); 
    Route::post('/store_message', [APICommunicationController::class, 'store_message']); 
    Route::post('/message_visible_count', [APICommunicationController::class, 'message_visible_count']);
    Route::post('/view_messages',[APICommunicationController::class,'view_messages']); 
    Route::post('/delete_messages',[APICommunicationController::class,'delete_messages']);
    Route::post('/message_approval',[APICommunicationController::class,'message_approval']);
    Route::post('/message_read',[APICommunicationController::class,'message_read']);
    Route::post('/message_info',[APICommunicationController::class,'message_info']);
    Route::post('/message_delivery_details',[APICommunicationController::class,'message_delivery_details']);
    Route::post('/save_profile',[APICommunicationController::class,'save_profile']);
    Route::post('/view_profile',[APICommunicationController::class,'view_profile']);
    Route::get('/approval_action_required',[APICommunicationController::class,'approval_action_required']);
    Route::post('/group_participants',[APICommunicationController::class,'group_participants']);
    Route::post('/image_list',[APICommunicationController::class,'image_list']);
    Route::get('/get_class_list',[APICommunicationController::class,'get_class_list']);

    /*Birthday Alert*/
    Route::post('/birthday_student_list',[APICommunicationController::class,'birthday_student_list']);
    Route::post('store_birthday_message',[APICommunicationController::class,'store_birthday_message']);
    /*Birthday Alert*/
    
    Route::post('/homework',[APIHomeworkController::class,'homework']);
    Route::post('/store_homework', [APIHomeworkController::class, 'store_homework']); 
    Route::post('/homework_approval',[APIHomeworkController::class,'homework_approval']);
    Route::post('/update_homework_status',[APIHomeworkController::class,'update_homework_status']);
    Route::post('/homework_details_report',[APIHomeworkController::class,'homework_details_report']);
    Route::post('/delete_homework_attachment',[APIHomeworkController::class,'delete_homework_attachment']);
    Route::post('/list_homework_status',[APIHomeworkController::class,'list_homework_status']);
    Route::post('/get_group_students',[APICommunicationController::class,'get_group_students']);
    /*News and Events -- Start*/
    Route::post('/store_news_events',[APINewsEventsController::class,'store_news_events']);
    Route::get('/mainscreen_view_newsevents',[APINewsEventsController::class,'mainscreen_view_newsevents']);
    Route::get('/view_all_images',[APINewsEventsController::class,'view_all_images']);
    Route::post('/publish_news_events',[APINewsEventsController::class,'publish_news_events']);
    Route::post('/delete_news_events',[APINewsEventsController::class,'delete_news_events']);
    Route::post('/view_individual_news_events',[APINewsEventsController::class,'view_individual_news_events']);
    Route::get('/mainscreen_view_allevents',[APINewsEventsController::class,'mainscreen_view_allevents']);
    Route::post('/event_accept_decline',[APINewsEventsController::class,'event_accept_decline']);
    Route::post('/store_liked_news',[APINewsEventsController::class,'store_liked_news']);//store liked data in db
    Route::post('/delete_attachements',[APINewsEventsController::class,'delete_attachements']);//delete attachments
    /*News and Events -- Ends*/

    /*Payfees -- starts*/
    Route::get('/feesStructure',[PayfeesController::class,'feesStructure']);
    Route::get('/studentFees', [PayfeesController::class,'studentFees']);
    Route::get('/academicYear', [PayfeesController::class,'academicYear']);
    Route::get('/paymentHistory', [PayfeesController::class,'paymentHistory']);
    /*Payfees - Ends*/
});
