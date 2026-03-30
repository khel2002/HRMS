<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountManagementController extends Controller
{
  // ── Index ─────────────────────────────────────────────────────────────────

  public function index()
  {
    $users = User::with(['role', 'employee.office'])
      ->latest()
      ->get();

    $roles = Role::orderBy('name')->get();

    // Employees that do NOT yet have a user account
    $availableEmployees = Employee::whereDoesntHave('user')
      ->orderBy('last_name')
      ->get();

    return view(
      'content.admin.accounts.account-management',
      compact('users', 'roles', 'availableEmployees')
    );
  }

  // ── Store (Add) ───────────────────────────────────────────────────────────

  public function store(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'employee_id' => ['required', 'integer', 'exists:employees,id', 'unique:users,employee_id'],
      'role_id'     => ['required', 'integer', 'exists:roles,id'],
      'username'    => ['required', 'string', 'max:255', 'unique:users,username'],
      'password'    => ['required', 'string', Password::min(8)],
    ]);

    $user = User::create([
      'employee_id' => $validated['employee_id'],
      'role_id'     => $validated['role_id'],
      'username'    => $validated['username'],
      'password'    => Hash::make($validated['password']),
      'status'      => 'active',
    ]);

    $user->load(['role', 'employee.office']);

    return response()->json([
      'success' => true,
      'message' => 'User account created successfully.',
      'user'    => $this->formatUser($user),
    ]);
  }

  // ── Show (View) ───────────────────────────────────────────────────────────

  public function show(User $user): JsonResponse
  {
    $user->load(['role', 'employee.office']);

    return response()->json([
      'success' => true,
      'user'    => $this->formatUser($user),
    ]);
  }

  // ── Update (Edit) ─────────────────────────────────────────────────────────

  public function update(Request $request, User $user): JsonResponse
  {
    $validated = $request->validate([
      'role_id'  => ['required', 'integer', 'exists:roles,id'],
      'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
      'password' => ['nullable', 'string', Password::min(8)],
    ]);

    $user->role_id  = $validated['role_id'];
    $user->username = $validated['username'];

    if (!empty($validated['password'])) {
      $user->password = Hash::make($validated['password']);
    }

    $user->save();
    $user->load(['role', 'employee.office']);

    return response()->json([
      'success' => true,
      'message' => 'User account updated successfully.',
      'user'    => $this->formatUser($user),
    ]);
  }

  // ── Change Status ─────────────────────────────────────────────────────────

  public function changeStatus(Request $request, User $user): JsonResponse
  {
    $validated = $request->validate([
      'status' => ['required', 'in:active,inactive,suspended'],
    ]);

    $user->update(['status' => $validated['status']]);

    return response()->json([
      'success' => true,
      'message' => 'User status updated to "' . ucfirst($validated['status']) . '".',
      'status'  => $user->status,
    ]);
  }

  // ── Destroy (Delete) ──────────────────────────────────────────────────────

  public function destroy(User $user): JsonResponse
  {
    $user->delete();

    return response()->json([
      'success' => true,
      'message' => 'User account deleted successfully.',
    ]);
  }

  // ── Private helpers ───────────────────────────────────────────────────────

  private function formatUser(User $user): array
  {
    $emp = $user->employee;

    return [
      'id'          => $user->id,
      'employee_id' => $user->employee_id,
      'username'    => $user->username,
      'role_id'     => $user->role_id,
      'role'        => $user->role->name            ?? 'N/A',
      'status'      => $user->status,
      'office'      => $emp?->office?->office_name  ?? 'N/A',
      'full_name'   => $emp?->full_name              ?? 'N/A',
      'email'       => $emp?->email                  ?? 'N/A',
      'created_at'  => $user->created_at?->format('Y-m-d H:i:s'),
      'updated_at'  => $user->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
