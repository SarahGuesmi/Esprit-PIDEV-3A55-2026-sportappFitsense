/**
 * ═══════════════════════════════════════════════════════
 *  PIDEVASSETS IMAGE PICKER — JavaScript Module
 *  Handles modal lifecycle, image loading, search,
 *  selection, upload, and form integration.
 * ═══════════════════════════════════════════════════════
 */
(function () {
    'use strict';

    const API_LIST   = '/coach/pidevassets/list';
    const API_UPLOAD = '/coach/pidevassets/upload';

    // ── State ──
    let currentImages   = [];
    let selectedImage   = null;  // { name, url }
    let activeCallback  = null;  // fn(name, url) called when user confirms selection
    let activeTriggerId = null;

    // ── DOM Refs (created once) ──
    let backdrop, modal, searchInput, grid, gridWrap,
        refreshBtn, uploadBtn, uploadInput,
        footer, footerImg, footerName, selectBtn, closeBtn,
        loadingEl;

    let isInitialized = false;

    /**
     * Build the modal DOM once and append to <body>.
     */
    function initModal() {
        if (isInitialized) return;
        isInitialized = true;

        backdrop = document.createElement('div');
        backdrop.className = 'pidev-picker-backdrop';
        backdrop.id = 'pidevPickerBackdrop';

        backdrop.innerHTML = `
            <div class="pidev-picker-modal">
                <!-- Header -->
                <div class="pidev-picker-header">
                    <h2><i class="fas fa-images"></i> Choose an Image</h2>
                    <button type="button" class="pidev-picker-close" id="pidevPickerClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="pidev-picker-toolbar">
                    <div class="pidev-picker-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="pidevPickerSearch" placeholder="Filter images..." autocomplete="off">
                    </div>
                    <button type="button" class="pidev-picker-btn pidev-picker-btn-upload" id="pidevPickerUploadBtn">
                        <i class="fas fa-plus"></i> Upload New
                    </button>
                    <button type="button" class="pidev-picker-btn pidev-picker-btn-refresh" id="pidevPickerRefreshBtn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <input type="file" accept="image/*" class="pidev-upload-hidden" id="pidevPickerUploadInput">
                </div>

                <!-- Grid -->
                <div class="pidev-picker-grid-wrap" id="pidevPickerGridWrap">
                    <div class="pidev-picker-grid" id="pidevPickerGrid"></div>
                </div>

                <!-- Footer / Selection -->
                <div class="pidev-picker-footer" id="pidevPickerFooter">
                    <img id="pidevPickerFooterImg" src="" alt="">
                    <div class="pidev-picker-footer-info">
                        <div class="pidev-picker-footer-name" id="pidevPickerFooterName"></div>
                        <div class="pidev-picker-footer-hint">Click "Select" to confirm</div>
                    </div>
                    <button type="button" class="pidev-picker-btn-select" id="pidevPickerSelectBtn">
                        <i class="fas fa-check"></i> Select
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(backdrop);

        // Cache refs
        modal       = backdrop.querySelector('.pidev-picker-modal');
        searchInput = document.getElementById('pidevPickerSearch');
        grid        = document.getElementById('pidevPickerGrid');
        gridWrap    = document.getElementById('pidevPickerGridWrap');
        refreshBtn  = document.getElementById('pidevPickerRefreshBtn');
        uploadBtn   = document.getElementById('pidevPickerUploadBtn');
        uploadInput = document.getElementById('pidevPickerUploadInput');
        footer      = document.getElementById('pidevPickerFooter');
        footerImg   = document.getElementById('pidevPickerFooterImg');
        footerName  = document.getElementById('pidevPickerFooterName');
        selectBtn   = document.getElementById('pidevPickerSelectBtn');
        closeBtn    = document.getElementById('pidevPickerClose');

        // ── Events ──
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && backdrop.classList.contains('active')) closeModal();
        });

        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadImages(searchInput.value.trim()), 250);
        });

        refreshBtn.addEventListener('click', () => {
            refreshBtn.classList.add('spinning');
            loadImages(searchInput.value.trim()).finally(() => {
                setTimeout(() => refreshBtn.classList.remove('spinning'), 400);
            });
        });

        uploadBtn.addEventListener('click', () => uploadInput.click());
        uploadInput.addEventListener('change', handleUpload);

        selectBtn.addEventListener('click', confirmSelection);
    }

    /**
     * Open the picker modal.
     * @param {function} callback - fn(name, url) called on selection confirm
     * @param {string} triggerId - optional ID of the trigger element
     */
    function openModal(callback, triggerId) {
        initModal();
        activeCallback  = callback;
        activeTriggerId = triggerId || null;
        selectedImage   = null;
        footer.classList.remove('active');
        searchInput.value = '';
        grid.innerHTML = '';
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadImages('');
        setTimeout(() => searchInput.focus(), 200);
    }

    function closeModal() {
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
        activeCallback = null;
    }

    /**
     * Fetch images from API and render the grid.
     */
    async function loadImages(query) {
        showLoading();
        try {
            const url = API_LIST + (query ? '?q=' + encodeURIComponent(query) : '');
            const res = await fetch(url);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            currentImages = await res.json();
            renderGrid(currentImages);
        } catch (err) {
            grid.innerHTML = `
                <div class="pidev-picker-empty" style="grid-column: 1/-1;">
                    <i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i>
                    <p style="color:#ef4444;">Error loading images: ${err.message}</p>
                </div>`;
        }
    }

    function showLoading() {
        grid.innerHTML = `
            <div class="pidev-picker-loading" style="grid-column: 1/-1;">
                <div class="pidev-spinner"></div>
                <p>Loading images...</p>
            </div>`;
    }

    function renderGrid(images) {
        if (!images.length) {
            grid.innerHTML = `
                <div class="pidev-picker-empty" style="grid-column: 1/-1;">
                    <i class="fas fa-image"></i>
                    <p>No images found</p>
                </div>`;
            return;
        }

        grid.innerHTML = '';
        images.forEach(img => {
            const card = document.createElement('div');
            card.className = 'pidev-picker-card';
            if (selectedImage && selectedImage.name === img.name) {
                card.classList.add('selected');
            }
            card.innerHTML = `
                <img src="${img.url}" alt="${img.name}" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22130%22><rect width=%22200%22 height=%22130%22 fill=%22%23111%22/><text x=%2250%%22 y=%2250%%22 fill=%22%23555%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2212%22>Error</text></svg>'">
                <div class="pidev-picker-card-name" title="${img.name}">${img.name}</div>
            `;
            card.addEventListener('click', () => selectCard(card, img));
            grid.appendChild(card);
        });
    }

    function selectCard(card, img) {
        // Deselect all
        grid.querySelectorAll('.pidev-picker-card').forEach(c => c.classList.remove('selected'));
        // Select this one
        card.classList.add('selected');
        selectedImage = img;

        // Show footer preview
        footerImg.src = img.url;
        footerName.textContent = img.name;
        footer.classList.add('active');
    }

    function confirmSelection() {
        if (!selectedImage) return;
        if (activeCallback) {
            activeCallback(selectedImage.name, selectedImage.url);
        }
        closeModal();
    }

    /**
     * Handle upload of a new image file.
     */
    async function handleUpload() {
        const file = uploadInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        uploadBtn.disabled = true;

        try {
            const res = await fetch(API_UPLOAD, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                // Refresh the grid and auto-select the new image
                await loadImages(searchInput.value.trim());
                // Find and select the newly uploaded card
                const newCard = grid.querySelector(`.pidev-picker-card-name[title="${data.name}"]`);
                if (newCard) {
                    const card = newCard.parentElement;
                    selectCard(card, { name: data.name, url: data.url });
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                alert('Upload failed: ' + (data.error || 'Unknown error'));
            }
        } catch (err) {
            alert('Upload error: ' + err.message);
        } finally {
            uploadBtn.innerHTML = '<i class="fas fa-plus"></i> Upload New';
            uploadBtn.disabled = false;
            uploadInput.value = '';
        }
    }

    // ═══════════════════════════════════════════
    //  PUBLIC API  — window.PidevAssetsPicker
    // ═══════════════════════════════════════════

    window.PidevAssetsPicker = {
        /**
         * Open the image picker.
         * @param {function} onSelect - fn(filename, url) 
         * @param {string} [triggerId] - optional trigger element ID
         */
        open: function (onSelect, triggerId) {
            openModal(onSelect, triggerId);
        },

        /**
         * Attach to all .pidev-trigger-btn elements on the page.
         * Each trigger must have:
         *   data-pidev-target="<hiddenInputId>"
         *   data-pidev-preview="<previewContainerId>"  (optional)
         */
        autoBindTriggers: function () {
            document.querySelectorAll('.pidev-trigger-btn').forEach(btn => {
                if (btn.dataset.pidevBound) return;
                btn.dataset.pidevBound = '1';

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const targetId  = this.dataset.pidevTarget;
                    const previewId = this.dataset.pidevPreview;

                    openModal((name, url) => {
                        // Set hidden input value
                        if (targetId) {
                            const input = document.getElementById(targetId);
                            if (input) input.value = name;
                        }

                        // Update inline preview
                        if (previewId) {
                            const preview = document.getElementById(previewId);
                            if (preview) {
                                preview.classList.add('active');
                                const img = preview.querySelector('img');
                                if (img) img.src = url;
                                const nameEl = preview.querySelector('.pidev-selected-inline-name');
                                if (nameEl) nameEl.textContent = name;
                            }
                        }
                    }, this.id);
                });
            });

            // Bind clear buttons
            document.querySelectorAll('.pidev-selected-inline-clear').forEach(btn => {
                if (btn.dataset.pidevBound) return;
                btn.dataset.pidevBound = '1';

                btn.addEventListener('click', function () {
                    const wrap = this.closest('.pidev-selected-inline');
                    if (wrap) wrap.classList.remove('active');

                    const targetId = this.dataset.pidevTarget;
                    if (targetId) {
                        const input = document.getElementById(targetId);
                        if (input) input.value = '';
                    }
                });
            });
        }
    };

    // ── Auto-bind: works whether DOM is ready or not ──
    // When script loads at bottom of <body>, DOMContentLoaded has already
    // fired, so we check readyState first.
    function domReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            // DOM already parsed — run immediately
            fn();
        }
    }

    domReady(function () {
        PidevAssetsPicker.autoBindTriggers();
    });

})();
