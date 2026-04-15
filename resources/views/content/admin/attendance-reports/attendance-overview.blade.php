@extends('layouts/contentNavbarLayout')
@section('title', 'Attendance Report')
@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/attendance-report.css') }}">
@endsection
@section('content')

  <div class="container-xxl flex-grow-1 container-p-y att-page">

    {{-- ── PAGE HEADER ────────────────────────────────────────────────────── --}}
    <div class="att-page-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
      <div>
        <h4 class="att-page-title">
          <i class="ri ri-calendar-check-line me-2"></i> Daily Attendance Report
        </h4>
        <p class="att-page-sub">Attendance summary, late arrivals, and employees on leave</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="att-btn"><i class="ri ri-download-2-line"></i> Export</button>
        <button class="att-btn"><i class="ri ri-printer-line"></i> Print</button>
      </div>
    </div>

    {{-- ── DATE NAVIGATION CARD ───────────────────────────────────────────── --}}
    <div class="att-card mb-4">
      <div class="att-card-header">
        <h6 class="att-card-title">
          <i class="ri ri-calendar-todo-line"></i> Daily Attendance
        </h6>
        <div class="d-flex align-items-center gap-2 flex-wrap">

          {{-- Date navigator --}}
          <div class="date-nav">
            <button class="date-nav-btn" id="btnPrevDay" title="Previous workday">
              <i class="ri ri-arrow-left-s-line"></i>
            </button>
            <span class="date-label" id="dateLabel">
              {{ \Carbon\Carbon::parse($initialDate)->format('M d, Y') }}
            </span>
            <button class="date-nav-btn" id="btnNextDay" title="Next workday">
              <i class="ri ri-arrow-right-s-line"></i>
            </button>
          </div>

          <button class="att-btn" id="btnToday" style="font-size:.78rem;padding:.35rem .8rem;">
            <i class="ri ri-focus-3-line"></i> Today
          </button>

          {{-- Search --}}
          <div class="att-search-wrap" style="min-width:200px;">
            <i class="ri ri-search-line"></i>
            <input type="text" class="att-search" id="dailySearch" placeholder="Search employee…">
          </div>

          {{-- Section filter --}}
          <select class="att-filter" id="dailyFilter">
            <option value="all">All Sections</option>
            <option value="late">Late</option>
            <option value="leave">On Leave</option>
          </select>

        </div>
      </div>
    </div>

    {{-- ── SUMMARY STAT CARDS ─────────────────────────────────────────────── --}}
    {{--
    data-stat="..." is targeted by JS animateNumber() when AJAX refreshes data.
    Initial values come from the controller so the page works without JS.
  --}}
    <div class="row g-3 mb-4" id="att-summary-cards">
      <div class="col-6 col-md-3">
        <div class="att-stat s-primary">
          <div class="att-stat-icon" style="background:var(--att-primary-soft);color:var(--att-primary);">
            <i class="ri ri-team-line"></i>
          </div>
          <div>
            <div class="stat-lbl">Total Employees</div>
            <div class="stat-num" style="color:var(--att-primary);" data-stat="total_employees">
              {{ $summary['total_employees'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="att-stat s-present">
          <div class="att-stat-icon" style="background:var(--att-present-soft,#e8f9ee);color:var(--att-present,#28a745);">
            <i class="ri ri-user-follow-line"></i>
          </div>
          <div>
            <div class="stat-lbl">Present</div>
            <div class="stat-num" style="color:var(--att-present,#28a745);" data-stat="present">{{ $summary['present'] }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="att-stat s-late">
          <div class="att-stat-icon" style="background:var(--att-late-soft);color:var(--att-late);">
            <i class="ri ri-time-line"></i>
          </div>
          <div>
            <div class="stat-lbl">Late</div>
            <div class="stat-num" style="color:var(--att-late);" data-stat="late">{{ $summary['late'] }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="att-stat s-vl">
          <div class="att-stat-icon" style="background:var(--att-vl-soft);color:var(--att-vl);">
            <i class="ri ri-flight-takeoff-line"></i>
          </div>
          <div>
            <div class="stat-lbl">On Leave</div>
            <div class="stat-num" style="color:var(--att-vl);" data-stat="on_leave">{{ $summary['on_leave'] }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── NOTICE PANEL (weekend / future — hidden by default) ───────────── --}}
    <div id="att-notice" style="display:none;" class="att-card mb-4">
      <div class="att-empty" id="att-notice-body" style="padding:3.5rem 1rem;"></div>
    </div>

    {{-- ── DATA PANELS ────────────────────────────────────────────────────── --}}
    <div id="att-data-panels">
      <div class="row g-4">

        {{-- ── LATE ARRIVALS ──────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-7" id="section-late">
          <div class="att-card h-100">

            <div class="daily-group-header">
              <i class="ri ri-time-line" style="color:var(--att-late);font-size:1.1rem;"></i>
              <h6 class="daily-group-title">Late Arrivals</h6>
              <span class="daily-group-count" id="count-late"
                style="background:var(--att-late-soft);color:var(--att-late);">{{ $summary['late'] }}</span>
            </div>

            <div class="table-responsive">
              <table class="table mb-0" style="font-size:.83rem;" id="late-table">
                <thead>
                  <tr style="background:#fffdf5;">
                    <th class="att-th">Employee</th>
                    <th class="att-th text-center">Time In</th>
                    <th class="att-th text-center">Late By</th>
                    <th class="att-th text-center">Remarks</th>
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
                    <tr class="daily-searchable att-tr-hover" data-name="{{ strtolower($emp['name']) }}"
                      data-group="late">
                      <td style="padding:.9rem 1.25rem;">
                        <div class="d-flex align-items-center gap-2">
                          <div class="emp-av av-c{{ $emp['av'] }}" style="width:34px;height:34px;font-size:.73rem;">
                            {{ $initials }}</div>
                          <div>
                            <div class="emp-name">{{ $emp['name'] }}</div>
                            <div class="emp-pos">{{ $emp['pos'] }} · {{ $emp['dept'] }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="att-td-center fw-semibold">{{ $emp['time_in'] }}</td>
                      <td class="att-td-center">
                        <span class="att-badge b-late">
                          <i class="ri ri-time-line"></i>{{ $emp['late_duration'] }}
                        </span>
                      </td>
                      <td class="att-td-center">
                        <span class="att-badge" style="background:#fff3cd;color:#856404;font-size:.7rem;">
                          Late
                        </span>
                      </td>
                    </tr>
                  @empty
                    <tr id="late-empty-row">
                      <td colspan="4">
                        <div class="att-empty" style="padding:2rem;">
                          <i class="ri ri-checkbox-circle-line"
                            style="font-size:2rem;color:var(--att-present,#28a745);"></i>
                          <p style="color:var(--att-muted);margin-top:.5rem;font-size:.83rem;">No late arrivals today.
                          </p>
                        </div>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

          </div>
        </div>

        {{-- ── ON LEAVE ────────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-5" id="section-leave">
          <div class="att-card h-100">

            <div class="daily-group-header">
              <i class="ri ri-flight-takeoff-line" style="color:var(--att-vl);font-size:1.1rem;"></i>
              <h6 class="daily-group-title">On Leave</h6>
              <span class="daily-group-count" id="count-leave"
                style="background:var(--att-vl-soft);color:var(--att-vl);">{{ $summary['on_leave'] }}</span>
            </div>

            <div id="leave-list">
              @forelse ($dailyLeave as $emp)
                @php
                  $leaveColor = match (true) {
                      str_contains($emp['leave_type'], 'Sick') => [
                          'color' => 'var(--att-sick)',
                          'bg' => 'var(--att-sick-soft)',
                      ],
                      str_contains($emp['leave_type'], 'Incentive') => [
                          'color' => 'var(--att-sil)',
                          'bg' => 'var(--att-sil-soft)',
                      ],
                      default => ['color' => 'var(--att-vl)', 'bg' => 'var(--att-vl-soft)'],
                  };
                  $initials = collect(explode(' ', $emp['name']))
                      ->map(fn($w) => strtoupper($w[0] ?? ''))
                      ->take(2)
                      ->implode('');
                @endphp
                <div class="daily-emp-row daily-searchable" data-name="{{ strtolower($emp['name']) }}"
                  data-group="leave">
                  <div class="emp-av av-c{{ $emp['av'] }}">{{ $initials }}</div>
                  <div class="daily-emp-info">
                    <div class="daily-emp-name">{{ $emp['name'] }}</div>
                    <div class="daily-emp-meta">{{ $emp['pos'] }} · {{ $emp['dept'] }}</div>
                  </div>
                  <div class="text-end" style="flex-shrink:0;">
                    <span class="att-badge"
                      style="background:{{ $leaveColor['bg'] }};color:{{ $leaveColor['color'] }};">
                      {{ $emp['leave_type'] }}
                    </span>
                    <div style="font-size:.7rem;color:var(--att-muted);margin-top:3px;">
                      {{ $emp['leave_days'] }} day{{ $emp['leave_days'] > 1 ? 's' : '' }}
                    </div>
                  </div>
                </div>
              @empty
                <div id="leave-empty-state" class="att-empty" style="padding:2rem;">
                  <i class="ri ri-checkbox-circle-line" style="font-size:2rem;color:var(--att-present,#28a745);"></i>
                  <p style="color:var(--att-muted);margin-top:.5rem;font-size:.83rem;">No employees on leave today.</p>
                </div>
              @endforelse
            </div>

          </div>
        </div>

      </div>{{-- /row --}}
    </div>{{-- /#att-data-panels --}}

  </div>{{-- /container --}}

  {{-- Pass config to JS — never hardcode URLs or dates in .js files --}}
  <script>
    window.AttConfig = {
      routes: {
        daily: "{{ route('daily') }}"
      },
      initialDate: "{{ $initialDate }}",
      today: "{{ \Carbon\Carbon::today()->toDateString() }}"
    };
  </script>
  <script src="{{ asset('assets/js/attendance-report.js') }}"></script>

@endsection
