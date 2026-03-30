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
            <i class="icon-base ri ri-add-line me-1"></i>
            Add New User
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
            style="width: 200px;">
          <select id="filterStatus" class="form-select form-select-sm" style="width: 150px;">
            <option value="">All Accounts</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.location.reload()">
            <i class="icon-base ri ri-refresh-line me-1"></i>
            Refresh
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>User Name</th>
              <th class="text-center">Status</th>
              <th class="text-center">Created At</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($users as $user)
              <tr>
                <td class='name'>{{ $user->employee->office->office_name }}</td>
                <td>{{ $user->username }}</td>
              <td class="text-center"></td>
                <td class="text-center">{{ $user->created_at }}</td>
                <td class="text-center"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  {{-- <script src="{{ asset('assets/js/user-management.js') }}"></script> --}}
@endsection
