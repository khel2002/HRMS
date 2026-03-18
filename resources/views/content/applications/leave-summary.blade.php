@extends('layouts/contentNavbarLayout')
@section('title', 'Leave Summary')

@section('page-style')
  <style>
    /* ── Spin animation ───────────────────────────────────────────────── */
    @keyframes lr-spin {
      to {
        transform: rotate(360deg);
      }
    }

    .spin {
      display: inline-block;
      animation: lr-spin .8s linear infinite;
    }

    /* ── Ensure table action buttons are always clickable ────────────── */
    .table td {
      position: static;
    }

    .table .btn-icon {
      pointer-events: auto !important;
      cursor: pointer;
    }

    .table .dropdown {
      position: static;
    }

    /* ── Detail modal grid ────────────────────────────────────────────── */
    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .75rem 1.5rem;
    }

    @media (max-width:575px) {
      .detail-grid {
        grid-template-columns: 1fr;
      }
    }

    .detail-label {
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #a1acb8;
      margin-bottom: 2px;
    }

    .detail-value {
      font-size: .875rem;
      color: #566a7f;
      font-weight: 500;
      margin-bottom: 0;
    }

    /* ── Approval timeline ────────────────────────────────────────────── */
    .leave-timeline {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .leave-timeline li {
      position: relative;
      padding: .5rem 0 .5rem 2rem;
      border-left: 2px solid #e4e6ea;
    }

    .leave-timeline li:last-child {
      border-left-color: transparent;
    }

    .leave-timeline li::before {
      content: '';
      position: absolute;
      left: -7px;
      top: .75rem;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #696cff;
      border: 2px solid #fff;
      box-shadow: 0 0 0 2px #696cff33;
    }

    .leave-timeline li.tl-approved::before {
      background: #28c76f;
      box-shadow: 0 0 0 2px #28c76f33;
    }

    .leave-timeline li.tl-rejected::before {
      background: #ea5455;
      box-shadow: 0 0 0 2px #ea545533;
    }
  </style>
@endsection

@section('content')

  {{-- Toast container --}}
  <div id="toastContainer" aria-live="polite" aria-atomic="true"
    style="position:fixed;top:1.25rem;right:1.25rem;z-index:1090;min-width:280px;display:flex;flex-direction:column;gap:.5rem;">
  </div>

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card border-0 shadow-sm mb-4">

      <div class="card-header bg-white border-bottom">
        <div class="d-flex align-items-center gap-2">
          <i class="ri ri-history-line text-primary fs-5"></i>
          <div>
            <h6 class="mb-0 fw-bold">Leave Requests</h6>
            <small class="text-muted">As of {{ now()->format('F d, Y') }}</small>
          </div>
        </div>
      </div>

      <div class="card-body px-3 px-md-4">

        {{-- Tabs --}}
        <ul class="nav nav-tabs border-bottom mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="tab-pending" data-bs-toggle="tab"
              data-bs-target="#pane-pending" type="button" role="tab">
              <i class="ri ri-time-line text-warning"></i> Pending
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-approved" data-bs-toggle="tab"
              data-bs-target="#pane-approved" type="button" role="tab">
              <i class="ri ri-checkbox-circle-line text-success"></i> Approved
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="tab-disapproved" data-bs-toggle="tab"
              data-bs-target="#pane-disapproved" type="button" role="tab">
              <i class="ri ri-close-circle-line text-danger"></i> Disapproved
            </button>
          </li>
        </ul>

        <div class="tab-content">

          {{-- ── PENDING ─────────────────────────────────────────────────── --}}
          <div class="tab-pane fade show active" id="pane-pending" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <p class="fw-semibold mb-0" style="color:#ff9f43;">Pending Requests</p>
                <small class="text-muted">Awaiting HR action.</small>
              </div>
              <select class="form-select form-select-sm" style="width:110px;" id="year-select-pending"
                onchange="leaveRequests.loadTab('pending', this.value)">
                @foreach (range(date('Y'), date('Y') - 6) as $yr)
                  <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:44px;">#</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Date Filed</th>
                    <th>Inclusive Dates</th>
                    <th class="text-center">Days</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width:120px;">Action</th>
                  </tr>
                </thead>
                <tbody id="pending-tbody">
                  <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                      <i class="ri ri-loader-4-line fs-3 d-block mb-1 opacity-50 spin"></i>Loading…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {{-- ── APPROVED ────────────────────────────────────────────────── --}}
          <div class="tab-pane fade" id="pane-approved" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <p class="fw-semibold text-success mb-0">Approved Requests</p>
                <small class="text-muted">Approved by HR.</small>
              </div>
              <select class="form-select form-select-sm" style="width:110px;" id="year-select-approved"
                onchange="leaveRequests.loadTab('approved', this.value)">
                @foreach (range(date('Y'), date('Y') - 6) as $yr)
                  <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:44px;">#</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Date Filed</th>
                    <th>Inclusive Dates</th>
                    <th class="text-center">Days</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width:100px;">Action</th>
                  </tr>
                </thead>
                <tbody id="approved-tbody">
                  <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                      <i class="ri ri-loader-4-line fs-3 d-block mb-1 opacity-50 spin"></i>Loading…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {{-- ── DISAPPROVED ─────────────────────────────────────────────── --}}
          <div class="tab-pane fade" id="pane-disapproved" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <p class="fw-semibold text-danger mb-0">Disapproved Requests</p>
                <small class="text-muted">Rejected by HR.</small>
              </div>
              <select class="form-select form-select-sm" style="width:110px;" id="year-select-disapproved"
                onchange="leaveRequests.loadTab('disapproved', this.value)">
                @foreach (range(date('Y'), date('Y') - 6) as $yr)
                  <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:44px;">#</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Date Filed</th>
                    <th>Inclusive Dates</th>
                    <th class="text-center">Days</th>
                    <th class="text-center">Status</th>
                    <th>HR Remarks</th>
                    <th class="text-center" style="width:100px;">Action</th>
                  </tr>
                </thead>
                <tbody id="disapproved-tbody">
                  <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                      <i class="ri ri-loader-4-line fs-3 d-block mb-1 opacity-50 spin"></i>Loading…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>{{-- /tab-content --}}
      </div>
    </div>
  </div>
  @include('content.applications.view-modal')
  @include('content.applications.disapproval-modal')
  <script src="{{ asset('assets/js/leave-summary.js') }}"></script>
@endsection
