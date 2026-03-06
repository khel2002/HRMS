@extends('layouts/contentNavbarLayout')
@section('title', 'Face Enrollment')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Facial Enrollment</h5>
                <span id="statusBadge" class="badge bg-secondary">Models Loading...</span>
            </div>
            <div class="card-body text-center">
                <div class="form-group mb-4 text-start">
                    <label for="employeeSelect" class="form-label">Select Employee to Enroll</label>
                    <select id="employeeSelect" class="form-select">
                        <option value="">-- Choose Employee --</option>
                        @foreach($employeesWithoutFace as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->employee_number }} - {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="video-container" style="position: relative; display: inline-block;">
                    <video id="video" width="480" height="360" autoplay muted style="border-radius: 8px; background: #222; transform: scaleX(-1);"></video>
                    <canvas id="overlay" style="position: absolute; top: 0; left: 0; pointer-events: none;"></canvas>
                    
                    <div id="scanGuide" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 2px dashed rgba(255,255,255,0.5); width: 200px; height: 250px; border-radius: 50%; pointer-events: none;"></div>
                </div>

                <div class="mt-4">
                    <h6 id="instruction" class="text-muted">Please wait for models to initialize...</h6>
                    <button id="registerBtn" class="btn btn-primary btn-lg px-5" disabled>
                        <i class="bx bx-face me-1"></i> Enroll Face
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="{{ asset('assets/js/facial-recognition.js') }}"></script>
@endsection