<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminEmployeesController extends Controller
{
  public function index(Request $request)
  {
    $query = Employee::query()->orderByDesc('created_at');

    if ($search = $request->input('search')) {
      $query->where(function ($q) use ($search) {
        $q->where('employee_number', 'like', "%{$search}%")
          ->orWhere('first_name',  'like', "%{$search}%")
          ->orWhere('last_name',   'like', "%{$search}%")
          ->orWhere('middle_name', 'like', "%{$search}%")
          ->orWhere('email',       'like', "%{$search}%");
      });
    }

    if ($status = $request->input('status')) {
      $query->where('status', $status);
    }

    $employees = $query->paginate(15)->withQueryString();

    return view('content.admin.employees-management.employees-index', compact('employees'));
  }
  

  public function dashboard(){
    return view('content.admin.dashboard.dashboard-hris');
  }

  public function updateStatus(Request $request, string $encryptedId): RedirectResponse
  {
    $request->validate([
      'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
    ]);

    try {
      $id = (int) Crypt::decryptString($encryptedId);
    } catch (DecryptException) {
      return redirect()->back()->with('error', 'Employee record not found.');
    }

    try {
      Employee::findOrFail($id)->update(['status' => $request->input('status')]);

      return redirect()->back()->with('success', 'Employee status updated.');
    } catch (ModelNotFoundException) {
      return redirect()->back()->with('error', 'Employee record not found.');
    } catch (\Throwable $e) {
      Log::error('Employee status update failed', ['id' => $id, 'error' => $e->getMessage()]);

      return redirect()->back()->with('error', 'Failed to update employee status.');
    }
  }
}
