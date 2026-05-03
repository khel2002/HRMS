<?php

use App\Http\Controllers\admin\AccountManagementController;
use App\Http\Controllers\admin\AdminEmployeesController;
use App\Http\Controllers\admin\AttendanceReportController;
use App\Http\Controllers\admin\EmployeesRegistrationController;
use App\Http\Controllers\admin\SalarySettingsController;
use App\Http\Controllers\admin\PaySlipController;
use App\Http\Controllers\admin\PsgcController;
use App\Http\Controllers\admin\SeminarAttendedController;
use App\Http\Controllers\admin\ServiceRecordController;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\employee\DtrController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveSummaryController;
use App\Models\LogImage;
use App\Models\UserLogs;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// Main Page Route

// Laravel's `auth` middleware redirects unauthenticated/expired sessions to a route named `login`.
// This project uses a custom login page, so we provide the expected route name.
Route::get('/login', [LoginBasic::class, 'index'])->name('login');

Route::get('/', [LoginBasic::class, 'index'])->name('home');
Route::post('/auth/login', [LoginBasic::class, 'authLogin'])->name('auth.login');
Route::get('/face-recognition-login', [LoginBasic::class, 'faceRecognitionLogin'])->name('face-recognition-login');
Route::get('/employees/get-enrolled-descriptors', [LoginBasic::class, 'getEnrolledDescriptors'])->name('employees.enrolled.descriptors');
Route::post('/employees/attendance/log-book', [LoginBasic::class, 'storeLog'])->name('employees.attendance.log-book');
Route::get('/employees/user-logs', [LoginBasic::class, 'userLogs'])->name('employees.user-logs');

// Shared API routes used by back-office (Admin/HR) pages.
// Kept outside the `/admin` prefix so the frontend can call a stable `/api/...` base.
Route::prefix('api')->middleware(['auth'])->group(function () {
  // Admin/HR-only endpoints
  Route::middleware(['role:Admin,HR'])->group(function () {
    Route::get('/employees/search', [EmployeesRegistrationController::class, 'search']);

    Route::get('/leave-requests', [LeaveSummaryController::class, 'list']);
    Route::patch('/leave-requests/{id}/remark', [LeaveSummaryController::class, 'setRemark']);
    Route::get('/leave-requests/{id}/pdf', [LeaveSummaryController::class, 'generatePdf']);
    Route::get('/leave-requests/{id}', [LeaveSummaryController::class, 'show']);
    Route::delete('/leave-requests/{id}', [LeaveSummaryController::class, 'destroy']);
  });

  // Leave balances: Admin/HR can view any employee; Employee can view own via controller checks.
  Route::get('/employees/{id}/leave-balances', [LeaveApplicationController::class, 'balances']);
});

// HR routes
Route::prefix('HR')->middleware(['auth', 'role:HR'])->group(function () {
  Route::get('/dashboard', [LoginBasic::class, 'HRDashboard'])->name('HR.Dashboard');

  Route::get('/employees', [AdminEmployeesController::class, 'index'])->name('hr.employees.index');
  Route::patch('/employees/{id}/stat', [AdminEmployeesController::class, 'updateStatus'])->name('hr.employee-status');

  Route::get('/employees/registration', [EmployeesRegistrationController::class, 'index'])->name('hr.employee-registration');
  Route::get('/employees/facial-recognition/registration', [EmployeesRegistrationController::class, 'facialRecognitionRegistration'])->name('hr.employee-facial-registration');
  Route::post('/employees/facial-recognition/save', [EmployeesRegistrationController::class, 'facialRecognitionSave'])->name('hr.employee-facial-registration-save');
  Route::post('/employees', [EmployeesRegistrationController::class, 'store'])->name('hr.employee-store');

  Route::get('/employees/{id}/edit', [EmployeesRegistrationController::class, 'edit'])->name('hr.employee-edit');
  Route::put('/employees/{id}', [EmployeesRegistrationController::class, 'update'])->name('hr.employee-update');
  Route::get('/employees/{id}', [EmployeesRegistrationController::class, 'show'])->name('hr.employee-show');
  Route::delete('/employees/{id}', [EmployeesRegistrationController::class, 'destroy'])->name('hr.employee-destroy');

  Route::prefix('attendance-report')->group(function () {
    Route::get('/', [AttendanceReportController::class, 'index'])->name('hr.attendance-report');
    Route::get('/pdf',   [AttendanceReportController::class, 'exportPdf'])->name('hr.attendance-report.pdf');
  });

  Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('hr.leave-application-form');
  Route::post('/leave-application', [LeaveApplicationController::class, 'store'])->name('hr.leave-application-store');
  Route::get('/leave-summary', [LeaveSummaryController::class, 'index'])->name('hr.leave-summary');

  // Payslip (HR)
  Route::prefix('payslip')->group(function () {
    Route::get('/', [PaySlipController::class, 'index'])->name('hr.payslip.index');
    Route::post('/process', [PaySlipController::class, 'process'])->name('hr.payslip.process');
    Route::get('/pdf', [PaySlipController::class, 'pdf'])->name('hr.payslip.pdf');
    Route::post('/email', [PaySlipController::class, 'email'])->name('hr.payslip.email');
  });
});

// Employee Routes
Route::prefix('employees')->middleware(['auth', 'role:Employee'])->group(function () {


  Route::get('/change-password', [LoginBasic::class, 'changePassword'])->name('password.change');
  Route::put('/change-password-active', [LoginBasic::class, 'updatePassword'])->name('password.update.active');
  Route::get('/dashboard', [LoginBasic::class, 'employeeDashboard'])->name('employees.dashboard');


  Route::get('/face-recognition-login', [LoginBasic::class, 'faceRecognitionLogin'])->name('employees.face-recognition-login');


  Route::get('/service-records', [ServiceRecordController::class, 'employeeIndex'])
    ->name('employee.service-record.index');
  Route::post('/service-records/store', [ServiceRecordController::class, 'store'])
    ->name('employee.service-record.store');
  Route::put('/service-records/{id}', [ServiceRecordController::class, 'update'])
    ->name('employee.service-record.update');
  Route::delete('/service-records/{id}', [ServiceRecordController::class, 'destroy'])
    ->name('employee.service-record.destroy');



  // Seminars for Emloyee
  Route::get('/my-seminars', [SeminarAttendedController::class, 'employeeSeminars'])->name('employee.my-seminars');

  Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('employees.leave-application');
  Route::post('/leave-application', [LeaveApplicationController::class, 'store'])->name('employees.leave-application.store');

  // Payslip (Employee self-service)
  Route::prefix('payslip')->group(function () {
    Route::get('/', [PaySlipController::class, 'index'])->name('employees.payslip.index');
    Route::post('/process', [PaySlipController::class, 'process'])->name('employees.payslip.process');
    Route::get('/pdf', [PaySlipController::class, 'pdf'])->name('employees.payslip.pdf');
    Route::post('/email', [PaySlipController::class, 'email'])->name('employees.payslip.email');
  });
});

//Admin Routes
Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {


  Route::get('/dashboard', [AdminEmployeesController::class, 'dashboard'])->name('dashboard');
  // Route::get('/logout', [LoginBasic::class, 'logout'])->name('logout');

  // Specific for admin (employees registration)
  Route::prefix('employees')->group(function () {

    Route::get('/', [AdminEmployeesController::class, 'index'])->name('employees-index');
    Route::patch('/{id}/stat', [AdminEmployeesController::class, 'updateStatus'])->name('employee-status');

    Route::get('/registration', [EmployeesRegistrationController::class, 'index'])->name('employee-registration');
    Route::get('/facial-recognition/registration', [EmployeesRegistrationController::class, 'facialRecognitionRegistration'])->name('employee-facial-registration');
    Route::post('/facial-recognition/save', [EmployeesRegistrationController::class, 'facialRecognitionSave'])->name('employee-facial-registration-save');
    Route::post('/', [EmployeesRegistrationController::class, 'store'])->name('employee-store');

    Route::get('/{id}/edit', [EmployeesRegistrationController::class, 'edit'])->name('employee-edit');
    Route::put('/{id}', [EmployeesRegistrationController::class, 'update'])->name('employee-update');
    Route::get('/{id}', [EmployeesRegistrationController::class, 'show'])->name('employee-show');
    Route::delete('/{id}', [EmployeesRegistrationController::class, 'destroy'])->name('employee-destroy');
  });

  // ── Payrolls (Admin only) ───────────────────────────────────────
  Route::prefix('payroll')->group(function () {
    Route::get('/salary-settings', [SalarySettingsController::class, 'index'])->name('salary-settings.index');
    Route::get('/salary-settings/{employee}', [SalarySettingsController::class, 'show'])->name('salary-settings.show');
    Route::post('/salary-settings', [SalarySettingsController::class, 'store'])->name('salary-settings.store');
    Route::put('/salary-settings/{employee}', [SalarySettingsController::class, 'update'])->name('salary-settings.update');
    Route::delete('/salary-settings/recurring-items/{recurringItem}', [SalarySettingsController::class, 'destroyRecurringItem'])
      ->name('salary-settings.recurring-items.destroy');
  });

  Route::prefix('payslip')->group(function () {
    Route::get('/payslip-form', [PaySlipController::class, 'index'])->name('payslip.index');
    Route::post('/process', [PaySlipController::class, 'process'])->name('payslip.process');
    Route::get('/pdf', [PaySlipController::class, 'pdf'])->name('payslip.pdf');
    Route::post('/email', [PaySlipController::class, 'email'])->name('payslip.email');
  });


  // ── Service Records By Coldzy ───────────────────────────────────────
  Route::get('/service-record', [ServiceRecordController::class, 'records'])->name('service-record');
  Route::post('/service-record', [ServiceRecordController::class, 'store'])->name('service-record.store');
  Route::post('/service-record/{id}/approve', [ServiceRecordController::class, 'approve'])
    ->name('service-record.approve');
  Route::post('/service-record/{id}/reject', [ServiceRecordController::class, 'reject'])
    ->name('service-record.reject');
  Route::post('/service-record/{id}', [ServiceRecordController::class, 'destroy'])
    ->name('service-record.destroy');
  //Generate PDF Service Form
  Route::get('/service-record/{employee}/pdf', [ServiceRecordController::class, 'downloadPdf'])
    ->name('service-record.pdf');



  // ── Seminars Attended By Coldzy ───────────────────────────────────────
  Route::get('/seminars-attended', [SeminarAttendedController::class, 'seminars'])->name('seminars-attended');
  Route::post('/seminars-attended', [SeminarAttendedController::class, 'store'])->name('seminars-attended.store');
  Route::put('/seminars/{id}', [SeminarAttendedController::class, 'update'])
    ->name('seminars.update');
  Route::delete('/seminars/{id}', [SeminarAttendedController::class, 'destroy'])
    ->name('seminars.destroy');
  Route::patch('/seminars/{id}/approve', [SeminarAttendedController::class, 'approve'])
    ->name('seminars.approve');
  Route::patch('/seminars/{id}/reject', [SeminarAttendedController::class, 'reject'])
    ->name('seminars.reject');



  // ── Account Management (Admin only) ───────────────────────────────────────
  Route::prefix('account-management')->middleware(['role:Admin'])->group(function () {
    Route::get('/',           [AccountManagementController::class, 'index'])->name('account-management.index');
    Route::post('/',          [AccountManagementController::class, 'store'])->name('account-management.store');
    Route::get('/{user}',    [AccountManagementController::class, 'show'])->name('account-management.show');
    Route::put('/{user}',    [AccountManagementController::class, 'update'])->name('account-management.update');
    Route::patch('/{user}/change-status', [AccountManagementController::class, 'changeStatus'])->name('account-management.change-status');
    Route::delete('/{user}', [AccountManagementController::class, 'destroy'])->name('account-management.destroy');
  });

  Route::prefix('attendance-report')->group(function () {
    Route::get('/', [AttendanceReportController::class, 'index'])->name('attendance-report');
    Route::get('/daily', [AttendanceReportController::class, 'daily'])->name('daily');
    Route::get('/pdf',   [AttendanceReportController::class, 'exportPdf'])->name('attendance-report.pdf');
  });

  // ── PSGC address cascade proxy ─────────────────────────────────────────────
  Route::prefix('psgc')->group(function () {
    Route::get('/regions', [PsgcController::class, 'regions']);
    Route::get('/regions/{code}/provinces', [PsgcController::class, 'provinces']);
    Route::get('/provinces/{code}/cities', [PsgcController::class, 'cities']);
    Route::get('/cities/{code}/barangays', [PsgcController::class, 'barangays']);
  });

  Route::get('/leave-application', [LeaveApplicationController::class, 'index'])->name('leave-application-form');
  Route::post('/leave-application', [LeaveApplicationController::class, 'store'])->name('leave-application-store');
  Route::get('/leave-summary', [LeaveSummaryController::class, 'index'])->name('leave-summary');
  Route::get('/leave-pdf', [LeaveSummaryController::class, 'generate']);

  Route::prefix('api')->group(function () {
    Route::get('/employees/search', [EmployeesRegistrationController::class, 'search'])->name('api.employees.search');
    Route::get('/employees/{id}/leave-balances', [LeaveApplicationController::class, 'balances'])->name('api.employees.leave-balances');
    Route::get('/leave-requests', [LeaveSummaryController::class, 'list'])->name('api.leave-requests.list');
    Route::get('/leave-requests/replacements', [LeaveSummaryController::class, 'replacements']);
    Route::patch('/leave-requests/{id}/remark', [LeaveSummaryController::class, 'setRemark'])->name('api.leave-requests.remark');
    Route::get('/leave-requests/{id}/pdf', [LeaveSummaryController::class, 'generatePdf'])->name('api.leave-requests.pdf');
    Route::get('/leave-requests/{id}', [LeaveSummaryController::class, 'show'])->name('api.leave-requests.show');
    Route::delete('/leave-requests/{id}', [LeaveSummaryController::class, 'destroy'])->name('api.leave-requests.destroy');
  });
});


Route::get('/logout', [LoginBasic::class, 'logout'])->name('logout')->middleware('auth');

// require __DIR__ . '/appraisals.php';
