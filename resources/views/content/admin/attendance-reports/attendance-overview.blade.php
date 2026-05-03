@extends('layouts/contentNavbarLayout')
@section('title', 'Attendance Report')

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/attendance-report.css') }}">
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── TOP HEADER CARD (Company + Title + Date Picker) ──────────────── --}}
    <div class="card mb-4 shadow-none border">
      <div class="card-body px-5 py-4">

        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">

          {{-- Left: Company branding + title --}}
          <div>
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="logo">
                <i><img src="{{ asset('assets/img/logo/HRIS-LOGO.png') }}" class="logo"></i>
              </div>
              <div>
                <div class="text-muted" style="font-size:.78rem;">Rural Bank of Hindang</div>
                <div class="fw-bold" style="font-size:1rem;">{{ config('variables.templateName') }}</div>
              </div>
            </div>
            <h4 class="fw-bold mb-1" style="font-size:1.45rem;">Daily Attendance Report</h4>
            <div class="text-muted" id="att-date-subtitle" style="font-size:.9rem;">
              {{ \Carbon\Carbon::parse($initialDate)->format('l, F j, Y') }}
            </div>
          </div>

          {{-- Right: Export buttons + Date picker --}}
          <div class="d-flex flex-column align-items-end gap-3">
            <div class="d-flex gap-2">
              <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="btnExportPdf">
                <i class="ri-file-pdf-line"></i> PDF
              </button>
              <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="btnExportExcel">
                <i class="ri-file-excel-line"></i> Excel
              </button>
            </div>

            {{-- Date navigator — fused pill control --}}
            <div class="d-flex align-items-center gap-2">
              <div class="att-date-nav">
                <button class="att-date-nav-btn" id="btnPrevDay" title="Previous workday">&#8249;</button>
                <span class="att-date-nav-label">
                  <span id="datePickerInput">{{ \Carbon\Carbon::parse($initialDate)->format('m/d/Y') }}</span>
                </span>
                <button class="att-date-nav-btn" id="btnNextDay" title="Next workday">&#8250;</button>
              </div>
              <button class="btn btn-sm btn-outline-primary px-3" id="btnToday" style="font-size:.8rem;">Today</button>
            </div>
          </div>

        </div>

        {{-- Divider --}}
        <hr class="mt-4 mb-0">
      </div>
    </div>

    {{-- ── NOTICE PANEL (weekend / future / error states) ────────────────── --}}
    <div id="att-notice" style="display:none;" class="card mb-4 shadow-none border">
      <div class="card-body text-center py-5" id="att-notice-body"></div>
    </div>

    {{-- ── ATTENDANCE SUMMARY ──────────────────────────────────────────── --}}
    <div id="att-summary-section">
      <h6 class="fw-bold mb-3" style="font-size:.95rem;">Attendance Summary</h6>
      <div class="row g-3 mb-5" id="att-summary-cards">

        <div class="col-6 col-sm-4 col-md">
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon text-primary">
                <i class="ri-group-line"></i>
              </div>
              <div>
                <div class="fw-bold" style="font-size:1.6rem;line-height:1;" data-stat="total_employees">
                  {{ $summary['total_employees'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">Total Employees</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-sm-4 col-md">
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon text-success">
                <i class="ri-user-follow-line"></i>
              </div>
              <div>
                <div class="fw-bold text-success" style="font-size:1.6rem;line-height:1;" data-stat="present">
                  {{ $summary['present'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">Present</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-sm-4 col-md">
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon text-danger">
                <i class="ri-user-unfollow-line"></i>
              </div>
              <div>
                <div class="fw-bold text-danger" style="font-size:1.6rem;line-height:1;" data-stat="absent">
                  {{ $summary['absent'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">Absent</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-sm-4 col-md">
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon text-warning">
                <i class="ri-time-line"></i>
              </div>
              <div>
                <div class="fw-bold text-warning" style="font-size:1.6rem;line-height:1;" data-stat="late">
                  {{ $summary['late'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">Late</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-sm-4 col-md">
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon" style="color:#7367f0;">
                <i class="ri-calendar-event-line"></i>
              </div>
              <div>
                <div class="fw-bold" style="font-size:1.6rem;line-height:1;color:#7367f0;" data-stat="on_leave">
                  {{ $summary['on_leave'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">On Leave</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Pending card — hidden after cutoff --}}
        <div class="col-6 col-sm-4 col-md" id="stat-pending-card"
          @if ($cutoffPassed) style="display:none;" @endif>
          <div class="card shadow-none border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
              <div class="att-stat-icon text-secondary">
                <i class="ri-loader-4-line"></i>
              </div>
              <div>
                <div class="fw-bold text-secondary" style="font-size:1.6rem;line-height:1;" data-stat="pending">
                  {{ $summary['pending'] }}</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">Pending</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- ── SEARCH + FILTER BAR ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
      <div class="input-group input-group-sm" style="max-width:260px;">
        <span class="input-group-text bg-transparent"><i class=" ri ri-search-line text-muted"></i></span>
        <input type="text" class="form-control border-start-0" id="dailySearch" placeholder="Search employee…">
      </div>
      <select class="form-select form-select-sm" id="dailyFilter" style="max-width:170px;">
        <option value="all">All Sections</option>
        <option value="late">Late</option>
        <option value="leave">On Leave</option>
        <option value="absent">Absent</option>
        <option value="pending">Pending</option>
      </select>
    </div>

    {{-- ── DATA PANELS ─────────────────────────────────────────────────── --}}
    <div id="att-data-panels">

      {{-- ══ LATE EMPLOYEES ══════════════════════════════════════════════ --}}
      <div class="mb-5" id="section-late">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="ri-time-line text-warning" style="font-size:1.25rem;"></i>
          <h6 class="fw-bold mb-0" style="font-size:1rem;">Late Employees</h6>
          <span class="badge rounded-pill bg-label-warning ms-1" id="count-late">{{ $summary['late'] }}</span>
        </div>

        <div class="card shadow-none border">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;" id="late-table">
              <thead>
                <tr style="background:#f8f8fb;">
                  <th class="att-th ps-4">EMPLOYEE NAME</th>
                  <th class="att-th">OFFICE / DEPARTMENT</th>
                  <th class="att-th">TIME IN</th>
                  <th class="att-th">LATE DURATION</th>
                </tr>
              </thead>
              <tbody id="late-list">
                @forelse ($dailyLate as $emp)
                  @php
                    $initials = collect(explode(' ', $emp['name']))
                        ->map(fn($w) => strtoupper($w[0] ?? ''))
                        ->take(2)
                        ->implode('');
                  @endphp
                  <tr class="daily-searchable" data-name="{{ strtolower($emp['name']) }}" data-group="late">
                    <td class="ps-4 py-3">
                      <div class="d-flex align-items-center gap-3">
                        <div class="att-avatar av-c{{ $emp['av'] }}">{{ $initials }}</div>
                        <span class="fw-medium">{{ $emp['name'] }}</span>
                      </div>
                    </td>
                    <td class="py-3">
                      <div class="fw-medium">{{ $emp['pos'] }}</div>
                      <div class="text-muted" style="font-size:.78rem;">{{ $emp['dept'] }}</div>
                    </td>
                    <td class="py-3 fw-medium">{{ $emp['time_in'] }}</td>
                    <td class="py-3">
                      <span
                        class="att-late-badge {{ ($emp['late_minutes'] ?? 0) >= 60 ? 'is-severe' : (($emp['late_minutes'] ?? 0) >= 30 ? 'is-moderate' : '') }}">
                        <i class="ri-time-line"></i> {{ $emp['late_duration'] }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr id="late-empty-row">
                    <td colspan="4">
                      <div class="att-empty-state py-4"><i class="ri-checkbox-circle-line text-success fs-3"></i>
                        <p class="text-muted mt-2 mb-0 small">No late arrivals today.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ══ EMPLOYEES ON LEAVE ══════════════════════════════════════════ --}}
      <div class="mb-5" id="section-leave">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="ri-calendar-event-line" style="font-size:1.25rem;color:#7367f0;"></i>
          <h6 class="fw-bold mb-0" style="font-size:1rem;">Employees on Leave</h6>
          <span class="badge rounded-pill bg-label-primary ms-1" id="count-leave">{{ $summary['on_leave'] }}</span>
        </div>

        <div id="leave-groups-container">
          @php
            $leaveGroups = collect($dailyLeave)->groupBy('leave_type');
            $leaveGroupColors = [
                'Service Incentive Leave' => [
                    'header' => '#eef2ff',
                    'accent' => '#7367f0',
                    'initials' => 'text-primary',
                ],
                'Vacation Leave' => ['header' => '#e8f5e9', 'accent' => '#28c76f', 'initials' => 'text-success'],
                'Sick Leave' => ['header' => '#fff1f1', 'accent' => '#ea5455', 'initials' => 'text-danger'],
            ];
          @endphp

          @forelse ($leaveGroups as $leaveType => $employees)
            @php $colors = $leaveGroupColors[$leaveType] ?? ['header' => '#f5f5f5', 'accent' => '#888', 'initials' => 'text-secondary']; @endphp
            <div class="mb-4 daily-searchable-group" data-group="leave">
              <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                <span class="fw-semibold" style="font-size:.9rem;">{{ $leaveType }}</span>
                <span class="text-muted" style="font-size:.8rem;">{{ $employees->count() }}
                  {{ Str::plural('employee', $employees->count()) }}</span>
              </div>
              <div class="card shadow-none border">
                <div class="table-responsive">
                  <table class="table mb-0" style="font-size:.85rem;">
                    <thead>
                      <tr style="background:{{ $colors['header'] }};">
                        <th class="att-th ps-4" style="color:{{ $colors['accent'] }};">EMPLOYEE NAME</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($employees as $emp)
                        @php
                          $initials = collect(explode(' ', $emp['name']))
                              ->map(fn($w) => strtoupper($w[0] ?? ''))
                              ->take(2)
                              ->implode('');
                        @endphp
                        <tr class="daily-searchable" data-name="{{ strtolower($emp['name']) }}" data-group="leave">
                          <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                              <div class="att-avatar av-leave"
                                style="color:{{ $colors['accent'] }};background:{{ $colors['header'] }};">
                                {{ $initials }}</div>
                              <span class="fw-medium">{{ $emp['name'] }}</span>
                            </div>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @empty
            <div class="card shadow-none border">
              <div class="card-body">
                <div class="att-empty-state py-3"><i class="ri-checkbox-circle-line text-success fs-3"></i>
                  <p class="text-muted mt-2 mb-0 small">No employees on leave today.</p>
                </div>
              </div>
            </div>
          @endforelse
        </div>
      </div>

      {{-- ══ ABSENT EMPLOYEES ════════════════════════════════════════════ --}}
      <div class="mb-5" id="section-absent" @if (!$cutoffPassed) style="display:none;" @endif>
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="ri-user-unfollow-line text-danger" style="font-size:1.25rem;"></i>
          <h6 class="fw-bold mb-0" style="font-size:1rem;">Absent Employees</h6>
          <span class="badge rounded-pill bg-label-danger ms-1" id="count-absent">{{ $summary['absent'] }}</span>
        </div>

        <div class="card shadow-none border">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;" id="absent-table">
              <thead>
                <tr style="background:#fff5f5;">
                  <th class="att-th ps-4" style="color:#ea5455;">EMPLOYEE NAME</th>
                  <th class="att-th" style="color:#ea5455;">OFFICE / DEPARTMENT</th>
                  <th class="att-th" style="color:#ea5455;">STATUS</th>
                </tr>
              </thead>
              <tbody id="absent-list">
                @forelse ($dailyAbsent as $emp)
                  @php
                    $initials = collect(explode(' ', $emp['name']))
                        ->map(fn($w) => strtoupper($w[0] ?? ''))
                        ->take(2)
                        ->implode('');
                  @endphp
                  <tr class="daily-searchable" data-name="{{ strtolower($emp['name']) }}" data-group="absent">
                    <td class="ps-4 py-3">
                      <div class="d-flex align-items-center gap-3">
                        <div class="att-avatar av-absent">{{ $initials }}</div>
                        <span class="fw-medium">{{ $emp['name'] }}</span>
                      </div>
                    </td>
                    <td class="py-3">
                      <div class="fw-medium">{{ $emp['pos'] }}</div>
                      <div class="text-muted" style="font-size:.78rem;">{{ $emp['dept'] }}</div>
                    </td>
                    <td class="py-3">
                      <span class="badge bg-label-danger rounded-pill">Absent</span>
                    </td>
                  </tr>
                @empty
                  <tr id="absent-empty-row">
                    <td colspan="3">
                      <div class="att-empty-state py-4"><i class="ri-checkbox-circle-line text-success fs-3"></i>
                        <p class="text-muted mt-2 mb-0 small">No absences recorded.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ══ PENDING (before cutoff, no time-in yet) ════════════════════ --}}
      <div class="mb-5" id="section-pending" @if ($cutoffPassed) style="display:none;" @endif>
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="ri-loader-4-line text-secondary" style="font-size:1.25rem;"></i>
          <h6 class="fw-bold mb-0" style="font-size:1rem;">Pending</h6>
          <span class="badge rounded-pill bg-label-secondary ms-1" id="count-pending">{{ $summary['pending'] }}</span>
          <span class="badge bg-label-warning ms-1" style="font-size:.72rem;">
            <i class="ri-information-line me-1"></i>Will be marked absent after
            {{ \Carbon\Carbon::parse($scheduleInfo['morning_cutoff'])->format('g:i A') }}
          </span>
        </div>

        <div class="card shadow-none border">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;" id="pending-table">
              <thead>
                <tr style="background:#f8f8fb;">
                  <th class="att-th ps-4">EMPLOYEE NAME</th>
                  <th class="att-th">OFFICE / DEPARTMENT</th>
                  <th class="att-th">STATUS</th>
                </tr>
              </thead>
              <tbody id="pending-list">
                @forelse ($dailyPending as $emp)
                  @php
                    $initials = collect(explode(' ', $emp['name']))
                        ->map(fn($w) => strtoupper($w[0] ?? ''))
                        ->take(2)
                        ->implode('');
                  @endphp
                  <tr class="daily-searchable" data-name="{{ strtolower($emp['name']) }}" data-group="pending">
                    <td class="ps-4 py-3">
                      <div class="d-flex align-items-center gap-3">
                        <div class="att-avatar av-pending">{{ $initials }}</div>
                        <span class="fw-medium">{{ $emp['name'] }}</span>
                      </div>
                    </td>
                    <td class="py-3">
                      <div class="fw-medium">{{ $emp['pos'] }}</div>
                      <div class="text-muted" style="font-size:.78rem;">{{ $emp['dept'] }}</div>
                    </td>
                    <td class="py-3">
                      <span class="badge bg-label-secondary rounded-pill">Not yet in</span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3">
                      <div class="att-empty-state py-4"><i class="ri-checkbox-circle-line text-success fs-3"></i>
                        <p class="text-muted mt-2 mb-0 small">All employees have clocked in.</p>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>{{-- /#att-data-panels --}}

  </div>{{-- /container --}}

  {{-- ── JS Config injected by Blade ────────────────────────────────────── --}}
  @php
    $panelPrefix = strcasecmp(auth()->user()?->role?->name ?? '', 'HR') === 0 ? 'HR' : 'admin';
  @endphp
  <script>
    window.AttConfig = {
      routes: {
        daily: "{{ url('/' . $panelPrefix . '/attendance-report/daily') }}",
        pdf: "{{ url('/' . $panelPrefix . '/attendance-report/pdf') }}"
      },
      initialDate: "{{ $initialDate }}",
      today: "{{ \Carbon\Carbon::today()->toDateString() }}",
      cutoffTime: "{{ $scheduleInfo['morning_cutoff'] }}",
      cutoffPassed: {{ $cutoffPassed ? 'true' : 'false' }},
    };
  </script>
  <script src="{{ asset('assets/js/attendance-report.js') }}"></script>
@endsection
