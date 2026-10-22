/**
 * js/qr.js
 * Renders a QR code that points at the current page's public URL so a
 * desktop user can quickly open the same page (and launch AR) on their
 * phone. Never hard-codes localhost/127.0.0.1 — it always reads the
 * real URL the page is currently being served from.
 *
 * Implementation note: generating a QR code image purely offline in
 * vanilla JS (no build tools/npm) requires a sizeable encoding
 * algorithm. To keep this project dependency-free and framework-free,
 * we request a QR PNG from a public QR-generation endpoint using the
 * page's own URL as the payload — no user or product data is sent,
 * only the public URL that is already visible in the address bar.
 * If you prefer a fully offline solution, replace buildQrImageUrl()
 * below with a call to a self-hosted QR image endpoint.
 */
(function () {
    'use strict';

    function buildQrImageUrl(targetUrl, sizePx) {
        var size = sizePx || 220;
        var encoded = encodeURIComponent(targetUrl);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encoded;
    }

    function renderQrCode(containerSelector, targetUrl) {
        var container = document.querySelector(containerSelector);
        if (!container) {
            return;
        }
        // On a phone, localhost refers to the phone rather than this computer.
        if (/^(localhost|127\.0\.0\.1)$/i.test(window.location.hostname)) {
            container.textContent = 'To scan from your phone, open this site on your computer’s LAN IP first (for example http://192.168.1.10/ar-furniture/). Keep both devices on the same Wi-Fi.';
            return;
        }
        var img = document.createElement('img');
        img.src = buildQrImageUrl(targetUrl);
        img.alt = 'QR code linking to this page on your phone';
        img.width = 220;
        img.height = 220;
        img.loading = 'lazy';
        container.innerHTML = '';
        container.appendChild(img);
    }

    function initCopyLinkButtons() {
        document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-copy-link') || window.location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        var original = btn.textContent;
                        btn.textContent = 'Link copied!';
                        setTimeout(function () { btn.textContent = original; }, 1800);
                    });
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var qrTarget = document.querySelector('[data-qr-target]');
        if (qrTarget) {
            var url = qrTarget.getAttribute('data-qr-target');
            renderQrCode('[data-qr-container]', url);
        }
        initCopyLinkButtons();
    });

    window.ARFurnitureQR = { buildQrImageUrl: buildQrImageUrl, renderQrCode: renderQrCode };
})();
