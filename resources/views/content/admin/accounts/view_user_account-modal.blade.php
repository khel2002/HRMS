<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewUserModalLabel">
          <i class="icon-base ri ri-eye-line me-2"></i>View User Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        {{-- Loading --}}
        <div id="viewUserLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted mb-0">Fetching user details...</p>
        </div>

        {{-- Error --}}
        <div id="viewUserError" class="alert alert-danger" style="display:none;">
          <i class="ri ri-error-warning-line me-2"></i>
          Could not load user details. Please try again.
        </div>

        {{-- Content --}}
        <div id="viewUserContent" style="display:none;">
          <table class="table table-borderless mb-0">
            <tbody>
              <tr>
                <th class="text-muted ps-0" style="width:38%;">Full Name</th>
                <td id="viewFullName" class="fw-medium">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Username</th>
                <td id="viewUsername">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Email</th>
                <td id="viewEmail">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Role</th>
                <td id="viewRole">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Office</th>
                <td id="viewOffice">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Status</th>
                <td id="viewStatus">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Created At</th>
                <td id="viewCreatedAt">—</td>
              </tr>
              <tr>
                <th class="text-muted ps-0">Last Updated</th>
                <td id="viewUpdatedAt">—</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
