@extends('layouts/contentNavbarLayout')
@section('title', 'Application for Leave')

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/leave-application.css') }}">
@endsection

@section('content')

  {{-- Toast container (outside form, fixed position) --}}
  <div id="toastContainer"></div>

  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── Page title ─────────────────────────────── --}}
    <div class="page-title d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-0" style="color:#696cff;">Application for Leave</h4>
        <small class="text-muted">RBH Form No. 1 — Fill out all required fields completely.</small>
      </div>
    </div>

    <form action="{{ route('leave-application-store') }}" method="POST" id="leaveAppForm">
      @csrf
      {{-- Synced by JS (refreshTotal) before every submit --}}
      <input type="hidden" name="total_days" id="hiddenTotalDays" value="0">

      {{-- ════════════════════════════════════════════
           SECTION 1 — EMPLOYEE INFORMATION
      ════════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="ri ri-user-3-line text-primary fs-5"></i>
            <div>
              <h6 class="mb-0 fw-bold">Employee Information</h6>
              <small class="text-muted">Office, name, position, and filing details</small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3">

            {{-- ── Employee Search ── --}}
            <div class="col-12">
              <label class="form-label fw-semibold">
                Search Employee
                <span class="text-danger">*</span>
              </label>
              <div class="employee-search-wrap position-relative">
                <div class="input-group">
                  <span class="input-group-text bg-white">
                    <i class="ri ri-search-line text-muted"></i>
                  </span>
                  <input type="text" id="employeeSearchInput" class="form-control"
                    placeholder="Type employee number or name…" autocomplete="off">
                  <button type="button" class="btn btn-outline-secondary" id="clearEmployeeBtn" style="display:none;"
                    title="Clear employee">
                    <i class="ri ri-close-line"></i>
                  </button>
                </div>
                {{-- Dropdown results --}}
                <ul class="employee-search-dropdown list-unstyled mb-0" id="employeeDropdown" style="display:none;"></ul>
              </div>
              {{-- Hidden employee_id posted with the form --}}
              <input type="hidden" name="employee_id" id="selectedEmployeeId">
            </div>

            {{-- ── Auto-populated name fields ── --}}
            <div class="col-12 col-sm-6 col-md-8">
              <label class="form-label">Employee Name</label>
              <div class="row g-2">
                <div class="col-4">
                  <input type="hidden" name="last_name" id="hiddenLastName">
                  <div class="form-control bg-light d-flex flex-column emp-display-field"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body emp-field-value" id="displayLastName"
                      style="font-size:.875rem;">—</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">Last Name</small>
                  </div>
                </div>
                <div class="col-4">
                  <input type="hidden" name="first_name" id="hiddenFirstName">
                  <div class="form-control bg-light d-flex flex-column emp-display-field"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body emp-field-value" id="displayFirstName"
                      style="font-size:.875rem;">—</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">First Name</small>
                  </div>
                </div>
                <div class="col-4">
                  <input type="hidden" name="middle_name" id="hiddenMiddleName">
                  <div class="form-control bg-light d-flex flex-column emp-display-field"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body emp-field-value" id="displayMiddleName"
                      style="font-size:.875rem;">—</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">Middle Name</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
              <label class="form-label">Date of Filing</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri ri-calendar-line"></i></span>
                <input type="text" class="form-control bg-light" id="filingDateDisplay" readonly
                  style="cursor:default;">
                <input type="hidden" name="date_of_filing" id="filingDateHidden">
              </div>
            </div>

            {{-- Position — auto-filled from employee record --}}
            <div class="col-12 col-sm-6 col-md-5">
              <label class="form-label">Position / Designation</label>
              <input type="hidden" name="position_id" id="hiddenPositionId">
              <div class="input-group">
                <span class="input-group-text bg-light">
                  <i class="ri ri-briefcase-line text-muted"></i>
                </span>
                <div class="form-control bg-light d-flex align-items-center" id="displayPosition"
                  style="color:#555;font-size:.875rem;">—</div>
              </div>
              <small class="text-muted" style="font-size:.75rem;">
                Auto-filled from the selected employee record.
              </small>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
              <label class="form-label">Monthly Salary</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="text" name="salary" class="form-control" placeholder="0.00"
                  oninput="formatSalary(this)">
              </div>
            </div>

          </div>
        </div>
      </div>

      {{-- ════════════════════════════════════════════
           SECTION 2 — LEAVE TYPE  |  COMMUTATION
      ════════════════════════════════════════════ --}}
      <div class="row g-4 mb-4">

        {{-- ── Type of Leave (col-lg-8) ── --}}
        <div class="col-12 col-lg-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="ri ri-file-list-3-line text-primary fs-5"></i>
                <div>
                  <h6 class="mb-0 fw-bold">Type of Leave Applied For</h6>
                  <small class="text-muted">Select one or more leave types — tap a card to see requirements</small>
                </div>
              </div>
            </div>
            <div class="card-body">

              {{-- 3-column leave card grid --}}
              <div class="row g-3">

                {{-- Column A --}}
                <div class="col-12 col-sm-6 col-xl-4">
                  <p class="leave-group-label">Vacation / Sick</p>
                  <div class="d-flex flex-column gap-2">

                    <label class="leave-card" id="lc-vacation" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Vacation Leave"
                      data-bs-content="✔ For personal vacation, rest, or recreation.<br><strong>Requirements:</strong><br>• Leave application form<br>• Supervisor approval at least <strong>3 days</strong> in advance<br>• Must have sufficient leave balance">
                      <input type="checkbox" name="leave_type[]" value="vacation" class="leave-input">
                      <div class="leave-card-icon" style="background:#e7edff;color:#696cff;">
                        <i class="ri ri-sun-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Vacation Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                    <label class="leave-card" id="lc-sick" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Sick Leave"
                      data-bs-content="✔ For illness, injury, or medical/dental appointments.<br><strong>Requirements:</strong><br>• Medical certificate (required if &gt;5 consecutive days)<br>• For hospitalization: discharge summary<br>• Notify supervisor as soon as possible">
                      <input type="checkbox" name="leave_type[]" value="sick" class="leave-input">
                      <div class="leave-card-icon" style="background:#e8f5e9;color:#28a745;">
                        <i class="ri ri-heart-pulse-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Sick Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                  </div>
                </div>

                {{-- Column B --}}
                <div class="col-12 col-sm-6 col-xl-4">
                  <p class="leave-group-label">Special Privilege Leave</p>
                  <div class="d-flex flex-column gap-2">

                    <label class="leave-card" id="lc-maternity" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Maternity Leave"
                      data-bs-content="✔ For female employees who are pregnant.<br><strong>Requirements:</strong><br>• Medical certificate with expected delivery date<br>• Notify agency at least <strong>30 days</strong> before<br>• Entitlement: <strong>105 days</strong> normal / <strong>60 days</strong> miscarriage">
                      <input type="checkbox" name="leave_type[]" value="maternity" class="leave-input">
                      <div class="leave-card-icon" style="background:#fce4ec;color:#e91e63;">
                        <i class="ri ri-heart-2-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Maternity Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                    <label class="leave-card" id="lc-paternity" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Paternity Leave"
                      data-bs-content="✔ For married male employees upon delivery or miscarriage of their spouse.<br><strong>Requirements:</strong><br>• Marriage certificate (photocopy)<br>• Birth certificate or hospital record<br>• Must be filed within <strong>60 days</strong> of delivery<br>• Max <strong>7 working days</strong> per delivery (up to 4 deliveries)">
                      <input type="checkbox" name="leave_type[]" value="paternity" class="leave-input">
                      <div class="leave-card-icon" style="background:#f3e8ff;color:#9155fd;">
                        <i class="ri ri-parent-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Paternity Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                  </div>
                </div>

                {{-- Column C --}}
                <div class="col-12 col-sm-6 col-xl-4">
                  <p class="leave-group-label">Other Leave Types</p>
                  <div class="d-flex flex-column gap-2">

                    <label class="leave-card" id="lc-terminal" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Terminal Leave"
                      data-bs-content="✔ Granted upon retirement, resignation, or separation.<br><strong>Requirements:</strong><br>• Approved resignation / retirement papers<br>• Clearance from the agency<br>• Computation of accumulated leave credits<br>• HR and head of agency approval">
                      <input type="checkbox" name="leave_type[]" value="terminal" class="leave-input">
                      <div class="leave-card-icon" style="background:#ede7f6;color:#6f42c1;">
                        <i class="ri ri-logout-box-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Terminal Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                    <label class="leave-card" id="lc-sil" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="bottom" data-bs-html="true" data-bs-title="Service Incentive Leave"
                      data-bs-content="✔ For employees with at least 1 year of service.<br><strong>Requirements:</strong><br>• Leave application form<br>• Supervisor approval<br>• Entitlement: <strong>5 days</strong> per year<br>• File at least <strong>3 days</strong> in advance">
                      <input type="checkbox" name="leave_type[]" value="sil" class="leave-input">
                      <div class="leave-card-icon" style="background:#e8f5e9;color:#28c76f;">
                        <i class="ri ri-briefcase-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Service Incentive Leave</span>
                      </div>
                      <i class="ri ri-checkbox-circle-fill leave-card-check"></i>
                    </label>

                  </div>
                </div>

              </div>{{-- /row g-3 --}}

              {{-- CAUSE textarea --}}
              <div class="mt-4 pt-3 border-top">
                <label class="form-label fw-semibold" id="leaveDetailsLabel">
                  CAUSE:
                  <span class="text-warning fw-normal" style="font-size:.82rem;">
                    (Whether illness, Personal, Resignation etc.)
                  </span>
                </label>
                <textarea name="leave_details" id="leaveDetailsInput" class="form-control" rows="3"
                  placeholder="Enter any additional information related to your leave application…"></textarea>
              </div>

            </div>{{-- /card-body --}}
          </div>{{-- /card --}}
        </div>{{-- /col-lg-8 --}}

        {{-- ── Commutation (col-lg-4) ── --}}
        <div class="col-12 col-lg-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="ri ri-exchange-funds-line text-primary fs-5"></i>
                <div>
                  <h6 class="mb-0 fw-bold">Commutation of Leave Credits</h6>
                  <small class="text-muted">Indicate whether commutation is requested</small>
                </div>
              </div>
            </div>
            <div class="card-body d-flex flex-column">

              <p class="text-muted mb-3" style="font-size:.82rem;line-height:1.6;">
                Commutation converts unused leave credits into their monetary equivalent.
                Select <strong>Requested</strong> only if you wish to apply commutation
                together with this leave application.
              </p>

              <div class="d-flex flex-column gap-2">

                <label class="leave-card commut-card" id="cc-not-requested">
                  <input type="radio" name="commutation" value="not_requested" class="leave-input"
                    id="commutNotRequested" checked>
                  <div class="leave-card-icon" style="background:#f0f0ff;color:#696cff;">
                    <i class="ri ri-close-circle-line"></i>
                  </div>
                  <div class="leave-card-text">
                    <span class="leave-card-name">Not Requested</span>
                    <span class="leave-card-desc">No commutation for this leave</span>
                  </div>
                  <i class="ri ri-radio-button-fill leave-card-check"></i>
                </label>

                <label class="leave-card commut-card" id="cc-requested">
                  <input type="radio" name="commutation" value="requested" class="leave-input"
                    id="commutRequested">
                  <div class="leave-card-icon" style="background:#e8f5e9;color:#28a745;">
                    <i class="ri ri-check-double-line"></i>
                  </div>
                  <div class="leave-card-text">
                    <span class="leave-card-name">Requested</span>
                    <span class="leave-card-desc">Apply commutation of unused leave credits</span>
                  </div>
                  <i class="ri ri-radio-button-fill leave-card-check"></i>
                </label>

              </div>

              <div class="alert alert-warning d-flex gap-2 mt-auto mb-0 py-2 px-3" style="font-size:.78rem;">
                <i class="ri ri-information-line flex-shrink-0 mt-1"></i>
                <span>
                  Commutation is subject to HR approval and available budget.
                  Ensure your leave balance is sufficient before requesting.
                </span>
              </div>

            </div>
          </div>
        </div>{{-- /col-lg-4 --}}

      </div>{{-- /row g-4 --}}

      {{-- ════════════════════════════════════════════
           SECTION 3 — LEAVE DURATION & DATES
      ════════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
          {{-- Header row: title left, total pill right — stacks on mobile via .duration-header --}}
          <div class="duration-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
              <i class="ri ri-calendar-event-line text-primary fs-5"></i>
              <div>
                <h6 class="mb-0 fw-bold">Leave Duration &amp; Inclusive Dates</h6>
                <small class="text-muted">Sundays are automatically excluded from the working-day count</small>
              </div>
            </div>
            <div class="total-days-pill">
              <i class="ri ri-time-line" style="color:#696cff;"></i>
              <span style="font-size:.82rem;font-weight:600;color:#696cff;">Total Working Days:</span>
              <span id="totalDaysDisplay"
                style="font-size:1.25rem;font-weight:800;color:#696cff;min-width:2rem;text-align:center;">
                0
              </span>
            </div>
          </div>
        </div>

        <div class="card-body">

          {{-- Whole day range --}}
          <div class="mb-4">
            <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.72rem;letter-spacing:.07em;">
              <i class="ri ri-calendar-2-line me-1"></i> Whole Day(s)
            </p>
            <div class="row g-3 align-items-end">
              <div class="col-12 col-sm-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="whole_day_from" id="wholeDayFrom" class="form-control"
                  onchange="computeWholeDays()">
              </div>
              <div class="col-12 col-sm-4">
                <label class="form-label">End Date</label>
                <input type="date" name="whole_day_to" id="wholeDayTo" class="form-control"
                  onchange="computeWholeDays()">
              </div>
              <div class="col-12 col-sm-4">
                <label class="form-label">Computed Working Days</label>
                <div class="input-group">
                  <span class="input-group-text bg-light">
                    <i class="ri ri-calculator-line text-muted"></i>
                  </span>
                  <div class="form-control bg-light d-flex align-items-center" id="wholeDayResult"
                    style="font-weight:600;color:#696cff;">
                    —
                  </div>
                </div>
              </div>
            </div>
            <div id="wholeDayError" class="text-danger mt-1" style="font-size:.82rem;display:none;">
              <i class="ri ri-error-warning-line me-1"></i>
              End date must be on or after the start date.
            </div>
          </div>

          {{-- Half day --}}
          <div>
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
              <p class="text-uppercase fw-bold text-muted mb-0" style="font-size:.72rem;letter-spacing:.07em;">
                <i class="ri ri-calendar-check-line me-1"></i> Half Day(s)
              </p>
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="addHalfDay()">
                <i class="ri ri-add-line me-1"></i> Add Half Day
              </button>
            </div>
            <div id="halfDayContainer">
              <div id="halfDayEmpty" class="text-muted d-flex align-items-center gap-2 py-2" style="font-size:.83rem;">
                <i class="ri ri-calendar-line opacity-50 fs-5"></i>
                No half-day entries added yet. Click
                <strong class="mx-1">Add Half Day</strong> to include one.
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- ════════════════════════════════════════════
           FOOTER — Certification & Submit
      ════════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <div class="alert alert-info d-flex align-items-start gap-2 mb-3 py-2 px-3" style="font-size:.82rem;">
            <i class="ri ri-shield-check-line flex-shrink-0 mt-1"></i>
            <span>
              I certify on my honor that the information provided above is true and correct,
              and that my absence for the period stated will not be prejudicial to the interest
              of public service.
            </span>
          </div>
          <div class="form-footer-actions d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="resetLeaveForm()">
              <i class="ri ri-refresh-line me-1"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="ri ri-send-plane-fill me-1"></i> Submit Application
            </button>
          </div>
        </div>
      </div>

    </form>

  </div>{{-- /container --}}

  {{-- ── Scripts ────────────────────────────────────────────────── --}}
  {{-- Form wizard (popovers, card selection, date calc, AJAX submit) --}}
  <script src="{{ asset('assets/js/leave-application.js') }}"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── Popovers ─────────────────────────────────────────────
      document.querySelectorAll('.leave-card[data-bs-toggle="popover"]').forEach(function(el) {
        new bootstrap.Popover(el, {
          sanitize: false
        });
      });

      // ── Commutation — pre-highlight the default checked card ──
      document.querySelectorAll('.commut-card').forEach(function(c) {
        c.classList.toggle('lc-selected', c.querySelector('.leave-input')?.checked ?? false);
      });

    });
  </script>

@endsection
