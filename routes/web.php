<?php

use Illuminate\Support\Facades\Route;

// admins
use App\Http\Controllers\admin\AdminEmployeesController;
use App\Http\Controllers\admin\EmployeesRegistrationController;
use App\Http\Controllers\admin\PsgcController;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveSummaryController;

// Main Page Route
Route::get('/', [LoginBasic::class, 'index'])->name('home');
Route::get('/face-recognition-login', [LoginBasic::class, 'faceRecognitionLogin'])->name('face-recognition-login');
Route::get('/employees/get-enrolled-descriptors', [LoginBasic::class, 'getEnrolledDescriptors'])->name('employees.enrolled.descriptors');

// Employee Management
Route::prefix('admin')->group(function () {

  // ── Employees list + status update ────────────────────────
  Route::get('/employees', [AdminEmployeesController::class, 'index'])->name('employees-index');
  Route::patch('/employees/{id}/stat', [AdminEmployeesController::class, 'updateStatus'])->name('employee-status');

  // ── Registration (MUST come before /{id} wildcard routes) ─
  Route::get('/employees/registration', [EmployeesRegistrationController::class, 'index'])->name('employee-registration');
  Route::get('/employees/facial-recognition/registration', [EmployeesRegistrationController::class, 'facialRecognitionRegistration'])->name('employee-facial-registration');
  Route::post('/employees/facial-recognition/save', [EmployeesRegistrationController::class, 'facialRecognitionSave'])->name('employee-facial-registration-save');
  Route::post('/employees', [EmployeesRegistrationController::class, 'store'])->name('employee-store');

  // ── Specific employee routes ───────────────────────────────
  Route::get('/employees/{id}/edit', [EmployeesRegistrationController::class, 'edit'])->name('employee-edit');
  Route::put('/employees/{id}', [EmployeesRegistrationController::class, 'update'])->name('employee-update');
  Route::get('/employees/{id}', [EmployeesRegistrationController::class, 'show'])->name('employee-show');
  Route::delete('/employees/{id}', [EmployeesRegistrationController::class, 'destroy'])->name('employee-destroy');

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

  // ── API Routes ─────────────────────────────────────────────
  Route::prefix('api')->group(function () {

    // employee lookup for leave form
    Route::get('/employees/search', [EmployeesRegistrationController::class, 'search'])
      ->name('api.employees.search');

    // leave request list
    Route::get('/leave-requests', [LeaveSummaryController::class, 'list'])
      ->name('api.leave-requests.list');

    // leave request details
    Route::get('/leave-requests/{id}', [LeaveSummaryController::class, 'show'])
      ->name('api.leave-requests.show');

    // delete leave request
    Route::delete('/leave-requests/{id}', [LeaveSummaryController::class, 'destroy'])
      ->name('api.leave-requests.destroy');
  });
});
