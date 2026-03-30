<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changeStatusModalLabel">
          <i class="icon-base ri ri-refresh-line me-2"></i>Change User Status
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="changeStatusForm">
        @csrf
        <input type="hidden" name="user_id" id="statusUserId">
        <div class="modal-body">
          <p class="mb-3">Changing status for: <strong id="statusUserName"></strong></p>
          <div class="mb-3">
            <label for="newStatus" class="form-label">Select New Status</label>
            <select name="status" id="newStatus" class="form-select" required>
              <option value="">— Select Status —</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="changeStatusBtn" class="btn btn-primary">
            <i class="icon-base ri ri-check-line me-1"></i>Update Status
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
