/**
 * MG 3D Product Viewer - Frontend Script
 */

(function($) {
    'use strict';

    // Handle model-viewer errors
    document.addEventListener('model-viewer-error', function(e) {
        console.error('Model viewer error:', e.detail);
        showError(mg3dData.errorMessages.loadError);
    });

    // Initialize all model viewers on the page
    function initModelViewers() {
        const viewers = document.querySelectorAll('model-viewer');
        
        viewers.forEach(viewer => {
            // Handle model loading errors
            viewer.addEventListener('error', function(e) {
                console.error('Model error:', e);
                showError(mg3dData.errorMessages.modelError);
            });

            // Handle AR errors
            viewer.addEventListener('ar-status', function(e) {
                if (e.detail.status === 'failed') {
                    showError(mg3dData.errorMessages.arError);
                }
            });

            // Handle progress bar
            viewer.addEventListener('progress', function(e) {
                const progressBar = viewer.querySelector('.progress-bar');
                const updateBar = viewer.querySelector('.update-bar');
                if (progressBar && updateBar) {
                    progressBar.classList.remove('hide');
                    updateBar.style.width = `${e.detail.totalProgress * 100}%`;
                    if (e.detail.totalProgress === 1) {
                        setTimeout(() => {
                            progressBar.classList.add('hide');
                        }, 500);
                    }
                }
            });

            // Handle camera controls
            const resetBtn = viewer.parentElement.querySelector('.reset-camera');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    viewer.setAttribute('camera-orbit', viewer.dataset.initialCameraOrbit);
                });
            }

            // Handle AR button
            const arButton = viewer.querySelector('.ar-button');
            if (arButton) {
                arButton.addEventListener('click', () => {
                    if (!viewer.canActivateAR) {
                        showError(mg3dData.errorMessages.arError);
                    }
                });
            }
        });
    }

    // Show error message
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mg3d-error';
        errorDiv.textContent = message;
        
        // Find the closest container
        const container = document.querySelector('.mg3d-viewer-container');
        if (container) {
            container.insertBefore(errorDiv, container.firstChild);
            
            // Remove error after 5 seconds
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initModelViewers();
    });

    // Re-initialize when new content is loaded (e.g., via AJAX)
    $(document).on('mg3d-content-loaded', function() {
        initModelViewers();
    });

})(jQuery);
