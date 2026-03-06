  $(document).ready(function() {
        // jQuery selectors for elements
        const $video = $('#video');
        const $btn = $('#registerBtn');
        const $instruction = $('#instruction');
        const videoElement = $video.get(0);

        async function setup() {
            try {
                const MODEL_URL = "/models";

                // Load models sequentially
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)

                console.log("Models loaded successfully!");

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
                 
                    const detection = await faceapi.detectSingleFace(videoElement)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                 
                    const ctx = $canvas.get(0).getContext('2d');
                    ctx.clearRect(0, 0, $canvas.prop('width'), $canvas.prop('height'));

                    if (detection) {
                      
                        const resizedDetection = faceapi.resizeResults(detection, displaySize);
                        
                       
                        faceapi.draw.drawDetections($canvas.get(0), resizedDetection);
                        // Optional: draw dots on face features
                         //faceapi.draw.drawFaceLandmarks($canvas.get(0), resizedDetection);

                        window.currentDescriptor = detection.descriptor;
                        $btn.prop('disabled', false);
                        $instruction.text("Face detected! Click Enroll.").css("color", "green");
                    } else {
                        $btn.prop('disabled', true);
                        $instruction.text("No face detected. Adjust your position.").css("color", "orange");
                    }

                } catch (error) {
                    console.warn("Processing frame error:", error);
                }
            }, 100);
        }

        // Pag Start sa setup
        setup();

        // Button Click Event using jQuery
        $btn.on('click', function() {
            // Check if we have the descriptor data
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
                    // Convert the Float32Array to a standard array for JSON storage
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
                   // alert("Error saving face data. Check your Laravel route.");
                    
                  
                    $this.text("Enroll Face").prop('disabled', false);
                }
            });
        });
    });