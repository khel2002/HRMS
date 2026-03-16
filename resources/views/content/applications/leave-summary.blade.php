@extends('layouts/contentNavbarLayout')
@section('title', 'Application for Leave')

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/leave-application.css') }}">
@endsection

@section('content')

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

    <div class="card-body">

      <ul class="nav nav-tabs border-bottom mb-3" role="tablist">

        <li class="nav-item" role="presentation">
          <button class="nav-link active d-flex align-items-center gap-2" id="tab-pending" data-bs-toggle="tab"
            data-bs-target="#pane-pending" type="button" role="tab" aria-selected="true">
            <i class="ri ri-time-line text-warning"></i>
            <span>Pending</span>
            <span class="badge rounded-pill bg-warning text-dark" id="badge-pending">0</span>
          </button>
        </li>

        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2" id="tab-approved" data-bs-toggle="tab"
            data-bs-target="#pane-approved" type="button" role="tab" aria-selected="false">
            <i class="ri ri-checkbox-circle-line text-success"></i>
            <span>Approved</span>
            <span class="badge rounded-pill bg-success" id="badge-approved">0</span>
          </button>
        </li>

        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2" id="tab-disapproved" data-bs-toggle="tab"
            data-bs-target="#pane-disapproved" type="button" role="tab" aria-selected="false">
            <i class="ri ri-close-circle-line text-danger"></i>
            <span>Disapproved</span>
            <span class="badge rounded-pill bg-danger" id="badge-disapproved">0</span>
          </button>
        </li>

      </ul>

      {{-- ── Tab panes ── --}}
      <div class="tab-content">

        {{-- Pending --}}
        <div class="tab-pane fade show active" id="pane-pending" role="tabpanel">
          <div class="tab-pane-header d-flex align-items-start
                    justify-content-between mb-3 gap-2">
            <div>
              <p class="fw-semibold text-warning mb-0">Pending Requests</p>
              <small class="text-muted">
                To modify dates, delete your current request and submit a new one.
              </small>
            </div>
            <select class="form-select form-select-sm year-select" style="width:120px;" id="year-select-pending"
              onchange="leaveRequests.loadTab('pending', this.value)">
              @foreach (range(date('Y'), date('Y') - 6) as $yr)
                <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>
                  {{ $yr }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-requests" id="pending-request-table">
              <thead class="table-primary">
                <tr>
                  <th style="width:44px;">#</th>
                  <th>Leave Type</th>
                  <th>Date Filed</th>
                  <th>Duration</th>
                  <th class="text-center">Status</th>
                  <th class="text-center" style="width:110px;">Action</th>
                </tr>
              </thead>
              <tbody id="pending-tbody">
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="ri ri-loader-4-line fs-4 d-block mb-1 opacity-50"></i>
                    Loading…
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- Approved --}}
        <div class="tab-pane fade" id="pane-approved" role="tabpanel">
          <div class="tab-pane-header d-flex align-items-start
                    justify-content-between mb-3 gap-2">
            <p class="fw-semibold text-success mb-0">Approved Requests</p>
            <select class="form-select form-select-sm year-select" style="width:120px;" id="year-select-approved"
              onchange="leaveRequests.loadTab('approved', this.value)">
              @foreach (range(date('Y'), date('Y') - 6) as $yr)
                <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>
                  {{ $yr }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-requests" id="approved-request-table">
              <thead class="table-primary">
                <tr>
                  <th style="width:44px;">#</th>
                  <th>Leave Type</th>
                  <th>Date Filed</th>
                  <th>Duration</th>
                  <th class="text-center">Status</th>
                  <th class="text-center" style="width:110px;">Action</th>
                </tr>
              </thead>
              <tbody id="approved-tbody">
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="ri ri-loader-4-line fs-4 d-block mb-1 opacity-50"></i>
                    Loading…
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- Disapproved --}}
        <div class="tab-pane fade" id="pane-disapproved" role="tabpanel">
          <div class="tab-pane-header d-flex align-items-start
                    justify-content-between mb-3 gap-2">
            <p class="fw-semibold text-danger mb-0">Disapproved Requests</p>
            <select class="form-select form-select-sm year-select" style="width:120px;" id="year-select-disapproved"
              onchange="leaveRequests.loadTab('disapproved', this.value)">
              @foreach (range(date('Y'), date('Y') - 6) as $yr)
                <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>
                  {{ $yr }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle table-requests table-disapproved"
              id="disapproved-request-table">
              <thead class="table-primary">
                <tr>
                  <th style="width:44px;">#</th>
                  <th>Leave Type</th>
                  <th>Date Filed</th>
                  <th>Duration</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Comment</th>
                  <th class="text-center" style="width:110px;">Action</th>
                </tr>
              </thead>
              <tbody id="disapproved-tbody">
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="ri ri-loader-4-line fs-4 d-block mb-1 opacity-50"></i>
                    Loading…
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>{{-- /tab-content --}}
    </div>{{-- /card-body --}}
  </div>{{-- /card --}}


  {{-- ════════════════════════════════════════════════════════
     MODAL — View Leave Request
════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="view-leave-request-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content border-0 shadow">

        <div class="modal-header border-bottom px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="ri ri-file-search-line text-primary fs-5"></i>
            <h6 class="modal-title fw-bold mb-0">
              <span id="modal-employee-name"></span> — Leave Request
            </h6>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <span hidden id="modal-file-id"></span>
        </div>

        <div class="modal-body px-4 py-3">
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#modal-track" type="button"
                role="tab">
                <i class="ri ri-route-line me-1"></i> Track
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#modal-attachments" type="button"
                role="tab">
                <i class="ri ri-attachment-2 me-1"></i> Attachments
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#modal-file" type="button"
                role="tab">
                <i class="ri ri-file-text-line me-1"></i> Leave File
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <div class="tab-pane fade show active" id="modal-track" role="tabpanel">
              <ul class="timeline" id="request-timeline"></ul>
              <div class="d-flex justify-content-end mt-2" id="btn-track-container"></div>
            </div>
            <div class="tab-pane fade" id="modal-attachments" role="tabpanel">
              <ul class="list-group" id="track-files-attachments-container"></ul>
            </div>
            <div class="tab-pane fade" id="modal-file" role="tabpanel">
              <iframe class="frame-preview-file" src="" width="100%" height="500"
                style="border:none;border-radius:.375rem;"></iframe>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top px-4 py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ri ri-close-line me-1"></i> Close
          </button>
        </div>

      </div>
    </div>
  </div>


  {{-- ════════════════════════════════════════════════════════
     MODAL — View Attachment
════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="view-attachment-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content border-0 shadow">
        <div class="modal-body p-0">
          <iframe class="frame-preview-attachment" src="" width="100%" height="600"
            style="border:none;border-radius:.375rem;"></iframe>
        </div>
        <div class="modal-footer border-top px-4 py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ri ri-close-line me-1"></i> Close
          </button>
        </div>
      </div>
    </div>
  </div>
  <script src="{{ asset('assets/js/leave-summary.js') }}"></script>
@endsection
