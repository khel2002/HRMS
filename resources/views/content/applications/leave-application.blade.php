@extends('layouts/contentNavbarLayout')
@section('title', 'Application for Leave')

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/leave-application.css') }}">
@endsection

@section('content')

  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- Page title --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-0" style="color:#696cff;">Application for Leave</h4>
        <small class="text-muted">RBH Form No. 1 — Fill out all required fields completely.</small>
      </div>
    </div>

    <form action="" method="POST" id="leaveAppForm">
      @csrf

      {{-- ══════════════════════════════════════════
           SECTION 1 — EMPLOYEE INFORMATION
      ══════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="ri ri-user-3-line text-primary fs-5"></i>
            <div>
              <h6 class="mb-0 fw-bold">Employee Information</h6>
              <small class="text-muted">Office, name, position, and filing details</small>
            </div>
          </div>
        </div>
        <div class="card-body px-4 py-4">
          <div class="row g-3">

            <div class="col-md-4">
              <label class="form-label">Office / Department</label>
              <select name="department" class="form-select">
                <option value="">— Select Department —</option>
                <option>Admin</option>
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Employee Name</label>
              <div class="row g-2">
                <div class="col-md-4">
                  <input type="hidden" name="last_name" value="Roa">
                  <div class="form-control bg-light d-flex flex-column"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body" style="font-size:.875rem;">Roa</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">Last Name</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <input type="hidden" name="first_name" value="Jun Michael">
                  <div class="form-control bg-light d-flex flex-column"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body" style="font-size:.875rem;">Jun Michael</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">First Name</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <input type="hidden" name="middle_name" value="Melendres">
                  <div class="form-control bg-light d-flex flex-column"
                    style="height:auto;min-height:38px;padding:.5rem .75rem;">
                    <span class="fw-semibold text-body" style="font-size:.875rem;">Melendres</span>
                    <small class="text-muted" style="font-size:.72rem;line-height:1.2;">Middle Name</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Date of Filing</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri ri-calendar-line"></i></span>
                <input type="text" class="form-control bg-light" id="filingDateDisplay" readonly
                  style="cursor:default;">
                <input type="hidden" name="date_of_filing" id="filingDateHidden">
              </div>
            </div>

            <div class="col-md-5">
              <label class="form-label">Position / Designation</label>
              <input type="text" name="position" class="form-control" placeholder="e.g. Administrative Officer II">
            </div>

            <div class="col-md-4">
              <label class="form-label">Monthly Salary</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="text" name="salary" class="form-control" placeholder="0.00" oninput="formatSalary(this)">
              </div>
            </div>

          </div>
        </div>
      </div>

      {{-- ══════════════════════════════════════════
           SECTION 2 — LEAVE TYPE  |  COMMUTATION
      ══════════════════════════════════════════ --}}
      <div class="row g-4 mb-4">

        {{-- ── LEFT: Type of Leave (col-lg-8) ── --}}
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom px-4 py-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri ri-file-list-3-line text-primary fs-5"></i>
                <div>
                  <h6 class="mb-0 fw-bold">Type of Leave Applied For</h6>
                  <small class="text-muted">Select one leave type — hover a card to see requirements</small>
                </div>
              </div>
            </div>
            <div class="card-body px-4 py-4">

              {{-- ════════════════════════════
                   LEAVE CARDS — 3 columns
                   All use type="radio" name="leave_type"
                   so only one can be active at a time.
              ════════════════════════════ --}}
              <div class="row g-3">

                {{-- ── Column A: Vacation / Sick / Forced ── --}}
                <div class="col-sm-6 col-xl-4">
                  <p class="leave-group-label">Vacation / Sick / Forced</p>
                  <div class="d-flex flex-column gap-2">

                    <label class="leave-card" id="lc-vacation" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="right" data-bs-html="true" data-bs-title="Vacation Leave"
                      data-bs-content="✔ For personal vacation, rest, or recreation.<br><strong>Requirements:</strong><br>• Leave application form<br>• Supervisor approval at least <strong>3 days</strong> in advance<br>• Must have sufficient leave balance">
                      <input type="radio" name="leave_type" value="vacation" class="leave-input">
                      <div class="leave-card-icon" style="background:#e7edff;color:#696cff;">
                        <i class="ri ri-sun-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Vacation Leave</span>
                      </div>
                      <i class="ri ri-radio-button-fill leave-card-check"></i>
                    </label>

                    <label class="leave-card" id="lc-sick" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="right" data-bs-html="true" data-bs-title="Sick Leave"
                      data-bs-content="✔ For illness, injury, or medical/dental appointments.<br><strong>Requirements:</strong><br>• Medical certificate from a licensed physician<br>&nbsp;&nbsp;(required if absence exceeds <strong>5 consecutive days</strong>)<br>• For hospitalization: hospital discharge summary<br>• Notify supervisor as soon as possible">
                      <input type="radio" name="leave_type" value="sick" class="leave-input">
                      <div class="leave-card-icon" style="background:#e8f5e9;color:#28a745;">
                        <i class="ri ri-heart-pulse-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Sick Leave</span>
                      </div>
                      <i class="ri ri-radio-button-fill leave-card-check"></i>
                    </label>

                  </div>
                </div>

                {{-- ── Column B: Special Privilege Leave ── --}}
                <div class="col-sm-6 col-xl-4">
                  <p class="leave-group-label">Special Privilege Leave</p>
                  <div class="d-flex flex-column gap-2">

                    <label class="leave-card" id="lc-maternity" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="right" data-bs-html="true" data-bs-title="Maternity Leave"
                      data-bs-content="✔ For female employees who are pregnant.<br><strong>Requirements:</strong><br>• Medical certificate indicating expected date of delivery<br>• Notify agency at least <strong>30 days</strong> before<br>• Entitlement: <strong>105 days</strong> (normal); <strong>60 days</strong> (miscarriage)<br>• Additional 30 days without pay upon request">
                      <input type="radio" name="leave_type" value="maternity" class="leave-input">
                      <div class="leave-card-icon" style="background:#fce4ec;color:#e91e63;">
                        <i class="ri ri-heart-2-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Maternity Leave</span>
                      </div>
                      <i class="ri ri-radio-button-fill leave-card-check"></i>
                    </label>

                    <label class="leave-card" id="lc-paternity" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="right" data-bs-html="true" data-bs-title="Paternity Leave"
                      data-bs-content="✔ For married male employees upon delivery or miscarriage of their legitimate spouse.<br><strong>Requirements:</strong><br>• Marriage certificate (photocopy)<br>• Birth certificate or hospital record of delivery<br>• Must be filed within <strong>60 days</strong> of delivery<br>• Maximum of <strong>7 working days</strong> per delivery (up to 4 deliveries)">
                      <input type="radio" name="leave_type" value="paternity" class="leave-input">
                      <div class="leave-card-icon" style="background:#f3e8ff;color:#9155fd;">
                        <i class="ri ri-parent-line"></i>
                      </div>
                      <div class="leave-card-text">
                        <span class="leave-card-name">Paternity Leave</span>
                      </div>
                      <i class="ri ri-radio-button-fill leave-card-check"></i>


                  </div>
                </div>

                {{-- ── Column C: Other Leave Types ── --}}
                <div class="col-sm-6 col-xl-4">
                  <p class="leave-group-label">Other Leave Types</p>
                  <div class="d-flex flex-column gap-2">
                    <label class="leave-card" id="lc-terminal" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="left" data-bs-html="true" data-bs-title="Terminal Leave"
                      data-bs-content="✔ Leave granted to employees who are retiring, resigning, or separating from service.<br><strong>Requirements:</strong><br>• Approved resignation or retirement papers<br>• Clearance from the agency<br>• Computation of accumulated leave credits<br>• HR and head of agency approval required">

                      <input type="radio" name="leave_type" value="terminal" class="leave-input">

                      <div class="leave-card-icon" style="background:#ede7f6;color:#6f42c1;">
                        <i class="ri ri-logout-box-line"></i>
                      </div>

                      <div class="leave-card-text">
                        <span class="leave-card-name">Terminal Leave</span>
                      </div>

                      <i class="ri ri-radio-button-fill leave-card-check"></i>
                    </label>
                    <label class="leave-card" id="lc-sil" data-bs-toggle="popover" data-bs-trigger="hover focus"
                      data-bs-placement="right" data-bs-html="true" data-bs-title="Service Incentive Leave"
                      data-bs-content="✔ Leave granted to employees who have rendered at least one year of service.<br><strong>Requirements:</strong><br>• Leave application form<br>• Supervisor approval<br>• Entitlement: <strong>5 days</strong> per year<br>• Must be filed at least <strong>3 days</strong> in advance">

                      <input type="radio" name="leave_type" value="sil" class="leave-input">

                      <div class="leave-card-icon" style="background:#e8f5e9;color:#28c76f;">
                        <i class="ri ri-briefcase-line"></i>
                      </div>

                      <div class="leave-card-text">
                        <span class="leave-card-name">Service Incentive Leave</span>
                      </div>

                      <i class="ri ri-radio-button-fill leave-card-check"></i>
                    </label>

                  </div>
                </div>

              </div>{{-- /row g-3 --}}

              {{-- ════════════════════════════════════════
                   DETAILS / REMARKS TEXT INPUT
                   Always visible below the cards.
                   The label and hint text update dynamically
                   based on the selected leave type.
              ════════════════════════════════════════ --}}
              <div class="mt-4 pt-3 border-top">
                <label class="form-label fw-semibold" id="leaveDetailsLabel">
                  <p>CAUSE: <code class="text-warning">(Whether illness, Pesonal, Resignation etc. )</code></p>
                </label>
                <textarea name="leave_details" id="leaveDetailsInput" class="form-control" rows="3"
                  placeholder="Enter any additional information related to your leave application…"></textarea>
              </div>

            </div>{{-- /card-body --}}
          </div>{{-- /card --}}
        </div>{{-- /col-lg-8 --}}

        {{-- ── RIGHT: Commutation (col-lg-4) ── --}}
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom px-4 py-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri ri-exchange-funds-line text-primary fs-5"></i>
                <div>
                  <h6 class="mb-0 fw-bold">Commutation of Leave Credits</h6>
                  <small class="text-muted">Indicate whether commutation is requested</small>
                </div>
              </div>
            </div>
            <div class="card-body px-4 py-4 d-flex flex-column">

              <p class="text-muted mb-3" style="font-size:.82rem;line-height:1.6;">
                Commutation allows the conversion of unused leave credits into their monetary
                equivalent. Select <strong>Requested</strong> only if you wish to apply for
                commutation together with this leave application.
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

              <div class="alert alert-warning d-flex gap-2 mt-auto pt-4 mb-0 py-2 px-3" style="font-size:.78rem;">
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

      {{-- ══════════════════════════════════════════
           SECTION 3 — LEAVE DURATION & DATES
      ══════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom px-4 py-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <i class="ri ri-calendar-event-line text-primary fs-5"></i>
              <div>
                <h6 class="mb-0 fw-bold">Leave Duration &amp; Inclusive Dates</h6>
                <small class="text-muted">Sundays are automatically excluded from the working-day count</small>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded"
              style="background:#e7edff;border:1px solid #c5ceff;">
              <i class="ri ri-time-line" style="color:#696cff;"></i>
              <span style="font-size:.82rem;font-weight:600;color:#696cff;">
                Total Working Days Applied For:
              </span>
              <span id="totalDaysDisplay"
                style="font-size:1.25rem;font-weight:800;color:#696cff;min-width:2.5rem;text-align:center;">
                0
              </span>
            </div>
          </div>
        </div>
        <div class="card-body px-4 py-4">

          <div class="mb-4">
            <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.72rem;letter-spacing:.07em;">
              <i class="ri ri-calendar-2-line me-1"></i> Whole Day(s)
            </p>
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="whole_day_from" id="wholeDayFrom" class="form-control"
                  onchange="computeWholeDays()">
              </div>
              <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="whole_day_to" id="wholeDayTo" class="form-control"
                  onchange="computeWholeDays()">
              </div>
              <div class="col-md-4">
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

          <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
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

      {{-- ══════════════════════════════════════════
           FOOTER — Certification & Submit
      ══════════════════════════════════════════ --}}
      <div class="card border-0 shadow-sm">
        <div class="card-body px-4 py-3">
          <div class="alert alert-info d-flex align-items-start gap-2 mb-3 py-2 px-3" style="font-size:.82rem;">
            <i class="ri ri-shield-check-line flex-shrink-0 mt-1"></i>
            <span>
              I certify on my honor that the information provided above is true and correct, and that
              my absence for the period stated will not be prejudicial to the interest of public service.
            </span>
          </div>
          <div class="d-flex justify-content-end gap-2">
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
  </div>

  <script src="{{ asset('assets/js/leave-application.js') }}"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── Popovers ──────────────────────────────────────────────
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
