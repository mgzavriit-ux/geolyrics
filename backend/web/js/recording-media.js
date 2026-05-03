(function () {
    'use strict';

    function initializeRecordingMediaSection(root) {
        const typeSelect = root.querySelector('[data-role="recording-type-select"]');

        if (typeSelect === null) {
            return;
        }

        const update = function () {
            updateRecordingMediaVisibility(root, typeSelect.value);
        };

        typeSelect.addEventListener('change', update);
        update();
    }

    function updateRecordingMediaVisibility(root, recordingType) {
        const audioSection = root.querySelector('[data-role="recording-media-audio"]');
        const videoSection = root.querySelector('[data-role="recording-media-video"]');
        const emptyState = root.querySelector('[data-role="recording-media-empty"]');

        if (audioSection !== null) {
            audioSection.classList.toggle('d-none', recordingType !== 'audio');
        }

        if (videoSection !== null) {
            videoSection.classList.toggle('d-none', recordingType !== 'video');
        }

        if (emptyState !== null) {
            emptyState.classList.toggle('d-none', recordingType === 'audio' || recordingType === 'video');
        }
    }

    window.initializeRecordingMediaSection = initializeRecordingMediaSection;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-role="recording-media-root"], [data-recording-media-root]').forEach(function (root) {
            initializeRecordingMediaSection(root);
        });
    });
})();
