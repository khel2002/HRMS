<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminEmployeesController;
use App\Http\Controllers\admin\EmployeesRegistrationController;
use App\Http\Controllers\admin\PsgcController;
use App\Http\Controllers\LeaveApplicationController;

Route::prefix('admin')->group(function () {

  // ── Employees list + status update ────────────────────────
  Route::get('/employees',                  [AdminEmployeesController::class, 'index'])->name('employees-index');
  Route::patch('/employees/{id}/status',    [AdminEmployeesController::class, 'updateStatus'])->name('employee-status');

  // ── Registration (MUST come before /{id} wildcard routes) ─
  Route::get('/employees/registration',     [EmployeesRegistrationController::class, 'index'])->name('employee-registration');
  Route::post('/employees',                 [EmployeesRegistrationController::class, 'store'])->name('employee-store');

  // ── Specific employee routes ───────────────────────────────
  Route::get('/employees/{id}/edit',        [EmployeesRegistrationController::class, 'edit'])->name('employee-edit');
  Route::put('/employees/{id}',          [EmployeesRegistrationController::class, 'update'])->name('employee-update');
  Route::get('/employees/{id}',             [EmployeesRegistrationController::class, 'show'])->name('employee-show');
  Route::delete('/employees/{id}',          [EmployeesRegistrationController::class, 'destroy'])->name('employee-destroy');

  // ── PSGC address cascade proxy ─────────────────────────────
  Route::prefix('psgc')->group(function () {
    Route::get('/regions',                  [PsgcController::class, 'regions']);
    Route::get('/regions/{code}/provinces', [PsgcController::class, 'provinces']);
    Route::get('/provinces/{code}/cities',  [PsgcController::class, 'cities']);
    Route::get('/cities/{code}/barangays',  [PsgcController::class, 'barangays']);
  });
});

Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('leave-application-form');
