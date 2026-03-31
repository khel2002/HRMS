@extends('layouts/contentNavbarLayout')
@section('title', 'Face Enrollment')

@section('content')
<style>
    .setup-spinner {
        width: 18px;
        height: 18px;
        border: 3px solid rgba(13, 110, 253, 0.2);
        border-top-color: #0d6efd;
        border-radius: 50%;
        animation: spinSetup 0.8s linear infinite;
        flex-shrink: 0;
    }

    @keyframes spinSetup {
        to {
            transform: rotate(360deg);
        }
    }

    .setup-dots::after {
        content: '';
        display: inline-block;
        width: 1.5em;
        text-align: left;
        animation: dots 1.2s steps(4, end) infinite;
        overflow: hidden;
        vertical-align: bottom;
    }

    @keyframes dots {
        0%   { content: ''; }
        25%  { content: '.'; }
        50%  { content: '..'; }
        75%  { content: '...'; }
        100% { content: ''; }
    }

    .pulse-success {
        animation: pulseSuccess 1.4s ease-in-out infinite;
    }

    @keyframes pulseSuccess {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.75; transform: scale(1.03); }
    }
</style>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Facial Enrollment</h5>
                {{-- <span id="statusBadge" class="badge bg-secondary">Models Loading...</span> --}}
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
                    <canvas id="overlay" style="position: absolute; top: 0; left: 0; pointer-events: none; filter:blur(100px);"></canvas>
                    
                    <div id="scanGuide" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 2px dashed rgba(255,255,255,0.5); width: 200px; height: 250px; border-radius: 50%; pointer-events: none;"></div>
                </div>
                <div id="statusAlert" class="alert d-none align-items-center" role="alert">
                    <span id="statusIcon" class="badge badge-center rounded-pill me-3">
                        <i class="bx bx-check"></i>
                    </span>
                    <div id="resultMsg"></div>
                </div>

                <div class="mt-3">
                    <div id="setupStatus" class="d-flex justify-content-center align-items-center gap-2 text-primary">
                        <div class="setup-spinner"></div>
                        <span id="setupText">Initializing models...</span>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 id="instruction" class="text-muted"></h6>
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