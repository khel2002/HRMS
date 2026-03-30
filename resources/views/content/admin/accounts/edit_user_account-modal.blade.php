<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="icon-base ri ri-edit-line me-2"></i>
          Edit User
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="editUserForm">
        @csrf
        <input type="hidden" id="editUserId">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-12">
              <label for="editRoleId" class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role_id" id="editRoleId" class="form-select" required>
                <option value="">— Select Role —</option>
                @foreach ($roles as $role)
                  <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="edit_err_role_id"></div>
            </div>

            <div class="col-md-12">
              <label for="editUsername" class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" id="editUsername" class="form-control" required>
              <div class="invalid-feedback" id="edit_err_username"></div>
            </div>

            <div class="col-md-12">
              <label for="editPassword" class="form-label">
                Password
                <small class="text-muted">(Leave blank to keep current)</small>
              </label>
              <div class="input-group">
                <input type="password" name="password" id="editPassword" class="form-control"
                  placeholder="Leave blank to keep current">
                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"
                  data-target="#editPassword">
                  <i class="ri ri-eye-line"></i>
                </button>
              </div>
              <div class="invalid-feedback d-block" id="edit_err_password"></div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editUserBtn">
            <i class="icon-base ri ri-save-line me-1"></i>
            Update User
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
