@extends('layouts/contentNavbarLayout')

@section('title', 'Seminars Attended')

@section('content')

@if ($errors->has('seminars'))
  <div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="bx bx-error-circle me-1"></i>
    {{ $errors->first('seminars') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card border-0 shadow-sm service-record-card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-transparent border-bottom mb-3">
    <div>
      <h4 class="mb-1 fw-bold">Seminars Attended</h4>
      <p class="mb-0 text-muted">Manage employee seminars and training records</p>
    </div>

    <button class="btn btn-primary rounded-pill px-3"
            data-bs-toggle="modal"
            data-bs-target="#addSeminarModal">
      <i class="ri-add-line me-1"></i> Add Seminar
    </button>
  </div>

  <div class="card-body">

    {{-- Tabs --}}
    <ul class="nav nav-pills custom-status-tabs mb-4" role="tablist">
      <li class="nav-item me-2 mb-2">
        <button class="nav-link active d-flex align-items-center gap-2"
                data-bs-toggle="tab"
                data-bs-target="#pendingTab"
                type="button">
          <i class="ri-time-line"></i>
          <span>Pending</span>
          @if($seminars->where('record_status', 'pending')->count() > 0)
          <span class="badge rounded-pill bg-warning text-dark">
            {{ $seminars->where('record_status', 'pending')->count() }}
          </span>
          @endif
        </button>
      </li>

      <li class="nav-item me-2 mb-2">
        <button class="nav-link d-flex align-items-center gap-2"
                data-bs-toggle="tab"
                data-bs-target="#approvedTab"
                type="button">
          <i class="ri-checkbox-circle-line"></i>
          <span>Approved</span>
          @if($seminars->where('record_status', 'approved')->count() > 0)
          <span class="badge rounded-pill bg-success">
            {{ $seminars->where('record_status', 'approved')->count() }}
          </span>
          @endif
        </button>
      </li>

      <li class="nav-item mb-2">
        <button class="nav-link d-flex align-items-center gap-2"
                data-bs-toggle="tab"
                data-bs-target="#rejectedTab"
                type="button">
          <i class="ri-close-circle-line"></i>
          <span>Rejected</span>
          @if($seminars->where('record_status', 'rejected')->count() > 0)
          <span class="badge rounded-pill bg-danger">
            {{ $seminars->where('record_status', 'rejected')->count() }}
          </span>
          @endif
        </button>
      </li>
    </ul>

    <div class="tab-content shadow-none border-0 p-0 m-0">

      <div class="tab-pane fade show active" id="pendingTab">
        <div class="record-tab-panel rounded-0">
          @include('_partials._seminars', [
            'seminars' => $seminars->where('record_status', 'pending'),
            'type' => 'pending'
          ])
        </div>
      </div>

      <div class="tab-pane fade" id="approvedTab">
        <div class="record-tab-panel">
          @include('_partials._seminars', [
            'seminars' => $seminars->where('record_status', 'approved'),
            'type' => 'approved'
          ])
        </div>
      </div>

      <div class="tab-pane fade" id="rejectedTab">
        <div class="record-tab-panel">
          @include('_partials._seminars', [
            'seminars' => $seminars->where('record_status', 'rejected'),
            'type' => 'rejected'
          ])
        </div>
      </div>

    </div>
  </div>
</div>

{{-- ADD SEMINAR MODAL --}}
<div class="modal fade" id="addSeminarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content sr-modal">

      <div class="modal-header sr-modal-header pb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="sr-header-icon">
            <i class="bx bx-certification"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0">Add seminar attended</h5>
            <small class="text-muted">Create and save employee seminar records</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ route('seminars-attended.store') }}" method="POST" id="seminarForm">
        @csrf

        <div class="modal-body sr-modal-body">

          <p class="sr-section-label">Employee information</p>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="sr-field-label">Employee name</label>
              <select name="employee_id" class="form-select sr-select" required>
                <option value="">Select employee</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <p class="sr-section-label mb-0">Seminar entries</p>
            <small class="text-muted">Add one or more seminars</small>
          </div>

          <div class="sr-table-wrapper">
            <table class="table sr-table mb-0">
              <thead>
                <tr>
                  <th class="col-no">#</th>
                  <th style="min-width: 260px;">Training Program</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Hours</th>
                  <th style="min-width: 180px;">Conducted By</th>
                  <th style="min-width: 220px;">Address</th>
                  <th class="col-action"></th>
                </tr>
              </thead>

              <tbody id="seminarRows">
                <tr>
                  <td class="row-num text-muted">1</td>

                  <td>
                    <textarea name="seminars[1][training_program]"
                              class="sr-input"
                              rows="1"
                              placeholder="Seminar / Training title"></textarea>
                  </td>

                  <td>
                    <input type="date" name="seminars[1][from_date]" class="sr-input">
                  </td>

                  <td>
                    <input type="date" name="seminars[1][to_date]" class="sr-input">
                  </td>

                  <td>
                    <input type="number"
                           name="seminars[1][number_of_hours]"
                           class="sr-input"
                           min="0"
                           step="0.01"
                           placeholder="0">
                  </td>

                  <td>
                    <input type="text"
                           name="seminars[1][conducted_by]"
                           class="sr-input"
                           placeholder="Conducted by">
                  </td>

                  <td>
                    <input type="text"
                           name="seminars[1][address]"
                           class="sr-input"
                           placeholder="Address">
                  </td>

                  <td>
                    <span></span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <button type="button" class="sr-add-row-btn mt-2" onclick="addSeminarRow()">
            <i class="ri-add-line me-1"></i> Add row
          </button>

        </div>

        <div class="modal-footer sr-modal-footer pt-3">
          <button type="button" class="btn sr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn sr-btn-save">
            <i class="ri-save-line me-1"></i> Save seminar
          </button>
        </div>

      </form>
    </div>
  </div>

</div>

{{-- Modal for Editing Seminar --}}
<div class="modal fade" id="editSeminarModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content sr-modal">

      <div class="modal-header sr-modal-header">
        <h5 class="modal-title">Edit Seminar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="editSeminarForm" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-body">

          <input type="hidden" id="edit_id">

          <div class="mb-3">
            <label class="form-label">Training Program</label>
            <textarea id="edit_training_program"
                      name="training_program"
                      class="form-control"
                      required></textarea>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label>From</label>
              <input type="date" id="edit_from_date" name="from_date" class="form-control">
            </div>

            <div class="col-md-6">
              <label>To</label>
              <input type="date" id="edit_to_date" name="to_date" class="form-control">
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-md-6">
              <label>Hours</label>
              <input type="number" id="edit_hours" name="number_of_hours" class="form-control">
            </div>

            <div class="col-md-6">
              <label>Conducted By</label>
              <input type="text" id="edit_conducted_by" name="conducted_by" class="form-control">
            </div>
          </div>

          <div class="mt-3">
            <label>Address</label>
            <input type="text" id="edit_address" name="address" class="form-control">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>

      </form>

    </div>
  </div>
</div>


{{-- VIEW SEMINAR MODAL --}}
<div class="modal fade" id="viewSeminarModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content sr-view-modal">

      <div class="modal-header">
        <h5 class="modal-title fw-bold">Seminar Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="sr-paper">

          {{-- HEADER --}}
          <div class="text-center mb-4">
            <h5 class="fw-bold">SEMINARS / TRAININGS ATTENDED</h5>
          </div>

          {{-- EMPLOYEE --}}
          <div class="row mb-3">
            <div class="col-md-8">
              <strong>Name:</strong>
              <span class="sr-line" id="view_employee">—</span>
            </div>

            <div class="col-md-4 text-end">
              <strong>Status:</strong>
              <span id="view_status">—</span>
            </div>
          </div>

          {{-- TABLE --}}
          <div class="table-responsive">
            <table class="table table-bordered text-center align-middle sr-view-table">
              <thead>
                <tr>
                  <th rowspan="2">TRAINING PROGRAM</th>
                  <th colspan="2">INCLUSIVE DATES</th>
                  <th rowspan="2">HOURS</th>
                  <th rowspan="2">CONDUCTED BY</th>
                  <th rowspan="2">ADDRESS</th>
                </tr>
                <tr>
                  <th>FROM</th>
                  <th>TO</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td id="view_training_program">—</td>
                  <td id="view_from_date">—</td>
                  <td id="view_to_date">—</td>
                  <td id="view_hours">—</td>
                  <td id="view_conducted_by">—</td>
                  <td id="view_address">—</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>


@include('_partials.mini-alert') 
@if(session('success'))
<script>
  miniAlert.toast("{{ session('success') }}", 'success');
</script>
@endif

@endsection

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/service-record.css') }}">
@endsection

@section('page-script')


<script>
  let seminarRowCount = 1;

  function addSeminarRow() {
    seminarRowCount++;

    const tbody = document.getElementById('seminarRows');
    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td class="row-num text-muted">${seminarRowCount}</td>

      <td>
        <textarea name="seminars[${seminarRowCount}][training_program]"
                  class="sr-input"
                  rows="1"
                  placeholder="Seminar / Training title"></textarea>
      </td>

      <td>
        <input type="date" name="seminars[${seminarRowCount}][from_date]" class="sr-input">
      </td>

      <td>
        <input type="date" name="seminars[${seminarRowCount}][to_date]" class="sr-input">
      </td>

      <td>
        <input type="number"
               name="seminars[${seminarRowCount}][number_of_hours]"
               class="sr-input"
               min="0"
               step="0.01"
               placeholder="0">
      </td>

      <td>
        <input type="text"
               name="seminars[${seminarRowCount}][conducted_by]"
               class="sr-input"
               placeholder="Conducted by">
      </td>

      <td>
        <input type="text"
               name="seminars[${seminarRowCount}][address]"
               class="sr-input"
               placeholder="Address">
      </td>

      <td>
        <button type="button" class="sr-remove-btn" onclick="removeSeminarRow(this)">
          <i class="bx bxs-trash"></i>
        </button>
      </td>
    `;

    tbody.appendChild(tr);
    renumberSeminarRows();
  }

  function removeSeminarRow(btn) {
    btn.closest('tr').remove();
    renumberSeminarRows();
  }

  function renumberSeminarRows() {
    const rows = document.querySelectorAll('#seminarRows tr');

    rows.forEach((tr, index) => {
      tr.querySelector('.row-num').textContent = index + 1;
    });
  }
</script>
<script>
  window.confirmAction = function (title, message, url, method,type,action) {
    miniAlert.confirm(
      title,
      message,
      function () {
        submitActionForm(url, method);
      },
      function () {
        miniAlert.toast('Action cancelled', 'info');
      },
      type,
      action
    );
  }

  window.submitActionForm = function (url, method) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    form.innerHTML = `
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="_method" value="${method}">
    `;

    document.body.appendChild(form);
    form.submit();
  }
</script>

<script>
function openEditModal(seminar) {

  // Set form action dynamically
  document.getElementById('editSeminarForm').action =
    `seminars/${seminar.id}`;

  // Fill inputs
  document.getElementById('edit_training_program').value = seminar.training_program ?? '';
  document.getElementById('edit_from_date').value = seminar.from_date ?? '';
  document.getElementById('edit_to_date').value = seminar.to_date ?? '';
  document.getElementById('edit_hours').value = seminar.number_of_hours ?? '';
  document.getElementById('edit_conducted_by').value = seminar.conducted_by ?? '';
  document.getElementById('edit_address').value = seminar.address ?? '';

  // Show modal
  new bootstrap.Modal(document.getElementById('editSeminarModal')).show();
}

</script>

<script>
function openViewModal(seminar) {

  console.log(seminar);

  document.getElementById('view_employee').textContent =
    seminar.employee?.first_name + ' ' + seminar.employee?.last_name ?? '—';

  document.getElementById('view_training_program').textContent =
    seminar.training_program ?? '—';

  document.getElementById('view_from_date').textContent =
    seminar.from_date ? formatDate(seminar.from_date) : '—';

  document.getElementById('view_to_date').textContent =
    seminar.to_date ? formatDate(seminar.to_date) : 'Present';

  document.getElementById('view_hours').textContent =
    seminar.number_of_hours ?? '—';

  document.getElementById('view_conducted_by').textContent =
    seminar.conducted_by ?? '—';

  document.getElementById('view_address').textContent =
    seminar.address ?? '—';

  let statusHtml = '';

  if (seminar.record_status === 'pending') {
    statusHtml = '<span class="badge bg-label-warning">Pending</span>';
  } else if (seminar.record_status === 'approved') {
    statusHtml = '<span class="badge bg-label-success">Approved</span>';
  } else {
    statusHtml = '<span class="badge bg-label-danger">Rejected</span>';
  }

  document.getElementById('view_status').innerHTML = statusHtml;

  new bootstrap.Modal(document.getElementById('viewSeminarModal')).show();
}
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}
</script>
@endsection