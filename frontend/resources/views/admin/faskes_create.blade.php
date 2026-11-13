@extends('layouts.admin')

@section('title', 'Tambah Rumah Sakit Baru - Admin MediGO')
@section('page_title', 'Tambah Rumah Sakit Baru')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Header Section --}}
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1" style="font-size: 1.6rem; letter-spacing: -0.02em;">Tambah Rumah Sakit Baru</h3>
        <p class="text-secondary small mb-0">Lengkapi informasi di bawah ini untuk mendaftarkan fasilitas kesehatan ke dalam sistem Medigo.</p>
    </div>

    {{-- Segmented Tab Navigation --}}
    <div class="d-inline-flex p-1 bg-white border rounded-3 mb-4 shadow-2xs custom-tabs-nav" style="border-color: #e2e8f0 !important;">
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold active" data-tab="tab-info">
            Informasi Rumah Sakit
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold" data-tab="tab-fasilitas">
            Fasilitas
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold" data-tab="tab-spesialisasi">
            Spesialisasi
        </button>
    </div>

    {{-- Unified Form --}}
    <form id="formCreateFaskes" action="{{ route('admin.faskes.store') }}" method="POST">
        @csrf
        {{-- Hidden Defaults --}}
        <input type="hidden" name="tipe" value="Rumah Sakit">
        <input type="hidden" name="jenis_faskes" value="Rumah Sakit">
        <input type="hidden" name="provinsi" value="DKI Jakarta" id="inputProvinsi">
        <input type="hidden" name="is_support_bpjs" id="inputSupportBpjs" value="1">
        <div id="hiddenInputsContainer"></div>

        {{-- =====================================================================
             TAB 1: INFORMASI RUMAH SAKIT
             ===================================================================== --}}
        <div class="tab-pane-content" id="tab-info">
            {{-- Card 1: Informasi Dasar --}}
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-4" style="font-size: 1.15rem;">Informasi Dasar</h5>

                {{-- Nama Rumah Sakit --}}
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark mb-1">
                        Nama Rumah Sakit <span class="text-danger">*</span>
                    </label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                            <i class="bi bi-hospital fs-5"></i>
                        </span>
                        <input type="text" name="nama_faskes" id="inputNamaFaskes" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: RSUD Dr. Soetomo" required>
                    </div>
                </div>

                {{-- Row: Kategori/Tipe RS & Status BPJS --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Kategori / Tipe RS <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-buildings fs-5"></i>
                            </span>
                            <select name="kelas" id="selectKelas" class="form-select border-start-0 py-2.5 rounded-end-3" required>
                                <option value="" disabled selected>Pilih Kelas</option>
                                <option value="A">Kelas A</option>
                                <option value="B">Kelas B</option>
                                <option value="C">Kelas C</option>
                                <option value="D">Kelas D</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Mendukung BPJS?
                        </label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold active" data-val="1">
                                Menerima BPJS
                            </button>
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold" data-val="0">
                                Tidak Menerima BPJS
                            </button>
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold" data-val="">
                                Tidak diketahui
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Nomor Telepon --}}
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1">
                        Nomor Telepon
                    </label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                            <i class="bi bi-telephone fs-5"></i>
                        </span>
                        <input type="text" name="telepon" id="inputTelepon" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="(031) XXXXXXX">
                    </div>
                </div>
            </div>

            {{-- Card 2: Lokasi & Alamat --}}
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2" style="font-size: 1.15rem;">
                    <i class="bi bi-geo-alt text-medigo-primary"></i>
                    <span>Lokasi & Alamat</span>
                </h5>

                {{-- Alamat Lengkap --}}
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark mb-1">
                        Alamat Lengkap <span class="text-danger">*</span>
                    </label>
                    <textarea name="alamat" id="inputAlamat" class="form-control py-2.5 rounded-3" rows="3" placeholder="Masukkan nama jalan, nomor, RT/RW..." required></textarea>
                </div>

                {{-- Row: Kota/Kabupaten & Link Google Maps --}}
                <div class="row g-4 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Kota / Kabupaten <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-buildings fs-5"></i>
                            </span>
                            <input type="text" name="kota_kabupaten" id="inputKota" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: Surabaya" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Link Google Maps
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-link-45deg fs-5"></i>
                            </span>
                            <input type="url" name="link_maps" id="inputLinkMaps" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: https://maps.app.goo.gl/...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =====================================================================
             TAB 2: FASILITAS
             ===================================================================== --}}
        <div class="tab-pane-content d-none" id="tab-fasilitas">
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-4" style="font-size: 1.15rem;">Fasilitas</h5>

                <div class="row g-4">
                    {{-- Left Column: Search & Available Facilities --}}
                    <div class="col-lg-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Fasilitas Rumah Sakit</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="searchFasilitasInput" class="form-control py-2.5 rounded-3 ps-3 pe-5" placeholder="Contoh: Ambulan, IGD 24 Jam, Laboratorium...">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="fw-semibold text-muted small">Hasil Pencarian</span>
                            <span class="badge bg-light text-secondary border" id="availableFasilitasCountBadge">0 tersedia</span>
                        </div>

                        <div class="border rounded-3 p-2 bg-light available-list-scrollable" id="availableFasilitasList" style="max-height: 420px; overflow-y: auto;">
                            {{-- Rendered dynamically via JS --}}
                        </div>
                    </div>

                    {{-- Right Column: Selected Facilities Drop Zone --}}
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">Fasilitas Rumah Sakit</label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" id="selectedFasilitasCountBadge">0 dipilih</span>
                        </div>
                        <div class="text-muted small mb-3" style="font-size: 0.78rem;">Klik "+ Tambah" atau drag & drop fasilitas ke area di bawah:</div>

                        <div class="border rounded-3 p-3 bg-white dropzone-container position-relative" id="selectedFasilitasContainer" style="min-height: 460px; max-height: 460px; overflow-y: auto; border: 2px dashed #cbd5e1 !important;">
                            <div id="selectedFasilitasPills" class="d-flex flex-wrap gap-2"></div>
                            
                            <div id="emptyFasilitasNotice" class="text-center py-5 text-muted small">
                                <i class="bi bi-hospital fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <span>Belum ada fasilitas dipilih.<br>Cari fasilitas di sebelah kiri lalu klik <strong>+ Tambah</strong> atau seret ke area ini.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =====================================================================
             TAB 3: SPESIALISASI
             ===================================================================== --}}
        <div class="tab-pane-content d-none" id="tab-spesialisasi">
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-4" style="font-size: 1.15rem;">Spesialisasi</h5>

                <div class="row g-4">
                    {{-- Left Column: Search & Available Specialties --}}
                    <div class="col-lg-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Spesialisasi Rumah Sakit</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="searchSpesialisasiInput" class="form-control py-2.5 rounded-3 ps-3 pe-5" placeholder="Contoh: Dokter Spesialis Jantung">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="fw-semibold text-muted small">Hasil Pencarian</span>
                            <span class="badge bg-light text-secondary border" id="availableSpesialisasiCountBadge">0 tersedia</span>
                        </div>

                        <div class="border rounded-3 p-2 bg-light available-list-scrollable" id="availableSpesialisasiList" style="max-height: 420px; overflow-y: auto;">
                            {{-- Rendered dynamically via JS --}}
                        </div>
                    </div>

                    {{-- Right Column: Selected Specialties Grid Drop Zone --}}
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">Spesialisasi Rumah Sakit</label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" id="selectedSpesialisasiCountBadge">0 dipilih</span>
                        </div>
                        <div class="text-muted small mb-3" style="font-size: 0.78rem;">Daftar spesialisasi yang dimasukkan ke dalam profil rumah sakit:</div>

                        <div class="border rounded-3 p-3 bg-white dropzone-container position-relative" id="selectedSpesialisasiContainer" style="min-height: 460px; max-height: 460px; overflow-y: auto; border: 2px dashed #cbd5e1 !important;">
                            <div id="selectedSpesialisasiGrid" class="selected-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px;"></div>
                            
                            <div id="emptySpesialisasiNotice" class="text-center py-5 text-muted small">
                                <i class="bi bi-person-badge fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <span>Belum ada spesialisasi dipilih.<br>Cari spesialisasi di sebelah kiri lalu klik <strong>+ Tambah</strong> atau seret ke area ini.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =====================================================================
             BOTTOM ACTION BUTTONS (PERSISTENT ACROSS TABS)
             ===================================================================== --}}
        <div class="d-flex justify-content-between align-items-center gap-3 pt-2 pb-5">
            <a href="{{ route('admin.faskes') }}" class="btn btn-outline-dark rounded-3 px-5 py-2.5 fw-semibold" style="min-width: 200px; border-color: #0b2b52; color: #0b2b52; background: #fff;">
                Batal
            </a>
            <button type="button" id="btnSubmitForm" class="btn text-white rounded-3 px-5 py-2.5 fw-semibold shadow-xs" style="min-width: 200px; background-color: #0b2b52; border-color: #0b2b52;">
                Simpan Data
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* MediGO Color Constants */
    :root {
        --medigo-navy: #0b2b52;
        --medigo-navy-hover: #071e3b;
    }

    /* Tab Segmented Navigation */
    .custom-tabs-nav .tab-btn {
        background: transparent;
        color: #475569;
        font-size: 0.92rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .custom-tabs-nav .tab-btn:hover {
        color: var(--medigo-navy);
    }
    .custom-tabs-nav .tab-btn.active {
        background: var(--medigo-navy);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(11, 43, 82, 0.2);
    }

    /* Input Groups */
    .custom-input-group .form-control:focus,
    .custom-input-group .form-select:focus,
    textarea:focus {
        border-color: var(--medigo-navy) !important;
        box-shadow: 0 0 0 3px rgba(11, 43, 82, 0.12) !important;
    }

    /* BPJS Toggle Buttons */
    .btn-bpjs-toggle {
        background: #ffffff;
        color: #1e293b;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.88rem;
        transition: all 0.2s ease;
    }
    .btn-bpjs-toggle:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .btn-bpjs-toggle.active {
        background: var(--medigo-navy) !important;
        color: #ffffff !important;
        border-color: var(--medigo-navy) !important;
        box-shadow: 0 2px 4px rgba(11, 43, 82, 0.2);
    }

    /* Available Items List */
    .available-item-row {
        background: #ffffff;
        transition: background-color 0.15s ease, transform 0.15s ease;
        user-select: none;
        cursor: grab;
    }
    .available-item-row:active {
        cursor: grabbing;
    }
    .available-item-row:hover {
        background: #f1f5f9;
    }
    .available-item-row .btn-add-item {
        color: #0b5ed7;
        font-size: 0.82rem;
        transition: color 0.15s ease;
    }
    .available-item-row .btn-add-item:hover {
        color: #002b5e;
        text-decoration: underline;
    }

    /* Selected Item Badges / Cards */
    .selected-pill-badge {
        background-color: var(--medigo-navy);
        color: #ffffff;
        font-size: 0.85rem;
        border-radius: 9999px;
        padding: 0.45rem 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 3px rgba(11, 43, 82, 0.15);
        cursor: grab;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }
    .selected-pill-badge:hover {
        background-color: var(--medigo-navy-hover);
    }
    .selected-pill-badge .btn-remove-pill {
        color: #ffffff;
        opacity: 0.85;
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
    }
    .selected-pill-badge .btn-remove-pill:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    /* Specialty Selected Card (3 Column Grid) */
    .selected-spec-card {
        background-color: var(--medigo-navy);
        color: #ffffff;
        border-radius: 8px;
        padding: 0.55rem 0.75rem;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 0.45rem;
        box-shadow: 0 1px 3px rgba(11, 43, 82, 0.15);
        cursor: grab;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }
    .selected-spec-card:hover {
        background-color: var(--medigo-navy-hover);
    }
    .selected-spec-card .btn-remove-spec {
        color: #ffffff;
        opacity: 0.85;
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        flex-shrink: 0;
    }
    .selected-spec-card .btn-remove-spec:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    /* Drop Zone Dragover */
    .dropzone-container.drag-over {
        border-color: var(--medigo-navy) !important;
        background-color: #f0f7ff !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------------
    // 1. MASTER DATA INITIALIZATION
    // -------------------------------------------------------------------------
    const rawFasilitas = @json($allFasilitas ?? []);
    const rawSpesialisasi = @json($allSpesialisasi ?? []);

    // Standardize master data to clean unique name strings
    const masterFasilitas = Array.from(new Set(
        rawFasilitas.map(f => (typeof f === 'object' ? f.nama_fasilitas : f).trim()).filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'id'));

    const masterSpesialisasi = Array.from(new Set(
        rawSpesialisasi.map(s => (typeof s === 'object' ? s.nama_spesialisasi : s).trim()).filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'id'));

    // Selected state sets
    const selectedFasilitas = new Set();
    const selectedSpesialisasi = new Set();

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
    const form = document.getElementById('formCreateFaskes');
    const btnSubmitForm = document.getElementById('btnSubmitForm');
    const inputNamaFaskes = document.getElementById('inputNamaFaskes');
    const selectKelas = document.getElementById('selectKelas');
    const inputAlamat = document.getElementById('inputAlamat');
    const inputKota = document.getElementById('inputKota');

    btnSubmitForm.addEventListener('click', function () {
        // Validate required fields on Tab 1
        if (!inputNamaFaskes.value.trim() || !selectKelas.value || !inputAlamat.value.trim() || !inputKota.value.trim()) {
            // Automatically switch back to Tab 1 if user is on Tab 2 or Tab 3
            switchTab('tab-info');
            form.reportValidity();
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

    // Initial Renders
    renderFasilitas();
    renderSpesialisasi();
});
</script>
@endpush

