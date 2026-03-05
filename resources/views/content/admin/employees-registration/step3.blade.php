{{-- content/admin/employees-registration/step3.blade.php --}}

<div class="wz-panel-head">
  <i class="ri ri-team-line"></i>
  <div>
    <h6 class="wz-panel-title">Family Background</h6>
    <p class="wz-panel-sub text-warning">Put N/A if not applicable.</p>
  </div>
</div>

{{-- ── Family Details ── --}}
<div class="wz-section-label mt-3">
  <span>Family Details</span>
  <code>employee_family</code>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <label class="form-label">Father's Name <span class="text-danger">*</span></label>
    <input type="text" name="family[father_name]" placeholder="Full Name"
      class="form-control @error('family.father_name') is-invalid @enderror"
      value="{{ old('family.father_name') }}"required>
    @error('family.father_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label">Mother's Maiden Name <span class="text-danger">*</span></label>
    <input type="text" name="family[mother_name]" placeholder="Full Name"
      class="form-control @error('family.mother_name') is-invalid @enderror"
      value="{{ old('family.mother_name') }}"required>
    @error('family.mother_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

{{-- ── Spouse Fields — auto-disabled when civil status is single/widowed/separated ── --}}
<div id="spouseNotice" class="alert alert-info d-flex align-items-center gap-2 mt-3 py-2 px-3"
  style="font-size:.82rem; display:none !important;">
  <i class="ri ri-information-line flex-shrink-0"></i>
  <span>Spouse fields are not applicable based on the selected civil status.</span>
</div>

<div class="row g-3 mt-1" id="spouseFields">
  <div class="col-md-6">
    <label class="form-label spouse-label">Spouse Name</label>
    <input type="text" id="spouse_name" name="family[spouse_name]"
      class="form-control @error('family.spouse_name') is-invalid @enderror" value="{{ old('family.spouse_name') }}">
    @error('family.spouse_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label spouse-label">Spouse Occupation</label>
    <input type="text" id="spouse_occupation" name="family[spouse_occupation]"
      class="form-control @error('family.spouse_occupation') is-invalid @enderror"
      value="{{ old('family.spouse_occupation') }}">
    @error('family.spouse_occupation')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label spouse-label">Spouse Employer</label>
    <input type="text" id="spouse_employer" name="family[spouse_employer]"
      class="form-control @error('family.spouse_employer') is-invalid @enderror"
      value="{{ old('family.spouse_employer') }}">
    @error('family.spouse_employer')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label spouse-label">Spouse Business Address</label>
    <input type="text" id="spouse_business_address" name="family[spouse_business_address]"
      class="form-control @error('family.spouse_business_address') is-invalid @enderror"
      value="{{ old('family.spouse_business_address') }}">
    @error('family.spouse_business_address')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

{{-- ── Emergency Contact ── --}}
<div class="wz-section-label mt-4">
  <span>Emergency Contact</span>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-4">
    <label class="form-label">Contact Name <span class="text-danger">*</span></label>
    <input type="text" name="family[emergency_contact_name]"
      class="form-control @error('family.emergency_contact_name') is-invalid @enderror"
      value="{{ old('family.emergency_contact_name') }}" required>
    @error('family.emergency_contact_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label">Contact Number <span class="text-danger">*</span></label>
    <input type="text" name="family[emergency_contact_number]"
      class="form-control @error('family.emergency_contact_number') is-invalid @enderror"
      value="{{ old('family.emergency_contact_number') }}" required>
    @error('family.emergency_contact_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label">Relationship <span class="text-danger">*</span></label>
    <input type="text" name="family[emergency_relationship]"
      class="form-control @error('family.emergency_relationship') is-invalid @enderror"
      value="{{ old('family.emergency_relationship') }}" required>
    @error('family.emergency_relationship')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

{{-- ── Children ── --}}
<div class="wz-section-label mt-4">
  <span>Children</span>
  <code>employee_children</code>
</div>

<div id="childrenContainer">
  @forelse(old('children', []) as $i => $child)
    <div class="child-row row g-2 align-items-end mt-2">
      <div class="col-md-6">
        <label class="form-label">Child's Full Name</label>
        <input type="text" name="children[{{ $i }}][child_name]" class="form-control"
          placeholder="Full Name" value="{{ $child['child_name'] ?? '' }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="children[{{ $i }}][date_of_birth]" class="form-control"
          value="{{ $child['date_of_birth'] ?? '' }}">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="button" class="btn btn-outline-danger btn-sm w-100"
          onclick="this.closest('.child-row').remove()">
          <i class="ri ri-delete-bin-line me-1"></i> Remove
        </button>
      </div>
    </div>
  @empty
  @endforelse
</div>

<button type="button" class="btn btn-outline-primary btn-sm mt-3" id="addChildBtn">
  <i class="ri ri-add-line me-1"></i> Add Child
</button>
