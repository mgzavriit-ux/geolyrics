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
        const addSongArrangementButton = root.querySelector('[data-role="add-song-arrangement"]');
        const songArrangementItems = root.querySelector('[data-role="song-arrangement-items"]');
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
            initializeSongLineTimingEditor(root, songLineItems);
        }

        if (songArrangementItems !== null) {
            initializeSongArrangementManager(root, songArrangementItems, addSongArrangementButton);
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
        updateSongLineTimingEditor(root);

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

    function addSongArrangementItem(root, songArrangementItems) {
        const item = createSongArrangementItemFromTemplate(root, songArrangementItems);

        if (item === null) {
            return null;
        }

        clearSongArrangementItem(item);
        updateSongArrangementPresentation(root);

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

    function initializeSongArrangementManager(root, songArrangementItems, addSongArrangementButton) {
        if (addSongArrangementButton !== null) {
            addSongArrangementButton.addEventListener('click', function () {
                addSongArrangementItem(root, songArrangementItems);
            });
        }

        root.addEventListener('click', function (event) {
            const button = event.target.closest('[data-role="remove-song-arrangement"]');

            if (button === null) {
                return;
            }

            const arrangementItem = button.closest('[data-role="song-arrangement-item"]');

            if (arrangementItem === null) {
                return;
            }

            removeSongArrangementItem(root, arrangementItem);
        });

        updateSongArrangementPresentation(root);
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

    function initializeSongLineTimingEditor(root, songLineItems) {
        const editor = root.querySelector('[data-role="song-line-timing-editor"]');

        if (editor === null) {
            return;
        }

        const audio = editor.querySelector('[data-role="song-line-timing-audio"]');
        const sourceSelect = editor.querySelector('[data-role="song-line-timing-audio-source"]');
        const playButton = editor.querySelector('[data-role="song-line-timing-play"]');
        const tapButton = editor.querySelector('[data-role="song-line-timing-tap"]');
        const undoButton = editor.querySelector('[data-role="song-line-timing-undo"]');
        const autoEndInput = editor.querySelector('[data-role="song-line-timing-auto-end"]');
        const playbackRateSelect = editor.querySelector('[data-role="song-line-timing-playback-rate"]');
        const status = editor.querySelector('[data-role="song-line-timing-status"]');
        const linesContainer = editor.querySelector('[data-role="song-line-timing-lines"]');
        const saveButton = editor.querySelector('[data-role="song-line-timing-save"]');
        const shiftInput = editor.querySelector('[data-role="song-line-timing-shift-ms"]');
        const shiftBackwardButton = editor.querySelector('[data-role="song-line-timing-shift-backward"]');
        const shiftForwardButton = editor.querySelector('[data-role="song-line-timing-shift-forward"]');
        const history = [];
        let currentLineIndex = findInitialSongLineTimingIndex(songLineItems);
        let shouldScrollCurrentLine = false;

        if (
            audio === null
            || playButton === null
            || tapButton === null
            || undoButton === null
            || linesContainer === null
        ) {
            return;
        }

        editor.songLineTimingUpdate = function () {
            const visibleItems = getVisibleSongLineItems(songLineItems);

            if (currentLineIndex > visibleItems.length) {
                currentLineIndex = Math.max(0, visibleItems.length);
            }

            updateSongLineTimingPresentation(
                visibleItems,
                currentLineIndex,
                audio,
                tapButton,
                undoButton,
                history,
                status,
                linesContainer,
                shouldScrollCurrentLine,
            );
            shouldScrollCurrentLine = false;
        };

        if (sourceSelect !== null) {
            sourceSelect.addEventListener('change', function () {
                updateSongLineTimingAudioSource(audio, sourceSelect);
                updateSongLineTimingPlayButton(playButton, audio);
                editor.songLineTimingUpdate();
            });
        }

        playButton.addEventListener('click', function () {
            toggleSongLineTimingPlayback(audio, tapButton);
        });

        tapButton.addEventListener('click', function () {
            currentLineIndex = tapSongLineStart(
                songLineItems,
                currentLineIndex,
                audio,
                autoEndInput,
                history,
            );
            shouldScrollCurrentLine = true;
            editor.songLineTimingUpdate();

            if (currentLineIndex >= getVisibleSongLineItems(songLineItems).length && saveButton !== null) {
                saveButton.focus({ preventScroll: true });

                return;
            }

            tapButton.focus({ preventScroll: true });
        });

        undoButton.addEventListener('click', function () {
            currentLineIndex = undoSongLineTiming(history, currentLineIndex);
            shouldScrollCurrentLine = true;
            editor.songLineTimingUpdate();
            tapButton.focus({ preventScroll: true });
        });

        if (shiftBackwardButton !== null) {
            shiftBackwardButton.addEventListener('click', function () {
                const shiftMilliseconds = findSongLineTimingShiftMilliseconds(shiftInput);

                if (shiftMilliseconds === 0) {
                    return;
                }

                shiftSongLineTiming(songLineItems, -shiftMilliseconds, currentLineIndex, history);
                editor.songLineTimingUpdate();
                shiftBackwardButton.focus({ preventScroll: true });
            });
        }

        if (shiftForwardButton !== null) {
            shiftForwardButton.addEventListener('click', function () {
                const shiftMilliseconds = findSongLineTimingShiftMilliseconds(shiftInput);

                if (shiftMilliseconds === 0) {
                    return;
                }

                shiftSongLineTiming(songLineItems, shiftMilliseconds, currentLineIndex, history);
                editor.songLineTimingUpdate();
                shiftForwardButton.focus({ preventScroll: true });
            });
        }

        if (playbackRateSelect !== null) {
            playbackRateSelect.addEventListener('change', function () {
                audio.playbackRate = Number(playbackRateSelect.value || '1');
            });
            audio.playbackRate = Number(playbackRateSelect.value || '1');
        }

        audio.addEventListener('play', function () {
            updateSongLineTimingPlayButton(playButton, audio);
        });
        audio.addEventListener('pause', function () {
            updateSongLineTimingPlayButton(playButton, audio);
        });
        audio.addEventListener('timeupdate', function () {
            editor.songLineTimingUpdate();
        });

        linesContainer.addEventListener('click', function (event) {
            if (event.target === null || typeof event.target.closest !== 'function') {
                return;
            }

            const line = event.target.closest('[data-role="song-line-timing-line"]');

            if (line === null) {
                return;
            }

            currentLineIndex = Number(line.dataset.timingLineIndex || '0');
            shouldScrollCurrentLine = true;
            editor.songLineTimingUpdate();
            tapButton.focus({ preventScroll: true });
        });

        editor.addEventListener('shown.bs.modal', function () {
            currentLineIndex = findInitialSongLineTimingIndex(songLineItems);
            shouldScrollCurrentLine = true;
            editor.songLineTimingUpdate();
            tapButton.focus({ preventScroll: true });
        });

        editor.addEventListener('hidden.bs.modal', function () {
            audio.pause();
        });

        root.addEventListener('click', function (event) {
            if (isSongLineTimingInteractiveTarget(event.target)) {
                return;
            }

            if (event.target === null || typeof event.target.closest !== 'function') {
                return;
            }

            const item = event.target.closest('[data-role="song-line-item"]');

            if (item === null) {
                return;
            }

            const visibleItems = getVisibleSongLineItems(songLineItems);
            const itemIndex = visibleItems.indexOf(item);

            if (itemIndex === -1) {
                return;
            }

            currentLineIndex = itemIndex;
            shouldScrollCurrentLine = true;
            editor.songLineTimingUpdate();
        });

        root.addEventListener('input', function (event) {
            if (isSongLineTimingInput(event.target) === false) {
                return;
            }

            editor.songLineTimingUpdate();
        });

        updateSongLineTimingPlayButton(playButton, audio);
        editor.songLineTimingUpdate();
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
        item.querySelectorAll([
            '[data-role="song-line-original-text"]',
            '[data-role="song-line-start-ms"]',
            '[data-role="song-line-end-ms"]',
            '[data-role="song-line-translation-text"]',
        ].join(', ')).forEach(function (input) {
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

    function createSongArrangementItemFromTemplate(root, songArrangementItems) {
        const template = root.querySelector('template[data-role="song-arrangement-template"]');

        if (template === null) {
            return null;
        }

        const arrangementIndex = Number(songArrangementItems.dataset.nextArrangementIndex || '0');
        const html = template.innerHTML.replaceAll('__arrangement_index__', String(arrangementIndex));
        const container = document.createElement('div');

        container.innerHTML = html.trim();

        if (container.firstElementChild === null) {
            return null;
        }

        const item = container.firstElementChild;

        songArrangementItems.appendChild(item);
        songArrangementItems.dataset.nextArrangementIndex = String(arrangementIndex + 1);

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

    function findInitialSongLineTimingIndex(songLineItems) {
        const visibleItems = getVisibleSongLineItems(songLineItems);

        for (let index = 0; index < visibleItems.length; index += 1) {
            const startInput = findSongLineTimingStartInput(visibleItems[index]);

            if (startInput !== null && startInput.value.trim() === '') {
                return index;
            }
        }

        return visibleItems.length;
    }

    function findSongLineTimingStartInput(item) {
        return item.querySelector('[data-role="song-line-start-ms"]');
    }

    function findSongLineTimingEndInput(item) {
        return item.querySelector('[data-role="song-line-end-ms"]');
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

    function updateSongLineTimingEditor(root) {
        const editor = root.querySelector('[data-role="song-line-timing-editor"]');

        if (editor === null || typeof editor.songLineTimingUpdate !== 'function') {
            return;
        }

        editor.songLineTimingUpdate();
    }

    function updateSongLineTimingPresentation(
        visibleItems,
        currentLineIndex,
        audio,
        tapButton,
        undoButton,
        history,
        status,
        linesContainer,
        shouldScrollCurrentLine,
    ) {
        syncSongLineTimingRows(visibleItems, linesContainer);

        visibleItems.forEach(function (item, index) {
            const startInput = findSongLineTimingStartInput(item);
            const hasStart = startInput !== null && startInput.value.trim() !== '';
            const modalLine = linesContainer.querySelector(
                '[data-role="song-line-timing-line"][data-timing-line-index="' + String(index) + '"]',
            );

            item.classList.toggle('song-line-timing-current', index === currentLineIndex);
            item.classList.toggle('song-line-timing-done', hasStart);

            if (modalLine !== null) {
                modalLine.classList.toggle('song-line-timing-line-current', index === currentLineIndex);
                modalLine.classList.toggle('song-line-timing-line-done', hasStart);
            }
        });

        tapButton.disabled = visibleItems.length === 0;
        undoButton.disabled = history.length === 0;

        if (status !== null) {
            status.textContent = createSongLineTimingStatus(visibleItems, currentLineIndex, audio);
        }

        if (shouldScrollCurrentLine) {
            scrollSongLineTimingLineIntoView(linesContainer, currentLineIndex);
        }
    }

    function syncSongLineTimingRows(visibleItems, linesContainer) {
        while (linesContainer.children.length > visibleItems.length) {
            linesContainer.lastElementChild.remove();
        }

        while (linesContainer.children.length < visibleItems.length) {
            linesContainer.appendChild(createSongLineTimingRowElement());
        }

        visibleItems.forEach(function (item, index) {
            const row = linesContainer.children[index];
            const number = row.querySelector('[data-role="song-line-timing-line-number"]');
            const text = row.querySelector('[data-role="song-line-timing-line-text"]');
            const range = row.querySelector('[data-role="song-line-timing-line-range"]');

            row.dataset.timingLineIndex = String(index);

            if (number !== null) {
                number.textContent = String(index + 1);
            }

            if (text !== null) {
                text.textContent = findSongLineTimingText(item);
            }

            if (range !== null) {
                range.textContent = createSongLineTimingRangeText(item);
            }
        });

        linesContainer.classList.toggle('song-line-timing-lines-empty', visibleItems.length === 0);
    }

    function createSongLineTimingRowElement() {
        const row = document.createElement('button');
        const cursor = document.createElement('span');
        const number = document.createElement('span');
        const body = document.createElement('span');
        const text = document.createElement('span');
        const range = document.createElement('span');

        row.type = 'button';
        row.className = 'song-line-timing-line';
        row.dataset.role = 'song-line-timing-line';

        cursor.className = 'song-line-timing-cursor';
        cursor.setAttribute('aria-hidden', 'true');
        cursor.textContent = '';

        number.className = 'song-line-timing-line-number';
        number.dataset.role = 'song-line-timing-line-number';

        body.className = 'song-line-timing-line-body';

        text.className = 'song-line-timing-line-text';
        text.dataset.role = 'song-line-timing-line-text';

        range.className = 'song-line-timing-line-range';
        range.dataset.role = 'song-line-timing-line-range';

        body.appendChild(text);
        body.appendChild(range);
        row.appendChild(cursor);
        row.appendChild(number);
        row.appendChild(body);

        return row;
    }

    function findSongLineTimingText(item) {
        const originalTextInput = item.querySelector('[data-role="song-line-original-text"]');
        const originalText = originalTextInput === null ? '' : originalTextInput.value.trim();
        const transliteratedText = (item.dataset.transliteratedText || '').trim();

        if (transliteratedText !== '') {
            return transliteratedText;
        }

        if (originalText !== '') {
            return originalText;
        }

        return 'Пустая строка';
    }

    function createSongLineTimingRangeText(item) {
        const startInput = findSongLineTimingStartInput(item);
        const endInput = findSongLineTimingEndInput(item);
        const startValue = startInput === null ? '' : startInput.value.trim();
        const endValue = endInput === null ? '' : endInput.value.trim();

        if (startValue === '' && endValue === '') {
            return 'ожидает старта';
        }

        if (startValue !== '' && endValue === '') {
            return formatSongLineTimingMs(Number(startValue)) + ' - ...';
        }

        if (startValue === '') {
            return '... - ' + formatSongLineTimingMs(Number(endValue));
        }

        return formatSongLineTimingMs(Number(startValue)) + ' - ' + formatSongLineTimingMs(Number(endValue));
    }

    function scrollSongLineTimingLineIntoView(linesContainer, currentLineIndex) {
        const lineIndex = Math.min(currentLineIndex, Math.max(0, linesContainer.children.length - 1));
        const line = linesContainer.querySelector(
            '[data-role="song-line-timing-line"][data-timing-line-index="' + String(lineIndex) + '"]',
        );

        if (line === null) {
            return;
        }

        line.scrollIntoView({
            block: 'center',
            behavior: 'smooth',
        });
    }

    function createSongLineTimingStatus(visibleItems, currentLineIndex, audio) {
        if (visibleItems.length === 0) {
            return 'Нет строк';
        }

        if (currentLineIndex >= visibleItems.length) {
            return 'Разметка завершена · ' + formatSongLineTimingMs(Math.round(audio.currentTime * 1000));
        }

        return 'Строка ' + (currentLineIndex + 1) + ' / ' + visibleItems.length
            + ' · ' + formatSongLineTimingMs(Math.round(audio.currentTime * 1000));
    }

    function updateSongLineTimingPlayButton(playButton, audio) {
        if (audio.paused) {
            playButton.innerHTML = '<i class="bi bi-play-fill me-1" aria-hidden="true"></i>Запустить';

            return;
        }

        playButton.innerHTML = '<i class="bi bi-pause-fill me-1" aria-hidden="true"></i>Пауза';
    }

    function updateSongLineTimingAudioSource(audio, sourceSelect) {
        if (sourceSelect.value === '') {
            return;
        }

        audio.pause();
        audio.src = sourceSelect.value;
        audio.load();
    }

    function toggleSongLineTimingPlayback(audio, tapButton) {
        if (audio.paused) {
            const playPromise = audio.play();

            if (playPromise !== undefined) {
                playPromise.catch(function () {});
            }

            tapButton.focus({ preventScroll: true });

            return;
        }

        audio.pause();
    }

    function tapSongLineStart(songLineItems, currentLineIndex, audio, autoEndInput, history) {
        const visibleItems = getVisibleSongLineItems(songLineItems);

        if (visibleItems.length === 0) {
            return currentLineIndex;
        }

        if (currentLineIndex >= visibleItems.length) {
            return currentLineIndex;
        }

        const normalizedLineIndex = Math.min(currentLineIndex, visibleItems.length - 1);
        const currentItem = visibleItems[normalizedLineIndex];
        const startInput = findSongLineTimingStartInput(currentItem);

        if (startInput === null) {
            return normalizedLineIndex;
        }

        const previousItem = visibleItems[normalizedLineIndex - 1] || null;
        const previousEndInput = autoEndInput !== null && autoEndInput.checked && previousItem !== null
            ? findSongLineTimingEndInput(previousItem)
            : null;
        const timingValue = String(Math.max(0, Math.round(audio.currentTime * 1000)));

        history.push({
            lineIndex: normalizedLineIndex,
            startInput: startInput,
            previousStartValue: startInput.value,
            previousEndInput: previousEndInput,
            previousEndValue: previousEndInput === null ? null : previousEndInput.value,
        });

        setSongLineTimingInputValue(startInput, timingValue);

        if (previousEndInput !== null) {
            setSongLineTimingInputValue(previousEndInput, timingValue);
        }

        return normalizedLineIndex + 1;
    }

    function shiftSongLineTiming(songLineItems, shiftMilliseconds, currentLineIndex, history) {
        const values = [];

        getVisibleSongLineItems(songLineItems).forEach(function (item) {
            [
                findSongLineTimingStartInput(item),
                findSongLineTimingEndInput(item),
            ].forEach(function (input) {
                if (input === null || input.value.trim() === '') {
                    return;
                }

                const previousValue = input.value;
                const previousMilliseconds = Number(previousValue);

                if (Number.isFinite(previousMilliseconds) === false) {
                    return;
                }

                const nextValue = String(Math.max(0, Math.round(previousMilliseconds + shiftMilliseconds)));

                if (nextValue === previousValue) {
                    return;
                }

                values.push({
                    input: input,
                    previousValue: previousValue,
                });
                setSongLineTimingInputValue(input, nextValue);
            });
        });

        if (values.length === 0) {
            return;
        }

        history.push({
            lineIndex: currentLineIndex,
            values: values,
        });
    }

    function findSongLineTimingShiftMilliseconds(shiftInput) {
        if (shiftInput === null) {
            return 0;
        }

        const value = Number(shiftInput.value || '0');

        if (Number.isFinite(value) === false || value <= 0) {
            return 0;
        }

        return Math.round(value);
    }

    function undoSongLineTiming(history, currentLineIndex) {
        const action = history.pop();

        if (action === undefined) {
            return currentLineIndex;
        }

        if (Array.isArray(action.values)) {
            action.values.forEach(function (value) {
                setSongLineTimingInputValue(value.input, value.previousValue);
            });

            return action.lineIndex;
        }

        setSongLineTimingInputValue(action.startInput, action.previousStartValue);

        if (action.previousEndInput !== null) {
            setSongLineTimingInputValue(action.previousEndInput, action.previousEndValue);
        }

        return action.lineIndex;
    }

    function setSongLineTimingInputValue(input, value) {
        input.value = value === null ? '' : value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function isSongLineTimingInteractiveTarget(target) {
        if (target === null || typeof target.closest !== 'function') {
            return false;
        }

        return target.closest('input, textarea, select, button, a, label') !== null;
    }

    function isSongLineTimingInput(target) {
        if (target === null || typeof target.matches !== 'function') {
            return false;
        }

        return target.matches([
            '[data-role="song-line-original-text"]',
            '[data-role="song-line-start-ms"]',
            '[data-role="song-line-end-ms"]',
        ].join(', '));
    }

    function formatSongLineTimingMs(milliseconds) {
        const safeMilliseconds = Math.max(0, milliseconds);
        const minutes = Math.floor(safeMilliseconds / 60000);
        const seconds = Math.floor((safeMilliseconds % 60000) / 1000);
        const ms = safeMilliseconds % 1000;

        return String(minutes).padStart(2, '0')
            + ':' + String(seconds).padStart(2, '0')
            + '.' + String(ms).padStart(3, '0');
    }

    function clearSongArrangementItem(item) {
        item.querySelectorAll('input, textarea, select').forEach(function (input) {
            if (input.type === 'hidden' && input.name.indexOf('[id]') !== -1) {
                return;
            }

            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
                return;
            }

            input.value = '';
        });
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

    function removeSongArrangementItem(root, arrangementItem) {
        if (window.confirm('Удалить эту аранжировку?') === false) {
            return;
        }

        clearSongArrangementItem(arrangementItem);
        arrangementItem.classList.add('d-none');
        updateSongArrangementPresentation(root);
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

    function getVisibleSongArrangementItems(songArrangementItems) {
        return Array.from(songArrangementItems.querySelectorAll('[data-role="song-arrangement-item"]')).filter(function (item) {
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

    function updateSongArrangementPresentation(root) {
        const songArrangementItems = root.querySelector('[data-role="song-arrangement-items"]');
        const emptyState = root.querySelector('[data-role="song-arrangement-empty-state"]');

        if (songArrangementItems === null) {
            return;
        }

        const visibleItems = getVisibleSongArrangementItems(songArrangementItems);

        visibleItems.forEach(function (item, index) {
            const title = item.querySelector('[data-role="song-arrangement-title"]');

            if (title !== null) {
                title.textContent = 'Аранжировка ' + (index + 1);
            }
        });

        if (emptyState !== null) {
            emptyState.classList.toggle('d-none', visibleItems.length > 0);
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
