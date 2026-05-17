(function () {
    'use strict';

    function applyExistingCardState(card) {
        const deleteInput = card.querySelector('[data-role="artist-gallery-delete-input"]');
        const deleteButton = card.querySelector('[data-role="artist-gallery-delete-toggle"]');
        const primaryInput = card.querySelector('[data-role="artist-gallery-primary-radio"]');
        const sortInput = card.querySelector('[data-role="artist-gallery-sort-order"]');

        if (deleteInput === null) {
            return;
        }

        const isDeleting = deleteInput.value === '1';

        card.classList.toggle('artist-gallery-card-deleting', isDeleting);

        if (deleteButton !== null) {
            deleteButton.textContent = isDeleting ? 'Вернуть' : 'Удалить';
            deleteButton.classList.toggle('btn-outline-danger', isDeleting === false);
            deleteButton.classList.toggle('btn-outline-secondary', isDeleting);
        }

        if (primaryInput !== null) {
            if (isDeleting && primaryInput.checked) {
                primaryInput.checked = false;
            }

            primaryInput.disabled = isDeleting;
        }

        if (sortInput !== null) {
            sortInput.disabled = isDeleting;
        }
    }

    function bindExistingItems(root) {
        root.querySelectorAll('[data-role="artist-gallery-existing-item"]').forEach(function (card) {
            const deleteButton = card.querySelector('[data-role="artist-gallery-delete-toggle"]');
            const deleteInput = card.querySelector('[data-role="artist-gallery-delete-input"]');

            if (deleteButton !== null && deleteInput !== null) {
                deleteButton.addEventListener('click', function () {
                    deleteInput.value = deleteInput.value === '1' ? '0' : '1';
                    applyExistingCardState(card);
                    updateExistingEmptyState(root);
                });
            }

            applyExistingCardState(card);
        });
    }

    function createPreviewCard(file, fileIndex, input) {
        const card = document.createElement('div');
        card.className = 'artist-gallery-card';

        const preview = document.createElement('div');
        preview.className = 'artist-gallery-card-preview';

        const image = document.createElement('img');
        image.className = 'artist-gallery-card-image';
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        image.addEventListener('load', function () {
            URL.revokeObjectURL(image.src);
        });

        preview.appendChild(image);

        const body = document.createElement('div');
        body.className = 'artist-gallery-card-body';

        const title = document.createElement('div');
        title.className = 'fw-semibold text-truncate mb-1';
        title.textContent = file.name;

        const meta = document.createElement('div');
        meta.className = 'text-muted small mb-3';
        meta.textContent = [file.type || 'image', formatFileSize(file.size)].join(' · ');

        const note = document.createElement('div');
        note.className = 'd-flex justify-content-between align-items-center';

        const badge = document.createElement('span');
        badge.className = 'badge text-bg-light';
        badge.textContent = 'Новый файл';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-outline-danger btn-sm';
        removeButton.textContent = 'Убрать';
        removeButton.addEventListener('click', function () {
            removeFileFromInput(input, fileIndex);
            renderNewFilePreviews(input.closest('[data-role="artist-gallery-root"]'));
        });

        note.appendChild(badge);
        note.appendChild(removeButton);

        body.appendChild(title);
        body.appendChild(meta);
        body.appendChild(note);

        card.appendChild(preview);
        card.appendChild(body);

        return card;
    }

    function formatFileSize(size) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let value = size;
        let unitIndex = 0;

        while (value >= 1024 && unitIndex < units.length - 1) {
            value = value / 1024;
            unitIndex += 1;
        }

        const rounded = value >= 10 || unitIndex === 0
            ? Math.round(value)
            : Math.round(value * 10) / 10;

        return rounded + ' ' + units[unitIndex];
    }

    function removeFileFromInput(input, fileIndex) {
        const transfer = new DataTransfer();

        Array.from(input.files || []).forEach(function (file, index) {
            if (index === fileIndex) {
                return;
            }

            transfer.items.add(file);
        });

        input.files = transfer.files;
    }

    function renderNewFilePreviews(root) {
        if (root === null) {
            return;
        }

        const input = root.querySelector('[data-role="artist-gallery-input"]');
        const list = root.querySelector('[data-role="artist-gallery-new-list"]');
        const empty = root.querySelector('[data-role="artist-gallery-new-empty"]');

        if (input === null || list === null || empty === null) {
            return;
        }

        list.innerHTML = '';

        Array.from(input.files || []).forEach(function (file, index) {
            list.appendChild(createPreviewCard(file, index, input));
        });

        const hasFiles = list.children.length > 0;
        list.classList.toggle('d-none', hasFiles === false);
        empty.classList.toggle('d-none', hasFiles);
    }

    function updateExistingEmptyState(root) {
        const list = root.querySelector('[data-role="artist-gallery-existing-list"]');
        const empty = root.querySelector('[data-role="artist-gallery-existing-empty"]');

        if (list === null || empty === null) {
            return;
        }

        const activeItemsCount = Array.from(
            root.querySelectorAll('[data-role="artist-gallery-existing-item"]')
        ).filter(function (card) {
            const deleteInput = card.querySelector('[data-role="artist-gallery-delete-input"]');

            return deleteInput !== null && deleteInput.value !== '1';
        }).length;

        list.classList.toggle('d-none', activeItemsCount === 0);
        empty.classList.toggle('d-none', activeItemsCount > 0);
    }

    function initializeArtistGallery(root) {
        const input = root.querySelector('[data-role="artist-gallery-input"]');

        bindExistingItems(root);
        updateExistingEmptyState(root);
        renderNewFilePreviews(root);

        if (input !== null) {
            input.addEventListener('change', function () {
                renderNewFilePreviews(root);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-role="artist-gallery-root"]').forEach(function (root) {
            initializeArtistGallery(root);
        });
    });
})();
