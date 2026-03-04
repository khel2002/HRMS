{{-- content/admin/employees-registration/step2.blade.php --}}

<div class="wz-panel-head">
  <i class="ri ri-map-pin-line"></i>
  <div>
    <h6 class="wz-panel-title">Address Information</h6>
    <p class="wz-panel-sub text-warning">Fill in from top to bottom. Each field unlocks after the one above is selected.
      Put N/A if not applicable.
    </p>
  </div>
</div>

{{-- ════════════════════════════════
     PERMANENT ADDRESS
════════════════════════════════ --}}
<div class="wz-section-label mt-3">
  <span>Permanent Address</span>
</div>

<div class="row g-3 mt-1">

  {{-- 1. Region — always enabled, loaded on mount --}}
  <div class="col-md-6">
    <label class="form-label">Region <span class="text-danger">*</span></label>
    <select id="perm_region" class="form-select @error('permanent.region') is-invalid @enderror">
      <option value="">— Select Region —</option>
    </select>
    <input type="hidden" name="permanent[region]" id="perm_region_name" value="{{ old('permanent.region') }}">
    <input type="hidden" name="permanent[region_code]" id="perm_region_code"
      value="{{ old('permanent.region_code') }}">
    @error('permanent.region')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 2. Province — unlocks after Region --}}
  <div class="col-md-6">
    <label class="form-label">Province <span class="text-danger">*</span></label>
    <select id="perm_province" class="form-select @error('permanent.province') is-invalid @enderror" disabled>
      <option value="">— Select Province —</option>
    </select>
    <input type="hidden" name="permanent[province]" id="perm_province_name" value="{{ old('permanent.province') }}">
    <input type="hidden" name="permanent[province_code]" id="perm_province_code"
      value="{{ old('permanent.province_code') }}">
    @error('permanent.province')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 3. City / Municipality — unlocks after Province --}}
  <div class="col-md-6">
    <label class="form-label">City / Municipality <span class="text-danger">*</span></label>
    <select id="perm_city" class="form-select @error('permanent.city') is-invalid @enderror" disabled>
      <option value="">— Select City / Municipality —</option>
    </select>
    <input type="hidden" name="permanent[city]" id="perm_city_name" value="{{ old('permanent.city') }}">
    <input type="hidden" name="permanent[city_code]" id="perm_city_code" value="{{ old('permanent.city_code') }}">
    @error('permanent.city')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 4. Barangay — unlocks after City --}}
  <div class="col-md-6">
    <label class="form-label">Barangay <span class="text-danger">*</span></label>
    <select id="perm_barangay" class="form-select @error('permanent.barangay') is-invalid @enderror" disabled>
      <option value="">— Select Barangay —</option>
    </select>
    <input type="hidden" name="permanent[barangay]" id="perm_barangay_name" value="{{ old('permanent.barangay') }}">
    @error('permanent.barangay')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 5. Street — unlocks after Barangay --}}
  <div class="col-md-4">
    <label class="form-label">Street</label>
    <input type="text" id="perm_street" name="permanent[street]"
      class="form-control @error('permanent.street') is-invalid @enderror" value="{{ old('permanent.street') }}"
      disabled>
    @error('permanent.street')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 6. Subdivision — unlocks after Barangay --}}
  <div class="col-md-4">
    <label class="form-label">Subdivision / Village</label>
    <input type="text" id="perm_subdivision" name="permanent[subdivision]"
      class="form-control @error('permanent.subdivision') is-invalid @enderror"
      value="{{ old('permanent.subdivision') }}" disabled>
    @error('permanent.subdivision')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 7. House No — unlocks after Barangay --}}
  <div class="col-md-4">
    <label class="form-label">House / Unit No.</label>
    <input type="text" id="perm_house_number" name="permanent[house_number]"
      class="form-control @error('permanent.house_number') is-invalid @enderror"
      value="{{ old('permanent.house_number') }}" disabled>
    @error('permanent.house_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 8. ZIP Code — unlocks after Barangay --}}
  <div class="col-md-2">
    <label class="form-label">ZIP Code</label>
    <input type="text" id="perm_zip_code" name="permanent[zip_code]"
      class="form-control @error('permanent.zip_code') is-invalid @enderror" value="{{ old('permanent.zip_code') }}"
      disabled>
    @error('permanent.zip_code')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

</div>

{{-- ════════════════════════════════
     SAME AS PERMANENT TOGGLE
════════════════════════════════ --}}
<div class="form-check mt-4 mb-1">
  <input class="form-check-input" type="checkbox" id="sameAsPermanent" onchange="copyAddress(this)">
  <label class="form-check-label" for="sameAsPermanent" style="font-size:.875rem;">
    <code>Current address is the same as permanent address</code>
  </label>
</div>

{{-- ════════════════════════════════
     CURRENT ADDRESS
════════════════════════════════ --}}
<div class="wz-section-label mt-3">
  <span>Current Address</span>
</div>

<div class="row g-3 mt-1" id="currentAddressFields">

  {{-- 1. Region --}}
  <div class="col-md-6">
    <label class="form-label">Region <span class="text-danger">*</span></label>
    <select id="curr_region" class="form-select @error('current.region') is-invalid @enderror">
      <option value="">— Select Region —</option>
    </select>
    <input type="hidden" name="current[region]" id="curr_region_name" value="{{ old('current.region') }}">
    <input type="hidden" name="current[region_code]" id="curr_region_code"
      value="{{ old('current.region_code') }}" required>
    @error('current.region')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 2. Province --}}
  <div class="col-md-6">
    <label class="form-label">Province <span class="text-danger">*</span></label>
    <select id="curr_province" class="form-select @error('current.province') is-invalid @enderror" disabled>
      <option value="">— Select Province —</option>
    </select>
    <input type="hidden" name="current[province]" id="curr_province_name" value="{{ old('current.province') }}">
    <input type="hidden" name="current[province_code]" id="curr_province_code"
      value="{{ old('current.province_code') }}" required>
    @error('current.province')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 3. City / Municipality --}}
  <div class="col-md-6">
    <label class="form-label">City / Municipality <span class="text-danger">*</span></label>
    <select id="curr_city" class="form-select @error('current.city') is-invalid @enderror" disabled>
      <option value="">— Select City / Municipality —</option>
    </select>
    <input type="hidden" name="current[city]" id="curr_city_name" value="{{ old('current.city') }}">
    <input type="hidden" name="current[city_code]" id="curr_city_code" value="{{ old('current.city_code') }}">
    @error('current.city')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 4. Barangay --}}
  <div class="col-md-6">
    <label class="form-label">Barangay <span class="text-danger">*</span></label>
    <select id="curr_barangay" class="form-select @error('current.barangay') is-invalid @enderror" disabled>
      <option value="">— Select Barangay —</option>
    </select>
    <input type="hidden" name="current[barangay]" id="curr_barangay_name" value="{{ old('current.barangay') }}">
    @error('current.barangay')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  {{-- 5. Street — unlocks after Barangay --}}
  <div class="col-md-5">
    <label class="form-label">Street</label>
    <input type="text" id="curr_street" name="current[street]"
      class="form-control @error('current.street') is-invalid @enderror" value="{{ old('current.street') }}"
      disabled>
    @error('current.street')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 6. Subdivision — unlocks after Barangay --}}
  <div class="col-md-4">
    <label class="form-label">Subdivision / Village</label>
    <input type="text" id="curr_subdivision" name="current[subdivision]"
      class="form-control @error('current.subdivision') is-invalid @enderror"
      value="{{ old('current.subdivision') }}" disabled>
    @error('current.subdivision')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 7. House No — unlocks after Barangay --}}
  <div class="col-md-3">
    <label class="form-label">House / Unit No.</label>
    <input type="text" id="curr_house_number" name="current[house_number]"
      class="form-control @error('current.house_number') is-invalid @enderror"
      value="{{ old('current.house_number') }}" disabled>
    @error('current.house_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 8. ZIP Code — unlocks after Barangay --}}
  <div class="col-md-2">
    <label class="form-label">ZIP Code</label>
    <input type="text" id="curr_zip_code" name="current[zip_code]"
      class="form-control @error('current.zip_code') is-invalid @enderror" value="{{ old('current.zip_code') }}"
      disabled>
    @error('current.zip_code')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

</div>
