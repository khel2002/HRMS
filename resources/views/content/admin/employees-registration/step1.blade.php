<div class="wz-panel-head">
  <i class="ri ri-user-3-line"></i>
  <div>
    <h6 class="wz-panel-title">Personal Information</h6>
    <p class="wz-panel-sub text-warning ">Put N/A if not applicable.</p>
  </div>
</div>

<div class="row g-3 mt-1">

  {{-- Employee Number --}}
  <div class="col-md-4">
    <label class="form-label">Employee Number <span class="text-danger">*</span></label>
    <input type="text" name="employee_number" class="form-control @error('employee_number') is-invalid @enderror"
      placeholder="EMP-000#" value="{{ old('employee_number') }}" required>
    @error('employee_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- First Name --}}
  <div class="col-md-4">
    <label class="form-label">First Name <span class="text-danger">*</span></label>
    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
      value="{{ old('first_name') }}" required>
    @error('first_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Middle Name --}}
  <div class="col-md-4">
    <label class="form-label">Middle Name </label>
    <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
      value="{{ old('middle_name') }}">
    @error('middle_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Last Name --}}
  <div class="col-md-4">
    <label class="form-label">Last Name <span class="text-danger">*</span></label>
    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
      value="{{ old('last_name') }}" required>
    @error('last_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Date of Birth --}}
  <div class="col-md-4">
    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
      value="{{ old('date_of_birth') }}" required>
    @error('date_of_birth')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Place of Birth --}}
  <div class="col-md-4">
    <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
    <input type="text" name="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror"
      value="{{ old('place_of_birth') }}" required>
    @error('place_of_birth')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Gender --}}
  <div class="col-md-3">
    <label class="form-label">Gender <span class="text-danger">*</span></label>
    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
      <option value="">— Select —</option>
      @foreach (\App\Models\Employee::GENDERS as $g)
        <option value="{{ $g }}" {{ old('gender') == $g ? 'selected' : '' }}>
          {{ ucfirst($g) }}
        </option>
      @endforeach
    </select>
    @error('gender')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Civil Status — values must match DB enum: single, married, widow --}}
  <div class="col-md-3">
    <label class="form-label">Civil Status <span class="text-danger">*</span></label>
    <select name="civil_status" class="form-select @error('civil_status') is-invalid @enderror" required>
      <option value="">— Select —</option>
      <option value="single" {{ old('civil_status') == 'single' ? 'selected' : '' }}>Single</option>
      <option value="married" {{ old('civil_status') == 'married' ? 'selected' : '' }}>Married</option>
      <option value="widow" {{ old('civil_status') == 'widow' ? 'selected' : '' }}>Widow / Widower</option>
    </select>
    @error('civil_status')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Blood Type --}}
  <div class="col-md-3">
    <label class="form-label">Blood Type <span class="text-danger">*</span></label>
    <select name="blood_type" class="form-select @error('blood_type') is-invalid @enderror" required>
      <option value="">— Select —</option>
      @foreach (\App\Models\Employee::BLOOD_TYPES as $bt)
        <option value="{{ $bt }}" {{ old('blood_type') == $bt ? 'selected' : '' }}>
          {{ $bt }}
        </option>
      @endforeach
    </select>
    @error('blood_type')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Citizenship --}}
  <div class="col-md-3">
    <label class="form-label">Citizenship <span class="text-danger">*</span></label>
    <input type="text" name="citizenship" class="form-control @error('citizenship') is-invalid @enderror"
      placeholder="" value="{{ old('citizenship') }}" required>
    @error('citizenship')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Mobile Number --}}
  <div class="col-md-4">
    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
    <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror"
      placeholder="+63" value="{{ old('mobile_number') }}" required>
    @error('mobile_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>


  <div class="col-md-4">
    <label class="form-label">Email Address <span class="text-danger">*</span></label>
    <input type="text" name="email" class="form-control @error('email') is-invalid @enderror"
      placeholder="@gmail.com" value="{{ old('email') }}" required>
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>


  <div class="col-md-2">
    <label class="form-label">Height (cm) <span class="text-danger">*</span></label>
    <input type="number" name="height_cm" class="form-control @error('height_cm') is-invalid @enderror"
      placeholder="165.00" step="0.01" min="0" value="{{ old('height_cm') }}" required>
    @error('height_cm')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- Weight --}}
  <div class="col-md-2">
    <label class="form-label">Weight (kg) <span class="text-danger">*</span></label>
    <input type="number" name="weight_kg" class="form-control @error('weight_kg') is-invalid @enderror"
      placeholder="60.00" step="0.01" min="0" value="{{ old('weight_kg') }}" required>
    @error('weight_kg')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

</div>
