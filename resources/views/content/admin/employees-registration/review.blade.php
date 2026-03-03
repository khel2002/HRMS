<div class="wz-panel-head">
  <i class="ri ri-file-check-line"></i>
  <div>
    <h6 class="wz-panel-title">Review & Confirm</h6>
    <p class="wz-panel-sub">Please review all information carefully before submitting.</p>
  </div>
</div>

<div class="row g-3 mt-2">

  {{-- Personal --}}
  <div class="col-md-6">
    <div class="review-block">
      <div class="review-block-title">
        <span><i class="ri ri-user-3-line me-1"></i>Personal Info</span>
        <a href="javascript:void(0);" class="review-edit" onclick="goStep(1)">Edit</a>
      </div>
      <div class="review-block-body" id="rv-personal">—</div>
    </div>
  </div>

  {{-- Address --}}
  <div class="col-md-6">
    <div class="review-block">
      <div class="review-block-title">
        <span><i class="ri ri-map-pin-line me-1"></i>Address</span>
        <a href="javascript:void(0);" class="review-edit" onclick="goStep(2)">Edit</a>
      </div>
      <div class="review-block-body" id="rv-address">—</div>
    </div>
  </div>

  {{-- Family --}}
  <div class="col-md-6">
    <div class="review-block">
      <div class="review-block-title">
        <span><i class="ri ri-team-line me-1"></i>Family</span>
        <a href="javascript:void(0);" class="review-edit" onclick="goStep(3)">Edit</a>
      </div>
      <div class="review-block-body" id="rv-family">—</div>
    </div>
  </div>

  {{-- Education & IDs --}}
  <div class="col-md-6">
    <div class="review-block">
      <div class="review-block-title">
        <span><i class="ri ri-book-open-line me-1"></i>Education & IDs</span>
        <a href="javascript:void(0);" class="review-edit" onclick="goStep(4)">Edit</a>
      </div>
      <div class="review-block-body" id="rv-education">—</div>
    </div>
  </div>

</div>

<div class="alert alert-warning d-flex align-items-start gap-2 mt-4" style="font-size:.875rem;">
  <i class="ri ri-information-line mt-1 flex-shrink-0"></i>
  <span>
    Double-check all entries above. Clicking <strong>Save Employee</strong> will save the employee record.
  </span>
</div>
