<?php

use App\Http\Controllers\admin\AccountManagementController;
use App\Http\Controllers\admin\AdminEmployeesController;
use App\Http\Controllers\admin\EmployeesRegistrationController;
use App\Http\Controllers\admin\PsgcController;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveSummaryController;
use App\Models\LogImage;
use App\Models\UserLogs;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// Main Page Route

Route::get('/encryption-test',function (){

 dd(Hash::make('hrs123'));
});

Route::get('/', [LoginBasic::class, 'index'])->name('home');
Route::post('/auth/login',[LoginBasic::class,'authLogin'])->name('auth.login');
Route::get('/face-recognition-login', [LoginBasic::class, 'faceRecognitionLogin'])->name('face-recognition-login');
Route::get('/employees/get-enrolled-descriptors', [LoginBasic::class, 'getEnrolledDescriptors'])->name('employees.enrolled.descriptors');
Route::post('/employees/attendance/log-book', [LoginBasic::class, 'storeLog'])->name('employees.attendance.log-book');
Route::get('/employees/user-logs', [LoginBasic::class, 'userLogs'])->name('employees.user-logs');



// Employee Management
Route::prefix('admin')->group(function () {


    Route::get('/dashboard',[AdminEmployeesController::class,'dashboard'])->name('dashboard');
    Route::get('/logout',[LoginBasic::class,'logout'])->name('logout');

  Route::prefix('employees')->group(function () {


    Route::get('/', [AdminEmployeesController::class, 'index'])->name('employees-index');
    Route::patch('/{id}/stat', [AdminEmployeesController::class, 'updateStatus'])->name('employee-status');

    // ── Registration (MUST come before /{id} wildcard routes) ─
    Route::get('/registration', [EmployeesRegistrationController::class, 'index'])->name('employee-registration');
    Route::get('/facial-recognition/registration', [EmployeesRegistrationController::class, 'facialRecognitionRegistration'])->name('employee-facial-registration');
    Route::post('/facial-recognition/save', [EmployeesRegistrationController::class, 'facialRecognitionSave'])->name('employee-facial-registration-save');
    Route::post('/', [EmployeesRegistrationController::class, 'store'])->name('employee-store');

    // ── Specific employee routes ───────────────────────────────
    Route::get('/{id}/edit', [EmployeesRegistrationController::class, 'edit'])->name('employee-edit');
    Route::put('/{id}', [EmployeesRegistrationController::class, 'update'])->name('employee-update');
    Route::get('/{id}', [EmployeesRegistrationController::class, 'show'])->name('employee-show');
    Route::delete('/{id}', [EmployeesRegistrationController::class, 'destroy'])->name('employee-destroy');
  });
  Route::prefix('account-management')->group(function () {
    Route::get('/',           [AccountManagementController::class, 'index'])->name('account-management.index');
    Route::post('/',          [AccountManagementController::class, 'store'])->name('account-management.store');
    Route::get('/{user}',    [AccountManagementController::class, 'show'])->name('account-management.show');
    Route::put('/{user}',    [AccountManagementController::class, 'update'])->name('account-management.update');
    Route::patch('/{user}/change-status', [AccountManagementController::class, 'changeStatus'])->name('account-management.change-status');
    Route::delete('/{user}', [AccountManagementController::class, 'destroy'])->name('account-management.destroy');
  });
  // ── PSGC address cascade proxy ─────────────────────────────
  Route::prefix('psgc')->group(function () {
    Route::get('/regions', [PsgcController::class, 'regions']);
    Route::get('/regions/{code}/provinces', [PsgcController::class, 'provinces']);
    Route::get('/provinces/{code}/cities', [PsgcController::class, 'cities']);
    Route::get('/cities/{code}/barangays', [PsgcController::class, 'barangays']);
  });

  // ── Leave Management ───────────────────────────────────────
  Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('leave-application-form');
  Route::post('/leave-application', [LeaveApplicationController::class, 'store'])->name('leave-application-store');
  Route::get('/leave-summary', [LeaveSummaryController::class, 'index'])->name('leave-summary');
  Route::get('/leave-pdf', [LeaveSummaryController::class, 'generate']);

  // ── API Routes ─────────────────────────────────────────────
  Route::prefix('api')->group(function () {

    // employee lookup for leave form
    Route::get('/employees/search', [EmployeesRegistrationController::class, 'search'])->name('api.employees.search');

    // leave balances for a specific employee (used by the leave application form)
    Route::get('/employees/{id}/leave-balances', [LeaveApplicationController::class, 'balances'])->name('api.employees.leave-balances');

    // Leave requests
    Route::get('/leave-requests', [LeaveSummaryController::class, 'list'])->name('api.leave-requests.list');

    // Specific sub-routes MUST come before the /{id} wildcard
    Route::patch('/leave-requests/{id}/remark', [LeaveSummaryController::class, 'setRemark'])->name('api.leave-requests.remark');

    // PDF download — must be before /{id} wildcard
    Route::get('/leave-requests/{id}/pdf', [LeaveSummaryController::class, 'generatePdf'])->name('api.leave-requests.pdf');

    Route::get('/leave-requests/{id}', [LeaveSummaryController::class, 'show'])->name('api.leave-requests.show');

    Route::delete('/leave-requests/{id}', [LeaveSummaryController::class, 'destroy'])->name('api.leave-requests.destroy');
  });
});
