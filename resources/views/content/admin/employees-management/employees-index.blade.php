@extends('layouts/contentNavbarLayout')
@section('title', 'Employees')

@section('content')

  <div class="col-12">
    <div class="card">

      {{-- ── Flash Messages ─────────────────────────────────────── --}}
      @if (session('success'))
        <div class="alert alert-success alert-dismissible mx-4 mt-4 mb-0 d-flex align-items-center gap-2" role="alert">
          <i class="ri ri-checkbox-circle-line fs-5"></i>
          <span>{{ session('success') }}</span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
      @endif

      @if (session('error'))
        <div class="alert alert-danger alert-dismissible mx-4 mt-4 mb-0 d-flex align-items-center gap-2" role="alert">
          <i class="ri ri-error-warning-line fs-5"></i>
          <span>{{ session('error') }}</span>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
      @endif

      {{-- ── Header / Filters ────────────────────────────────────── --}}
      <div class="card-header border-bottom-0 pb-0 px-4 pt-4">
        <form method="GET" action="{{ route('employees-index') }}" id="filterForm">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pb-3">

            <div class="d-flex gap-2 flex-wrap align-items-center">

              {{-- Search --}}
              <div class="input-group input-group-sm" style="width: 230px;">
                <input type="text" name="search" id="searchInput" class="form-control border-end-0"
                  placeholder="Search employee…" value="{{ request('search') }}"
                  style="border-radius: .375rem 0 0 .375rem;">
                <span class="input-group-text bg-white border-start-0" style="border-radius: 0 .375rem .375rem 0;">
                  <i class="ri ri-search-line text-muted" style="font-size:.85rem;"></i>
                </span>
              </div>

              {{-- Status filter --}}
              <select name="status" id="filterStatus" class="form-select form-select-sm" style="width:140px;"
                onchange="this.form.submit()">
                <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value=""
                  {{ request('status') === '' || (request('status') === null && request()->has('status')) ? 'selected' : '' }}>
                  All Employees</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive
                </option>
                <option value="suspended"{{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended
                </option>
              </select>

              {{-- Clear filters --}}
              @if (request('search') || request()->has('status'))
                <a href="{{ route('employees-index') }}" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                  <i class="ri ri-close-line me-1"></i>Clear
                </a>
              @endif

            </div>

            <div class="d-flex gap-2 flex-wrap">
              <a href="{{ route('employee-registration') }}" class="btn btn-sm btn-primary" style="font-weight:500;">
                <i class="ri ri-user-add-line me-1"></i> Add Employee
              </a>
            </div>

          </div>
        </form>
      </div>

      {{-- ── Table ────────────────────────────────────────────────── --}}
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:140px;">Employee No.</th>
              <th>Name</th>
              <th>Email</th>
              <th class="text-center" style="width:150px;">Contact</th>
              <th class="text-center" style="width:110px;">Status</th>
              <th class="text-center" style="width:130px;">Created</th>
              <th class="text-center" style="width:120px;">Actions</th>
            </tr>
          </thead>
          <tbody>

            @forelse ($employees as $emp)
              <tr>

                {{-- Employee Number --}}
                <td>
                  <span class="fw-semibold text-primary" style="font-size:.85rem;">
                    {{ $emp->employee_number }}
                  </span>
                </td>

                {{-- Full Name --}}
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div>
                      <div class="fw-semibold" style="font-size:.875rem;">
                        {{ $emp->last_name }}, {{ $emp->first_name }}
                        @if ($emp->middle_name)
                          {{ substr($emp->middle_name, 0, 1) }}.
                        @endif
                      </div>
                      @if ($emp->civil_status)
                        <div class="text-muted" style="font-size:.75rem;">
                          {{ ucfirst($emp->civil_status) }}
                        </div>
                      @endif
                    </div>
                  </div>
                </td>

                {{-- Email --}}
                <td style="font-size:.875rem;">
                  {{ $emp->email ?? '—' }}
                </td>

                {{-- Contact --}}
                <td class="text-center" style="font-size:.8rem;">
                  {{ $emp->mobile_number ?? ($emp->landline_number ?? '—') }}
                </td>

                {{-- Status Badge --}}
                <td class="text-center">
                  @php
                    $badgeMap = [
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'suspended' => 'warning',
                    ];
                    $status = $emp->status ?? 'active';
                    $badge = $badgeMap[$status] ?? 'secondary';
                  @endphp
                  <span class="badge bg-label-{{ $badge }} text-capitalize" style="font-size:.75rem;">
                    {{ $status }}
                  </span>
                </td>

                {{-- Created At --}}
                <td class="text-center text-muted" style="font-size:.8rem;">
                  {{ $emp->created_at->format('M d, Y') }}
                </td>

                {{-- Actions --}}
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">

                    {{-- View --}}
                    <a href="{{ route('employee-show', $emp->id) }}"
                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="View Details">
                      <i class="icon-base ri ri-eye-line"></i>
                    </a>

                    {{-- Edit --}}
                    <a href="{{ route('employee-edit', $emp->id) }}"
                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Edit">
                      <i class="icon-base ri ri-edit-line"></i>
                    </a>

                    {{-- More --}}
                    <div class="dropdown">
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                        <i class="icon-base ri ri-more-2-line"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end">

                        {{-- Change Status submenu --}}
                        <div class="dropdown-submenu">
                          <a class="dropdown-item d-flex align-items-center justify-content-between"
                            href="javascript:void(0);">
                            <span><i class="icon-base ri ri-refresh-line me-2"></i>Change Status</span>
                            <i class="ri ri-arrow-right-s-line"></i>
                          </a>
                          <div class="dropdown-menu">
                            @foreach (['active', 'inactive', 'suspended'] as $s)
                              @if ($s !== $status)
                                <form method="POST" action="{{ route('employee-status', $emp->id) }}"
                                  class="d-inline">
                                  @csrf @method('PATCH')
                                  <input type="hidden" name="status" value="{{ $s }}">
                                  <button type="submit" class="dropdown-item text-capitalize">
                                    {{ $s }}
                                  </button>
                                </form>
                              @endif
                            @endforeach
                          </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        {{-- Delete --}}
                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                          onclick="confirmDelete({{ $emp->id }}, '{{ addslashes($emp->first_name . ' ' . $emp->last_name) }}')">
                          <i class="icon-base ri ri-delete-bin-line me-2"></i>Delete Employee
                        </a>

                      </div>
                    </div>

                  </div>
                </td>

              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ri ri-user-search-line fs-1 d-block mb-2 opacity-25"></i>
                  No employees found.
                  @if (request('search') || request('status'))
                    <a href="{{ route('employees-index') }}" class="d-block mt-1 small">Clear filters</a>
                  @endif
                </td>
              </tr>
            @endforelse

          </tbody>
        </table>
      </div>

      {{-- ── Footer: count + pagination ─────────────────────────── --}}
      {{-- @if ($employees->total() > 0)
        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 py-3">
          <span class="text-muted" style="font-size:.82rem;">
            Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }}
            of {{ $employees->total() }} employee{{ $employees->total() !== 1 ? 's' : '' }}
          </span>
          {{ $employees->links() }}
        </div>
      @endif --}}

    </div>
  </div>

  {{-- ── Delete Confirmation Modal ──────────────────────────────── --}}
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title">Delete Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex align-items-start gap-3">
            <div class="text-danger fs-3 mt-1">
              <i class="ri ri-error-warning-line"></i>
            </div>
            <div>
              <p class="mb-1">Are you sure you want to delete</p>
              <p class="fw-semibold mb-1" id="deleteModalName"></p>
              <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <form id="deleteForm" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
              <i class="ri ri-delete-bin-line me-1"></i>Delete
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    // ── Delete modal ─────────────────────────────────────────────
    function confirmDelete(id, name) {
      document.getElementById('deleteModalName').textContent = name;
      document.getElementById('deleteForm').action = `/admin/employees/${id}`;
      new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    // ── Live search (debounced) ──────────────────────────────────
    (function() {
      const input = document.getElementById('searchInput');
      const form = document.getElementById('filterForm');
      if (!input || !form) return;

      let timer;
      input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 450);
      });
    })();
  </script>
@endsection
