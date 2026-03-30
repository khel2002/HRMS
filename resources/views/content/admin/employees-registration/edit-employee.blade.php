@extends('layouts/contentNavbarLayout')
@section('title', 'Edit Employee — ' . $employee->last_name . ', ' . $employee->first_name)

@section('vendor-style')
  <link rel="stylesheet" href="{{ asset('assets/css/registration.css') }}">
@endsection

@section('content')

  {{-- ══════════════════════════════════════════════════════ ERRORS --}}
  @if ($errors->any())
    <div class="alert alert-danger mb-4">
      <strong><i class="ri ri-error-warning-line me-1"></i>Please fix the following:</strong>
      <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger mb-4"><i class="ri ri-error-warning-line me-1"></i>{{ session('error') }}</div>
  @endif

  {{-- ══════════════════════════════════════════════════════ HEADER --}}
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('employee-show', Crypt::encryptString($employee->id)) }}" class="btn btn-sm btn-outline-secondary">
      <i class="ri ri-arrow-left-line me-1"></i> Back
    </a>
    <div>
      <h5 class="mb-0 fw-bold" style="color:#696cff;">Edit Employee</h5>
      <small class="text-muted">
        {{ $employee->last_name }}, {{ $employee->first_name }} — {{ $employee->employee_number }}
      </small>
    </div>
  </div>

  <form method="POST" action="{{ route('employee-update', Crypt::encryptString($employee->id)) }}">
    @csrf @method('PUT')

    {{-- ══════════════════════════════════════════════════════
       1 — PERSONAL INFORMATION
  ══════════════════════════════════════════════════════ --}}
    <div class="es-card mb-4">
      <div class="es-head"><i class="ri ri-user-3-line me-2"></i>Personal Information</div>
      <div class="es-body">
        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Employee Number <span class="text-danger">*</span></label>
            <input type="text" name="employee_number"
              class="form-control @error('employee_number') is-invalid @enderror"
              value="{{ old('employee_number', $employee->employee_number) }}" required>
            @error('employee_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Position / Designation <span class="text-danger">*</span></label>
            <select name="position_id" class="form-select @error('position_id') is-invalid @enderror" required>
              <option value=""></option>
              @foreach (\App\Models\EmployeePosition::orderBy('position_name')->get() as $position)
                <option value="{{ $position->id }}"
                  {{ old('position_id', $employee->position_id) == $position->id ? 'selected' : '' }}>
                  {{ $position->position_name }}
                </option>
              @endforeach
            </select>
            @error('position_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Office / Unit <span class="text-danger">*</span></label>
            <select name="office_id" class="form-select @error('office_id') is-invalid @enderror" required>
              <option value=""></option>
              @foreach (\App\Models\Office::orderBy('office_name')->get() as $office)
                <option value="{{ $office->id }}"
                  {{ old('office_id', $employee->office_id) == $office->id ? 'selected' : '' }}>
                  {{ $office->office_name }}
                </option>
              @endforeach
            </select>
            @error('office_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
              value="{{ old('first_name', $employee->first_name) }}" required>
            @error('first_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
              value="{{ old('middle_name', $employee->middle_name) }}">
            @error('middle_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
              value="{{ old('last_name', $employee->last_name) }}" required>
            @error('last_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
              value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" required>
            @error('date_of_birth')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
            <input type="text" name="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror"
              value="{{ old('place_of_birth', $employee->place_of_birth) }}" required>
            @error('place_of_birth')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
              <option value="">— Select —</option>
              @foreach (\App\Models\Employee::GENDERS as $g)
                <option value="{{ $g }}" {{ old('gender', $employee->gender) == $g ? 'selected' : '' }}>
                  {{ ucfirst($g) }}
                </option>
              @endforeach
            </select>
            @error('gender')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Civil Status <span class="text-danger">*</span></label>
            <select name="civil_status" class="form-select @error('civil_status') is-invalid @enderror" required>
              <option value="">— Select —</option>
              <option value="single" {{ old('civil_status', $employee->civil_status) == 'single' ? 'selected' : '' }}>
                Single
              </option>
              <option value="married" {{ old('civil_status', $employee->civil_status) == 'married' ? 'selected' : '' }}>
                Married</option>
              <option value="widow" {{ old('civil_status', $employee->civil_status) == 'widow' ? 'selected' : '' }}>
                Widow /
                Widower</option>
            </select>
            @error('civil_status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Blood Type <span class="text-danger">*</span></label>
            <select name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" required>
              <option value="">— Select —</option>
              @foreach (\App\Models\Employee::BLOOD_TYPES as $bt)
                <option value="{{ $bt }}"
                  {{ old('blood_type', $employee->blood_type) == $bt ? 'selected' : '' }}>
                  {{ $bt }}</option>
              @endforeach
            </select>
            @error('blood_type')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label">Citizenship <span class="text-danger">*</span></label>
            <input type="text" name="citizenship" class="form-control @error('citizenship') is-invalid @enderror"
              value="{{ old('citizenship', $employee->citizenship) }}" required>
            @error('citizenship')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
            <input type="text" name="mobile_number"
              class="form-control @error('mobile_number') is-invalid @enderror"
              value="{{ old('mobile_number', $employee->mobile_number) }}" required>
            @error('mobile_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
              value="{{ old('email', $employee->email) }}" required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Height (cm) <span class="text-danger">*</span></label>
            <input type="number" name="height_cm" step="0.01" min="0"
              class="form-control @error('height_cm') is-invalid @enderror"
              value="{{ old('height_cm', $employee->height_cm) }}" required>
            @error('height_cm')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Weight (kg) <span class="text-danger">*</span></label>
            <input type="number" name="weight_kg" step="0.01" min="0"
              class="form-control @error('weight_kg') is-invalid @enderror"
              value="{{ old('weight_kg', $employee->weight_kg) }}" required>
            @error('weight_kg')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

        </div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
       2 — PERMANENT ADDRESS
  ══════════════════════════════════════════════════════ --}}
    @php $pa = $employee->permanentAddress; @endphp
    <div class="es-card mb-4">
      <div class="es-head"><i class="ri ri-map-pin-2-line me-2"></i>Permanent Address</div>
      <div class="es-body">
        <p class="text-muted mb-3" style="font-size:.82rem;">
          <i class="ri ri-information-line me-1"></i>
          Address fields are free-text on the edit form. Province, City, and Barangay names are stored as-is.
        </p>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Province</label>
            <input type="text" name="permanent[province]"
              class="form-control @error('permanent.province') is-invalid @enderror"
              value="{{ old('permanent.province', $pa?->province) }}">
            @error('permanent.province')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">City / Municipality</label>
            <input type="text" name="permanent[city]"
              class="form-control @error('permanent.city') is-invalid @enderror"
              value="{{ old('permanent.city', $pa?->city) }}">
            @error('permanent.city')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Barangay</label>
            <input type="text" name="permanent[barangay]"
              class="form-control @error('permanent.barangay') is-invalid @enderror"
              value="{{ old('permanent.barangay', $pa?->barangay) }}">
            @error('permanent.barangay')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Street</label>
            <input type="text" name="permanent[street]"
              class="form-control @error('permanent.street') is-invalid @enderror"
              value="{{ old('permanent.street', $pa?->street) }}">
            @error('permanent.street')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Subdivision / Village</label>
            <input type="text" name="permanent[subdivision]"
              class="form-control @error('permanent.subdivision') is-invalid @enderror"
              value="{{ old('permanent.subdivision', $pa?->subdivision) }}">
            @error('permanent.subdivision')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">House / Unit No.</label>
            <input type="text" name="permanent[house_number]"
              class="form-control @error('permanent.house_number') is-invalid @enderror"
              value="{{ old('permanent.house_number', $pa?->house_number) }}">
            @error('permanent.house_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">ZIP Code</label>
            <input type="text" name="permanent[zip_code]"
              class="form-control @error('permanent.zip_code') is-invalid @enderror"
              value="{{ old('permanent.zip_code', $pa?->zip_code) }}">
            @error('permanent.zip_code')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
       3 — CURRENT ADDRESS
  ══════════════════════════════════════════════════════ --}}
    @php $ca = $employee->currentAddress; @endphp
    <div class="es-card mb-4">
      <div class="es-head">
        <i class="ri ri-home-4-line me-2"></i>Current Address
        <div class="form-check ms-auto mb-0">
          <input class="form-check-input" type="checkbox" id="editSameAsPerm" onchange="editCopyAddress(this)">
          <label class="form-check-label" for="editSameAsPerm"
            style="font-size:.78rem;font-weight:400;text-transform:none;letter-spacing:0;">
            Same as permanent
          </label>
        </div>
      </div>
      <div class="es-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Province</label>
            <input type="text" id="ce_province" name="current[province]"
              class="form-control @error('current.province') is-invalid @enderror"
              value="{{ old('current.province', $ca?->province) }}">
            @error('current.province')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">City / Municipality</label>
            <input type="text" id="ce_city" name="current[city]"
              class="form-control @error('current.city') is-invalid @enderror"
              value="{{ old('current.city', $ca?->city) }}">
            @error('current.city')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Barangay</label>
            <input type="text" id="ce_barangay" name="current[barangay]"
              class="form-control @error('current.barangay') is-invalid @enderror"
              value="{{ old('current.barangay', $ca?->barangay) }}">
            @error('current.barangay')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Street</label>
            <input type="text" id="ce_street" name="current[street]"
              class="form-control @error('current.street') is-invalid @enderror"
              value="{{ old('current.street', $ca?->street) }}">
            @error('current.street')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Subdivision / Village</label>
            <input type="text" id="ce_subdivision" name="current[subdivision]"
              class="form-control @error('current.subdivision') is-invalid @enderror"
              value="{{ old('current.subdivision', $ca?->subdivision) }}">
            @error('current.subdivision')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">House / Unit No.</label>
            <input type="text" id="ce_house_number" name="current[house_number]"
              class="form-control @error('current.house_number') is-invalid @enderror"
              value="{{ old('current.house_number', $ca?->house_number) }}">
            @error('current.house_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">ZIP Code</label>
            <input type="text" id="ce_zip_code" name="current[zip_code]"
              class="form-control @error('current.zip_code') is-invalid @enderror"
              value="{{ old('current.zip_code', $ca?->zip_code) }}">
            @error('current.zip_code')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
       4 — FAMILY BACKGROUND
  ══════════════════════════════════════════════════════ --}}
    @php $fam = $employee->family; @endphp
    <div class="es-card mb-4">
      <div class="es-head"><i class="ri ri-team-line me-2"></i>Family Background</div>
      <div class="es-body">
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Father's Name <span class="text-danger">*</span></label>
            <input type="text" name="family[father_name]"
              class="form-control @error('family.father_name') is-invalid @enderror"
              value="{{ old('family.father_name', $fam?->father_name) }}" required>
            @error('family.father_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Mother's Maiden Name <span class="text-danger">*</span></label>
            <input type="text" name="family[mother_name]"
              class="form-control @error('family.mother_name') is-invalid @enderror"
              value="{{ old('family.mother_name', $fam?->mother_name) }}" required>
            @error('family.mother_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Spouse Name</label>
            <input type="text" name="family[spouse_name]"
              class="form-control @error('family.spouse_name') is-invalid @enderror"
              value="{{ old('family.spouse_name', $fam?->spouse_name) }}">
            @error('family.spouse_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Spouse Occupation</label>
            <input type="text" name="family[spouse_occupation]"
              class="form-control @error('family.spouse_occupation') is-invalid @enderror"
              value="{{ old('family.spouse_occupation', $fam?->spouse_occupation) }}">
            @error('family.spouse_occupation')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Spouse Employer</label>
            <input type="text" name="family[spouse_employer]"
              class="form-control @error('family.spouse_employer') is-invalid @enderror"
              value="{{ old('family.spouse_employer', $fam?->spouse_employer) }}">
            @error('family.spouse_employer')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Spouse Business Address</label>
            <input type="text" name="family[spouse_business_address]"
              class="form-control @error('family.spouse_business_address') is-invalid @enderror"
              value="{{ old('family.spouse_business_address', $fam?->spouse_business_address) }}">
            @error('family.spouse_business_address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

        </div>

        {{-- Emergency Contact --}}
        <div class="es-subhead mt-4 mb-2"><i class="ri ri-alarm-warning-line me-1"></i>Emergency Contact</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Contact Name <span class="text-danger">*</span></label>
            <input type="text" name="family[emergency_contact_name]"
              class="form-control @error('family.emergency_contact_name') is-invalid @enderror"
              value="{{ old('family.emergency_contact_name', $fam?->emergency_contact_name) }}" required>
            @error('family.emergency_contact_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
            <input type="text" name="family[emergency_contact_number]"
              class="form-control @error('family.emergency_contact_number') is-invalid @enderror"
              value="{{ old('family.emergency_contact_number', $fam?->emergency_contact_number) }}" required>
            @error('family.emergency_contact_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Relationship <span class="text-danger">*</span></label>
            <input type="text" name="family[emergency_relationship]"
              class="form-control @error('family.emergency_relationship') is-invalid @enderror"
              value="{{ old('family.emergency_relationship', $fam?->emergency_relationship) }}" required>
            @error('family.emergency_relationship')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        {{-- Children --}}
        <div class="es-subhead mt-4 mb-2"><i class="ri ri-user-heart-line me-1"></i>Children</div>
        <div id="editChildrenContainer">
          @foreach (old('children', $employee->children->toArray()) as $i => $child)
            <div class="child-row row g-2 align-items-end mt-2">
              <div class="col-md-6">
                <label class="form-label">Child's Full Name</label>
                <input type="text" name="children[{{ $i }}][child_name]" class="form-control"
                  value="{{ is_array($child) ? $child['child_name'] ?? '' : $child['child_name'] ?? '' }}">
              </div>
              <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                @php
                  $dob = is_array($child) ? $child['date_of_birth'] ?? '' : $child['date_of_birth'] ?? '';
                  if ($dob && !is_string($dob)) {
                      $dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                  } elseif (is_string($dob) && strlen($dob) > 10) {
                      $dob = substr($dob, 0, 10);
                  }
                @endphp
                <input type="date" name="children[{{ $i }}][date_of_birth]" class="form-control"
                  value="{{ $dob }}">
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm w-100"
                  onclick="this.closest('.child-row').remove()">
                  <i class="ri ri-delete-bin-line me-1"></i> Remove
                </button>
              </div>
            </div>
          @endforeach
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="editAddChildBtn">
          <i class="ri ri-add-line me-1"></i> Add Child
        </button>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
       5 — EDUCATIONAL BACKGROUND
  ══════════════════════════════════════════════════════ --}}
    @php
      $eduLevels = [
          1 => 'Primary Education',
          2 => 'Secondary Education',
          3 => 'Vocational / Trade Course',
          4 => 'College',
          5 => 'Graduate Studies',
      ];
      $existingEdu = old('education')
          ? collect(old('education'))->values()
          : $employee->education->sortBy('level_id')->values();
      $editEduCount = $existingEdu->count();
    @endphp
    <div class="es-card mb-4">
      <div class="es-head"><i class="ri ri-book-open-line me-2"></i>Educational Background</div>
      <div class="es-body">
        <div id="editEducationContainer">
          @foreach ($existingEdu as $i => $edu)
            @php
              $lvId = is_array($edu) ? (int) ($edu['level_id'] ?? 1) : (int) $edu->level_id;
              $lvName = $eduLevels[$lvId] ?? 'Education';
            @endphp
            <div class="edu-row border rounded-2 p-3 {{ $loop->first ? '' : 'mt-3' }}"
              data-level="{{ $lvId }}">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="edu-level-badge"><i class="ri ri-graduation-cap-line me-1"></i>{{ $lvName }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger"
                  onclick="this.closest('.edu-row').remove(); editSyncEduBtn()">
                  <i class="ri ri-delete-bin-line me-1"></i> Remove
                </button>
              </div>
              <input type="hidden" name="education[{{ $i }}][level_id]" value="{{ $lvId }}">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">School / University Name <span class="text-danger">*</span></label>
                  <input type="text" name="education[{{ $i }}][school_name]" class="form-control"
                    value="{{ is_array($edu) ? $edu['school_name'] ?? '' : $edu->school_name }}" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Degree / Course</label>
                  <input type="text" name="education[{{ $i }}][degree_course]" class="form-control"
                    value="{{ is_array($edu) ? $edu['degree_course'] ?? '' : $edu->degree_course }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">From (Year)</label>
                  <input type="number" name="education[{{ $i }}][period_from]" class="form-control"
                    min="1950" max="2099"
                    value="{{ is_array($edu) ? $edu['period_from'] ?? '' : $edu->period_from }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">To (Year)</label>
                  <input type="number" name="education[{{ $i }}][period_to]" class="form-control"
                    min="1950" max="2099"
                    value="{{ is_array($edu) ? $edu['period_to'] ?? '' : $edu->period_to }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Year Graduated</label>
                  <input type="number" name="education[{{ $i }}][year_graduated]" class="form-control"
                    min="1950" max="2099"
                    value="{{ is_array($edu) ? $edu['year_graduated'] ?? '' : $edu->year_graduated }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Highest Level / Units</label>
                  <input type="text" name="education[{{ $i }}][highest_level_units]"
                    class="form-control"
                    value="{{ is_array($edu) ? $edu['highest_level_units'] ?? '' : $edu->highest_level_units }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Scholarship / Honors</label>
                  <input type="text" name="education[{{ $i }}][scholarship_honors]"
                    class="form-control"
                    value="{{ is_array($edu) ? $edu['scholarship_honors'] ?? '' : $edu->scholarship_honors }}">
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="mt-3 d-flex align-items-center gap-3">
          <button type="button" class="btn btn-outline-primary btn-sm" id="editAddEduBtn">
            <i class="ri ri-add-line me-1"></i> <span id="editAddEduLabel">Add Education</span>
          </button>
          <span id="editAddEduHint" class="text-muted" style="font-size:.8rem;"></span>
        </div>
        <div id="editEduMeta" data-count="{{ $editEduCount }}" style="display:none;"></div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
       6 — GOVERNMENT IDs
  ══════════════════════════════════════════════════════ --}}
    <div class="es-card mb-4">
      <div class="es-head"><i class="ri ri-government-line me-2"></i>Government IDs</div>
      <div class="es-body">
        <p class="text-muted mb-3" style="font-size:.82rem;">SSS, PhilHealth, Pag-IBIG, TIN, etc.</p>
        <div id="editGovIdsContainer">
          @foreach (old('gov_ids', $employee->governmentIds->toArray()) as $i => $gid)
            @php
              $gname = is_array($gid) ? $gid['name'] ?? '' : $gid['name'] ?? '';
              $gnumber = is_array($gid) ? $gid['id_number'] ?? '' : $gid['id_number'] ?? '';
            @endphp
            @if ($gname)
              <div class="govid-row row g-2 align-items-center mt-2">
                <div class="col-md-5">
                  <input type="text" name="gov_ids[{{ $i }}][name]" class="form-control"
                    placeholder="ID Name" required value="{{ $gname }}">
                </div>
                <div class="col-md-5">
                  <input type="text" name="gov_ids[{{ $i }}][id_number]" class="form-control"
                    placeholder="ID Number" required value="{{ $gnumber }}">
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-outline-danger btn-sm w-100"
                    onclick="this.closest('.govid-row').remove()">
                    <i class="ri ri-delete-bin-line"></i>
                  </button>
                </div>
              </div>
            @endif
          @endforeach
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="editAddGovIdBtn">
          <i class="ri ri-add-line me-1"></i> Add Government ID
        </button>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ FOOTER --}}
    <div class="d-flex justify-content-between align-items-center pb-2">
      <a href="{{ route('employee-show', Crypt::encryptString($employee->id)) }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i> Cancel
      </a>
      <button type="submit" class="btn btn-success px-4">
        <i class="ri ri-save-line me-1"></i> Save Changes
      </button>
    </div>

  </form>

  {{-- ══════════════════════════════════════════════════════ STYLES --}}
  <style>
    .es-card {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .05), 0 4px 16px rgba(105, 108, 255, .07);
      overflow: hidden;
      margin-bottom: 1.25rem;
    }

    .es-head {
      display: flex;
      align-items: center;
      padding: .75rem 1.25rem;
      background: #f8f7ff;
      font-weight: 700;
      font-size: .78rem;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: #696cff;
      border-bottom: 1px solid #ebebff;
    }

    .es-body {
      padding: 1.25rem;
    }

    .es-subhead {
      font-size: .73rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #696cff;
      border-top: 1px dashed #ebebff;
      padding-top: .75rem;
    }

    .edu-level-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 14px;
      background: #696cff;
      color: #fff;
      border-radius: 20px;
      font-size: .8rem;
      font-weight: 600;
      letter-spacing: .03em;
    }
  </style>

  {{-- ══════════════════════════════════════════════════════ SCRIPT --}}
  <script>
    (function() {
      const EDU_LEVELS = [{
          id: 1,
          name: 'Primary Education',
          ph: 'e.g. San Lorenzo Elementary School'
        },
        {
          id: 2,
          name: 'Secondary Education',
          ph: 'e.g. Rizal National High School'
        },
        {
          id: 3,
          name: 'Vocational / Trade Course',
          ph: 'e.g. TESDA Training Center'
        },
        {
          id: 4,
          name: 'College',
          ph: 'e.g. University of the Philippines'
        },
        {
          id: 5,
          name: 'Graduate Studies',
          ph: 'e.g. Ateneo de Manila University'
        },
      ];

      let eduRowIdx = parseInt(document.getElementById('editEduMeta')?.dataset.count || '0');
      let childIdx = document.querySelectorAll('#editChildrenContainer .child-row').length;
      let govIdIdx = document.querySelectorAll('#editGovIdsContainer .govid-row').length;

      function usedLevels() {
        return new Set([...document.querySelectorAll('#editEducationContainer .edu-row')]
          .map(r => parseInt(r.dataset.level)));
      }

      function nextLevel() {
        const used = usedLevels();
        const i = EDU_LEVELS.findIndex(l => !used.has(l.id));
        return i === -1 ? null : EDU_LEVELS[i];
      }

      window.editSyncEduBtn = function() {
        const btn = document.getElementById('editAddEduBtn');
        const label = document.getElementById('editAddEduLabel');
        const hint = document.getElementById('editAddEduHint');
        const next = nextLevel();
        if (!btn) return;
        if (!next) {
          btn.style.display = 'none';
          if (hint) hint.textContent = '✓ All education levels added.';
        } else {
          btn.style.display = 'inline-flex';
          if (label) label.textContent = 'Add ' + next.name;
          if (hint) hint.textContent = '';
        }
      };

      document.addEventListener('DOMContentLoaded', () => {
        editSyncEduBtn();

        // ── Add Education ──────────────────────────────────────
        document.getElementById('editAddEduBtn')?.addEventListener('click', () => {
          const level = nextLevel();
          if (!level) return;
          const idx = eduRowIdx++;
          document.getElementById('editEducationContainer').insertAdjacentHTML('beforeend', `
        <div class="edu-row border rounded-2 p-3 mt-3" data-level="${level.id}">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="edu-level-badge"><i class="ri ri-graduation-cap-line me-1"></i>${level.name}</span>
            <button type="button" class="btn btn-sm btn-outline-danger"
              onclick="this.closest('.edu-row').remove(); editSyncEduBtn()">
              <i class="ri ri-delete-bin-line me-1"></i> Remove
            </button>
          </div>
          <input type="hidden" name="education[${idx}][level_id]" value="${level.id}">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">School / University Name <span class="text-danger">*</span></label>
              <input type="text" name="education[${idx}][school_name]" class="form-control" placeholder="${level.ph}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Degree / Course</label>
              <input type="text" name="education[${idx}][degree_course]" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">From (Year)</label>
              <input type="number" name="education[${idx}][period_from]" class="form-control" min="1950" max="2099">
            </div>
            <div class="col-md-3">
              <label class="form-label">To (Year)</label>
              <input type="number" name="education[${idx}][period_to]" class="form-control" min="1950" max="2099">
            </div>
            <div class="col-md-3">
              <label class="form-label">Year Graduated</label>
              <input type="number" name="education[${idx}][year_graduated]" class="form-control" min="1950" max="2099">
            </div>
            <div class="col-md-3">
              <label class="form-label">Highest Level / Units</label>
              <input type="text" name="education[${idx}][highest_level_units]" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Scholarship / Honors</label>
              <input type="text" name="education[${idx}][scholarship_honors]" class="form-control">
            </div>
          </div>
        </div>`);
          editSyncEduBtn();
        });

        // ── Add Government ID ──────────────────────────────────
        document.getElementById('editAddGovIdBtn')?.addEventListener('click', () => {
          document.getElementById('editGovIdsContainer').insertAdjacentHTML('beforeend', `
        <div class="govid-row row g-2 align-items-center mt-2">
          <div class="col-md-5">
            <input type="text" name="gov_ids[${govIdIdx}][name]" class="form-control"
              placeholder="ID Name (e.g. SSS)">
          </div>
          <div class="col-md-5">
            <input type="text" name="gov_ids[${govIdIdx}][id_number]" class="form-control"
              placeholder="ID Number">
          </div>
          <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm w-100"
              onclick="this.closest('.govid-row').remove()">
              <i class="ri ri-delete-bin-line"></i>
            </button>
          </div>
        </div>`);
          govIdIdx++;
        });

        // ── Add Child ──────────────────────────────────────────
        document.getElementById('editAddChildBtn')?.addEventListener('click', () => {
          document.getElementById('editChildrenContainer').insertAdjacentHTML('beforeend', `
        <div class="child-row row g-2 align-items-end mt-2">
          <div class="col-md-6">
            <label class="form-label">Child's Full Name</label>
            <input type="text" name="children[${childIdx}][child_name]" class="form-control" placeholder="Full Name">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="children[${childIdx}][date_of_birth]" class="form-control">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm w-100"
              onclick="this.closest('.child-row').remove()">
              <i class="ri ri-delete-bin-line me-1"></i> Remove
            </button>
          </div>
        </div>`);
          childIdx++;
        });
      });

      // ── Same as permanent ──────────────────────────────────
      window.editCopyAddress = function(cb) {
        const fields = ['province', 'city', 'barangay', 'street', 'subdivision', 'house_number', 'zip_code'];
        fields.forEach(f => {
          const src = document.querySelector(`[name="permanent[${f}]"]`);
          const dst = document.getElementById(`ce_${f}`);
          if (!src || !dst) return;
          dst.value = cb.checked ? src.value : '';
          dst.readOnly = cb.checked;
        });
      };
    })();
  </script>

@endsection
