@extends('layouts/contentNavbarLayout')
@section('title', 'Employee — ' . $employee->last_name . ', ' . $employee->first_name)

@section('content')

  {{-- ══════════════════════════════════════════════════════ TOAST --}}
  @if (session('success') || session('error'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
      <div id="flashToast"
        class="toast align-items-center text-white border-0 {{ session('success') ? 'bg-success' : 'bg-danger' }}"
        role="alert" data-bs-delay="4000">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center gap-2">
            <i class="ri {{ session('success') ? 'ri-check-circle-line' : 'ri-error-warning-line' }} fs-5"></i>
            {{ session('success') ?? session('error') }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>
  @endif

  {{-- ══════════════════════════════════════════════════════ HERO --}}
  <div class="emp-hero mb-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <a href="{{ route('employees-index') }}" class="btn btn-sm btn-outline-light">
        <i class="ri ri-arrow-left-line me-1"></i> Back to List
      </a>
      <div class="d-flex gap-2">
        <a href="{{ route('employee-edit', $employee->id) }}" class="btn btn-sm btn-light fw-semibold">
          <i class="ri ri-edit-line me-1"></i> Edit
        </a>
      </div>
    </div>

    <div class="d-flex align-items-center gap-4 flex-wrap">
      <div class="flex-grow-1">
        <h3 class="text-white fw-bold mb-1 lh-1">
          {{ $employee->first_name }}
          {{ $employee->middle_name ? $employee->middle_name . ' ' : '' }}{{ $employee->last_name }}
        </h3>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <span class="ep"><i class="ri ri-id-card-line me-1"></i>{{ $employee->employee_number }}</span>
          <span class="ep"><i class="ri ri-mail-line me-1"></i>{{ $employee->email ?: '—' }}</span>
          <span class="ep"><i class="ri ri-phone-line me-1"></i>{{ $employee->mobile_number ?: '—' }}</span>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        @foreach ([['Gender', ucfirst($employee->gender ?: '—')], ['Civil Status', ucfirst($employee->civil_status ?: '—')], ['Blood Type', $employee->blood_type ?: '—'], ['Citizenship', $employee->citizenship ?: '—'], ['Height', $employee->height_cm ? $employee->height_cm . ' cm' : '—'], ['Weight', $employee->weight_kg ? $employee->weight_kg . ' kg' : '—']] as [$lbl, $val])
          <div class="ec">
            <div class="ec-val">{{ $val }}</div>
            <div class="ec-lbl">{{ $lbl }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════ BODY --}}
  <div class="row g-4">

    {{-- LEFT COLUMN ─────────────────────────── --}}
    <div class="col-lg-6">

      {{-- Personal Details --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-user-3-line me-2"></i>Personal Details</div>
        <div class="sv-body">
          @php $dob = $employee->date_of_birth; @endphp
          @foreach ([
          'Date of Birth' => $dob ? $dob->format('F d, Y') . ' (Age ' . $dob->age . ')' : '—',
          'Place of Birth' => $employee->place_of_birth ?: '—',
          'Registered' => $employee->created_at->format('M d, Y'),
      ] as $lbl => $val)
            <div class="sv-row">
              <span class="sv-lbl">{{ $lbl }}</span>
              <span class="sv-val">{{ $val }}</span>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Permanent Address --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-map-pin-2-line me-2"></i>Permanent Address</div>
        <div class="sv-body">
          @php $pa = $employee->permanentAddress; @endphp
          @if ($pa && $pa->full_address)
            @foreach ([
          'House / Unit No.' => $pa->house_number,
          'Street' => $pa->street,
          'Subdivision' => $pa->subdivision,
          'Barangay' => $pa->barangay,
          'City / Mun.' => $pa->city,
          'Province' => $pa->province,
          'ZIP Code' => $pa->zip_code,
      ] as $lbl => $val)
              @if ($val)
                <div class="sv-row">
                  <span class="sv-lbl">{{ $lbl }}</span>
                  <span class="sv-val">{{ $val }}</span>
                </div>
              @endif
            @endforeach
            <div class="sv-addr-full mt-2">
              <i class="ri ri-map-pin-fill me-1"></i>{{ $pa->full_address }}
            </div>
          @else
            <p class="sv-empty">No permanent address on record.</p>
          @endif
        </div>
      </div>

      {{-- Current Address --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-home-4-line me-2"></i>Current Address</div>
        <div class="sv-body">
          @php $ca = $employee->currentAddress; @endphp
          @if ($ca && $ca->full_address)
            @foreach ([
          'House / Unit No.' => $ca->house_number,
          'Street' => $ca->street,
          'Subdivision' => $ca->subdivision,
          'Barangay' => $ca->barangay,
          'City / Mun.' => $ca->city,
          'Province' => $ca->province,
          'ZIP Code' => $ca->zip_code,
      ] as $lbl => $val)
              @if ($val)
                <div class="sv-row">
                  <span class="sv-lbl">{{ $lbl }}</span>
                  <span class="sv-val">{{ $val }}</span>
                </div>
              @endif
            @endforeach
            <div class="sv-addr-full mt-2">
              <i class="ri ri-map-pin-fill me-1"></i>{{ $ca->full_address }}
            </div>
          @else
            <p class="sv-empty">No current address on record.</p>
          @endif
        </div>
      </div>

      {{-- Government IDs --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-government-line me-2"></i>Government IDs</div>
        <div class="sv-body">
          @forelse($employee->governmentIds as $govId)
            <div class="sv-row">
              <span class="sv-val"><i class="ri ri-price-tag-3-line me-2 text-primary"></i>{{ $govId->name }}</span>
            </div>
          @empty
            <p class="sv-empty">No government IDs recorded.</p>
          @endforelse
        </div>
      </div>

    </div>

    {{-- RIGHT COLUMN ────────────────────────── --}}
    <div class="col-lg-6">

      {{-- Family Background --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-team-line me-2"></i>Family Background</div>
        <div class="sv-body">
          @if ($employee->family)
            @php $fam = $employee->family; @endphp
            @foreach ([
          "Father's Name" => $fam->father_name,
          "Mother's Maiden Name" => $fam->mother_name,
      ] as $lbl => $val)
              <div class="sv-row">
                <span class="sv-lbl">{{ $lbl }}</span>
                <span class="sv-val">{{ $val ?: '—' }}</span>
              </div>
            @endforeach

            @if ($fam->spouse_name)
              <div class="sv-subhead mt-2 mb-1"><i class="ri ri-user-heart-line me-1"></i>Spouse</div>
              @foreach ([
          'Spouse Name' => $fam->spouse_name,
          'Occupation' => $fam->spouse_occupation,
          'Employer' => $fam->spouse_employer,
          'Business Address' => $fam->spouse_business_address,
      ] as $lbl => $val)
                @if ($val)
                  <div class="sv-row">
                    <span class="sv-lbl">{{ $lbl }}</span>
                    <span class="sv-val">{{ $val }}</span>
                  </div>
                @endif
              @endforeach
            @endif

            <div class="sv-subhead mt-3 mb-1"><i class="ri ri-alarm-warning-line me-1"></i>Emergency Contact</div>
            <div class="sv-row">
              <span class="sv-lbl">Name</span>
              <span class="sv-val fw-semibold">{{ $fam->emergency_contact_name ?: '—' }}</span>
            </div>
            <div class="sv-row">
              <span class="sv-lbl">Number</span>
              <span class="sv-val">{{ $fam->emergency_contact_number ?: '—' }}</span>
            </div>
            <div class="sv-row">
              <span class="sv-lbl">Relationship</span>
              <span class="sv-val">{{ $fam->emergency_relationship ?: '—' }}</span>
            </div>
          @else
            <p class="sv-empty">No family record found.</p>
          @endif
        </div>
      </div>

      {{-- Children --}}
      @if ($employee->children->count())
        <div class="sv-card mb-4">
          <div class="sv-head">
            <i class="ri ri-user-heart-line me-2"></i>Children
            <span class="badge bg-primary ms-2 rounded-pill" style="font-size:.7rem;">
              {{ $employee->children->count() }}
            </span>
          </div>
          <div class="sv-body">
            @foreach ($employee->children as $child)
              <div class="sv-child">
                <div class="sv-child-av">{{ strtoupper(substr($child->child_name, 0, 1)) }}</div>
                <div>
                  <div class="fw-semibold" style="font-size:.875rem;">{{ $child->child_name }}</div>
                  @if ($child->date_of_birth)
                    <div class="text-muted" style="font-size:.77rem;">
                      {{ $child->date_of_birth->format('M d, Y') }} · Age {{ $child->date_of_birth->age }}
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Education --}}
      <div class="sv-card mb-4">
        <div class="sv-head"><i class="ri ri-book-open-line me-2"></i>Educational Background</div>
        <div class="sv-body">
          @php
            $lvlNames = [1 => 'Primary', 2 => 'Secondary', 3 => 'Vocational', 4 => 'College', 5 => 'Graduate Studies'];
            $lvlColors = [1 => '#4caf50', 2 => '#2196f3', 3 => '#ff9800', 4 => '#696cff', 5 => '#9c27b0'];
          @endphp
          @forelse($employee->education->sortBy('level_id') as $edu)
            <div class="sv-edu">
              <div class="sv-edu-dot" style="background:{{ $lvlColors[$edu->level_id] ?? '#696cff' }};"></div>
              <div class="flex-grow-1">
                <div class="sv-edu-level" style="color:{{ $lvlColors[$edu->level_id] ?? '#696cff' }};">
                  {{ $lvlNames[$edu->level_id] ?? 'Education' }}
                </div>
                <div class="sv-edu-school">{{ $edu->school_name }}</div>
                @if ($edu->degree_course)
                  <div class="sv-edu-course">{{ $edu->degree_course }}</div>
                @endif
                <div class="d-flex flex-wrap gap-1 mt-1">
                  @if ($edu->period_from || $edu->period_to)
                    <span class="sv-tag"><i
                        class="ri ri-calendar-line me-1"></i>{{ $edu->period_from ?? '?' }}–{{ $edu->period_to ?? '?' }}</span>
                  @endif
                  @if ($edu->year_graduated)
                    <span class="sv-tag"><i class="ri ri-award-line me-1"></i>Grad. {{ $edu->year_graduated }}</span>
                  @endif
                  @if ($edu->scholarship_honors)
                    <span class="sv-tag" style="color:#f59e0b;background:#fffbeb;"><i
                        class="ri ri-medal-line me-1"></i>{{ $edu->scholarship_honors }}</span>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <p class="sv-empty">No education records found.</p>
          @endforelse
        </div>
      </div>

    </div>
  </div>

  <style>
    /* Hero */
    .emp-hero {
      background: linear-gradient(135deg, #696cff 0%, #9155fd 100%);
      border-radius: 1rem;
      padding: 1.75rem 2rem;
      box-shadow: 0 8px 32px rgba(105, 108, 255, .25);
    }

    .emp-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .2);
      border: 2.5px solid rgba(255, 255, 255, .4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
    }

    .ep {
      display: inline-flex;
      align-items: center;
      background: rgba(255, 255, 255, .15);
      color: rgba(255, 255, 255, .95);
      padding: .25rem .85rem;
      border-radius: 20px;
      font-size: .8rem;
    }

    .ec {
      display: flex;
      flex-direction: column;
      align-items: center;
      background: rgba(255, 255, 255, .15);
      border-radius: .5rem;
      padding: .5rem .9rem;
      min-width: 76px;
      text-align: center;
    }

    .ec-val {
      color: #fff;
      font-weight: 700;
      font-size: .88rem;
    }

    .ec-lbl {
      color: rgba(255, 255, 255, .65);
      font-size: .67rem;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    /* Cards */
    .sv-card {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .05), 0 4px 16px rgba(105, 108, 255, .07);
      overflow: hidden;
    }

    .sv-head {
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

    .sv-body {
      padding: .75rem 1.25rem;
    }

    .sv-empty {
      font-size: .85rem;
      color: #aaa;
      margin: .4rem 0 0;
    }

    /* Rows */
    .sv-row {
      display: flex;
      align-items: flex-start;
      padding: .42rem 0;
      border-bottom: 1px solid #f4f4f8;
      gap: .5rem;
    }

    .sv-row:last-child {
      border-bottom: none;
    }

    .sv-lbl {
      flex-shrink: 0;
      width: 145px;
      font-size: .775rem;
      color: #9e9e9e;
      font-weight: 500;
      padding-top: .05rem;
    }

    .sv-val {
      font-size: .875rem;
      color: #222;
      flex: 1;
    }

    /* Subhead inside card */
    .sv-subhead {
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #696cff;
      border-top: 1px dashed #e8e0ff;
      padding-top: .65rem;
    }

    /* Address full-string */
    .sv-addr-full {
      font-size: .78rem;
      color: #666;
      background: #f8f7ff;
      border-radius: .4rem;
      padding: .4rem .65rem;
    }

    /* Children */
    .sv-child {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .5rem 0;
      border-bottom: 1px solid #f4f4f8;
    }

    .sv-child:last-child {
      border-bottom: none;
    }

    .sv-child-av {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #696cff;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .85rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    /* Education */
    .sv-edu {
      display: flex;
      align-items: flex-start;
      gap: .75rem;
      padding: .75rem 0;
      border-bottom: 1px solid #f4f4f8;
    }

    .sv-edu:last-child {
      border-bottom: none;
    }

    .sv-edu-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      flex-shrink: 0;
      margin-top: .4rem;
    }

    .sv-edu-level {
      font-size: .71rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .sv-edu-school {
      font-weight: 600;
      font-size: .9rem;
      color: #222;
      margin-top: .1rem;
    }

    .sv-edu-course {
      font-size: .82rem;
      color: #666;
    }

    .sv-tag {
      display: inline-flex;
      align-items: center;
      font-size: .74rem;
      color: #666;
      background: #f4f4f8;
      border-radius: 20px;
      padding: .1rem .55rem;
    }
  </style>

  @if (session('success') || session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('flashToast');
        if (el) new bootstrap.Toast(el).show();
      });
    </script>
  @endif

@endsection
