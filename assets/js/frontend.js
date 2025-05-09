/**
 * MG 3D Product Viewer - Frontend JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find alle model-viewer elementer på siden
    const viewers = document.querySelectorAll('model-viewer');
    
    viewers.forEach(viewer => {
        const container = viewer.closest('.mg3d-viewer-container');
        if (!container) return;

        // Initialiser når model-viewer er loadet
        viewer.addEventListener('load', function() {
            const toolbar = container.querySelector('.mg3d-toolbar');
            if (!toolbar) return;

            // Håndter Save View knap
            const saveBtn = toolbar.querySelector('.save-camera-position');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    const currentOrbit = viewer.getCameraOrbit();
                    const position = {
                        theta: (currentOrbit.theta * 180 / Math.PI) + 'deg',
                        phi: (currentOrbit.phi * 180 / Math.PI) + 'deg',
                        radius: (currentOrbit.radius * 100) + '%'
                    };
                    
                    // Gem position som data-attribut
                    const positionString = `${position.theta} ${position.phi} ${position.radius}`;
                    viewer.dataset.savedPosition = positionString;
                    
                    // Vis feedback til brugeren
                    const originalText = saveBtn.textContent;
                    saveBtn.textContent = 'View Saved!';
                    setTimeout(() => {
                        saveBtn.textContent = originalText;
                    }, 2000);
                });
            }

            // Håndter Reset View knap
            const resetBtn = toolbar.querySelector('.reset-camera');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
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
                        
                        const isDifferent = currentTheta !== initTheta || 
                                          currentPhi !== initPhi || 
                                          currentRadius !== initRadius;
                        
                        resetBtn.style.opacity = isDifferent ? '1' : '0.5';
                    }
                });
            }

            // Håndter loading indicator
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
