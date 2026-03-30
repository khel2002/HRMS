<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AccountManagementController extends Controller
{
  public function index()
  {
    
    $users = User::with(['role', 'employee.office'])
      ->latest()
      ->get();

    // dd(User::all());

    return view('content.admin.accounts.account-management', compact('users'));
  }
}
