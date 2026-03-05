{{-- content/admin/employees-registration/review.blade.php
     Step 5 — Review panel.
     The actual panels 1-4 (with their filled values) are physically
     moved INTO #reviewScroll by JS when this step is entered, then
     moved back when navigating away. No duplicate DOM — no empty fields.
--}}

<div class="wz-panel-head">
  <i class="ri ri-file-check-line"></i>
  <div>
    <h6 class="wz-panel-title">Review &amp; Confirm</h6>
    <p class="wz-panel-sub">Review and edit everything below before saving.</p>
  </div>
</div>

<div class="alert alert-info d-flex align-items-center gap-2 mt-2 py-2 px-3" style="font-size:.82rem;">
  <i class="ri ri-edit-line flex-shrink-0"></i>
  <span>You can still edit any field here. Scroll through all sections before clicking <strong>Save
      Employee</strong>.</span>
</div>

{{-- Panels 1-4 will be moved here by JS --}}
<div id="reviewScroll"
  style="height:60vh; overflow-y:auto; border:1px solid #e0e0e0; border-radius:.5rem; padding:1.5rem 1.75rem; margin-top:1rem;">
</div>

<style>
  /* Sticky section label that floats as you scroll */
  .review-section-header {
    position: sticky;
    top: -1.5rem;
    z-index: 10;
    background: #f0f0ff;
    color: #696cff;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .45rem .75rem;
    border-radius: .35rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
  }

  .review-divider {
    border-color: #e0e0e0;
    margin: 2rem 0;
  }

  /* While inside the review scroll, hide the per-step panel headers
     (the sticky section headers above replace them) */
  #reviewScroll .wz-panel-head {
    display: none !important;
  }

  /* Hide table/db code badges inside review */
  #reviewScroll .wz-section-label code {
    display: none;
  }
</style>
