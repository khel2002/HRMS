@extends('layouts/contentNavbarLayout')
@section('title', 'Account - Management')
@section('content')

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h4 class="mb-1">User Management</h4>
            <p class="mb-0">Manage and view all user accounts</p>
          </div>
          <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="icon-base ri ri-add-line me-1"></i>Add New User
          </button>
        </div>
      </div>
    </div>
  </div>

  <br>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h5 class="mb-0">All Users</h5>
        <div class="d-flex gap-2 flex-wrap">
          <input type="text" id="searchTable" class="form-control form-control-sm" placeholder="Search users..."
            style="width:200px;">
          <select id="filterStatus" class="form-select form-select-sm" style="width:150px;">
            <option value="">All Accounts</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.location.reload()">
            <i class="icon-base ri ri-refresh-line me-1"></i>Refresh
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Office</th>
              <th>Username</th>
              <th class="text-center">Status</th>
              <th class="text-center">Created At</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($users as $user)
              <tr data-user-id="{{ $user->id }}">
                <td>{{ $user->employee->office->office_name ?? 'N/A' }}</td>
                <td>{{ $user->username }}</td>
                <td class="text-center">
                  <span class="user-status-badge">
                    <span
                      class="badge {{ strtolower($user->status) === 'active' ? 'bg-success' : (strtolower($user->status) === 'suspended' ? 'bg-warning text-dark' : 'bg-danger') }}">
                      {{ ucfirst(strtolower($user->status)) }}
                    </span>
                  </span>
                </td>
                <td class="text-center">{{ $user->created_at }}</td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">

                    {{-- View --}}
                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button"
                      title="View Details" onclick="viewUser({{ $user->id }})">
                      <i class="icon-base ri ri-eye-line"></i>
                    </button>

                    {{-- Edit --}}
                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" title="Edit"
                      onclick="editUser({{ $user->id }})">
                      <i class="icon-base ri ri-edit-line"></i>
                    </button>

                    {{-- More --}}
                    <div class="dropdown">
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                        <i class="icon-base ri ri-more-2-line"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="javascript:void(0);"
                          onclick="changeStatus({{ $user->id }}, '{{ addslashes($user->employee->full_name ?? 'User') }}', '{{ strtolower($user->status) }}')">
                          <i class="icon-base ri ri-refresh-line me-2"></i>Change Status
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                          onclick="deleteUser({{ $user->id }}, '{{ addslashes($user->employee->full_name ?? 'User') }}')">
                          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete Account
                        </a>
                      </div>
                    </div>

                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No user accounts found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @include('content.admin.accounts.add_new-user-modal')
  @include('content.admin.accounts.view_user_account-modal')
  @include('content.admin.accounts.edit_user_account-modal')
  @include('content.admin.accounts.change_status-modal')

@endsection

@section('page-script')
  <script src="{{ asset('assets/js/user-management.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
