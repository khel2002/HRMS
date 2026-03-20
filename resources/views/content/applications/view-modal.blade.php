{{-- ══════════════════════════════════════════════════════════════════
       MODAL — View Leave Details
       Embedded directly to avoid @include path resolution issues.
  ══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-view-details" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content border-0 shadow">

      <div class="modal-header border-bottom px-4 py-3">
        <div class="d-flex align-items-center gap-2">
          <span class="avatar avatar-sm rounded bg-label-primary">
            <i class="ri ri-file-search-line fs-5"></i>
          </span>
          <div>
            <h6 class="modal-title fw-bold mb-0" id="modal-detail-title">Leave Request</h6>
            <small class="text-muted" id="modal-detail-subtitle"></small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      {{-- Skeleton shown while loading --}}
      <div class="modal-body px-4 py-4" id="modal-detail-skeleton">
        <div class="d-flex justify-content-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>
      </div>

      {{-- Populated content --}}
      <div class="modal-body px-4 py-4" id="modal-detail-body" style="display:none;">

        {{-- Employee info --}}
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3"
          style="background:#f8f8ff;border:1px solid #ebebff;">
          <div>
            <p class="fw-bold mb-0" id="modal-emp-name" style="font-size:.95rem;"></p>
            <small class="text-muted" id="modal-emp-meta"></small>
          </div>
          <div class="ms-auto" id="modal-status-badge"></div>
        </div>

        {{-- Detail grid --}}
        <div class="detail-grid mb-4">
          <div class="detail-item">
            <p class="detail-label">Leave Type</p>
            <p class="detail-value" id="d-leave-type"></p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Date Filed</p>
            <p class="detail-value" id="d-date-filed"></p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Start Date</p>
            <p class="detail-value" id="d-start-date"></p>
          </div>
          <div class="detail-item">
            <p class="detail-label">End Date</p>
            <p class="detail-value" id="d-end-date"></p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Total Working Days</p>
            <p class="detail-value" id="d-total-days"></p>
          </div>
          <div class="detail-item">
            <p class="detail-label">Manager Endorsement</p>
            <p class="detail-value" id="d-manager-status"></p>
          </div>
        </div>

        {{-- Cause --}}
        <div class="mb-4" id="d-cause-wrap">
          <p class="detail-label"
            style="font-size:.72rem;font-weight:600;text-transform:uppercase;
              letter-spacing:.05em;color:#a1acb8;margin-bottom:4px;">
            Cause / Reason for Leave</p>
          <p class="mb-0 p-3 rounded-2" id="d-cause" style="background:#f5f5f9;font-size:.875rem;color:#566a7f;">
          </p>
        </div>

        {{-- HR Remarks (disapproved only) --}}
        <div class="mb-4" id="d-remarks-wrap" style="display:none;">
          <p class="detail-label"
            style="font-size:.72rem;font-weight:600;text-transform:uppercase;
              letter-spacing:.05em;color:#a1acb8;margin-bottom:4px;">
            HR Remarks</p>
          <div class="alert alert-danger d-flex gap-2 py-2 px-3 mb-0" style="font-size:.83rem;">
            <i class="ri ri-information-line flex-shrink-0 mt-1"></i>
            <span id="d-remarks"></span>
          </div>
        </div>

        {{-- Timeline --}}
        <div>
          <p class="detail-label mb-2"
            style="font-size:.72rem;font-weight:600;text-transform:uppercase;
              letter-spacing:.05em;color:#a1acb8;">
            Approval Timeline</p>
          <ul class="leave-timeline" id="d-timeline"></ul>
        </div>

      </div>

      <div class="modal-footer border-top px-4 py-2 gap-2" id="modal-detail-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ri ri-close-line me-1"></i>Close
        </button>
      </div>

    </div>
  </div>
</div>
