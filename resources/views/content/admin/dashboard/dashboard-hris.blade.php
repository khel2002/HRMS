@extends('layouts/contentNavbarLayout')

@section('title', 'HRIS Dashboard')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])
@endsection

@section('page-script')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Attendance Overview Chart
    const attendanceChartEl = document.querySelector('#attendanceChart');
    if (attendanceChartEl) {
        const attendanceChart = new ApexCharts(attendanceChartEl, {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Employees',
                data: [44, 38, 41, 35, 49, 50, 47]
            }],
            xaxis: {
                categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '45%'
                }
            }
        });
        attendanceChart.render();
    }

    // Employees by Office Chart
    const officeChartEl = document.querySelector('#officeChart');
    if (officeChartEl) {
        const officeChart = new ApexCharts(officeChartEl, {
            chart: {
                type: 'donut',
                height: 320
            },
            series: [25, 18, 14, 10, 8],
            labels: ['HR', 'IT', 'Finance', 'Registrar', 'Admin'],
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true
            }
        });
        officeChart.render();
    }
});
</script>
@endsection

@section('content')
<div class="row g-4">

  <!-- Welcome Card -->
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
          <h4 class="mb-1">Welcome back!</h4>
          <p class="mb-0 text-muted">Here’s the latest overview of your Human Resource Information System.</p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="{{ route('employees-index') }}" class="btn btn-primary me-2">View Employees</a>
          <a href="{{ route('employee-registration') }}" class="btn btn-outline-primary">Register Employee</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="text-heading d-block mb-1">Total Employees</span>
            <h3 class="mb-2">{{ $totalEmployees ?? 0 }}</h3>
            <small class="text-success">+12 this month</small>
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
            <small class="text-success">Currently employed</small>
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
            <span class="text-heading d-block mb-1">Pending Registrations</span>
            <h3 class="mb-2">{{ $pendingEmployees ?? 0 }}</h3>
            <small class="text-warning">Needs review</small>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-warning rounded">
              <i class="ti ti-user-plus ti-24px"></i>
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
            <h3 class="mb-2">{{ $leaveToday ?? 0 }}</h3>
            <small class="text-danger">Approved leaves</small>
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

  <!-- Attendance Overview -->
  <div class="col-xl-8 col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="card-title mb-0">Attendance Overview</h5>
          <small class="text-muted">Weekly employee attendance trend</small>
        </div>
      </div>
      <div class="card-body">
        <div id="attendanceChart"></div>
      </div>
    </div>
  </div>

  <!-- Employees by Office -->
  <div class="col-xl-4 col-lg-5">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">Employees by Office</h5>
      </div>
      <div class="card-body">
        <div id="officeChart"></div>
      </div>
    </div>
  </div>

  <!-- Recent Employees -->
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Registered Employees</h5>
        <a href="" class="btn btn-sm btn-primary">View All</a>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Employee No.</th>
              <th>Office</th>
              <th>Position</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($recentEmployees ?? [] as $employee)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                      <img src="{{ $employee->image ?? asset('assets/img/avatars/default-avatar.jpg') }}"
                           alt="Avatar"
                           class="rounded-circle">
                    </div>
                    <div>
                      <h6 class="mb-0">{{ $employee->first_name }} {{ $employee->last_name }}</h6>
                      <small class="text-muted">{{ $employee->email ?? 'No email' }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ $employee->employee_number }}</td>
                <td>{{ $employee->office->name ?? 'N/A' }}</td>
                <td>{{ $employee->position->name ?? 'N/A' }}</td>
                <td>
                  <span class="badge bg-label-success">Active</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No recent employees found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-xl-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-3">
          <a href="" class="btn btn-outline-primary">
            <i class="ti ti-user-plus me-2"></i> Register Employee
          </a>
          <a href="" class="btn btn-outline-secondary">
            <i class="ti ti-clock me-2"></i> View DTR
          </a>
          <a href="" class="btn btn-outline-warning">
            <i class="ti ti-calendar me-2"></i> Manage Leave
          </a>
          <a href="" class="btn btn-outline-info">
            <i class="ti ti-file-text me-2"></i> Service Records
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection