<?php

use Illuminate\Support\Facades\Route;
// admins
use App\Http\Controllers\admin\AdminEmployeesController;
use App\Http\Controllers\admin\EmployeesRegistrationController;
use App\Http\Controllers\admin\PsgcController;
use App\Http\Controllers\LeaveApplicationController;

// Main Page Route


//employee management

Route::prefix('admin')->group(function () {
  Route::get('/employees', [AdminEmployeesController::class, 'index'])->name('employees-index');
  Route::patch('/employees/{id}/status', [AdminEmployeesController::class, 'updateStatus'])->name('employee-status');

  // Registration route
  Route::get('/employees/registration', [EmployeesRegistrationController::class, 'index'])->name('employee-registration');
  Route::post('/employees', [EmployeesRegistrationController::class, 'store'])->name('employee-store');
  Route::get('/employees/{id}', [EmployeesRegistrationController::class, 'show'])->name('employee-show');
  Route::get('/employees/{id}/edit', [EmployeesRegistrationController::class, 'show'])->name('employee-edit');


  Route::prefix('psgc')->group(function () {
    Route::get('/regions',                        [PsgcController::class, 'regions']);
    Route::get('/regions/{code}/provinces',       [PsgcController::class, 'provinces']);
    Route::get('/provinces/{code}/cities',        [PsgcController::class, 'cities']);
    Route::get('/cities/{code}/barangays',        [PsgcController::class, 'barangays']);
  });
});


//Application for leave

Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('leave-application-form');
