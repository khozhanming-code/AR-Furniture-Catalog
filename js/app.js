/**
 * js/app.js
 * Site-wide interactions: mobile nav toggle, model-viewer error/loading
 * handling, and AR lifecycle recovery.
 */
(function () {
    'use strict';

    /* ---------------- Mobile navigation ---------------- */
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('.nav-toggle');
        var links = document.querySelector('.nav-links');
        if (toggle && links) {
            toggle.addEventListener('click', function () {
                links.classList.toggle('open');
            });
        }
    });

    /* ---------------- <model-viewer> handling ---------------- */
    document.addEventListener('DOMContentLoaded', function () {
        var viewer = document.querySelector('model-viewer');
        if (!viewer) {
            return;
        }

        var loadingEl = document.querySelector('[data-viewer-loading]');
        var errorEl = document.querySelector('[data-viewer-error]');

        function showLoading(state) {
            if (loadingEl) loadingEl.style.display = state ? 'flex' : 'none';
        }
        function showError(state) {
            if (errorEl) errorEl.style.display = state ? 'block' : 'none';
            viewer.style.display = state ? 'none' : 'block';
        }

        showLoading(true);

        viewer.addEventListener('load', function () {
            showLoading(false);
            showError(false);
        });

        viewer.addEventListener('error', function () {
            showLoading(false);
            showError(true);
        });

        /*
         * AR lifecycle recovery.
         * Some Android/iOS browsers suspend or reset the WebGL context
         * when the AR Quick Look / Scene Viewer session hands control
         * back to the browser. We listen for the relevant events and,
         * if the viewer looks "stuck" after returning, nudge it to
         * reload its current model so the user sees a working viewer
         * again rather than a frozen/blank canvas.
         */
        var wasInAR = false;

        viewer.addEventListener('ar-status', function (event) {
            var status = event.detail && event.detail.status;
            if (status === 'session-started') {
                wasInAR = true;
            }
            if (status === 'not-presenting' && wasInAR) {
                wasInAR = false;
                // Give the browser a moment to fully return control,
                // then verify the canvas still renders correctly.
                setTimeout(function () {
                    reloadViewerIfNeeded(viewer);
                }, 400);
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && wasInAR) {
                setTimeout(function () {
                    reloadViewerIfNeeded(viewer);
                }, 300);
            }
        });

        window.addEventListener('pageshow', function (event) {
            // Handles the back-forward cache (bfcache) case on iOS Safari.
            if (event.persisted) {
                reloadViewerIfNeeded(viewer);
            }
        });

        function reloadViewerIfNeeded(viewerEl) {
            // Re-assigning the same src forces model-viewer to
            // re-initialize its renderer without a full page reload.
            var currentSrc = viewerEl.getAttribute('src');
            if (currentSrc) {
                viewerEl.setAttribute('src', '');
                requestAnimationFrame(function () {
                    viewerEl.setAttribute('src', currentSrc);
                });
            }
        }
    });

    /* ---------------- Simple mobile-device detection ---------------- */
    window.isMobileDevice = function () {
        return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    };

    /* On mobile, the "scan this QR code" hint is redundant — hide it. */
    document.addEventListener('DOMContentLoaded', function () {
        var qrBlock = document.querySelector('[data-qr-block]');
        if (qrBlock && window.isMobileDevice()) {
            qrBlock.style.display = 'none';
        }
    });
})();
