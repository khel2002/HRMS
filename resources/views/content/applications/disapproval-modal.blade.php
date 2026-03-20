  {{-- ══════════════════════════════════════════════════════════════════
       MODAL — Disapprove with Reason
       Embedded directly to avoid @include path resolution issues.
  ══════════════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="modal-disapprove" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">

        <div class="modal-header border-bottom px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <span class="avatar avatar-sm rounded bg-label-danger">
              <i class="ri ri-close-circle-line fs-5 text-danger"></i>
            </span>
            <h6 class="modal-title fw-bold mb-0">Disapprove Leave Request</h6>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body px-4 py-3">
          <input type="hidden" id="disapprove-target-id">
          <p class="text-muted mb-3" style="font-size:.875rem;">
            You are about to <strong class="text-danger">disapprove</strong> the leave request of
            <strong id="disapprove-emp-name"></strong>.
            Providing a reason helps the employee understand the decision.
          </p>
          <div>
            <label class="form-label fw-semibold">
              Reason <span class="text-muted fw-normal" style="font-size:.8rem;">(optional)</span>
            </label>
            <textarea id="disapprove-reason" class="form-control" rows="3"
              placeholder="e.g. Insufficient leave credits, critical project period…" maxlength="500"></textarea>
            <small class="text-muted d-block mt-1">Max 500 characters.</small>
          </div>
        </div>

        <div class="modal-footer border-top px-4 py-2 gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ri ri-close-line me-1"></i>Cancel
          </button>
          {{-- Calls leaveRequests.submitDisapprove() which is now exposed on the public object --}}
          <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-disapprove"
            onclick="leaveRequests.submitDisapprove()">
            <i class="ri ri-close-circle-line me-1"></i>Confirm Disapprove
          </button>
        </div>

      </div>
    </div>
  </div>
