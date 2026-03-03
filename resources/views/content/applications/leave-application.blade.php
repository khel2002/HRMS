@extends('layouts/contentNavbarLayout')

@section('title', 'Application for Leave')

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    <div class="card">
      <div class="card-header text-center">
        <h4 class="mb-0 fw-bold">APPLICATION FOR LEAVE</h4>
        <small>RBH Form No. 1</small>
      </div>

      <div class="card-body">

        <form action="" method="POST">
          @csrf

          {{-- ================= Employee Information ================= --}}
          {{-- <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">Department / Staff</label>
              <input type="text" class="form-control" name="department" value="ADMIN">
            </div>

            <div class="col-md-6">
              <label class="form-label">Name (Last, First, Middle)</label>
              <input type="text" class="form-control" name="name" value="GEORGE A. DAYOLA">
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">Position</label>
              <input type="text" class="form-control" name="position" value="">
            </div>

            <div class="col-md-6">
              <label class="form-label">Salary (Monthly)</label>
              <input type="text" class="form-control" name="salary">
            </div>
          </div> --}}

          <div class="mb-4">
            <label class="form-label">Cause (Illness, Personal, Resignation, etc.)</label>
            <textarea class="form-control" name="cause" rows="2"></textarea>
          </div>

          {{-- ================= Leave Type ================= --}}
          <div class="card mb-4">
            <div class="card-header fw-bold">Leave Applied For</div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="leave_type[]" value="Vacation">
                    <label class="form-check-label">Vacation</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="leave_type[]" value="Sick">
                    <label class="form-check-label">Sick</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="leave_type[]" value="Paternity">
                    <label class="form-check-label">Paternity</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="service_incentive">
                    <label class="form-check-label">Service Incentive</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="office_notified">
                    <label class="form-check-label">Office Notified</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="office_not_notified">
                    <label class="form-check-label">Office Not Notified</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- ================= Leave Duration ================= --}}
          <div class="row mb-4">
            <div class="col-md-4">
              <label class="form-label">No. of Days</label>
              <input type="number" class="form-control" name="days">
            </div>

            <div class="col-md-4">
              <label class="form-label">From</label>
              <input type="date" class="form-control" name="from_date">
            </div>

            <div class="col-md-4">
              <label class="form-label">To</label>
              <input type="date" class="form-control" name="to_date">
            </div>
          </div>

          {{-- ================= Computation ================= --}}
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label d-block">Computation</label>

              <div class="form-check">
                <input class="form-check-input" type="radio" name="computation" value="Requested">
                <label class="form-check-label">Requested</label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="radio" name="computation" value="Not Requested">
                <label class="form-check-label">Not Requested</label>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Date of Filing</label>
              <input type="date" class="form-control" name="date_filed">
            </div>
          </div>

          {{-- ================= Manager Action ================= --}}
          <div class="card mb-4">
            <div class="card-header fw-bold">Action (By Dept. Manager / Staff Head)</div>
            <div class="card-body">

              <div class="form-check">
                <input class="form-check-input" type="radio" name="manager_action" value="Approved">
                <label class="form-check-label">Approval Recommended</label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="radio" name="manager_action" value="Not Approved">
                <label class="form-check-label">Approval Not Recommended</label>
              </div>

              <div class="mt-3">
                <label class="form-label">Reason</label>
                <textarea class="form-control" name="manager_reason"></textarea>
              </div>

            </div>
          </div>

          {{-- ================= Administrator Action ================= --}}
          <div class="card mb-4">
            <div class="card-header fw-bold">Action (By the Administrator)</div>
            <div class="card-body">

              <textarea class="form-control mb-3" name="admin_action" rows="4"
                placeholder="Approved for ___ days with pay / without pay etc."></textarea>

              <div class="row">
                <div class="col-md-4">
                  <label class="form-label">Date</label>
                  <input type="date" class="form-control" name="admin_date">
                </div>

                <div class="col-md-4">
                  <label class="form-label">Signature</label>
                  <input type="text" class="form-control" name="admin_signature">
                </div>

                <div class="col-md-4">
                  <label class="form-label">Office Title</label>
                  <input type="text" class="form-control" name="admin_title">
                </div>
              </div>

            </div>
          </div>

          {{-- ================= Submit ================= --}}
          <div class="text-end">
            <button type="submit" class="btn btn-primary">
              Submit Application
            </button>
          </div>

        </form>

      </div>
    </div>

  </div>
@endsection
