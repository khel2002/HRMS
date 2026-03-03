@extends('layouts/contentNavbarLayout')
@section('title', 'Add Employee')

@section('vendor-style')
  {{-- TomSelect: searchable dropdowns for PSGC address hierarchy --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
  <link rel="stylesheet" href="{{ asset('assets/css/registration.css') }}">
@endsection

@section('content')

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card border-0 shadow-sm">

      {{-- ══════════════════════════════════
           STEP INDICATORS
      ══════════════════════════════════ --}}
      <div class="card-header bg-white border-bottom px-4 pt-4 pb-3">
        <div class="d-flex align-items-start justify-content-between position-relative" id="wizardSteps">

          <div class="wz-track">
            <div class="wz-fill" id="wzFill"></div>
          </div>

          @php
            $steps = [
                1 => 'Personal',
                2 => 'Address',
                3 => 'Family',
                4 => 'Education',
                5 => 'Review',
            ];
          @endphp

          @foreach ($steps as $num => $label)
            <div class="wz-step {{ $num === 1 ? 'active' : '' }}" data-step="{{ $num }}">
              <div class="wz-circle">
                <span class="wz-num">{{ $num }}</span>
                <i class="ri ri-check-line wz-check"></i>
              </div>
              <span class="wz-label">{{ $label }}</span>
            </div>
          @endforeach

        </div>
      </div>

      {{-- ══════════════════════════════════
           FORM
      ══════════════════════════════════ --}}
      <form id="wizardForm" method="POST" action="{{ route('employee-store') }}">
        @csrf

        <div class="card-body px-4 py-4">

          <div class="wz-panel" id="panel-1">
            @include('content.admin.employees-registration.step1')
          </div>

          <div class="wz-panel" id="panel-2">
            @include('content.admin.employees-registration.step2')
          </div>

          <div class="wz-panel" id="panel-3">
            @include('content.admin.employees-registration.step3')
          </div>

          <div class="wz-panel" id="panel-4">
            @include('content.admin.employees-registration.step4')
          </div>

          <div class="wz-panel" id="panel-5">
            @include('content.admin.employees-registration.review')
          </div>

        </div>

        {{-- ══════════════════════════════════
             FOOTER BUTTONS
        ══════════════════════════════════ --}}
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center px-4 py-3">

          <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeStep(-1)"
            style="display:none;">
            <i class="ri ri-arrow-left-line me-1"></i> Back
          </button>

          <div class="ms-auto d-flex gap-2">
            <a href="{{ route('employees-index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
              Next <i class="ri ri-arrow-right-line ms-1"></i>
            </button>
            <button type="submit" class="btn btn-success" id="submitBtn" style="display:none;">
              <i class="ri ri-check-line me-1"></i> Save Employee
            </button>
          </div>

        </div>

      </form>
    </div>
  </div>

  {{-- Blade → JS data bridge --}}
  <div id="wizardData" data-child-idx="{{ count(old('children', [])) }}" data-edu-idx="{{ count(old('education', [])) }}"
    data-govid-idx="{{ count(old('gov_ids', [])) }}" style="display:none;">
  </div>

  {{-- TomSelect JS (must load before registration.js) --}}
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

  {{-- Main wizard script (includes address PSGC logic) --}}
  <script src="{{ asset('assets/js/registration.js') }}"></script>

@endsection
