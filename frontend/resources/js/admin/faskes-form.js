/**
 * Form tambah & ubah faskes di panel admin: tab, BPJS, fasilitas/spesialisasi (drag & drop), foto.
 * Data master dan data awal dibaca dari atribut data-* pada form #formCreateFaskes.
 */
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------------
    // 1. MASTER DATA INITIALIZATION
    // -------------------------------------------------------------------------
    const form = document.getElementById('formCreateFaskes');
    if (!form) return;

    const readJson = (key) => {
        try {
            return JSON.parse(form.dataset[key] || '[]');
        } catch (e) {
            return [];
        }
    };
    const rawFasilitas = readJson('fasilitas');
    const rawSpesialisasi = readJson('spesialisasi');
    // Data yang sudah dimiliki faskes (mode ubah)
    const initialFasilitas = readJson('selectedFasilitas');
    const initialSpesialisasi = readJson('selectedSpesialisasi');
    const toName = (item, key) => String(typeof item === 'object' && item !== null ? item[key] || '' : item || '').trim();

    // Standardize master data to clean unique name strings
    const masterFasilitas = Array.from(new Set(
        rawFasilitas.concat(initialFasilitas).map(f => toName(f, 'nama_fasilitas')).filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'id'));

    const masterSpesialisasi = Array.from(new Set(
        rawSpesialisasi.concat(initialSpesialisasi).map(s => toName(s, 'nama_spesialisasi')).filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'id'));

    // Selected state sets
    const selectedFasilitas = new Set(initialFasilitas.map(f => toName(f, 'nama_fasilitas')).filter(Boolean));
    const selectedSpesialisasi = new Set(initialSpesialisasi.map(s => toName(s, 'nama_spesialisasi')).filter(Boolean));

    // -------------------------------------------------------------------------
    // 2. TAB NAVIGATION
    // -------------------------------------------------------------------------
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane-content');

    function switchTab(targetTabId) {
        tabButtons.forEach(btn => {
            if (btn.getAttribute('data-tab') === targetTabId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        tabPanes.forEach(pane => {
            if (pane.id === targetTabId) {
                pane.classList.remove('d-none');
            } else {
                pane.classList.add('d-none');
            }
        });
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');
            switchTab(target);
        });
    });

    // -------------------------------------------------------------------------
    // 2b. JENIS FASKES: kelas hanya wajib untuk Rumah Sakit
    // -------------------------------------------------------------------------
    const selectJenis = document.getElementById('selectJenisFaskes');
    const kelasWrapper = document.getElementById('kelasWrapper');
    function syncKelasRequirement() {
        const kelasSelect = document.getElementById('selectKelas');
        if (!selectJenis || !kelasSelect) return;
        const isRs = selectJenis.value === 'Rumah Sakit';
        kelasSelect.required = isRs;
        if (kelasWrapper) kelasWrapper.classList.toggle('d-none', !isRs);
        if (!isRs) kelasSelect.value = '';
    }
    if (selectJenis) {
        selectJenis.addEventListener('change', syncKelasRequirement);
        syncKelasRequirement();
    }

    // -------------------------------------------------------------------------
    // 3. BPJS TOGGLE BUTTONS
    // -------------------------------------------------------------------------
    const bpjsButtons = document.querySelectorAll('.btn-bpjs-toggle');
    const inputBpjs = document.getElementById('inputSupportBpjs');

    bpjsButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            bpjsButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            inputBpjs.value = this.getAttribute('data-val');
        });
    });

    // -------------------------------------------------------------------------
    // 4. FASILITAS (FACILITIES) MANAGEMENT & DRAG-AND-DROP
    // -------------------------------------------------------------------------
    const searchFasilitasInput = document.getElementById('searchFasilitasInput');
    const availableFasilitasList = document.getElementById('availableFasilitasList');
    const selectedFasilitasPills = document.getElementById('selectedFasilitasPills');
    const emptyFasilitasNotice = document.getElementById('emptyFasilitasNotice');
    const availableFasilitasCountBadge = document.getElementById('availableFasilitasCountBadge');
    const selectedFasilitasCountBadge = document.getElementById('selectedFasilitasCountBadge');
    const dropzoneFasilitas = document.getElementById('selectedFasilitasContainer');

    function renderFasilitas() {
        const query = (searchFasilitasInput.value || '').trim().toLowerCase();
        
        // Filter available items: not in selected, and matches search query
        const available = masterFasilitas.filter(item => {
            return !selectedFasilitas.has(item) && item.toLowerCase().includes(query);
        });

        availableFasilitasCountBadge.textContent = `${available.length} tersedia`;
        availableFasilitasList.innerHTML = '';

        if (available.length === 0) {
            availableFasilitasList.innerHTML = `
                <div class="text-center py-4 text-muted small">
                    Tidak ada fasilitas yang cocok dengan "${escapeHtml(query)}"
                </div>
            `;
        } else {
            available.forEach(name => {
                const row = document.createElement('div');
                row.className = 'available-item-row d-flex justify-content-between align-items-center py-2 px-3 border-bottom rounded-2 mb-1';
                row.setAttribute('draggable', 'true');
                row.setAttribute('data-name', name);
                row.innerHTML = `
                    <div class="d-flex align-items-center gap-2 text-dark small">
                        <i class="bi bi-grip-vertical text-muted"></i>
                        <span>${escapeHtml(name)}</span>
                    </div>
                    <button type="button" class="btn-add-item bg-transparent border-0 fw-semibold p-0">
                        + Tambah
                    </button>
                `;

                // Click "+ Tambah"
                row.querySelector('.btn-add-item').addEventListener('click', () => {
                    addFasilitas(name);
                });

                // Drag Start
                row.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'fasilitas', name: name }));
                    e.dataTransfer.effectAllowed = 'copy';
                });

                availableFasilitasList.appendChild(row);
            });
        }

        // Render Selected Pills
        selectedFasilitasPills.innerHTML = '';
        const selectedArr = Array.from(selectedFasilitas);
        selectedFasilitasCountBadge.textContent = `${selectedArr.length} dipilih`;

        if (selectedArr.length === 0) {
            emptyFasilitasNotice.classList.remove('d-none');
        } else {
            emptyFasilitasNotice.classList.add('d-none');
            selectedArr.forEach(name => {
                const pill = document.createElement('div');
                pill.className = 'selected-pill-badge';
                pill.innerHTML = `
                    <button type="button" class="btn-remove-pill" title="Hapus">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                    <span>${escapeHtml(name)}</span>
                `;

                pill.querySelector('.btn-remove-pill').addEventListener('click', () => {
                    removeFasilitas(name);
                });

                selectedFasilitasPills.appendChild(pill);
            });
        }

        syncHiddenInputs();
    }

    function addFasilitas(name) {
        if (!selectedFasilitas.has(name)) {
            selectedFasilitas.add(name);
            renderFasilitas();
        }
    }

    function removeFasilitas(name) {
        if (selectedFasilitas.has(name)) {
            selectedFasilitas.delete(name);
            renderFasilitas();
        }
    }

    searchFasilitasInput.addEventListener('input', renderFasilitas);

    // Dropzone Events for Facilities
    dropzoneFasilitas.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzoneFasilitas.classList.add('drag-over');
    });
    dropzoneFasilitas.addEventListener('dragleave', () => {
        dropzoneFasilitas.classList.remove('drag-over');
    });
    dropzoneFasilitas.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzoneFasilitas.classList.remove('drag-over');
        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            if (data && data.type === 'fasilitas' && data.name) {
                addFasilitas(data.name);
            }
        } catch (err) {}
    });

    // -------------------------------------------------------------------------
    // 5. SPESIALISASI (SPECIALTIES) MANAGEMENT & DRAG-AND-DROP
    // -------------------------------------------------------------------------
    const searchSpesialisasiInput = document.getElementById('searchSpesialisasiInput');
    const availableSpesialisasiList = document.getElementById('availableSpesialisasiList');
    const selectedSpesialisasiGrid = document.getElementById('selectedSpesialisasiGrid');
    const emptySpesialisasiNotice = document.getElementById('emptySpesialisasiNotice');
    const availableSpesialisasiCountBadge = document.getElementById('availableSpesialisasiCountBadge');
    const selectedSpesialisasiCountBadge = document.getElementById('selectedSpesialisasiCountBadge');
    const dropzoneSpesialisasi = document.getElementById('selectedSpesialisasiContainer');

    function renderSpesialisasi() {
        const query = (searchSpesialisasiInput.value || '').trim().toLowerCase();
        
        // Filter available items: not in selected, and matches search query
        const available = masterSpesialisasi.filter(item => {
            return !selectedSpesialisasi.has(item) && item.toLowerCase().includes(query);
        });

        availableSpesialisasiCountBadge.textContent = `${available.length} tersedia`;
        availableSpesialisasiList.innerHTML = '';

        if (available.length === 0) {
            availableSpesialisasiList.innerHTML = `
                <div class="text-center py-4 text-muted small">
                    Tidak ada spesialisasi yang cocok dengan "${escapeHtml(query)}"
                </div>
            `;
        } else {
            available.forEach(name => {
                const row = document.createElement('div');
                row.className = 'available-item-row d-flex justify-content-between align-items-center py-2 px-3 border-bottom rounded-2 mb-1';
                row.setAttribute('draggable', 'true');
                row.setAttribute('data-name', name);
                row.innerHTML = `
                    <div class="d-flex align-items-center gap-2 text-dark small text-truncate">
                        <i class="bi bi-grip-vertical text-muted"></i>
                        <span class="text-truncate">${escapeHtml(name)}</span>
                    </div>
                    <button type="button" class="btn-add-item bg-transparent border-0 fw-semibold p-0 flex-shrink-0 ms-2">
                        + Tambah
                    </button>
                `;

                // Click "+ Tambah"
                row.querySelector('.btn-add-item').addEventListener('click', () => {
                    addSpesialisasi(name);
                });

                // Drag Start
                row.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'spesialisasi', name: name }));
                    e.dataTransfer.effectAllowed = 'copy';
                });

                availableSpesialisasiList.appendChild(row);
            });
        }

        // Render Selected Grid (3 columns on desktop matching Mockup 4)
        selectedSpesialisasiGrid.innerHTML = '';
        const selectedArr = Array.from(selectedSpesialisasi);
        selectedSpesialisasiCountBadge.textContent = `${selectedArr.length} dipilih`;

        if (selectedArr.length === 0) {
            emptySpesialisasiNotice.classList.remove('d-none');
        } else {
            emptySpesialisasiNotice.classList.add('d-none');
            selectedArr.forEach(name => {
                const card = document.createElement('div');
                card.className = 'selected-spec-card';
                card.innerHTML = `
                    <button type="button" class="btn-remove-spec" title="Hapus">
                        <i class="bi bi-x-circle-fill fs-6"></i>
                    </button>
                    <span class="text-truncate" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                `;

                card.querySelector('.btn-remove-spec').addEventListener('click', () => {
                    removeSpesialisasi(name);
                });

                selectedSpesialisasiGrid.appendChild(card);
            });
        }

        syncHiddenInputs();
    }

    function addSpesialisasi(name) {
        if (!selectedSpesialisasi.has(name)) {
            selectedSpesialisasi.add(name);
            renderSpesialisasi();
        }
    }

    function removeSpesialisasi(name) {
        if (selectedSpesialisasi.has(name)) {
            selectedSpesialisasi.delete(name);
            renderSpesialisasi();
        }
    }

    searchSpesialisasiInput.addEventListener('input', renderSpesialisasi);

    // Dropzone Events for Specialties
    dropzoneSpesialisasi.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzoneSpesialisasi.classList.add('drag-over');
    });
    dropzoneSpesialisasi.addEventListener('dragleave', () => {
        dropzoneSpesialisasi.classList.remove('drag-over');
    });
    dropzoneSpesialisasi.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzoneSpesialisasi.classList.remove('drag-over');
        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            if (data && data.type === 'spesialisasi' && data.name) {
                addSpesialisasi(data.name);
            }
        } catch (err) {}
    });

    // -------------------------------------------------------------------------
    // 6. SYNC HIDDEN INPUTS FOR FORM SUBMIT
    // -------------------------------------------------------------------------
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');

    function syncHiddenInputs() {
        hiddenInputsContainer.innerHTML = '';

        selectedFasilitas.forEach(f => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'fasilitas[]';
            input.value = f;
            hiddenInputsContainer.appendChild(input);
        });

        selectedSpesialisasi.forEach(s => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'spesialisasi[]';
            input.value = s;
            hiddenInputsContainer.appendChild(input);
        });
    }

    // -------------------------------------------------------------------------
    // 7. SUBMIT BUTTON & CLIENT VALIDATION
    // -------------------------------------------------------------------------
    const btnSubmitForm = document.getElementById('btnSubmitForm');

    btnSubmitForm.addEventListener('click', function () {
        // Validate required fields on Tab 1
        if (!form.checkValidity()) {
            // Automatically switch back to Tab 1 if user is on Tab 2 or Tab 3
            switchTab('tab-info');
            form.reportValidity();
            return;
        }

        // Foto lebih dari 5MB akan ditolak server, cegah sebelum dikirim
        if (document.querySelector('.mg-dropzone-preview.is-invalid')) {
            switchTab('tab-foto');
            if (typeof window.showGlobalToast === 'function') {
                window.showGlobalToast('Ada foto yang ukurannya lebih dari 5MB. Ganti foto tersebut terlebih dahulu.', 'error');
            }
            return;
        }

        // Show loading state on button
        btnSubmitForm.disabled = true;
        btnSubmitForm.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span>Menyimpan...</span>
        `;

        form.submit();
    });

    // Helper: HTML escape
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // -------------------------------------------------------------------------
    // 8. FOTO: drag & drop + pratinjau sebelum diunggah
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
        const input = zone.querySelector('.mg-dropzone-input');
        const previews = document.querySelector('[data-previews="' + zone.dataset.dropzone + '"]');
        const maxBytes = 5 * 1024 * 1024;

        function renderPreviews() {
            previews.innerHTML = '';
            Array.from(input.files || []).forEach(function (file) {
                const item = document.createElement('div');
                item.className = 'mg-dropzone-preview' + (file.size > maxBytes ? ' is-invalid' : '');
                const img = document.createElement('img');
                img.alt = file.name;
                img.src = URL.createObjectURL(file);
                const cap = document.createElement('span');
                cap.textContent = file.size > maxBytes ? 'Lebih dari 5MB' : file.name;
                item.appendChild(img);
                item.appendChild(cap);
                previews.appendChild(item);
            });
        }

        input.addEventListener('change', renderPreviews);
        ['dragenter', 'dragover'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('is-dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('is-dragover'); });
        });
        zone.addEventListener('drop', function (e) {
            if (!e.dataTransfer || !e.dataTransfer.files.length) return;
            const dt = new DataTransfer();
            const files = input.multiple ? Array.from(e.dataTransfer.files) : [e.dataTransfer.files[0]];
            files.filter(function (f) { return f.type.startsWith('image/'); }).forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
            renderPreviews();
        });
    });

    // Initial Renders
    renderFasilitas();
    renderSpesialisasi();
});
