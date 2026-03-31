@extends('layouts/blankLayout')
@section('title', 'DTR - Face Recognition')

@section('content')
<div class="container-fluid my-4">
    <div class="row px-3">
        <div id="resultAlert" class="alert d-none" role="alert">
            <span id="resultBadge" class="badge badge-center rounded-pill me-3">
                <i id="resultIcon" class="bx"></i>
            </span>
            <div id="resultMsg">Success!</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div style="position: relative; display: inline-block; border-radius: 12px; width: 100%;">
                        <video id="video" width="100%" height="auto" autoplay muted style=" min-height: 400px; border-radius: 12px;"></video>
                        <canvas id="overlay" style="position: absolute; top: 0; left: 0; pointer-events: none;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">DTR System</h5>
                </div>
                <div class="card-body">
                    <div id="idSection">
                        <label for="employeeIdInput" class="form-label">Employee Number</label>
                        <input type="text" id="employeeIdInput" class="form-control form-control-lg mb-0" placeholder="Type here..." autocomplete="off" autofocus>
                        
                        <div id="searchSuggestions" class="list-group shadow-lg" 
                            style="position: absolute; z-index: 1000; width: 90%; display: none; 
                                    background-color: white; border: 1px solid #d9dee3; border-radius: 0.375rem; 
                                    overflow: hidden;">
                        </div>
                        
                        <button id="startScanBtn" class="btn btn-primary w-100 btn-lg mt-3">
                            <i class="bx bx-scan me-1"></i> Log Book
                        </button>
                    </div>

                    <hr>
                    <div id="logResult" class="mt-4">
                        <div class="border rounded-3 p-3 bg-lighter" style="min-height: 220px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Attendance Logs</h6>
                                <small class="text-muted">Latest activity</small>
                            </div>

                            <div id="logResultList" style="max-height: 350px; overflow-y: auto;">
                                {{-- <div id="logEmptyState" class="text-muted text-center py-4">
                                    No logs yet.
                                </div> --}}
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body mt-2">
                    <div id="statusSection">   
                        <div id="statusBadge" class="badge bg-label-secondary mb-3">Initializing Models...</div>
                        <p id="instruction" class="small text-secondary">The camera is active. Enter your number to begin verification.</p>
                    </div>
                </div>          
            </div>   
        </div>
    </div>
</div>



<script src="{{ asset('assets/js/attendance-employee.js') }}"></script>
@endsection