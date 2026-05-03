<div class="ss-section">
  <div class="ss-section-title">
    <i class="ri-user-settings-line"></i>
    Employee Information
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6">
      <label class="ss-label">Select Employee</label>
      <div class="ss-employee-select">
        <select class="ss-select-inner" id="selectEmployee">
          <option value="" {{ $selectedEmployee ? '' : 'selected' }}>Select an employee…</option>
          @foreach ($employees as $emp)
            <option value="{{ $emp->id }}" data-employee-number="{{ $emp->employee_number }}"
              data-dept="{{ $emp->office?->office_name ?? '—' }}" data-pos="{{ $emp->position?->position_name ?? '—' }}"
              data-status="{{ $emp->status }}" data-hired="{{ $emp->created_at?->format('M d, Y') ?? '—' }}"
              data-avatar="{{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}"
              {{ $selectedEmployee?->id === $emp->id ? 'selected' : '' }}>
              {{ $emp->full_name }} ({{ $emp->employee_number }})
            </option>
          @endforeach
        </select>
        <i class="ri-arrow-down-s-line ss-chevron"></i>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label class="ss-label">Employee ID</label>
      <div class="ss-input-icon">
        <i class="ri-id-card-line ss-icon-left"></i>
        <input type="text" class="ss-input ss-readonly" id="employeeId" readonly>
      </div>
    </div>
  </div>


  <div class="row g-3">
    <div class="col-12 col-sm-6 col-xl-3">
      <label class="ss-label">Department</label>
      <input type="text" class="ss-input ss-readonly" id="department" readonly>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <label class="ss-label">Position</label>
      <input type="text" class="ss-input ss-readonly" id="position" readonly>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <label class="ss-label">Employment Status</label>
      <div>
        <span
          class="ss-status-badge {{ ($selectedEmployee?->status ?? '') === 'active' ? 'ss-status-active' : 'ss-status-inactive' }}"
          id="empStatusBadge">
          {{ $selectedEmployee ? ucfirst($selectedEmployee->status ?? 'inactive') : '—' }}
        </span>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <label class="ss-label">Date Hired</label>
      <div class="ss-input-icon">
        <input type="text" class="ss-input ss-readonly" id="dateHired"
          value="{{ $selectedEmployee?->created_at?->format('M d, Y') ?? '' }}" readonly>
        <i class="ri-calendar-line ss-icon-right"></i>
      </div>
    </div>
  </div>
</div>
