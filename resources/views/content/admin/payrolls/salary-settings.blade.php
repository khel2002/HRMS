@extends('layouts/contentNavbarLayout')

@section('title', 'Employee Salary Settings')

@section('page-style')
  <link rel="stylesheet" href="{{ asset('assets/css/salary-settings.css') }}" />
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div id="ssToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb breadcrumb-style1">
        <li class="breadcrumb-item">
          <a href="{{ route('dashboard') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
          <a href="#">Payroll</a>
        </li>
        <li class="breadcrumb-item active">Employee Salary Settings</li>
      </ol>
    </nav>

    <div class="ss-page-title mb-4">
      <h4 class="fw-bold mb-1">Employee Salary Settings</h4>
      <p class="text-muted mb-0" style="font-size:.88rem;">Configure salary structure and payroll setup for employees</p>
    </div>

    <div class="ss-layout">

      <div class="ss-config-panel card shadow-none border">
        <div class="ss-panel-header card-header border-bottom py-3 px-4">
          <h6 class="fw-bold mb-0" style="font-size:.95rem;">Salary Configuration</h6>
        </div>


        <div class="card-body p-0">
          @include('content.admin.payrolls.employees-field')

          <div class="ss-section">
            <div class="ss-section-title">
              <i class="ri-money-dollar-circle-line"></i>
              Salary Rate
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-sm-6 col-xl-4">
                <label class="ss-label">
                  Monthly Rate <span class="ss-required">*</span>
                </label>
                <div class="ss-input-currency">
                  <span class="ss-currency">₱</span>
                  <input type="number" class="ss-input" id="monthlyRate" name="monthly_rate"
                    value="{{ old('monthly_rate', $salary?->monthly_rate ?? '') }}" step="0.01" min="0"
                    placeholder="0.00">
                </div>
              </div>

              <div class="col-12 col-sm-6 col-xl-4">
                <label class="ss-label">
                  Semi-Monthly Rate
                  <span class="ss-auto-tag">Auto-calculated</span>
                </label>
                <div class="ss-input-currency">
                  <span class="ss-currency">₱</span>
                  <input type="text" class="ss-input ss-readonly" id="semiMonthlyRate"
                    value="{{ old('semi_monthly_rate', $salary?->semi_monthly_rate ?? '') }}" readonly>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-xl-4">
                <label class="ss-label">
                  Daily Rate
                  <span class="ss-auto-tag">Auto-calculated</span>
                </label>
                <div class="ss-input-currency">
                  <span class="ss-currency">₱</span>
                  <input type="text" class="ss-input ss-readonly" id="dailyRate"
                    value="{{ old('daily_rate', $salary?->daily_rate ?? '') }}" readonly>
                </div>
              </div>

            </div>

            <div class="row g-3">

            </div>
          </div>

          <div class="ss-section">
            <div class="ss-section-title">
              <i class="ri-calendar-check-line"></i>
              Payroll Configuration
            </div>

            <div class="row g-3">
              <div class="col-12 col-sm-6 col-xl-3">
                <label class="ss-label">Payroll Type</label>
                <div class="ss-select-wrap">
                  <i class="ri-file-list-line ss-sel-icon"></i>
                  <select class="ss-input ss-select" id="payrollType" name="payroll_type">
                    <option value="semi_monthly"
                      {{ old('payroll_type', $salary?->payroll_type ?? 'semi_monthly') === 'semi_monthly' ? 'selected' : '' }}>
                      Semi-Monthly
                    </option>
                  </select>
                  <i class="ri-arrow-down-s-line ss-sel-chevron"></i>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-xl-3">
                <label class="ss-label">Payment Frequency</label>
                <div class="ss-select-wrap">
                  <i class="ri-repeat-line ss-sel-icon"></i>
                  <select class="ss-input ss-select" id="paymentFrequency" name="payment_frequency">
                    <option value="every_cutoff"
                      {{ old('payment_frequency', $salary?->payment_frequency ?? 'every_cutoff') === 'every_cutoff' ? 'selected' : '' }}>
                      Every Cutoff
                    </option>
                  </select>
                  <i class="ri-arrow-down-s-line ss-sel-chevron"></i>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-xl-3">
                <label class="ss-label">Effective Date <span class="ss-required">*</span></label>
                <div class="ss-input-icon">
                  <input type="date" class="ss-input" id="effectiveDate" name="effective_date"
                    value="{{ old('effective_date', optional($salary?->effective_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                  <i class="ri-calendar-line ss-icon-right"></i>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-xl-3">
                <label class="ss-label">Status</label>
                <div class="ss-toggle-row">
                  <label class="ss-toggle">
                    <input type="checkbox" id="statusToggle" name="is_active" value="1"
                      {{ old('is_active', $salary?->is_active ?? true) ? 'checked' : '' }}>
                    <span class="ss-slider"></span>
                  </label>
                  <span class="ss-toggle-label" id="statusLabel">
                    {{ old('is_active', $salary?->is_active ?? true) ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="ss-section">
            <div class="ss-section-title-row">
              <div>
                <div class="ss-section-title mb-1">
                  <i class="ri-refresh-line"></i>
                  Recurring Items
                </div>
                <p class="text-muted mb-0" style="font-size:.8rem;">
                  Add recurring earnings and deductions applied automatically based on cutoff rules
                </p>
              </div>
              <button type="button" class="ss-btn-add" id="btnAddItem">
                <i class="ri-add-line"></i> Add Item
              </button>
            </div>

            <div class="ss-table-wrap">
              <table class="ss-table" id="recurringTable">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th class="text-end">Amount</th>
                    <th>Apply On</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="recurringBody">
                  {{-- rendered by JS --}}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="ss-form-actions card-footer border-top">
          <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="button" class="btn btn-outline-secondary" id="btnReset">Reset</button>
          <button type="button" class="btn btn-primary ss-btn-save" id="btnSave">
            <i class="ri-save-2-line me-1"></i> Save Salary Settings
          </button>
        </div>
      </div>

      <aside class="ss-preview card shadow-none border">
        <div class="ss-preview-header card-header border-bottom py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <i class="ri-money-dollar-circle-line" style="font-size:1.2rem;color:#696cff;"></i>
            <div>
              <div class="fw-bold" style="font-size:.9rem;">Salary Preview</div>
              <div class="text-muted" style="font-size:.78rem;">Summary of salary breakdown</div>
            </div>
          </div>
        </div>

        <div class="card-body px-4 py-3">
          <div class="ss-preview-rows">
            <div class="ss-preview-row">
              <span class="ss-prev-label">Monthly Rate</span>
              <span class="ss-prev-val" id="prev-monthly">₱0.00</span>
            </div>
            <div class="ss-preview-row">
              <span class="ss-prev-label">Per Cutoff</span>
              <span class="ss-prev-val" id="prev-cutoff">₱0.00</span>
            </div>
            <div class="ss-preview-row">
              <span class="ss-prev-label">Daily Rate</span>
              <span class="ss-prev-val" id="prev-daily">₱0.00</span>
            </div>
            <hr class="my-2">
            <div class="ss-preview-row">
              <span class="ss-prev-label">Payroll Type</span>
              <span class="ss-prev-val fw-semibold" id="prev-type">—</span>
            </div>
            <div class="ss-preview-row">
              <span class="ss-prev-label">Payment Frequency</span>
              <span class="ss-prev-val fw-semibold" id="prev-freq">—</span>
            </div>
            <div class="ss-preview-row">
              <span class="ss-prev-label">Effective Date</span>
              <span class="ss-prev-val fw-semibold" id="prev-date">—</span>
            </div>
          </div>

          <div class="ss-annual-card mt-3">
            <div class="ss-annual-icon">
              <i class="ri-money-dollar-circle-line"></i>
            </div>
            <div>
              <div class="ss-annual-label">
                Estimated Annual Salary
                <i class="ri-information-line ms-1" style="font-size:12px;opacity:.65;cursor:help;"
                  title="Based on monthly rate × 12"></i>
              </div>
              <div class="ss-annual-value" id="prev-annual">₱0.00</div>
            </div>
          </div>

          <div class="ss-reminder mt-3">
            <div class="ss-reminder-head">
              <i class="ri-information-line"></i>
              Cutoff Rules Reminder
            </div>
            <ul class="ss-reminder-list">
              <li>1st Cutoff (26–10): Mandatory benefits will be deducted</li>
              <li>2nd Cutoff (11–25): Loans + recurring earnings/deductions will be applied</li>
            </ul>
          </div>
        </div>
      </aside>
    </div>
  </div>

  @include('content.admin.payrolls.recurring-items-modal')

@endsection

@section('page-script')
  @php
    $salaryJsConfig = [
        'routes' => $salaryConfig['routes'] ?? [],
        'recurringItems' => $recurringItems ?? [],
    ];
  @endphp

  <script>
    window.SalaryConfig = @json($salaryJsConfig);
  </script>

  <script src="{{ asset('assets/js/salary-settings.js') }}"></script>
@endsection
