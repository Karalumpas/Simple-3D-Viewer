/**
 * MG 3D Product Viewer - Frontend JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find alle model-viewer elementer på siden
    const viewers = document.querySelectorAll('model-viewer');
    
    viewers.forEach(viewer => {
        // Initialiser når model-viewer er loadet
        viewer.addEventListener('load', function() {
            const container = viewer.closest('.mg3d-viewer-container');
            if (!container) return;

            // Find reset knappen
            const resetBtn = container.querySelector('.reset-camera');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    // Nulstil kamera til oprindelig position
                    const initialOrbit = viewer.dataset.initialCameraOrbit;
                    if (initialOrbit) {
                        viewer.setAttribute('camera-orbit', initialOrbit);
                    }
                });

                // Vis/skjul reset knap baseret på kamera position
                viewer.addEventListener('camera-change', () => {
                    const currentOrbit = viewer.getCameraOrbit();
                    const initialOrbit = viewer.dataset.initialCameraOrbit;
                    
                    if (initialOrbit) {
                        const [initTheta, initPhi, initRadius] = initialOrbit.split(' ');
                        const currentTheta = (currentOrbit.theta * 180 / Math.PI) + 'deg';
                        const currentPhi = (currentOrbit.phi * 180 / Math.PI) + 'deg';
                        const currentRadius = (currentOrbit.radius * 100) + '%';
                        
                        // Tjek om nuværende position er forskellig fra start position
                        const isDifferent = currentTheta !== initTheta || 
                                          currentPhi !== initPhi || 
                                          currentRadius !== initRadius;
                        
                        resetBtn.style.opacity = isDifferent ? '1' : '0';
                        resetBtn.style.pointerEvents = isDifferent ? 'auto' : 'none';
                    }
                });
            }

            // Tilføj loading indicator
            viewer.addEventListener('progress', (e) => {
                const progress = e.detail.totalProgress * 100;
                const progressBar = container.querySelector('.progress-bar .update-bar');
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                    if (progress === 100) {
                        progressBar.parentElement.classList.add('hide');
                    } else {
                        progressBar.parentElement.classList.remove('hide');
                    }
                }
            });
        });
    });
});
