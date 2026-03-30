<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="icon-base ri ri-add-line me-2"></i>
          Add New User
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addUserForm">
        @csrf
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-12">
              <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
              <select name="employee_id" id="employee_id" class="form-select" required>
                <option value="">— Select Employee —</option>
                @foreach ($availableEmployees as $emp)
                  <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err_employee_id"></div>
            </div>

            <div class="col-md-12">
              <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role_id" id="role_id" class="form-select" required>
                <option value="">— Select Role —</option>
                @foreach ($roles as $role)
                  <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback" id="err_role_id"></div>
            </div>

            <div class="col-md-12">
              <label for="addUsername" class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" id="addUsername" class="form-control" required>
              <div class="invalid-feedback" id="err_username"></div>
            </div>

            <div class="col-md-12">
              <label for="addPassword" class="form-label">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" name="password" id="addPassword" class="form-control" required>
                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"
                  data-target="#addPassword">
                  <i class="ri ri-eye-line"></i>
                </button>
              </div>
              <div class="invalid-feedback d-block" id="err_password"></div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addUserBtn">
            <i class="icon-base ri ri-add-line me-1"></i>
            Add User
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
