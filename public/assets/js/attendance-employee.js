$(document).ready(function() {
    // jQuery selectors for elements
    const $video = $('#video');
    const $btn = $('#registerBtn');
    const $instruction = $('#instruction');
    const videoElement = $video.get(0);
    const statusMessage = $('#statusBadge');
    

    async function setup() {
        try {
            const MODEL_URL = "/models";

            // Load models sequentially
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)

            console.log("Models loaded successfully!");
            statusMessage.removeClass('bg-label-secondary').addClass('bg-label-success').text('Models loaded successfully!');



            // FETCH ENROLLED EMPLOYEES FROM YOUR BACKEND
            const response = await fetch('/employees/get-enrolled-descriptors');
            const enrolledEmployees = await response.json();

            if (enrolledEmployees.length > 0) {
                const labeledDescriptors = enrolledEmployees.map(emp => {
                    const descriptor = new Float32Array(JSON.parse(emp.face_descriptor));
                    return new faceapi.LabeledFaceDescriptors(emp.full_name, [descriptor]);
                });
                // 0.6 is the distance threshold (lower is stricter)
                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.4);
            }

            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoElement.srcObject = stream;

            // Handle video play event
            $video.on('play', function() {
                const $canvas = $('#overlay');
                
                $canvas.prop('width', videoElement.clientWidth);
                $canvas.prop('height', videoElement.clientHeight);

                $instruction.text("Scanning... Please look at the camera.");
                startDetection();
            });

        } catch (err) {
            console.error("Setup failed:", err);
            $instruction.text("Error: Models failed to load. Check public/models folder.")
                        .css("color", "red");
        }
    }

    function startDetection() {
        const $canvas = $('#overlay');
        const displaySize = { width: videoElement.clientWidth, height: videoElement.clientHeight };
        faceapi.matchDimensions($canvas.get(0), displaySize);

        setInterval(async () => {
            if (!faceapi.nets.ssdMobilenetv1.params) return;
            if (videoElement.paused || videoElement.ended) return;

            try {
                
                
                const detections = await faceapi.detectAllFaces(videoElement)
                    .withFaceLandmarks()
                    .withFaceDescriptors(); 

                
                const ctx = $canvas.get(0).getContext('2d');
                ctx.clearRect(0, 0, $canvas.prop('width'), $canvas.prop('height'));

                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                if (detections.length > 0) {
                    
                    resizedDetections.forEach((detection, i) => {
                        let label = "Unknown";

                        
                        if (faceMatcher) {
                            const result = faceMatcher.findBestMatch(detection.descriptor);
                            label = result.toString();
                        }

                        // 4. Draw a unique box for each face found
                        const drawBox = new faceapi.draw.DrawBox(detection.detection.box, { 
                            label: label,
                            boxColor: label.includes('unknown') ? 'red' : 'green' 
                        });
                        drawBox.draw($canvas.get(0));
                    });

                    
                    window.currentDescriptor = detections[0].descriptor;
                    $btn.prop('disabled', false);
                    $instruction.text(`${detections.length} Face(s) detected!`);
                } else {
                    $btn.prop('disabled', true);
                    $instruction.text("No face detected.").css("color", "orange");
                }

            } catch (error) {
                console.warn("Processing frame error:", error);
            }
        }, 100);
    }

    // Pag Start sa setup
    setup();

   
    $btn.on('click', function() {
       
        if (!window.currentDescriptor) return;

        const employeeId = $('#employeeSelect').val();

        // UI Feedback: Change button text and disable it
        const $this = $(this);
        $this.text("Saving to Database...").prop('disabled', true);

        $.ajax({
            url:`/admin/employees/facial-recognition/save`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
             
                descriptor: Array.from(window.currentDescriptor),
                employee_id: employeeId

            }),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                alert('Success! Face data enrolled.');
                //  window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
             
                $this.text("Enroll Face").prop('disabled', false);
            }
        });
    });

    $('#employeeIdInput').on('keyup', async function() {
        const query = $(this).val().toLowerCase();
        const $suggestions = $('#searchSuggestions');


         const response = await fetch('/employees/get-enrolled-descriptors');
         const enrolledEmployees = await response.json();

         console.log("Enrolled Employees:", enrolledEmployees);

      
        if (query.length < 1) {
            $suggestions.hide().empty();
            return;
        }

      
        const matches = enrolledEmployees.filter(emp => {
               
                const empId = String(emp.id || "").toLowerCase();
                const fullName = String(emp.full_name || "").toLowerCase();

             
                return empId.includes(query) || fullName.includes(query);
            }
        );

        // 3. Render the results
        if (matches.length > 0) {
            let html = '';
            matches.slice(0, 5).forEach(emp => { 
                html += `
                    <a href="javascript:void(0);" class="list-group-item list-group-item-action py-2 suggestion-item" 
                    data-id="${emp.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${emp.id}</strong><br>
                                <small class="text-muted">${emp.full_name}</small>
                            </div>
                            <i class="bx bx-chevron-right text-primary"></i>
                        </div>
                    </a>`;
            });
            $suggestions.html(html).show();
        } else {
            $suggestions.hide().empty();
        }
    });
});


$(document).on('click', '.suggestion-item', function() {
    const selectedId = $(this).data('id');
    $('#employeeIdInput').val(selectedId);
    $('#searchSuggestions').hide().empty();
    
  
    $('#startScanBtn').trigger('click');
});


$(document).on('click', function(e) {
    if (!$(e.target).closest('#idSection').length) {
        $('#searchSuggestions').hide();
    }
});