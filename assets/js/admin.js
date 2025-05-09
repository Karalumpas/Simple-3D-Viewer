/**
 * MG 3D Product Viewer - Admin Script
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initMediaUploader();
        initRangeInputs();
        initColorSettings();
        initCameraControls();
    });

    // Initialize media uploader
    function initMediaUploader() {
        $('.mg3d-upload-button').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const inputId = button.data('input');
            const input = $('#' + inputId);
            
            const frame = wp.media({
                title: mg3dAdmin.title,
                button: {
                    text: mg3dAdmin.button
                },
                multiple: false,
                library: {
                    type: inputId === 'mg3d_model_url' ? ['model/gltf-binary', 'model/gltf+json'] : 'image'
                }
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url).trigger('change');
                
                // Update preview if it's a model
                if (inputId === 'mg3d_model_url') {
                    updateModelPreview();
                }
            });

            frame.open();
        });
    }

    // Initialize range inputs
    function initRangeInputs() {
        $('input[type="range"]').on('input', function() {
            const value = $(this).val();
            $(this).siblings('.range-value').text(value);
        });
    }

    // Initialize color settings
    function initColorSettings() {
        const colorChangeCheckbox = $('input[name="mg3d_enable_color_change"]');
        const materialColorGroup = $('#material-color-group');
        
        function toggleMaterialColor() {
            materialColorGroup.toggleClass('disabled', !colorChangeCheckbox.is(':checked'));
        }
        
        colorChangeCheckbox.on('change', toggleMaterialColor);
        toggleMaterialColor(); // Initial state
    }

    // Initialize camera controls
    function initCameraControls() {
        const viewer = document.querySelector('#mg3d-model-preview');
        if (!viewer) return;

        const saveButton = $('#mg3d-save-camera');
        const savedPositionInput = $('#mg3d_saved_camera_position');
        const savedPositionPreview = $('#saved-position-preview');
        const savedPositionValue = $('#saved-position-value');

        // Move save button above preview
        const previewContainer = $('.mg3d-preview-container');
        const cameraSaveDiv = $('<div class="mg3d-camera-save"></div>');
        saveButton.appendTo(cameraSaveDiv);
        cameraSaveDiv.prependTo(previewContainer);

        saveButton.on('click', function() {
            const currentOrbit = viewer.getCameraOrbit();
            const position = {
                theta: (currentOrbit.theta * 180 / Math.PI) + 'deg',
                phi: (currentOrbit.phi * 180 / Math.PI) + 'deg',
                radius: (currentOrbit.radius * 100) + '%'
            };
            
            const positionString = `${position.theta} ${position.phi} ${position.radius}`;
            savedPositionInput.val(positionString);
            savedPositionValue.text(positionString);
            savedPositionPreview.removeClass('hidden');
            
            // Show feedback
            const originalText = saveButton.text();
            saveButton.text('Position Saved!');
            setTimeout(() => {
                saveButton.text(originalText);
            }, 2000);
        });

        // Update preview when settings change
        $('input, select').on('change', function() {
            updateModelPreview();
        });
    }

    // Update model preview
    function updateModelPreview() {
        const viewer = document.querySelector('#mg3d-model-preview');
        if (!viewer) return;

        // Get current settings
        const modelUrl = $('#mg3d_model_url').val();
        const posterUrl = $('#mg3d_poster_url').val();
        const bgColor = $('#mg3d_bg_color').val();
        const bgTransparent = $('input[name="mg3d_bg_transparent"]').is(':checked');
        const materialColor = $('#mg3d_material_color').val();
        const enableColorChange = $('input[name="mg3d_enable_color_change"]').is(':checked');
        const cameraAngle = $('#mg3d_camera_angle').val();
        const autoRotate = $('input[name="mg3d_auto_rotate"]').is(':checked');
        const rotationSpeed = $('#mg3d_rotation_speed').val();
        const shadowIntensity = $('#mg3d_shadow_intensity').val();
        const exposure = $('#mg3d_exposure').val();
        const animationName = $('#mg3d_animation_name').val();
        const autoplay = $('input[name="mg3d_autoplay"]').is(':checked');

        // Update viewer attributes
        viewer.src = modelUrl;
        if (posterUrl) {
            viewer.poster = posterUrl;
        }
        viewer.style.backgroundColor = bgTransparent ? 'transparent' : bgColor;
        if (enableColorChange) {
            viewer.style.setProperty('--poster-color', materialColor);
        }
        viewer.cameraOrbit = cameraAngle;
        if (autoRotate) {
            viewer.autoRotate = true;
            viewer.rotationPerSecond = 1 / rotationSpeed;
        } else {
            viewer.autoRotate = false;
        }
        viewer.shadowIntensity = shadowIntensity;
        viewer.exposure = exposure;
        if (animationName) {
            viewer.animationName = animationName;
            viewer.autoplay = autoplay;
        }
    }

})(jQuery);