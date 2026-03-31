$(document).ready(function () {
    const $video = $('#video');
    const $btn = $('#registerBtn');
    const $instruction = $('#instruction');
    const $setupStatus = $('#setupStatus');
    const $setupText = $('#setupText');
    const videoElement = $video.get(0);

    function setSetupState(type, message) {
        $setupText.removeClass('setup-dots');

        if (type === 'loading') {
            $setupStatus.removeClass('d-none text-success text-danger').addClass('d-flex text-primary');
            $setupStatus.find('.setup-spinner').show();
            $setupText.text(message).addClass('setup-dots');
        } else if (type === 'success') {
            $setupStatus.removeClass('text-primary text-danger').addClass('d-flex text-success pulse-success');
            $setupStatus.find('.setup-spinner').hide();
            $setupText.text(message);
        } else if (type === 'error') {
            $setupStatus.removeClass('text-primary text-success pulse-success').addClass('d-flex text-danger');
            $setupStatus.find('.setup-spinner').hide();
            $setupText.text(message);
        } else if (type === 'hide') {
            $setupStatus.addClass('d-none');
        }
    }

    async function setup() {
        try {
            const MODEL_URL = "/models";

            setSetupState('loading', 'Initializing models');

            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            setSetupState('loading', 'Loading face landmarks');

            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            setSetupState('loading', 'Loading recognition model');

            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            setSetupState('loading', 'Opening camera');

            console.log("Models loaded successfully!");

            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoElement.srcObject = stream;

            $video.on('play', function () {
                const $canvas = $('#overlay');

                $canvas.prop('width', videoElement.clientWidth);
                $canvas.prop('height', videoElement.clientHeight);

                //$instruction.text("Setting up, please wait.").css("color", "");
                setSetupState('loading', 'Setting up, please wait.');

                // setTimeout(() => {
                //     setSetupState('hide');
                // }, 1500);

                startDetection();
            });

        } catch (err) {
            console.error("Setup failed:", err);
            setSetupState('error', 'Setup failed');
            $instruction
                .text("Error: Models failed to load. Check public/models folder.")
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

                    window.currentDescriptor = detection.descriptor;
                    $btn.prop('disabled', false);
                     setSetupState('hide');
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

    setup();

    $btn.on('click', function () {
        if (!window.currentDescriptor) return;

        const employeeId = $('#employeeSelect').val();

        if (!employeeId) {
            showAlert('error', 'Please select employee.');
            return;
        }

        const $this = $(this);
        $this.text('Saving to Database...').prop('disabled', true);

        $.ajax({
            url: '/admin/employees/facial-recognition/save',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                descriptor: Array.from(window.currentDescriptor),
                employee_id: employeeId
            }),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                showAlert('success', response.message || 'Face data enrolled successfully.');
                $this.text('Enroll Face').prop('disabled', false);
            },
            error: function (xhr, status, error) {
                let msg = 'Error saving face data.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    msg = xhr.responseText;
                } else if (error) {
                    msg = error;
                }

                showAlert('error', msg);

                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);

                $this.text('Enroll Face').prop('disabled', false);
            }
        });
    });

    function showAlert(type, message) {
        const $alert = $('#statusAlert');
        const $icon = $('#statusIcon');
        const $iconI = $('#statusIcon i');

        $('#resultMsg').text(message);

        $alert.removeClass('d-none d-flex alert-success alert-danger');
        $icon.removeClass('bg-success bg-danger');

        if (type === 'success') {
            $alert.addClass('d-flex alert-success');
            $icon.addClass('bg-success');
            $iconI.attr('class', 'bx bx-check');
        } else {
            $alert.addClass('d-flex alert-danger');
            $icon.addClass('bg-danger');
            $iconI.attr('class', 'bx bx-x');
        }

        setTimeout(() => {
            $alert.removeClass('d-flex').addClass('d-none');
        }, 3000);
    }
});