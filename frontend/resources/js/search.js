/**
 * MediGO - Search & Advanced Multi-Filter Module
 * Handles Keyword Search, GPS Distance, Filter by Kota, Kelas, BPJS, Multi-Select Fasilitas & Multi-Select Spesialisasi.
 */

document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('searchResultsGrid');
    if (!grid) return;

    const form = document.getElementById('searchFaskesForm');
    const keywordInput = document.getElementById('searchKeywordInput');
    const loadingState = document.getElementById('searchLoadingState');
    const emptyState = document.getElementById('searchEmptyState');
    const countBadge = document.getElementById('searchCountBadge');
    const locationIndicatorText = document.getElementById('searchLocationText');
    const activeTagsContainer = document.getElementById('activeFilterTagsContainer');
    const searchCard = document.querySelector('.search-hero-card');

    const config = window.MedigoSearchConfig || {};
    const initial = config.initialQuery || {};

    // Helper: Parse comma-separated string to Array
    function parseList(val) {
        if (!val) return [];
        if (Array.isArray(val)) return val;
        return val.toString().split(',').map(s => s.trim()).filter(Boolean);
    }

    const state = {
        q: initial.q || '',
        kota: initial.kota || '',
        kelas: initial.kelas || '',
        bpjs: initial.bpjs || '',
        fasilitas: parseList(initial.fasilitas),
        spesialisasi: parseList(initial.spesialisasi),
        lat: null,
        lng: null,
        page: 1,
        limit: 24,
    };

    // 0. Expand / Collapse Search & Filter Section Toggle
    const btnToggleSearchExpand = document.getElementById('btnToggleSearchExpand');
    const btnBottomExpandToggle = document.getElementById('btnBottomExpandToggle');
    const searchExpandableSection = document.getElementById('searchExpandableSection');
    const toggleSearchText = document.getElementById('toggleSearchText');
    const toggleSearchChevron = document.getElementById('toggleSearchChevron');
    const bottomExpandText = document.getElementById('bottomExpandText');
    const bottomExpandChevron = document.getElementById('bottomExpandChevron');
    const toggleFilterCountBadge = document.getElementById('toggleFilterCountBadge');

    function updateActiveFilterBadge() {
        const filterCount = (state.kota ? 1 : 0) + 
                            (state.kelas ? 1 : 0) + 
                            (state.bpjs !== '' ? 1 : 0) + 
                            state.fasilitas.length + 
                            state.spesialisasi.length;

        if (toggleFilterCountBadge) {
            if (filterCount > 0) {
                toggleFilterCountBadge.textContent = filterCount;
                toggleFilterCountBadge.classList.remove('d-none');
            } else {
                toggleFilterCountBadge.classList.add('d-none');
            }
        }
    }

    function setSearchExpandedState(expanded, save = true) {
        if (!searchExpandableSection) return;
        
        if (expanded) {
            searchExpandableSection.classList.add('is-expanded');
            btnToggleSearchExpand?.classList.add('is-active');
            btnToggleSearchExpand?.setAttribute('aria-expanded', 'true');
            if (toggleSearchText) toggleSearchText.textContent = 'Tutup';
            if (bottomExpandText) bottomExpandText.textContent = 'Ciutkan Kolom Pencarian';
            if (bottomExpandChevron) bottomExpandChevron.className = 'bi bi-chevron-up ms-1';
        } else {
            searchExpandableSection.classList.remove('is-expanded');
            btnToggleSearchExpand?.classList.remove('is-active');
            btnToggleSearchExpand?.setAttribute('aria-expanded', 'false');
            if (toggleSearchText) toggleSearchText.textContent = 'Filter';
            if (bottomExpandText) bottomExpandText.textContent = 'Buka Filter & Opsi Lengkap';
            if (bottomExpandChevron) bottomExpandChevron.className = 'bi bi-chevron-down ms-1';
        }

        if (save) {
            try {
                sessionStorage.setItem('medigo_search_panel_expanded', expanded ? '1' : '0');
            } catch (_) {}
        }
    }

    function toggleSearchExpanded() {
        const isCurrentlyExpanded = searchExpandableSection?.classList.contains('is-expanded');
        setSearchExpandedState(!isCurrentlyExpanded);
    }

    btnToggleSearchExpand?.addEventListener('click', toggleSearchExpanded);
    btnBottomExpandToggle?.addEventListener('click', toggleSearchExpanded);

    // Initial state determination:
    const hasInitialSearch = !!(initial.q || initial.kota || initial.kelas || initial.bpjs || initial.fasilitas || initial.spesialisasi);
    const savedState = sessionStorage.getItem('medigo_search_panel_expanded');
    let defaultExpanded = false;

    if (savedState !== null) {
        defaultExpanded = savedState === '1';
    } else {
        if (hasInitialSearch) {
            defaultExpanded = false; // Collapse to give maximum screen space for results
        } else {
            // No initial search: expanded on desktop, collapsed on mobile
            defaultExpanded = window.innerWidth >= 992;
        }
    }
    setSearchExpandedState(defaultExpanded, false);

    // 1. Geolocation Setup
    function initLocation() {
        try {
            const sLat = sessionStorage.getItem("medigo_user_lat");
            const sLng = sessionStorage.getItem("medigo_user_lng");
            if (sLat && sLng) {
                state.lat = parseFloat(sLat);
                state.lng = parseFloat(sLng);
                locationIndicatorText.textContent = "GPS Aktif (Terkunci)";
            }
        } catch (_) {}

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    state.lat = pos.coords.latitude;
                    state.lng = pos.coords.longitude;
                    locationIndicatorText.textContent = "GPS Terhubung";
                    try {
                        sessionStorage.setItem("medigo_user_lat", state.lat);
                        sessionStorage.setItem("medigo_user_lng", state.lng);
                    } catch (_) {}
                    // Refresh data with updated GPS coordinates
                    performSearch();
                },
                (err) => {
                    console.warn("Izin lokasi tidak diberikan:", err.message);
                    if (!state.lat) {
                        locationIndicatorText.textContent = "GPS Dinonaktifkan";
                    }
                },
                { enableHighAccuracy: false, timeout: 8000 }
            );
        } else {
            locationIndicatorText.textContent = "Lokasi Tidak Didukung";
        }
    }

    // 2. Perform AJAX Search
    let searchDebounceTimer = null;
    let currentAbortController = null;

    async function performSearch() {
        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();
        const signal = currentAbortController.signal;

        grid.innerHTML = '';
        loadingState.classList.remove('d-none');
        emptyState.classList.add('d-none');
        countBadge.textContent = 'Mencari...';

        updateFilterButtonLabels();
        renderActiveFilterTags();
        syncModalsUI();

        const params = new URLSearchParams();
        if (state.q) params.set('q', state.q);
        if (state.kota) params.set('kota', state.kota);
        if (state.kelas) params.set('kelas', state.kelas);
        if (state.bpjs !== '') params.set('bpjs', state.bpjs);
        if (state.fasilitas.length > 0) params.set('fasilitas', state.fasilitas.join(','));
        if (state.spesialisasi.length > 0) params.set('spesialisasi', state.spesialisasi.join(','));
        if (state.lat && state.lng) {
            params.set('lat', state.lat);
            params.set('lng', state.lng);
        }
        params.set('page', state.page);
        params.set('limit', state.limit);

        // Update URL query params without page reload for bookmarkability
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);

        try {
            const endpoint = `${config.dataUrl}?${params.toString()}`;
            const res = await fetch(endpoint, {
                signal,
                headers: { 'Accept': 'application/json' }
            });
            const result = await res.json();

            loadingState.classList.add('d-none');

            if (res.ok && result.status === 'success') {
                const items = result.data || [];
                const total = result.total || items.length;

                countBadge.textContent = `Ditemukan ${total} Fasilitas Kesehatan`;

                if (items.length === 0) {
                    emptyState.classList.remove('d-none');
                } else {
                    renderFaskesCards(items);
                }
            } else {
                console.error("API response error:", result);
                countBadge.textContent = 'Gagal memuat';
                emptyState.classList.remove('d-none');
            }
        } catch (e) {
            if (e.name === 'AbortError') {
                return; // Request dibatalkan karena ada filter/pencarian baru
            }
            console.error('Search error:', e);
            loadingState.classList.add('d-none');
            emptyState.classList.remove('d-none');
            countBadge.textContent = 'Koneksi error';
        }
    }

    // 3. Render Cards in Grid
    function renderFaskesCards(items) {
        grid.innerHTML = '';
        items.forEach(faskes => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-lg-4';
            col.innerHTML = createCardHTML(faskes);
            grid.appendChild(col);
        });
    }

    function createCardHTML(faskes) {
        const id = faskes.id_faskes || 0;
        const name = (faskes.nama_faskes || 'Fasilitas Kesehatan').trim();
        const address = (faskes.alamat_faskes || faskes.kota_kabupaten || 'Alamat tidak tersedia').trim();
        const typeSlug = faskes.type_slug || 'hospital';
        const typeDisplay = faskes.jenis_faskes || 'Rumah Sakit';
        
        let typeIcon = 'bi-hospital';
        if (typeSlug === 'clinic') typeIcon = 'bi-heart-pulse-fill';
        if (typeSlug === 'puskesmas') typeIcon = 'bi-plus-circle-fill';

        const bpjsStatus = faskes.is_support_bpjs ? 'BPJS' : 'Non - BPJS';
        const bpjsClass = faskes.is_support_bpjs ? 'badge-bpjs' : 'badge-non-bpjs';
        const classBadge = faskes.kelas ? `<span class="badge-custom badge-kelas">Kelas ${faskes.kelas}</span>` : '';
        const distanceStr = faskes.distance_str && faskes.distance_str !== '-' ? faskes.distance_str : (faskes.distance_km ? `${parseFloat(faskes.distance_km).toFixed(1)} km` : '-');

        const mapUrl = faskes.maps_url || '#';
        const detailUrl = faskes.detail_url || `/faskes/${id}`;

        return `
            <div class="facility-item-card card h-100 shadow-xs border p-3" id="card-faskes-${id}">
                <div class="d-flex gap-3 align-items-start mb-2">
                    <div class="facility-thumb-wrap flex-shrink-0 faskes-avatar-map faskes-avatar-${typeSlug} rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi ${typeIcon} text-white fs-4"></i>
                    </div>
                    <div class="facility-info-wrap flex-grow-1 min-w-0">
                        <h6 class="facility-name fw-bold mb-1 text-dark text-truncate" title="${name}">
                            <a href="${detailUrl}" class="text-dark text-decoration-none hover-primary">${name}</a>
                        </h6>
                        <p class="facility-address text-muted mb-2 text-truncate" style="font-size: 0.8rem;" title="${address}">
                            <i class="bi bi-geo-alt me-1"></i>${address}
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-1 mb-3 flex-wrap mt-auto">
                    <span class="badge-custom badge-type-${typeSlug}" style="background-color: #f1f5f9; color: #475569; font-size: 0.72rem;">${typeDisplay}</span>
                    ${classBadge}
                    <span class="badge-custom ${bpjsClass}" style="font-size: 0.72rem;">${bpjsStatus}</span>
                    <span class="facility-distance ms-auto text-dark fw-bold" style="font-size: 0.8rem;">
                        <i class="bi bi-arrow-down-up me-1 text-medigo-primary"></i>${distanceStr}
                    </span>
                </div>

                <div class="d-flex gap-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-navigasi-pill flex-grow-1 text-white text-decoration-none d-flex align-items-center justify-content-center gap-1 py-1" style="font-size: 0.82rem;">
                        <i class="bi bi-sign-turn-right"></i> Navigasi
                    </a>
                    <a href="${detailUrl}" class="btn btn-sm btn-detail-pill flex-grow-1 text-decoration-none d-flex align-items-center justify-content-center gap-1 py-1" style="font-size: 0.82rem;">
                        <i class="bi bi-info-circle"></i> Detail
                    </a>
                </div>
            </div>
        `;
    }

    // 4. Update Filter Button Labels
    function updateFilterButtonLabels() {
        const btnKota = document.getElementById('btnFilterKota');
        const lblKota = document.getElementById('labelFilterKota');
        if (state.kota) {
            lblKota.textContent = `Kota: ${state.kota}`;
            btnKota.classList.add('is-active');
        } else {
            lblKota.textContent = 'Kota / Wilayah';
            btnKota.classList.remove('is-active');
        }

        const btnKelas = document.getElementById('btnFilterKelas');
        const lblKelas = document.getElementById('labelFilterKelas');
        if (state.kelas) {
            lblKelas.textContent = `Kelas ${state.kelas}`;
            btnKelas.classList.add('is-active');
        } else {
            lblKelas.textContent = 'Kelas RS';
            btnKelas.classList.remove('is-active');
        }

        const btnBpjs = document.getElementById('btnFilterBpjs');
        const lblBpjs = document.getElementById('labelFilterBpjs');
        if (state.bpjs === '1' || state.bpjs === 1) {
            lblBpjs.textContent = 'BPJS Kesehatan';
            btnBpjs.classList.add('is-active');
        } else if (state.bpjs === '0' || state.bpjs === 0) {
            lblBpjs.textContent = 'Non - BPJS';
            btnBpjs.classList.add('is-active');
        } else {
            lblBpjs.textContent = 'Jaminan / Asuransi';
            btnBpjs.classList.remove('is-active');
        }

        // Fasilitas Label (Multi-select)
        const btnFas = document.getElementById('btnOpenFasilitasModal');
        const lblFas = document.getElementById('labelFilterFasilitas');
        const countFas = state.fasilitas.length;
        if (countFas === 1) {
            lblFas.textContent = `Fasilitas: ${state.fasilitas[0]}`;
            btnFas.classList.add('is-active');
        } else if (countFas > 1) {
            lblFas.textContent = `Fasilitas (${countFas})`;
            btnFas.classList.add('is-active');
        } else {
            lblFas.textContent = 'Fasilitas Penunjang';
            btnFas.classList.remove('is-active');
        }

        // Spesialisasi Label (Multi-select)
        const btnSpes = document.getElementById('btnOpenSpesialisasiModal');
        const lblSpes = document.getElementById('labelFilterSpesialisasi');
        const countSpes = state.spesialisasi.length;
        if (countSpes === 1) {
            lblSpes.textContent = `Spesialis: ${state.spesialisasi[0]}`;
            btnSpes.classList.add('is-active');
        } else if (countSpes > 1) {
            lblSpes.textContent = `Spesialis (${countSpes})`;
            btnSpes.classList.add('is-active');
        } else {
            lblSpes.textContent = 'Spesialisasi Medis';
            btnSpes.classList.remove('is-active');
        }

        updateActiveFilterBadge();
    }

    // 5. Render Active Filter Tags
    function renderActiveFilterTags() {
        activeTagsContainer.innerHTML = '';

        const tags = [];
        if (state.q) tags.push({ key: 'q', label: `Keyword: "${state.q}"` });
        if (state.kota) tags.push({ key: 'kota', label: `Kota: ${state.kota}` });
        if (state.kelas) tags.push({ key: 'kelas', label: `Kelas: ${state.kelas}` });
        if (state.bpjs === '1' || state.bpjs === 1) tags.push({ key: 'bpjs', label: 'BPJS' });
        if (state.bpjs === '0' || state.bpjs === 0) tags.push({ key: 'bpjs', label: 'Non-BPJS' });

        // Multi-select tags
        state.fasilitas.forEach(f => {
            tags.push({ key: 'fasilitas', val: f, label: `Fasilitas: ${f}` });
        });
        state.spesialisasi.forEach(s => {
            tags.push({ key: 'spesialisasi', val: s, label: `Spesialis: ${s}` });
        });

        tags.forEach(t => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-medigo-subtle text-medigo-primary border d-inline-flex align-items-center gap-1 rounded-pill px-2 py-1';
            badge.style.fontSize = '0.75rem';
            badge.innerHTML = `
                <span>${t.label}</span>
                <i class="bi bi-x-circle-fill cursor-pointer text-muted hover-danger" style="font-size: 0.82rem;" title="Hapus filter"></i>
            `;

            badge.querySelector('i').addEventListener('click', (e) => {
                e.stopPropagation();
                if (t.key === 'q') {
                    state.q = '';
                    keywordInput.value = '';
                } else if (t.key === 'kota') {
                    state.kota = '';
                } else if (t.key === 'kelas') {
                    state.kelas = '';
                } else if (t.key === 'bpjs') {
                    state.bpjs = '';
                } else if (t.key === 'fasilitas') {
                    state.fasilitas = state.fasilitas.filter(item => item !== t.val);
                } else if (t.key === 'spesialisasi') {
                    state.spesialisasi = state.spesialisasi.filter(item => item !== t.val);
                }
                performSearch();
            });

            activeTagsContainer.appendChild(badge);
        });
    }

    // 6. Search Input & Form Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        state.q = keywordInput.value.trim();
        performSearch();
    });

    keywordInput.addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            state.q = this.value.trim();
            performSearch();
        }, 350);
    });

    // 7. Custom Dropdown Filters (Daerah / Kota, Kelas RS, Jaminan / BPJS)
    const dropdownWrappers = document.querySelectorAll('.filter-dropdown-wrapper');

    function closeAllFilterDropdowns() {
        dropdownWrappers.forEach(wrap => {
            wrap.classList.remove('is-open');
            const menu = wrap.querySelector('.custom-filter-menu');
            if (menu) menu.classList.remove('is-open');
        });
    }

    dropdownWrappers.forEach(wrap => {
        const btn = wrap.querySelector('.filter-select-btn');
        const menu = wrap.querySelector('.custom-filter-menu');
        if (!btn || !menu) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = wrap.classList.contains('is-open');
            closeAllFilterDropdowns();
            if (!isOpen) {
                wrap.classList.add('is-open');
                menu.classList.add('is-open');
            }
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.filter-dropdown-wrapper')) {
            closeAllFilterDropdowns();
        }
    });

    function syncDropdownsUI() {
        // Kota
        document.querySelectorAll('.opt-filter-kota').forEach(item => {
            const val = item.getAttribute('data-value') || '';
            if (val === state.kota) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Kelas
        document.querySelectorAll('.opt-filter-kelas').forEach(item => {
            const val = item.getAttribute('data-value') || '';
            if (val === state.kelas) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // BPJS
        document.querySelectorAll('.opt-filter-bpjs').forEach(item => {
            const val = item.getAttribute('data-value') || '';
            const bpjsStr = state.bpjs !== '' && state.bpjs !== null ? state.bpjs.toString() : '';
            if (val === bpjsStr) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    document.querySelectorAll('.opt-filter-kota').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.kota = this.getAttribute('data-value') || '';
            closeAllFilterDropdowns();
            syncDropdownsUI();
            performSearch();
        });
    });

    document.querySelectorAll('.opt-filter-kelas').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.kelas = this.getAttribute('data-value') || '';
            closeAllFilterDropdowns();
            syncDropdownsUI();
            performSearch();
        });
    });

    document.querySelectorAll('.opt-filter-bpjs').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.bpjs = this.getAttribute('data-value') || '';
            closeAllFilterDropdowns();
            syncDropdownsUI();
            performSearch();
        });
    });

    // 8. Popular Search Tags
    document.querySelectorAll('.popular-search-tag').forEach(btn => {
        btn.addEventListener('click', function () {
            const tag = this.getAttribute('data-tag');
            if (tag === 'IGD') {
                if (!state.fasilitas.includes('IGD')) state.fasilitas.push('IGD');
            } else if (tag === 'Kelas A') {
                state.kelas = 'A';
            } else if (tag === 'Ambulans') {
                if (!state.fasilitas.includes('Ambulans')) state.fasilitas.push('Ambulans');
            } else if (tag === 'Penyakit Dalam') {
                if (!state.spesialisasi.includes('Penyakit Dalam')) state.spesialisasi.push('Penyakit Dalam');
            }
            syncDropdownsUI();
            performSearch();
        });
    });

    // 9. Reset All Filters
    function resetAllFilters() {
        closeAllFilterDropdowns();
        state.q = '';
        state.kota = '';
        state.kelas = '';
        state.bpjs = '';
        state.fasilitas = [];
        state.spesialisasi = [];
        keywordInput.value = '';

        syncDropdownsUI();
        syncModalsUI();
        performSearch();
    }

    document.getElementById('btnResetFilters')?.addEventListener('click', resetAllFilters);
    document.querySelectorAll('.btn-reset-from-empty').forEach(b => b.addEventListener('click', resetAllFilters));

    // 10. Multi-Select Modal: Fasilitas Penunjang (762 data)
    const modalFasilitasSearchInput = document.getElementById('modalFasilitasSearchInput');
    const modalFasilitasList = document.querySelectorAll('.modal-fasilitas-item');

    modalFasilitasSearchInput?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;
        modalFasilitasList.forEach(item => {
            const name = item.getAttribute('data-name');
            if (!query || name.includes(query)) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });
        document.getElementById('countFasilitasListed').textContent = visibleCount;
    });

    // Toggle Facility Selection (Multi-select)
    function toggleFasilitas(name) {
        if (!name) return;
        const idx = state.fasilitas.indexOf(name);
        if (idx > -1) {
            state.fasilitas.splice(idx, 1);
        } else {
            state.fasilitas.push(name);
        }
        syncFasilitasModalUI();
    }

    document.querySelectorAll('.btn-choose-fasilitas').forEach(btn => {
        btn.addEventListener('click', function () {
            const val = this.getAttribute('data-value');
            toggleFasilitas(val);
        });
    });

    document.querySelectorAll('.btn-quick-select-fasilitas').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const name = this.getAttribute('data-name');
            toggleFasilitas(name);
        });
    });

    function syncFasilitasModalUI() {
        const count = state.fasilitas.length;
        const textSummary = document.getElementById('countFasilitasSelectedText');
        const btnApply = document.getElementById('btnApplyFasilitasModal');
        
        if (textSummary) {
            textSummary.textContent = count > 0 ? `${count} fasilitas dipilih` : '';
        }
        if (btnApply) {
            btnApply.textContent = count > 0 ? `Terapkan Filter (${count})` : 'Terapkan Filter';
        }

        // Sync list buttons
        document.querySelectorAll('.btn-choose-fasilitas').forEach(b => {
            const val = b.getAttribute('data-value');
            const isSelected = state.fasilitas.includes(val);
            if (isSelected) {
                b.classList.add('selected');
                b.querySelector('.check-icon')?.classList.remove('d-none');
            } else {
                b.classList.remove('selected');
                b.querySelector('.check-icon')?.classList.add('d-none');
            }
        });

        // Sync quick buttons
        document.querySelectorAll('.btn-quick-select-fasilitas').forEach(b => {
            const val = b.getAttribute('data-name');
            const isSelected = state.fasilitas.includes(val);
            if (isSelected) {
                b.classList.add('btn-medigo-primary', 'text-white');
                b.classList.remove('btn-outline-secondary');
            } else {
                b.classList.remove('btn-medigo-primary', 'text-white');
                b.classList.add('btn-outline-secondary');
            }
        });
    }

    document.getElementById('btnClearFasilitasModal')?.addEventListener('click', function () {
        state.fasilitas = [];
        syncFasilitasModalUI();
        performSearch();
    });

    document.getElementById('btnApplyFasilitasModal')?.addEventListener('click', function () {
        performSearch();
    });

    // 11. Multi-Select Modal: Spesialisasi Medis (152 data)
    const modalSpesialisasiSearchInput = document.getElementById('modalSpesialisasiSearchInput');
    const modalSpesialisasiList = document.querySelectorAll('.modal-spesialisasi-item');

    modalSpesialisasiSearchInput?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;
        modalSpesialisasiList.forEach(item => {
            const name = item.getAttribute('data-name');
            if (!query || name.includes(query)) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });
        document.getElementById('countSpesialisasiListed').textContent = visibleCount;
    });

    // Toggle Specialty Selection (Multi-select)
    function toggleSpesialisasi(name) {
        if (!name) return;
        const idx = state.spesialisasi.indexOf(name);
        if (idx > -1) {
            state.spesialisasi.splice(idx, 1);
        } else {
            state.spesialisasi.push(name);
        }
        syncSpesialisasiModalUI();
    }

    document.querySelectorAll('.btn-choose-spesialisasi').forEach(btn => {
        btn.addEventListener('click', function () {
            const val = this.getAttribute('data-value');
            toggleSpesialisasi(val);
        });
    });

    document.querySelectorAll('.btn-quick-select-spesialisasi').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const name = this.getAttribute('data-name');
            toggleSpesialisasi(name);
        });
    });

    function syncSpesialisasiModalUI() {
        const count = state.spesialisasi.length;
        const textSummary = document.getElementById('countSpesialisasiSelectedText');
        const btnApply = document.getElementById('btnApplySpesialisasiModal');
        
        if (textSummary) {
            textSummary.textContent = count > 0 ? `${count} spesialis dipilih` : '';
        }
        if (btnApply) {
            btnApply.textContent = count > 0 ? `Terapkan Filter (${count})` : 'Terapkan Filter';
        }

        // Sync list buttons
        document.querySelectorAll('.btn-choose-spesialisasi').forEach(b => {
            const val = b.getAttribute('data-value');
            const isSelected = state.spesialisasi.includes(val);
            if (isSelected) {
                b.classList.add('selected');
                b.querySelector('.check-icon')?.classList.remove('d-none');
            } else {
                b.classList.remove('selected');
                b.querySelector('.check-icon')?.classList.add('d-none');
            }
        });

        // Sync quick buttons
        document.querySelectorAll('.btn-quick-select-spesialisasi').forEach(b => {
            const val = b.getAttribute('data-name');
            const isSelected = state.spesialisasi.includes(val);
            if (isSelected) {
                b.classList.add('btn-medigo-primary', 'text-white');
                b.classList.remove('btn-outline-secondary');
            } else {
                b.classList.remove('btn-medigo-primary', 'text-white');
                b.classList.add('btn-outline-secondary');
            }
        });
    }

    document.getElementById('btnClearSpesialisasiModal')?.addEventListener('click', function () {
        state.spesialisasi = [];
        syncSpesialisasiModalUI();
        performSearch();
    });

    document.getElementById('btnApplySpesialisasiModal')?.addEventListener('click', function () {
        performSearch();
    });

    // Helper to sync all filters UI
    function syncModalsUI() {
        syncDropdownsUI();
        syncFasilitasModalUI();
        syncSpesialisasiModalUI();
    }

    // When modals are shown, sync UI
    document.getElementById('modalFasilitas')?.addEventListener('show.bs.modal', syncFasilitasModalUI);
    document.getElementById('modalSpesialisasi')?.addEventListener('show.bs.modal', syncSpesialisasiModalUI);

    // Initial Execution
    initLocation();
    performSearch();
});
