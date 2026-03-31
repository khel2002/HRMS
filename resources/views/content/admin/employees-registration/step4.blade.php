{{-- content/admin/employees-registration/step4.blade.php --}}

@php
  $levels = [
      1 => 'Primary Education',
      2 => 'Secondary Education',
      3 => 'Vocational / Trade Course',
      4 => 'College',
      5 => 'Graduate Studies',
  ];
  $oldEdu = old('education', []); // empty array by default — no phantom rows
  $oldGovIds = old('gov_ids', []); // empty array by default — no phantom rows
@endphp

{{-- ════════════════════════════════
     PANEL HEADER
════════════════════════════════ --}}
<div class="wz-panel-head">
  <i class="ri ri-book-open-line"></i>
  <div>
    <h6 class="wz-panel-title">Education & Government IDs</h6>
    <p class="wz-panel-sub text-warning ">Put N/A if not applicable.</p>
  </div>
</div>

{{-- ════════════════════════════════
     PART 1 — EDUCATIONAL BACKGROUND
════════════════════════════════ --}}
<div class="wz-section-label mt-4">
  <span>Educational Background</span>
</div>

<div id="educationContainer">
  {{-- Restored after validation failure via old() --}}
  @foreach ($oldEdu as $i => $edu)
    @php
      $lvId = (int) ($edu['level_id'] ?? $i + 1);
      $lvName = $levels[$lvId] ?? 'Education';
    @endphp
    <div class="edu-row border rounded-2 p-3 mt-3" data-level="{{ $lvId }}">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="edu-level-badge">
          <i class="ri ri-graduation-cap-line me-1"></i>{{ $lvName }}
        </span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEduRow(this)">
          <i class="ri ri-delete-bin-line me-1"></i> Remove
        </button>
      </div>
      <input type="hidden" name="education[{{ $i }}][level_id]" value="{{ $lvId }}">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">School / University Name <span class="text-danger">*</span></label>
          <input type="text" name="education[{{ $i }}][school_name]"
            class="form-control @error("education.{$i}.school_name") is-invalid @enderror"
            value="{{ $edu['school_name'] ?? '' }}" required>
          @error("education.{$i}.school_name")
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">Degree / Course</label>
          <input type="text" name="education[{{ $i }}][degree_course]" class="form-control"
            value="{{ $edu['degree_course'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">From (Year)</label>
          <input type="number" name="education[{{ $i }}][period_from]" class="form-control" min="1950"
            max="2099" value="{{ $edu['period_from'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">To (Year)</label>
          <input type="number" name="education[{{ $i }}][period_to]" class="form-control" min="1950"
            max="2099" value="{{ $edu['period_to'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Year Graduated</label>
          <input type="number" name="education[{{ $i }}][year_graduated]" class="form-control"
            min="1950" max="2099" value="{{ $edu['year_graduated'] ?? '' }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Highest Level / Units</label>
          <input type="text" name="education[{{ $i }}][highest_level_units]" class="form-control"
            value="{{ $edu['highest_level_units'] ?? '' }}">
        </div>
        <div class="col-md-6">
          <label class="form-label">Scholarship / Honors</label>
          <input type="text" name="education[{{ $i }}][scholarship_honors]" class="form-control"
            value="{{ $edu['scholarship_honors'] ?? '' }}">
        </div>
      </div>
    </div>
  @endforeach
</div>

{{-- Add Education button — JS updates label per sequence and hides after level 5 --}}
<div class="mt-3 d-flex align-items-center gap-3">
  <button type="button" class="btn btn-outline-primary btn-sm" id="addEduBtn">
    <i class="ri ri-add-line me-1"></i>
    <span id="addEduBtnLabel">Add Primary Education</span>
  </button>
  <span id="addEduHint" class="text-muted" style="font-size:.8rem;"></span>
</div>

{{-- Tells JS how many old edu rows exist so the sequence continues correctly --}}
<div id="eduMeta" data-old-count="{{ count($oldEdu) }}" style="display:none;"></div>

{{-- ════════════════════════════════
     DIVIDER
════════════════════════════════ --}}
<hr class="my-4">

{{-- ════════════════════════════════
     PART 2 — GOVERNMENT IDs
     Completely separate section.
     No connection to education rows.
════════════════════════════════ --}}
<div class="wz-section-label">
  <span>Government IDs</span>
</div>

<p class="text-muted mt-1 mb-2" style="font-size:.82rem;">
  Add each government-issued ID number (SSS, PhilHealth, Pag-IBIG, TIN, etc.)
</p>

<div id="govIdsContainer">
  {{-- Only loop real saved entries — $oldGovIds defaults to [] so nothing renders on fresh load --}}
  @foreach ($oldGovIds as $i => $gid)
    @if (!empty($gid['name']))
      <div class="govid-row row g-2 align-items-center mt-2">

        <div class="col-md-5">
          <input type="text" name="gov_ids[{{ $i }}][name]" class="form-control" placeholder="ID Name"
            required value="{{ $gid['name'] }}">
        </div>
        <div class="col-md-5">
          <input type="text" name="gov_ids[{{ $i }}][id_number]" class="form-control"
            placeholder="ID Number" required value="{{ $gid['id_number'] ?? '' }}">
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

<button type="button" class="btn btn-outline-primary btn-sm mt-3" id="addGovIdBtn">
  <i class="ri ri-add-line me-1"></i> Add Government ID
</button>

<style>
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
