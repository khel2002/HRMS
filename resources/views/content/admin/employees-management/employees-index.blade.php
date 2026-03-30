@extends('layouts/contentNavbarLayout')
@section('title', 'Employees')

@section('content')

  {{-- ══════════════════════════════════════════════════════
       TOAST NOTIFICATION (top-right, auto-dismisses)
  ══════════════════════════════════════════════════════ --}}
  @if (session('success') || session('error'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
      <div id="flashToast"
        class="toast align-items-center text-white border-0
               {{ session('success') ? 'bg-success' : 'bg-danger' }}"
        role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center gap-2">
            <i class="ri {{ session('success') ? 'ri-check-circle-line' : 'ri-error-warning-line' }} fs-5"></i>
            {{ session('success') ?? session('error') }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
            aria-label="Close"></button>
        </div>
      </div>
    </div>
  @endif

  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom-0 pb-0 px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pb-3">
          <div class="d-flex gap-2 flex-wrap align-items-center">
            {{-- Search --}}
            <form method="GET" action="{{ route('employees-index') }}"
              class="d-flex gap-2 flex-wrap align-items-center">
              <div class="input-group input-group-sm" style="width: 210px;">
                <input type="text" name="search" id="searchTable" class="form-control border-end-0"
                  placeholder="Search Employee" value="{{ request('search') }}"
                  style="border-radius: .375rem 0 0 .375rem;">
                <span class="input-group-text bg-white border-start-0" style="border-radius: 0 .375rem .375rem 0;">
                  <i class="ri ri-search-line text-muted" style="font-size: .85rem;"></i>
                </span>
              </div>

              {{-- Status filter --}}
              <select name="status" id="filterStatus" class="form-select form-select-sm" style="width: 140px;"
                onchange="this.form.submit()">
                <option value="" {{ request('status') === '' ? 'selected' : '' }}>All Employees</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
              </select>
            </form>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('employee-registration') }}" class="btn btn-sm btn-primary" style="font-weight: 500;">
              <i class="ri ri-add-line me-1"></i> Add Employee
            </a>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Employee No.</th>
              <th>Name</th>
              <th>Email</th>
              <th class="text-center">Mobile</th>
              <th class="text-center">Status</th>
              <th class="text-center">Created</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($employees as $emp)
              @php
                $encryptedId = Crypt::encryptString($emp->id);
              @endphp
              <tr>
                <td class="align-middle">{{ $emp->employee_number }}</td>
                <td class="align-middle">
                  {{ $emp->last_name }}, {{ $emp->first_name }}
                  {{ $emp->middle_name ? $emp->middle_name[0] . '.' : '' }}
                </td>
                <td class="align-middle">{{ $emp->email ?? '—' }}</td>
                <td class="text-center align-middle">{{ $emp->mobile_number ?? '—' }}</td>
                <td class="text-center align-middle">
                  @php
                    $badge = match ($emp->status ?? 'active') {
                        'active' => 'bg-label-success',
                        'inactive' => 'bg-label-secondary',
                        'suspended' => 'bg-label-warning',
                        default => 'bg-label-secondary',
                    };
                  @endphp
                  <span class="badge {{ $badge }}">{{ ucfirst($emp->status ?? 'active') }}</span>
                </td>
                <td class="text-center align-middle" style="font-size:.82rem;">
                  {{ $emp->created_at->format('M d, Y') }}
                </td>
                <td class="text-center align-middle">
                  <div class="d-flex justify-content-center gap-1">

                    <a href="{{ route('employee-show', $encryptedId) }}"
                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="View">
                      <i class="icon-base ri ri-eye-line"></i>
                    </a>

                    <a href="{{ route('employee-edit', $encryptedId) }}"
                      class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Edit">
                      <i class="icon-base ri ri-edit-line"></i>
                    </a>

                    <div class="dropdown">
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="More">
                        <i class="icon-base ri ri-more-2-line"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end">

                        {{-- Status change --}}
                        @foreach (['active', 'inactive', 'suspended'] as $s)
                          @if (($emp->status ?? 'active') !== $s)
                            <form method="POST" action="{{ route('employee-status', $encryptedId) }}">
                              @csrf @method('PATCH')
                              <input type="hidden" name="status" value="{{ $s }}">
                              <button type="submit" class="dropdown-item">
                                <i class="icon-base ri ri-refresh-line me-2"></i>
                                Set {{ ucfirst($s) }}
                              </button>
                            </form>
                          @endif
                        @endforeach

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('employee-destroy', $encryptedId) }}"
                          id="delete-form-{{ $emp->id }}">
                          @csrf @method('DELETE')
                          <button type="button" class="dropdown-item text-danger"
                            onclick="confirmDelete({{ $emp->id }}, '{{ addslashes($emp->last_name . ', ' . $emp->first_name) }}')">
                            <i class="icon-base ri ri-delete-bin-line me-2"></i>
                            Delete Employee
                          </button>
                        </form>

                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No employees found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if ($employees->hasPages())
        <div class="card-footer d-flex justify-content-end px-4 py-3">
          {{ $employees->links() }}
        </div>
      @endif

    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function confirmDelete(id, name) {
      Swal.fire({
        title: 'Delete Employee?',
        html: 'You are about to permanently delete<br><strong>' + name +
          '</strong>.<br><span style="font-size:.85rem;color:#888;">This action cannot be undone.</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ea5455',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri ri-delete-bin-line me-1"></i> Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById('delete-form-' + id).submit();
        }
      });
    }
  </script>

  {{-- Auto-show toast --}}
  @if (session('success') || session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('flashToast');
        if (el) new bootstrap.Toast(el).show();
      });
    </script>
  @endif

@endsection
