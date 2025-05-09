/**
 * MG 3D Product Viewer - Admin JavaScript
 */
(function($) {
    'use strict';

    // Wait for both jQuery and the model-viewer to be ready
    $(window).on('load', function() {
        // Make sure model-viewer is defined before continuing
        // This ensures the custom element is properly registered
        if (typeof customElements !== 'undefined' && customElements.get('model-viewer')) {
            initModelViewer();
        } else {
            // If model-viewer isn't ready yet, wait a bit and try again
            setTimeout(function() {
                if (typeof customElements !== 'undefined' && customElements.get('model-viewer')) {
                    initModelViewer();
                } else {
                    console.warn('Model-viewer not loaded properly. Please reload the page.');
                }
            }, 1000);
        }

        // Add camera position save functionality
        $('#mg3d-save-camera').on('click', function() {
            var modelViewer = document.getElementById('mg3d-model-preview');
            if (modelViewer) {
                // Get current camera position
                var cameraOrbit = modelViewer.getCameraOrbit();
                var position = {
                    theta: cameraOrbit.theta * 180 / Math.PI + 'deg',
                    phi: cameraOrbit.phi * 180 / Math.PI + 'deg',
                    radius: (cameraOrbit.radius * 100) + '%'
                };
                
                // Format camera position string
                var positionString = position.theta + ' ' + position.phi + ' ' + position.radius;
                
                // Update hidden input and preview
                $('#mg3d_saved_camera_position').val(positionString);
                $('#saved-position-value').text(positionString);
                $('#saved-position-preview').removeClass('hidden');
            }
        });

        // Apply saved position when checkbox is checked
        $('input[name="mg3d_use_saved_position"]').on('change', function() {
            if (this.checked) {
                var savedPosition = $('#mg3d_saved_camera_position').val();
                if (savedPosition) {
                    var modelViewer = document.getElementById('mg3d-model-preview');
                    if (modelViewer) {
                        modelViewer.setAttribute('camera-orbit', savedPosition);
                    }
                }
            }
        });

        // Reset camera position
        $('#mg3d-reset-camera').on('click', function() {
            var modelViewer = document.getElementById('mg3d-model-preview');
            if (modelViewer && modelViewer.dataset.initialCameraOrbit) {
                modelViewer.setAttribute('camera-orbit', modelViewer.dataset.initialCameraOrbit);
            }
        });
    });

    function initModelViewer() {
        // Handle file uploads
        $('.mg3d-upload-button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var inputId = button.data('input');
            var mediaUploader;
            
            // Configure different uploaders based on input type
            if (inputId === 'mg3d_model_url') {
                // For 3D models
                mediaUploader = wp.media({
                    title: button.text(),
                    button: {
                        text: 'Select 3D Model'
                    },
                    multiple: false,
                    library: {
                        type: ['model/gltf-binary', 'model/gltf+json', 'application/octet-stream']
                    }
                });
                
                // Add help text for uploading models
                setTimeout(function() {
                    var helpText = $('<p class="upload-help">').html('Supported formats: GLB, GLTF, BIN. If you cannot upload these files, please check that they have the correct file extension (.glb, .gltf or .bin).');
                    $('.media-frame-content:visible').prepend(helpText);
                }, 100);
                
            } else {
                // For other media like poster images
                mediaUploader = wp.media({
                    title: button.text(),
                    button: {
                        text: 'Select'
                    },
                    multiple: false
                });
            }

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Validate if this is a 3D model file
                if (inputId === 'mg3d_model_url') {
                    var fileExt = attachment.filename.split('.').pop().toLowerCase();
                    if (fileExt !== 'glb' && fileExt !== 'gltf' && fileExt !== 'bin') {
                        alert('Warning: The selected file does not appear to be a supported 3D model format (GLB/GLTF/BIN). The viewer may not display it correctly.');
                    }
                }
                
                $('#' + inputId).val(attachment.url);
                
                // If this is a model upload, update the preview immediately
                if (inputId === 'mg3d_model_url') {
                    updateModelPreview(attachment.url);
                } else if (inputId === 'mg3d_poster_url') {
                    updatePosterPreview(attachment.url);
                }
            });

            mediaUploader.open();
        });

        // Function to update the model preview
        function updateModelPreview(modelUrl) {
            var previewContainer = $('#mg3d-preview-container');
            var currentModelViewer = document.getElementById('mg3d-model-preview');
            
            // If no model viewer exists, create one
            if (!currentModelViewer) {
                var previewHtml = '<model-viewer id="mg3d-model-preview" camera-controls style="width: 100%; height: 400px;"></model-viewer>';
                previewContainer.html(previewHtml);
                currentModelViewer = document.getElementById('mg3d-model-preview');
            }
            
            // Update model-viewer src attribute
            if (currentModelViewer) {
                currentModelViewer.setAttribute('src', modelUrl);
                // Apply current settings to the model-viewer
                applyCurrentSettingsToPreview();
            }
        }

        // Function to update the poster preview
        function updatePosterPreview(posterUrl) {
            var modelViewer = document.getElementById('mg3d-model-preview');
            if (modelViewer) {
                modelViewer.setAttribute('poster', posterUrl);
            }
        }

        // Apply current settings to the preview model
        function applyCurrentSettingsToPreview() {
            var modelViewer = document.getElementById('mg3d-model-preview');
            if (!modelViewer) return;

            var bgColor = $('#mg3d_bg_color').val();
            var bgTransparent = $('input[name="mg3d_bg_transparent"]').is(':checked');
            var cameraAngle = $('#mg3d_camera_angle').val();
            var shadowIntensity = $('#mg3d_shadow_intensity').val();
            var exposure = $('#mg3d_exposure').val();
            var autoRotate = $('input[name="mg3d_auto_rotate"]').is(':checked');
            var rotationSpeed = $('#mg3d_rotation_speed').val();
            var enableAr = $('input[name="mg3d_enable_ar"]').is(':checked');
            var animationName = $('#mg3d_animation_name').val();
            var autoplay = $('input[name="mg3d_autoplay"]').is(':checked');
            var posterUrl = $('#mg3d_poster_url').val();
            var materialColor = $('#mg3d_material_color').val();
            var enableColorChange = $('input[name="mg3d_enable_color_change"]').is(':checked');

            // Set attributes
            modelViewer.style.backgroundColor = bgTransparent ? 'transparent' : bgColor;
            
            // Set material color if enabled
            if (enableColorChange) {
                modelViewer.style.setProperty('--poster-color', materialColor);
            } else {
                modelViewer.style.removeProperty('--poster-color');
            }
            
            // Apply saved camera position if enabled
            var useSavedPosition = $('input[name="mg3d_use_saved_position"]').is(':checked');
            var savedPosition = $('#mg3d_saved_camera_position').val();
            
            if (useSavedPosition && savedPosition) {
                modelViewer.setAttribute('camera-orbit', savedPosition);
            } else {
                modelViewer.setAttribute('camera-orbit', $('#mg3d_camera_angle').val());
            }

            // Store initial camera position for reset functionality
            if (!modelViewer.dataset.initialCameraOrbit) {
                modelViewer.dataset.initialCameraOrbit = useSavedPosition && savedPosition ? 
                    savedPosition : $('#mg3d_camera_angle').val();
            }

            modelViewer.setAttribute('shadow-intensity', shadowIntensity);
            modelViewer.setAttribute('exposure', exposure);
            modelViewer.setAttribute('min-camera-orbit', 'auto auto 50%');
            modelViewer.setAttribute('max-camera-orbit', 'auto auto 200%');
            
            if (posterUrl) {
                modelViewer.setAttribute('poster', posterUrl);
            }
            
            if (autoRotate) {
                modelViewer.setAttribute('auto-rotate', '');
                modelViewer.setAttribute('rotation-per-second', 1 / rotationSpeed);
            } else {
                modelViewer.removeAttribute('auto-rotate');
                modelViewer.removeAttribute('rotation-per-second');
            }

            if (enableAr) {
                modelViewer.setAttribute('ar', '');
                modelViewer.setAttribute('ar-modes', 'webxr scene-viewer quick-look');
            } else {
                modelViewer.removeAttribute('ar');
                modelViewer.removeAttribute('ar-modes');
            }

            if (animationName) {
                modelViewer.setAttribute('animation-name', animationName);
                if (autoplay) {
                    modelViewer.setAttribute('autoplay', '');
                } else {
                    modelViewer.removeAttribute('autoplay');
                }
            } else {
                modelViewer.removeAttribute('animation-name');
                modelViewer.removeAttribute('autoplay');
            }
        }

        // Update range input values
        $('input[type="range"]').on('input', function() {
            $(this).next('.range-value').text($(this).val());
        });

        // Toggle rotation speed visibility
        $('input[name="mg3d_auto_rotate"]').change(function() {
            $('#rotation-speed-group').toggle($(this).is(':checked'));
        }).trigger('change');

        // Toggle background color visibility when transparent is checked
        $('input[name="mg3d_bg_transparent"]').change(function() {
            $('#mg3d_bg_color').prop('disabled', $(this).is(':checked'));
        }).trigger('change');

        // Toggle material color visibility
        $('input[name="mg3d_enable_color_change"]').change(function() {
            $('#material-color-group').toggleClass('disabled', !$(this).is(':checked'));
            $('#mg3d_material_color').prop('disabled', !$(this).is(':checked'));
        }).trigger('change');

        // Attach update preview to all input changes
        $('.mg3d-model-settings input, .mg3d-model-settings select').on('change input', function() {
            applyCurrentSettingsToPreview();
        });
    }

})(jQuery);