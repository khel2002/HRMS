@extends('layouts/contentNavbarLayout')
@section('title', 'DTR - Face Recognition')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body text-center">
                <div style="position: relative; display: inline-block; border-radius: 12px; overflow: hidden; background: #000; width: 100%;">
                    <video id="video" width="100%" height="auto" autoplay muted style=" min-height: 400px;"></video>
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
                <div id="logResult" class="mt-4" style="display:none;">
                    <div class="alert alert-success d-flex" role="alert">
                        <span class="badge badge-center rounded-pill bg-success me-3"><i class="bx bx-check"></i></span>
                        <div id="resultMsg">Success!</div>
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


<script src="{{ asset('assets/js/attendance-employee.js') }}"></script>
@endsection