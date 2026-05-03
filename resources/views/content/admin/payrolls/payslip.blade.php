@extends('layouts/contentNavbarLayout')

@section('title', 'Payslip')

@section('page-style')
  <link rel="stylesheet" href="{{ asset('assets/css/payslip.css') }}" />
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    @php
      /* ─── Helpers ─────────────────────────────────────────────── */
      $peso = fn($v) => '₱' . number_format((float) $v, 2);

      /* ─── Route base ──────────────────────────────────────────── */
      $routeBase = $payslipRoutes['baseName'] ?? 'payslip';
      $backUrl = match ($routeBase) {
          'hr.payslip' => route('HR.Dashboard'),
          'employees.payslip' => route('employees.dashboard'),
          default => route('dashboard'),
      };

      /* ─── Payslip data (may be null when no employee selected) ── */
      $p = $payslipData ?? null;
      $hasPayroll = (bool) ($p['hasPayroll'] ?? false);
      $c1data = $p['first'] ?? [];
      $c2data = $p['second'] ?? [];
      $combined = $p['combined'] ?? ['gross' => 0, 'deductions_total' => 0, 'net' => 0];

      /* ─── Status badge ────────────────────────────────────────── */
      $statusRaw = (string) ($c2data['status'] ?? ($c1data['status'] ?? ''));
      $badge = match (true) {
          !$hasPayroll => [
              'cls' => 'ps-badge-pending',
              'txt' => 'Not Processed',
              'icon' => 'ri-time-line',
          ],
          in_array($statusRaw, ['released', 'approved'], true) => [
              'cls' => 'ps-badge-paid',
              'txt' => \Illuminate\Support\Str::title($statusRaw),
              'icon' => 'ri-checkbox-circle-line',
          ],
          $statusRaw === 'computed' => [
              'cls' => 'ps-badge-processing',
              'txt' => 'Computed',
              'icon' => 'ri-calculator-line',
          ],
          $statusRaw !== '' => [
              'cls' => 'ps-badge-pending',
              'txt' => \Illuminate\Support\Str::title($statusRaw),
              'icon' => 'ri-information-line',
          ],
          default => [
              'cls' => 'ps-badge-processing',
              'txt' => 'Computed',
              'icon' => 'ri-calculator-line',
          ],
      };

      /* ─── Government badge helper ─────────────────────────────── */
      $contribBadge = function ($label) {
          $l = strtolower((string) $label);
          return match (true) {
              str_contains($l, 'sss') => ['cls' => 'sss', 'txt' => 'SSS'],
              str_contains($l, 'phil') => ['cls' => 'phic', 'txt' => 'PHIC'],
              str_contains($l, 'pag') || str_contains($l, 'hdmf') => ['cls' => 'pagibig', 'txt' => 'HDMF'],
              str_contains($l, 'tax') => ['cls' => 'tax', 'txt' => 'TAX'],
              default => null,
          };
      };
    @endphp

    {{-- ── BREADCRUMB ──────────────────────────────────────────────── --}}
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb breadcrumb-style1">
        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="#">Payroll</a></li>
        <li class="breadcrumb-item active">Payslip</li>
      </ol>
    </nav>

    {{-- ── TOP ACTION BAR ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
          <i class="ri ri-arrow-left-line"></i> Back
        </a>
        <div>
          <h4 class="fw-bold mb-0" style="font-size:1.15rem;">Employee Payslip</h4>
          <p class="text-muted mb-0" style="font-size:.8rem;">
            {{ $p['periodLabel'] ?? \Carbon\Carbon::create($year ?? now()->year, $month ?? now()->month, 1)->format('F Y') }}
            — Semi-Monthly
          </p>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="btnEmail"
          {{ empty($selectedEmployee) ? 'disabled' : '' }}>
          <i class="ri ri-mail-line"></i> Send Email
        </button>
        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
          {{ empty($selectedEmployee) ? 'disabled' : '' }}>
          <i class="ri ri-printer-line"></i> Print
        </button>
      </div>
    </div>

    {{-- ── FILTERS / PROCESS ───────────────────────────────────────── --}}
    <div class="card border shadow-none mb-4">
      <div class="card-body px-4 py-3">
        <div class="row g-3 align-items-end">

          {{-- Employee --}}
          <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Employee</label>
            @if (!empty($canSelectEmployee))
              <select class="form-select" id="psSelectEmployee">
                <option value="" {{ empty($selectedEmployee) ? 'selected' : '' }}>Select an employee…</option>
                @foreach ($employees ?? [] as $emp)
                  <option value="{{ $emp->id }}" {{ $selectedEmployee?->id === $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }} ({{ $emp->employee_number }})
                  </option>
                @endforeach
              </select>
            @else
              <input class="form-control" value="{{ $selectedEmployee?->full_name ?? '—' }}" readonly>
            @endif
            <div class="form-text" style="font-size:.78rem;">
              Select a payroll period below, then click <strong>Process</strong> to compute the payslip.
            </div>
          </div>

          {{-- Year --}}
          <div class="col-6 col-lg-2 py-5">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Year</label>
            <select class="form-select" id="psYear">
              @foreach (range(now()->year, now()->year - 6) as $yr)
                <option value="{{ $yr }}" {{ (int) ($year ?? now()->year) === (int) $yr ? 'selected' : '' }}>
                  {{ $yr }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Month --}}
          <div class="col-6 col-lg-2 py-5">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Month</label>
            <select class="form-select" id="psMonth">
              @foreach (range(1, 12) as $m)
                <option value="{{ $m }}" {{ (int) ($month ?? now()->month) === (int) $m ? 'selected' : '' }}>
                  {{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Process button --}}
          <div class="col-12 col-lg-2 d-grid py-6">
            <form method="POST" action="{{ $payslipRoutes['process'] ?? '#' }}" id="psProcessForm">
              @csrf
              @if (!empty($canSelectEmployee))
                <input type="hidden" name="employee_id" id="psProcessEmployeeId"
                  value="{{ $selectedEmployee?->id ?? '' }}">
              @endif
              <input type="hidden" name="year" id="psProcessYear" value="{{ $year ?? now()->year }}">
              <input type="hidden" name="month" id="psProcessMonth" value="{{ $month ?? now()->month }}">
              <button type="submit" class="btn btn-primary w-100" id="psBtnProcess"
                {{ empty($selectedEmployee) ? 'disabled' : '' }}>
                <i class="ri ri-calculator-line me-1"></i> Process
              </button>
            </form>
          </div>

        </div>
      </div>
    </div>

    {{-- ── MAIN LAYOUT ─────────────────────────────────────────────── --}}
    <div class="ps-layout">

      {{-- ════ LEFT: PAYSLIP CONTENT ══════════════════════════════════ --}}
      <div class="ps-main">

        {{-- ── EMPLOYEE HEADER CARD ──────────────────────────────────── --}}
        <div class="card ps-header-card border shadow-none mb-4">
          <div class="ps-header-bg"></div>
          <div class="card-body pt-0 pb-4 px-4">
            <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">

              {{-- Avatar + Name --}}
              <div class="d-flex align-items-center gap-3 mt-3">
                @php
                  $psAvatar = $selectedEmployee
                      ? strtoupper(
                          substr($selectedEmployee->first_name ?? '', 0, 1) .
                              substr($selectedEmployee->last_name ?? '', 0, 1),
                      )
                      : '--';
                @endphp
                <div class="ps-avatar">{{ $psAvatar }}</div>
                <div>
                  <h5 class="fw-bold mb-0" style="font-size:1.1rem;">
                    {{ $selectedEmployee?->full_name ?? 'Select an employee' }}
                  </h5>
                  <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                    <span class="ps-meta-chip">
                      <i class="ri ri-id-card-line"></i>
                      {{ $selectedEmployee?->employee_number ?? '—' }}
                    </span>
                    <span class="ps-meta-chip">
                      <i class="ri ri-building-line"></i>
                      {{ $selectedEmployee?->office?->office_name ?? '—' }}
                    </span>
                    <span class="ps-meta-chip">
                      <i class="ri ri-briefcase-line"></i>
                      {{ $selectedEmployee?->position?->position_name ?? '—' }}
                    </span>
                  </div>
                </div>
              </div>

              {{-- Period + Status --}}
              <div class="text-end">
                <span class="badge {{ $badge['cls'] }} mb-2">
                  <i class="ri {{ $badge['icon'] }} me-1"></i> {{ $badge['txt'] }}
                </span>
                <div class="ps-period-info">
                  <div class="ps-period-label">Payroll Period</div>
                  <div class="ps-period-val">{{ $p['monthRange'] ?? '—' }}</div>
                </div>
                <div class="ps-period-info mt-1">
                  <div class="ps-period-label">Payroll Type</div>
                  <div class="ps-period-val">Semi-Monthly (2 Cutoffs)</div>
                </div>
              </div>

            </div>
          </div>
        </div>

        {{-- ── CONTENT: no employee selected ──────────────────────── --}}
        @if (empty($selectedEmployee))
          <div class="card border shadow-none mb-4">
            <div class="card-body px-4 py-4 text-center">
              <div class="mb-2" style="font-size:1.75rem;color:#a8aaae;">
                <i class="ri ri-file-list-3-line"></i>
              </div>
              <h6 class="fw-bold mb-1">No employee selected</h6>
              <p class="text-muted mb-0" style="font-size:.9rem;">
                Choose an employee from the dropdown above to display their payslip.
              </p>
            </div>
          </div>
        @else
          {{-- Validation errors --}}
          @if ($errors->has('payslip'))
            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
              <i class="ri ri-error-warning-line mt-1"></i>
              <div>{{ $errors->first('payslip') }}</div>
            </div>
          @endif

          {{-- Not processed yet --}}
          @if (!$hasPayroll)
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
              <i class="ri ri-information-line mt-1"></i>
              <div>
                <div class="fw-semibold">Payslip not processed yet</div>
                <div style="font-size:.9rem;">
                  Select the period and click <strong>Process</strong> to compute earnings and deductions.
                </div>
              </div>
            </div>
          @endif

          {{-- ── CUTOFF TABS ──────────────────────────────────────── --}}
          <div class="card border shadow-none mb-4">
            <div class="card-body p-0">

              {{-- Tab nav --}}
              <div class="ps-tab-nav">
                <button class="ps-tab-btn active" data-tab="cutoff1">
                  <i class="ri ri-calendar-line"></i>
                  <div>
                    <div class="ps-tab-title">1st Cutoff</div>
                    <div class="ps-tab-sub">
                      {{ $c1data['periodRange'] ?? '—' }} · Government Deductions
                    </div>
                  </div>
                </button>
                <button class="ps-tab-btn" data-tab="cutoff2">
                  <i class="ri ri-calendar-check-line"></i>
                  <div>
                    <div class="ps-tab-title">2nd Cutoff</div>
                    <div class="ps-tab-sub">
                      {{ $c2data['periodRange'] ?? '—' }} · Earnings + Deductions
                    </div>
                  </div>
                </button>
              </div>

              {{-- ══ 1ST CUTOFF PANEL ════════════════════════════════ --}}
              <div class="ps-tab-panel active" id="tab-cutoff1">
                <div class="px-4 pb-4 pt-3">

                  <div class="ps-period-banner mb-4">
                    <i class="ri ri-information-line"></i>
                    <span>
                      1st Cutoff{{ !empty($c1data['periodRange']) ? ': ' . $c1data['periodRange'] : '' }}
                      — Basic salary for the cutoff + mandatory government deductions.
                    </span>
                  </div>

                  <div class="row g-4">

                    {{-- Earnings --}}
                    <div class="col-12 col-lg-6">
                      <div class="ps-section-head earnings">
                        <i class="ri ri-arrow-up-circle-line"></i>
                        <span>Earnings</span>
                      </div>
                      <div class="ps-line-table">
                        @forelse(($c1data['earnings'] ?? []) as $it)
                          <div class="ps-line-row">
                            <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                            <span class="ps-line-val earning">{{ $peso($it->amount ?? 0) }}</span>
                          </div>
                        @empty
                          <div class="ps-line-row">
                            <span class="ps-line-label text-muted fst-italic">No earnings computed.</span>
                            <span class="ps-line-val earning">{{ $peso(0) }}</span>
                          </div>
                        @endforelse
                        <div class="ps-line-row highlight">
                          <span class="ps-line-label fw-bold">Gross Pay</span>
                          <span class="ps-line-val earning fw-bold fs-6">{{ $peso($c1data['gross'] ?? 0) }}</span>
                        </div>
                      </div>
                    </div>

                    {{-- Deductions --}}
                    <div class="col-12 col-lg-6">
                      <div class="ps-section-head deductions">
                        <i class="ri ri-arrow-down-circle-line"></i>
                        <span>Government Deductions</span>
                      </div>
                      <div class="ps-line-table">
                        @forelse(($c1data['deductions'] ?? []) as $it)
                          @php $b = $contribBadge($it->label ?? ''); @endphp
                          <div class="ps-line-row">
                            <div class="ps-line-label-wrap">
                              @if ($b)
                                <span class="ps-contrib-badge {{ $b['cls'] }}">{{ $b['txt'] }}</span>
                              @endif
                              <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                            </div>
                            <span class="ps-line-val deduction">–{{ $peso($it->amount ?? 0) }}</span>
                          </div>
                        @empty
                          <div class="ps-line-row">
                            <span class="ps-line-label text-muted fst-italic">No deductions configured.</span>
                            <span class="ps-line-val deduction">–{{ $peso(0) }}</span>
                          </div>
                        @endforelse
                        <div class="ps-line-row highlight">
                          <span class="ps-line-label fw-bold">Total Deductions</span>
                          <span class="ps-line-val deduction fw-bold fs-6">
                            –{{ $peso($c1data['deductions_total'] ?? 0) }}
                          </span>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="ps-net-bar mt-4">
                    <div class="ps-net-left">
                      <div class="ps-net-label">1st Cutoff Net Pay</div>
                      <div class="ps-net-sub">Basic pay less mandatory deductions</div>
                    </div>
                    <div class="ps-net-amount">{{ $peso($c1data['net'] ?? 0) }}</div>
                  </div>

                </div>
              </div>

              {{-- ══ 2ND CUTOFF PANEL ════════════════════════════════ --}}
              <div class="ps-tab-panel" id="tab-cutoff2">
                <div class="px-4 pb-4 pt-3">

                  <div class="ps-period-banner variant mb-4">
                    <i class="ri ri-information-line"></i>
                    <span>
                      2nd Cutoff{{ !empty($c2data['periodRange']) ? ': ' . $c2data['periodRange'] : '' }}
                      — Basic salary for the cutoff plus recurring earnings and deductions.
                    </span>
                  </div>

                  <div class="row g-4">

                    {{-- Earnings --}}
                    <div class="col-12 col-lg-6">
                      <div class="ps-section-head earnings">
                        <i class="ri ri-arrow-up-circle-line"></i>
                        <span>Earnings</span>
                      </div>
                      <div class="ps-line-table">
                        @forelse(($c2data['earnings'] ?? []) as $it)
                          <div class="ps-line-row">
                            <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                            <span class="ps-line-val earning">{{ $peso($it->amount ?? 0) }}</span>
                          </div>
                        @empty
                          <div class="ps-line-row">
                            <span class="ps-line-label text-muted fst-italic">No earnings computed.</span>
                            <span class="ps-line-val earning">{{ $peso(0) }}</span>
                          </div>
                        @endforelse
                        <div class="ps-line-row highlight">
                          <span class="ps-line-label fw-bold">Gross Pay</span>
                          <span class="ps-line-val earning fw-bold fs-6">{{ $peso($c2data['gross'] ?? 0) }}</span>
                        </div>
                      </div>
                    </div>

                    {{-- Deductions grouped --}}
                    <div class="col-12 col-lg-6">
                      <div class="ps-section-head deductions">
                        <i class="ri ri-arrow-down-circle-line"></i>
                        <span>Deductions</span>
                      </div>
                      <div class="ps-line-table">
                        @php
                          $ded2All = collect($c2data['deductions'] ?? []);
                          $isLoan = fn($lbl) => str_contains(strtolower((string) $lbl), 'loan');
                          $loan2 = $ded2All->filter(fn($i) => $isLoan($i->label ?? ''))->values();
                          $nonLoan2 = $ded2All->reject(fn($i) => $isLoan($i->label ?? ''));

                          $isCashAdv = fn($lbl) => str_contains(strtolower((string) $lbl), 'cash advance');
                          $isAttendance = fn($lbl) => str_contains(strtolower((string) $lbl), 'late') ||
                              str_contains(strtolower((string) $lbl), 'undertime');

                          $cash2 = $nonLoan2->filter(fn($i) => $isCashAdv($i->label ?? ''))->values();
                          $att2 = $nonLoan2
                              ->filter(fn($i) => $isAttendance($i->label ?? '') && !$isCashAdv($i->label ?? ''))
                              ->values();
                          $other2 = $nonLoan2
                              ->reject(fn($i) => $isCashAdv($i->label ?? '') || $isAttendance($i->label ?? ''))
                              ->values();
                        @endphp

                        @if ($loan2->isNotEmpty())
                          <div class="ps-line-group-label">Loans</div>
                          @foreach ($loan2 as $it)
                            <div class="ps-line-row">
                              <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                              <span class="ps-line-val deduction">–{{ $peso($it->amount ?? 0) }}</span>
                            </div>
                          @endforeach
                        @endif

                        @if ($cash2->isNotEmpty())
                          <div class="ps-line-group-label">Cash Advance</div>
                          @foreach ($cash2 as $it)
                            <div class="ps-line-row">
                              <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                              <span class="ps-line-val deduction">–{{ $peso($it->amount ?? 0) }}</span>
                            </div>
                          @endforeach
                        @endif

                        @if ($att2->isNotEmpty())
                          <div class="ps-line-group-label">Attendance Adjustments</div>
                          @foreach ($att2 as $it)
                            <div class="ps-line-row">
                              <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                              <span class="ps-line-val deduction">–{{ $peso($it->amount ?? 0) }}</span>
                            </div>
                          @endforeach
                        @endif

                        @if ($other2->isNotEmpty())
                          <div class="ps-line-group-label">Other</div>
                          @foreach ($other2 as $it)
                            <div class="ps-line-row">
                              <span class="ps-line-label">{{ $it->label ?? '—' }}</span>
                              <span class="ps-line-val deduction">–{{ $peso($it->amount ?? 0) }}</span>
                            </div>
                          @endforeach
                        @endif

                        @if ($ded2All->isEmpty())
                          <div class="ps-line-row">
                            <span class="ps-line-label text-muted fst-italic">No deductions configured.</span>
                            <span class="ps-line-val deduction">–{{ $peso(0) }}</span>
                          </div>
                        @endif

                        <div class="ps-line-row highlight">
                          <span class="ps-line-label fw-bold">Total Deductions</span>
                          <span class="ps-line-val deduction fw-bold fs-6">
                            –{{ $peso($c2data['deductions_total'] ?? 0) }}
                          </span>
                        </div>
                      </div>
                    </div>

                  </div>

                  <div class="ps-net-bar mt-4">
                    <div class="ps-net-left">
                      <div class="ps-net-label">2nd Cutoff Net Pay</div>
                      <div class="ps-net-sub">Earnings less all deductions</div>
                    </div>
                    <div class="ps-net-amount">{{ $peso($c2data['net'] ?? 0) }}</div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        @endif {{-- selectedEmployee --}}

      </div>{{-- /ps-main --}}

      {{-- ════ RIGHT: STICKY SUMMARY SIDEBAR ═══════════════════════════ --}}
      <aside class="ps-sidebar">

        {{-- Net Pay Hero --}}
        <div class="ps-net-hero card border shadow-none mb-3">
          <div class="card-body p-4 text-center">
            <div class="ps-net-hero-icon mb-2">
              <i class="ri ri-money-dollar-circle-line"></i>
            </div>
            <div class="ps-net-hero-label">Total Net Pay</div>
            <div class="ps-net-hero-sub">Both Cutoffs Combined</div>
            <div class="ps-net-hero-amount" data-amount="{{ (float) ($combined['net'] ?? 0) }}">
              {{ $peso($combined['net'] ?? 0) }}
            </div>
            <span class="badge {{ $badge['cls'] }} mt-2">
              <i class="ri {{ $badge['icon'] }} me-1"></i> {{ $badge['txt'] }}
            </span>
          </div>
        </div>

        {{-- Pay Summary --}}
        <div class="card border shadow-none mb-3">
          <div class="card-header border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0" style="font-size:.85rem;">Pay Summary</h6>
          </div>
          <div class="card-body px-4 py-3">
            <div class="ps-summary-rows">

              <div class="ps-sum-group-label">1st Cutoff</div>
              <div class="ps-sum-row">
                <span class="ps-sum-label">Gross Pay</span>
                <span class="ps-sum-val earning">{{ $peso($c1data['gross'] ?? 0) }}</span>
              </div>
              <div class="ps-sum-row">
                <span class="ps-sum-label">Deductions</span>
                <span class="ps-sum-val deduction">–{{ $peso($c1data['deductions_total'] ?? 0) }}</span>
              </div>
              <div class="ps-sum-row total">
                <span class="ps-sum-label fw-semibold">Net</span>
                <span class="ps-sum-val net fw-bold">{{ $peso($c1data['net'] ?? 0) }}</span>
              </div>

              <hr class="my-2">

              <div class="ps-sum-group-label">2nd Cutoff</div>
              <div class="ps-sum-row">
                <span class="ps-sum-label">Gross Pay</span>
                <span class="ps-sum-val earning">{{ $peso($c2data['gross'] ?? 0) }}</span>
              </div>
              <div class="ps-sum-row">
                <span class="ps-sum-label">Deductions</span>
                <span class="ps-sum-val deduction">–{{ $peso($c2data['deductions_total'] ?? 0) }}</span>
              </div>
              <div class="ps-sum-row total">
                <span class="ps-sum-label fw-semibold">Net</span>
                <span class="ps-sum-val net fw-bold">{{ $peso($c2data['net'] ?? 0) }}</span>
              </div>

              <hr class="my-2">

              <div class="ps-sum-row grand">
                <span class="ps-sum-label fw-bold" style="font-size:.9rem;">Total Gross</span>
                <span class="ps-sum-val earning fw-bold" style="font-size:.9rem;">
                  {{ $peso($combined['gross'] ?? 0) }}
                </span>
              </div>
              <div class="ps-sum-row grand">
                <span class="ps-sum-label fw-bold" style="font-size:.9rem;">Total Deductions</span>
                <span class="ps-sum-val deduction fw-bold" style="font-size:.9rem;">
                  –{{ $peso($combined['deductions_total'] ?? 0) }}
                </span>
              </div>

            </div>
          </div>
        </div>

        {{-- Period Info --}}
        <div class="card border shadow-none mb-3">
          <div class="card-header border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0" style="font-size:.85rem;">Period Info</h6>
          </div>
          <div class="card-body px-4 py-3">
            <div class="ps-info-rows">
              <div class="ps-info-row">
                <span class="ps-info-label"><i class="ri ri-calendar-line me-1"></i>Pay Period</span>
                <span class="ps-info-val">{{ $p['periodLabel'] ?? '—' }}</span>
              </div>
              <div class="ps-info-row">
                <span class="ps-info-label"><i class="ri ri-repeat-line me-1"></i>Type</span>
                <span class="ps-info-val">Semi-Monthly</span>
              </div>
              <div class="ps-info-row">
                <span class="ps-info-label"><i class="ri ri-shield-check-line me-1"></i>Status</span>
                <span class="ps-info-val">
                  <span class="badge {{ $badge['cls'] }}" style="font-size:.7rem;">
                    {{ $badge['txt'] }}
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>

        {{-- Sidebar action buttons --}}
        <div class="d-grid gap-2">
          <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
            id="btnPrintPayslip" {{ empty($selectedEmployee) ? 'disabled' : '' }}>
            <i class="ri ri-printer-line"></i> Print Payslip
          </button>
          <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
            id="btnEmail2" {{ empty($selectedEmployee) ? 'disabled' : '' }}>
            <i class="ri ri-mail-line"></i> Send to Email
          </button>
        </div>

      </aside>

    </div>{{-- /ps-layout --}}

  </div>{{-- /container --}}

  {{-- ── SEND EMAIL MODAL ──────────────────────────────────────────── --}}
  <div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
      <div class="modal-content">
        <div class="modal-header border-bottom py-3 px-4">
          <h5 class="modal-title fw-bold" style="font-size:.95rem;">
            <i class="ri ri-mail-send-line me-2" style="color:#696cff;"></i>Send Payslip via Email
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="psEmailForm" method="POST" action="{{ $payslipRoutes['email'] ?? '#' }}">
          @csrf
          @if (!empty($canSelectEmployee))
            <input type="hidden" name="employee_id" value="{{ $selectedEmployee?->id ?? '' }}">
          @endif
          <input type="hidden" name="year" value="{{ $year ?? now()->year }}">
          <input type="hidden" name="month" value="{{ $month ?? now()->month }}">

          <div class="modal-body px-4 py-4">
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:12px;">Recipient</label>
              <input type="email" class="form-control" value="{{ $selectedEmployee?->email ?? '' }}" readonly
                style="background:#f8f8fc;font-size:13.5px;">
              <div class="form-text" style="font-size:.78rem;">
                Recipient is pulled from the employee profile.
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:12px;">Subject <span
                  class="text-danger">*</span></label>
              <input type="text" class="form-control" name="subject"
                value="Your Payslip for {{ $p['periodLabel'] ?? \Carbon\Carbon::create($year ?? now()->year, $month ?? now()->month, 1)->format('F Y') }}"
                style="font-size:13.5px;" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:12px;">Message <span
                  class="text-muted">(optional)</span></label>
              <textarea class="form-control" name="message" rows="3" style="font-size:13px;resize:none;"
                placeholder="Add a note to the employee...">Please see your attached payslip.</textarea>
            </div>
            <div class="ps-email-attach">
              <i class="ri ri-attachment-line me-2" style="color:#696cff;"></i>
              <span>{{ $hasPayroll ? 'Payslip PDF' : 'Payslip PDF (will be generated on send)' }}</span>
              <span class="ms-auto text-muted" style="font-size:11px;">PDF</span>
            </div>
          </div>

          <div class="modal-footer border-top py-3 px-4">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" type="submit" id="btnSendEmail"
              {{ empty($selectedEmployee?->email) ? 'disabled title=No email on file' : '' }}>
              <i class="ri ri-send-plane-line"></i> Send Email
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ── TOAST ────────────────────────────────────────────────────── --}}
  <div id="psToast" class="ps-toast" aria-live="polite"></div>

@endsection

@section('page-script')
  @php
    $psConfig = [
        'indexUrl' => $payslipRoutes['index'] ?? '',
        'pdfUrl' => $payslipRoutes['pdf'] ?? '',
        'emailUrl' => $payslipRoutes['email'] ?? '',
        'canSelectEmployee' => (bool) ($canSelectEmployee ?? false),
        'selectedEmployeeId' => optional($selectedEmployee)->id,
        'year' => (int) ($year ?? now()->year),
        'month' => (int) ($month ?? now()->month),
    ];
  @endphp
  <script>
    window.PS_CONFIG = @json($psConfig);
  </script>
  <script src="{{ asset('assets/js/payslip.js') }}"></script>
@endsection
