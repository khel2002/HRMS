@extends('layouts/contentNavbarLayout')

@section('title', 'HR Dashboard')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
<div class="row g-4">

  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
          <h4 class="mb-1">Welcome back!</h4>
          <p class="mb-0 text-muted">Quick overview of HR activity and requests.</p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="{{ url('/HR/employees') }}" class="btn btn-primary me-2">Employees</a>
          <a href="{{ url('/HR/leave-summary') }}" class="btn btn-outline-primary">Leave Summary</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="text-heading d-block mb-1">Total Employees</span>
            <h3 class="mb-2">{{ $totalEmployees ?? 0 }}</h3>
            <small class="text-muted">All records</small>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="ti ti-users ti-24px"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="text-heading d-block mb-1">Active Employees</span>
            <h3 class="mb-2">{{ $activeEmployees ?? 0 }}</h3>
            <small class="text-muted">Status: active</small>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-success rounded">
              <i class="ti ti-user-check ti-24px"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="text-heading d-block mb-1">Pending Leave</span>
            <h3 class="mb-2">{{ $pendingLeaveRequests ?? 0 }}</h3>
            <small class="text-muted">Needs HR action</small>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-warning rounded">
              <i class="ti ti-calendar-time ti-24px"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="text-heading d-block mb-1">On Leave Today</span>
            <h3 class="mb-2">{{ $onLeaveToday ?? 0 }}</h3>
            <small class="text-muted">Approved leaves</small>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-danger rounded">
              <i class="ti ti-calendar-off ti-24px"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Pending Leave Requests</h5>
        <a href="{{ url('/HR/leave-summary') }}" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Leave Type</th>
              <th>Date Filed</th>
              <th>Period</th>
              <th>Days</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse(($recentPendingLeaves ?? []) as $la)
              <tr>
                <td>{{ $la->employee?->full_name ?? '—' }}</td>
                <td>{{ $la->leaveType?->name ?? '—' }}</td>
                <td>{{ $la->date_filed?->toDateString() ?? '—' }}</td>
                <td>
                  {{ $la->start_date?->toDateString() ?? '—' }}
                  —
                  {{ $la->end_date?->toDateString() ?? '—' }}
                </td>
                <td>{{ (float) ($la->total_days ?? 0) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No pending requests.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection
