
@foreach($userLogs as $userLog)
<div class="card shadow-none border bg-labe p-3 rounded-3">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0">
            <img id="logAvatar" src="{{ asset('storage/'. $userLog->image_path) }}" 
                alt="Avatar" class=" border border-2 border-white" 
                style="width: 65px; height: 65px; object-fit: cover;">
        </div>

        <div class="flex-grow-1 ms-3">
            <h6 id="logName" class="mb-0 text-uppercase" style="letter-spacing: 0.5px;">{{$userLog->userLogs->employee->first_name}} {{$userLog->userLogs->employee->last_name}}</h6>
            <small id="logId" class="text-muted d-block mb-1">ID: {{$userLog->userLogs->employee_number}} </small>
            
            <div class="d-flex gap-3 mt-1">
                <div class="small">
                    <i class="bx bx-time-five text-primary me-1"></i>
                    <span id="logTime" class="fw-semibold">{{ \Carbon\Carbon::parse($userLog->captured_at)->format('M d') }}</span>
                </div>
                <div class="small">
                    <i class="bx bx-calendar text-primary me-1"></i>
                    <span id="logDate" class="fw-semibold">{{ \Carbon\Carbon::parse($userLog->captured_at)->format('h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endforeach
