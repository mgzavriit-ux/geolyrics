(function () {
    'use strict';

    function initializeSongEditor(root) {
        const translationUrl = root.dataset.translationUrl;
        const textUrl = root.dataset.textUrl;
        const songId = root.dataset.songId;
        const languageSelect = root.querySelector('[data-role="translation-language-select"]');
        const translationPanels = root.querySelector('[data-role="translation-panels"]');
        const songTextLanguageSelect = root.querySelector('[data-role="song-text-language-select"]');
        const songTextPanels = root.querySelector('[data-role="song-text-panels"]');
        const addSongLineButton = root.querySelector('[data-role="add-song-line"]');
        const addRecordingButton = root.querySelector('[data-role="add-recording"]');
        const recordingItems = root.querySelector('[data-role="recording-items"]');
        const songLineItems = root.querySelector('[data-role="song-line-items"]');

        if (languageSelect !== null && translationPanels !== null && translationUrl !== undefined) {
            initializeAjaxPanelSwitcher(root, {
                url: translationUrl,
                songId: songId,
                languageSelect: languageSelect,
                panels: translationPanels,
                panelRole: 'translation-panel',
                errorMessage: 'Не удалось загрузить перевод для выбранного языка.',
            });
        }

        if (songTextLanguageSelect !== null && songTextPanels !== null && textUrl !== undefined) {
            initializeAjaxPanelSwitcher(root, {
                url: textUrl,
                songId: songId,
                languageSelect: songTextLanguageSelect,
                panels: songTextPanels,
                panelRole: 'song-text-panel',
                errorMessage: 'Не удалось загрузить текст для выбранного языка.',
            });
            initializeSongTextEditors(root);
        }

        if (songLineItems !== null) {
            initializeSongLineManager(root, songLineItems, addSongLineButton);
        }

        if (recordingItems !== null) {
            initializeRecordingManager(root, recordingItems, addRecordingButton);
        }
    }

    function addSongLineItem(root, songLineItems, lineText) {
        const item = createSongLineItemFromTemplate(root, songLineItems);

        if (item === null) {
            return null;
        }

        clearSongLineItem(item);

        if (lineText !== undefined) {
            const originalTextInput = item.querySelector('[data-role="song-line-original-text"]');

            if (originalTextInput !== null) {
                originalTextInput.value = lineText;
                originalTextInput.dispatchEvent(new Event('input', { bubbles: true }));
                originalTextInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        updateSongLinePresentation(root);

        return item;
    }

    function addRecordingArtistItem(root, recordingItem) {
        const item = createRecordingArtistItemFromTemplate(root, recordingItem);

        if (item === null) {
            return null;
        }

        clearRecordingArtistItem(item);
        updateRecordingPresentation(root);

        return item;
    }

    function addRecordingItem(root, recordingItems) {
        const item = createRecordingItemFromTemplate(root, recordingItems);

        if (item === null) {
            return null;
        }

        clearRecordingItem(item);

        if (typeof window.initializeRecordingMediaSection === 'function') {
            window.initializeRecordingMediaSection(item);
        }

        updateRecordingPresentation(root);

        return item;
    }

    function initializeAjaxPanelSwitcher(root, options) {
        const url = options.url;
        const songId = options.songId;
        const languageSelect = options.languageSelect;
        const panels = options.panels;
        const panelRole = options.panelRole;
        const errorMessage = options.errorMessage;
        let activeRequest = null;

        showTranslationPanel(
            panels,
            languageSelect.value,
            panelRole,
        );

        languageSelect.addEventListener('change', function () {
            const languageId = languageSelect.value;

            if (languageId === '') {
                hideAllTranslationPanels(panels, panelRole);

                return;
            }

            const existingPanel = findTranslationPanel(panels, languageId, panelRole);

            if (existingPanel !== null) {
                showTranslationPanel(panels, languageId, panelRole);

                return;
            }

            if (activeRequest !== null) {
                activeRequest.abort();
            }

            languageSelect.setAttribute('disabled', 'disabled');

            const request = new XMLHttpRequest();

            activeRequest = request;
            request.open('GET', buildTranslationUrl(url, languageId, songId), true);
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.onreadystatechange = function () {
                if (request.readyState !== XMLHttpRequest.DONE) {
                    return;
                }

                if (activeRequest !== null && request !== activeRequest) {
                    return;
                }

                if (activeRequest === request) {
                    activeRequest = null;
                }

                languageSelect.removeAttribute('disabled');

                if (request.status === 0) {
                    return;
                }

                if (request.status < 200 || request.status >= 300) {
                    window.alert(errorMessage);

                    return;
                }

                appendTranslationPanel(panels, request.responseText);
                showTranslationPanel(panels, languageId, panelRole);
                initializeSongTextEditors(root);
            };
            request.send();
        });
    }

    function initializeRecordingManager(root, recordingItems, addRecordingButton) {
        if (addRecordingButton !== null) {
            addRecordingButton.addEventListener('click', function () {
                addRecordingItem(root, recordingItems);
            });
        }

        root.addEventListener('click', function (event) {
            const button = event.target.closest('[data-role="add-recording-artist"]');

            if (button === null) {
                return;
            }

            const recordingItem = button.closest('[data-role="recording-item"]');

            if (recordingItem === null) {
                return;
            }

            addRecordingArtistItem(root, recordingItem);
        });

        root.addEventListener('click', function (event) {
            const button = event.target.closest('[data-role="remove-recording"]');

            if (button === null) {
                return;
            }

            const recordingItem = button.closest('[data-role="recording-item"]');

            if (recordingItem === null) {
                return;
            }

            removeRecordingItem(root, recordingItem);
        });

        updateRecordingPresentation(root);
    }

    function initializeSongLineManager(root, songLineItems, addSongLineButton) {
        if (addSongLineButton !== null) {
            addSongLineButton.addEventListener('click', function () {
                addSongLineItem(root, songLineItems);
            });
        }

        updateSongLinePresentation(root);
    }

    function initializeSongTextEditors(root) {
        root.querySelectorAll('[data-role="song-text-source"]').forEach(function (textarea) {
            if (textarea.dataset.dirtyInitialized === '1') {
                return;
            }

            textarea.dataset.dirtyInitialized = '1';
            textarea.addEventListener('input', function () {
                const panel = textarea.closest('[data-role="song-text-panel"]');

                if (panel === null) {
                    return;
                }

                const dirtyInput = panel.querySelector('[data-role="song-text-dirty"]');

                if (dirtyInput !== null) {
                    dirtyInput.value = '1';
                }
            });
        });
    }

    function appendTranslationPanel(translationPanels, html) {
        const container = document.createElement('div');
        container.innerHTML = html.trim();

        if (container.firstElementChild === null) {
            return;
        }

        translationPanels.appendChild(container.firstElementChild);
    }

    function buildTranslationUrl(translationUrl, languageId, songId) {
        const url = new URL(translationUrl, window.location.origin);
        url.searchParams.set('languageId', languageId);

        if (songId !== '') {
            url.searchParams.set('id', songId);
        }

        return url.toString();
    }

    function clearSongLineItem(item) {
        item.querySelectorAll('[data-role="song-line-original-text"], [data-role="song-line-translation-text"]').forEach(function (input) {
            input.value = '';
        });
    }

    function createSongLineItemFromTemplate(root, songLineItems) {
        const template = root.querySelector('template[data-role="song-line-template"]');

        if (template === null) {
            return null;
        }

        const lineIndex = getNextSongLineIndex(songLineItems);
        const translationLanguageCount = Number(songLineItems.dataset.translationLanguageCount || '0');
        let html = template.innerHTML.replaceAll('__line_index__', String(lineIndex));

        for (let translationOffset = 0; translationOffset < translationLanguageCount; translationOffset += 1) {
            const translationIndex = lineIndex * translationLanguageCount + translationOffset;

            html = html.replaceAll(
                '__translation_index_' + translationOffset + '__',
                String(translationIndex),
            );
        }

        const container = document.createElement('div');
        container.innerHTML = html.trim();

        if (container.firstElementChild === null) {
            return null;
        }

        const item = container.firstElementChild;

        songLineItems.appendChild(item);
        songLineItems.dataset.nextLineIndex = String(lineIndex + 1);

        return item;
    }

    function findTranslationPanel(translationPanels, languageId, panelRole) {
        return translationPanels.querySelector('[data-role="' + panelRole + '"][data-language-id="' + cssEscape(languageId) + '"]');
    }

    function createRecordingArtistItemFromTemplate(root, recordingItem) {
        const template = root.querySelector('template[data-role="recording-artist-template"]');

        if (template === null) {
            return null;
        }

        const recordingItems = root.querySelector('[data-role="recording-items"]');

        if (recordingItems === null) {
            return null;
        }

        const recordingIndex = recordingItem.dataset.recordingIndex;
        const artistFlatIndex = Number(recordingItems.dataset.nextRecordingArtistFlatIndex || '0');
        let html = template.innerHTML.replaceAll('__recording_index__', String(recordingIndex));

        html = html.replaceAll('__artist_flat_index__', String(artistFlatIndex));

        const container = document.createElement('div');
        container.innerHTML = html.trim();

        if (container.firstElementChild === null) {
            return null;
        }

        const artistItems = recordingItem.querySelector('[data-role="recording-artist-items"]');

        if (artistItems === null) {
            return null;
        }

        const item = container.firstElementChild;

        artistItems.appendChild(item);
        recordingItems.dataset.nextRecordingArtistFlatIndex = String(artistFlatIndex + 1);

        return item;
    }

    function createRecordingItemFromTemplate(root, recordingItems) {
        const template = root.querySelector('template[data-role="recording-template"]');

        if (template === null) {
            return null;
        }

        const recordingIndex = Number(recordingItems.dataset.nextRecordingIndex || '0');
        const html = template.innerHTML.replaceAll('__recording_index__', String(recordingIndex));
        const container = document.createElement('div');

        container.innerHTML = html.trim();

        if (container.firstElementChild === null) {
            return null;
        }

        const item = container.firstElementChild;

        recordingItems.appendChild(item);
        recordingItems.dataset.nextRecordingIndex = String(recordingIndex + 1);

        return item;
    }

    function getNextSongLineIndex(songLineItems) {
        return Number(songLineItems.dataset.nextLineIndex || '0');
    }

    function getVisibleSongLineItems(songLineItems) {
        return Array.from(songLineItems.querySelectorAll('[data-role="song-line-item"]')).filter(function (item) {
            return item.classList.contains('d-none') === false;
        });
    }

    function hideAllTranslationPanels(translationPanels, panelRole) {
        translationPanels.querySelectorAll('[data-role="' + panelRole + '"]').forEach(function (panel) {
            panel.classList.add('d-none');
        });
    }

    function showTranslationPanel(translationPanels, languageId, panelRole) {
        hideAllTranslationPanels(translationPanels, panelRole);

        if (languageId === '') {
            return;
        }

        const panel = findTranslationPanel(translationPanels, languageId, panelRole);

        if (panel === null) {
            return;
        }

        panel.classList.remove('d-none');
    }

    function cssEscape(value) {
        if (window.CSS !== undefined && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return value.replace(/"/g, '\\"');
    }

    function updateSongLinePresentation(root) {
        const songLineItems = root.querySelector('[data-role="song-line-items"]');
        const songLineCount = root.querySelector('[data-role="song-line-count"]');
        const emptyState = root.querySelector('[data-role="song-line-empty-state"]');

        if (songLineItems === null) {
            return;
        }

        const visibleItems = getVisibleSongLineItems(songLineItems);

        visibleItems.forEach(function (item, index) {
            const title = item.querySelector('[data-role="song-line-title"]');

            if (title !== null) {
                title.textContent = 'Строка ' + (index + 1);
            }
        });

        if (songLineCount !== null) {
            songLineCount.textContent = String(visibleItems.length);
        }

        if (emptyState !== null) {
            emptyState.classList.toggle('d-none', visibleItems.length > 0);
        }
    }

    function clearRecordingArtistItem(item) {
        item.querySelectorAll('input, textarea, select').forEach(function (input) {
            if (input.type === 'hidden') {
                return;
            }

            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
                return;
            }

            input.value = '';
        });
    }

    function clearRecordingItem(item) {
        item.querySelectorAll('input, textarea, select').forEach(function (input) {
            if (input.closest('[data-role="recording-artist-item"]') !== null) {
                return;
            }

            if (input.dataset.role === 'recording-delete-flag') {
                input.value = '0';
                return;
            }

            if (input.type === 'hidden' && input.name.indexOf('[id]') !== -1) {
                return;
            }

            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
                return;
            }

            input.value = '';
        });

        item.querySelectorAll('[data-role="recording-artist-item"]').forEach(function (artistItem) {
            clearRecordingArtistItem(artistItem);
            artistItem.remove();
        });
    }

    function removeRecordingItem(root, recordingItem) {
        if (window.confirm('Удалить эту запись?') === false) {
            return;
        }

        clearRecordingItem(recordingItem);

        const deleteFlagInput = recordingItem.querySelector('[data-role="recording-delete-flag"]');

        if (deleteFlagInput !== null) {
            deleteFlagInput.value = '1';
        }

        recordingItem.classList.add('d-none');
        updateRecordingPresentation(root);
    }

    function getVisibleRecordingArtistItems(recordingItem) {
        return Array.from(recordingItem.querySelectorAll('[data-role="recording-artist-item"]')).filter(function (item) {
            return item.classList.contains('d-none') === false;
        });
    }

    function getVisibleRecordingItems(recordingItems) {
        return Array.from(recordingItems.querySelectorAll('[data-role="recording-item"]')).filter(function (item) {
            return item.classList.contains('d-none') === false;
        });
    }

    function updateRecordingArtistPresentation(recordingItem) {
        const visibleArtistItems = getVisibleRecordingArtistItems(recordingItem);
        const emptyState = recordingItem.querySelector('[data-role="recording-artist-empty-state"]');

        if (emptyState !== null) {
            emptyState.classList.toggle('d-none', visibleArtistItems.length > 0);
        }
    }

    function updateRecordingPresentation(root) {
        const recordingItems = root.querySelector('[data-role="recording-items"]');
        const emptyState = root.querySelector('[data-role="recording-empty-state"]');

        if (recordingItems === null) {
            return;
        }

        const visibleRecordingItems = getVisibleRecordingItems(recordingItems);

        visibleRecordingItems.forEach(function (recordingItem, index) {
            const title = recordingItem.querySelector('[data-role="recording-title"]');

            if (title !== null) {
                title.textContent = 'Запись ' + (index + 1);
            }

            updateRecordingArtistPresentation(recordingItem);
        });

        if (emptyState !== null) {
            emptyState.classList.toggle('d-none', visibleRecordingItems.length > 0);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-song-editor]').forEach(function (root) {
            initializeSongEditor(root);
        });
    });
})();
