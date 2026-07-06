(function () {
    'use strict';

    function clampPoint(value) {
        return Math.max(0, Math.min(100, Math.round(value)));
    }

    function findPoint(event, preview) {
        const bounds = preview.getBoundingClientRect();

        return {
            x: clampPoint(((event.clientX - bounds.left) / bounds.width) * 100),
            y: clampPoint(((event.clientY - bounds.top) / bounds.height) * 100),
        };
    }

    function renderMarker(root) {
        const marker = root.querySelector('[data-role="home-hero-image-marker"]');
        const inputX = root.querySelector('[data-role="home-hero-image-focal-x"]');
        const inputY = root.querySelector('[data-role="home-hero-image-focal-y"]');

        if (marker === null || inputX === null || inputY === null) {
            return;
        }

        marker.style.left = clampPoint(Number(inputX.value || 50)) + '%';
        marker.style.top = clampPoint(Number(inputY.value || 50)) + '%';
    }

    function setPoint(root, point) {
        const inputX = root.querySelector('[data-role="home-hero-image-focal-x"]');
        const inputY = root.querySelector('[data-role="home-hero-image-focal-y"]');

        if (inputX === null || inputY === null) {
            return;
        }

        inputX.value = String(point.x);
        inputY.value = String(point.y);
        renderMarker(root);
    }

    function renderSelectedImage(root) {
        const input = root.querySelector('[data-role="home-hero-image-input"]');
        const image = root.querySelector('[data-role="home-hero-image"]');
        const preview = root.querySelector('[data-role="home-hero-image-preview"]');
        const empty = root.querySelector('[data-role="home-hero-image-empty"]');

        if (input === null || image === null || preview === null || empty === null) {
            return;
        }

        const file = input.files === null ? null : input.files[0] || null;

        if (file === null) {
            return;
        }

        if (root.dataset.objectUrl) {
            URL.revokeObjectURL(root.dataset.objectUrl);
        }

        root.dataset.objectUrl = URL.createObjectURL(file);
        image.src = root.dataset.objectUrl;
        image.alt = file.name;
        preview.classList.remove('d-none');
        empty.classList.add('d-none');
    }

    function bindFocalPoint(root) {
        const preview = root.querySelector('[data-role="home-hero-image-preview"]');
        const inputX = root.querySelector('[data-role="home-hero-image-focal-x"]');
        const inputY = root.querySelector('[data-role="home-hero-image-focal-y"]');
        let isDragging = false;

        if (preview === null) {
            return;
        }

        preview.addEventListener('pointerdown', function (event) {
            isDragging = true;
            preview.setPointerCapture(event.pointerId);
            setPoint(root, findPoint(event, preview));
        });

        preview.addEventListener('pointermove', function (event) {
            if (isDragging === false) {
                return;
            }

            setPoint(root, findPoint(event, preview));
        });

        preview.addEventListener('pointerup', function (event) {
            isDragging = false;
            preview.releasePointerCapture(event.pointerId);
        });

        if (inputX !== null) {
            inputX.addEventListener('input', function () {
                renderMarker(root);
            });
        }

        if (inputY !== null) {
            inputY.addEventListener('input', function () {
                renderMarker(root);
            });
        }
    }

    function initializeHomeHeroImageForm(root) {
        const input = root.querySelector('[data-role="home-hero-image-input"]');

        bindFocalPoint(root);
        renderMarker(root);

        if (input !== null) {
            input.addEventListener('change', function () {
                renderSelectedImage(root);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-role="home-hero-image-root"]').forEach(function (root) {
            initializeHomeHeroImageForm(root);
        });
    });
})();
