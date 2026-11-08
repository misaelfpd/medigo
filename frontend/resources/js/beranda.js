/**
 * MediGO - Beranda Module
 * Supports independent toggle filters (Distance < 5km, Types: RS/Klinik/Puskesmas, BPJS),
 * GPS distance calculation & automatic fallback to highest rating when location is not permitted.
 */

document.addEventListener("DOMContentLoaded", function () {
    const gridElement = document.getElementById("homeHospitalsGrid");
    const loadingState = document.getElementById("homeCardsLoadingState");
    if (!gridElement) return;

    const rawBeUrl = window.MEDIGO_BE_URL || "http://127.0.0.1:8005";
    const backendApiUrl = rawBeUrl
        .replace("localhost", "127.0.0.1")
        .replace(/\/+$/, "");

    let hospitalsData = window.hospitalsInitialData || [];
    let currentHomeLoadedPage = 1;
    let hasMoreHomeItems = !!window.hasMoreInitial;
    let isLoadingMore = false;

    // Geolocation & Device State
    const userState = {
        lat: -6.2088, // Default Jakarta Pusat
        lng: 106.822,
        isDetected: false,
        permissionDenied: false,
    };

    // Independent Toggle Filter State
    const filterState = {
        distance5: false, // When true, limits to <= 5km.
        types: new Set(), // Set of selected types ('hospital', 'clinic', 'puskesmas')
        bpjsOnly: false, // When true, only shows BPJS supported facilities
    };

    // 1. Haversine Distance Formula (Km)
    function getDistanceKm(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return null;
        const R = 6371; // Earth radius in km
        const dLat = ((lat2 - lat1) * Math.PI) / 180;
        const dLon = ((lon2 - lon1) * Math.PI) / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos((lat1 * Math.PI) / 180) *
                Math.cos((lat2 * Math.PI) / 180) *
                Math.sin(dLon / 2) *
                Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return Math.round(R * c * 10) / 10;
    }

    // Helper to generate Google Maps Search URL combining Name and Coordinates
    function buildGoogleMapsUrl(name, lat, lng, city = "") {
        const query = (name + (city ? " " + city : "")).trim();
        const encoded = encodeURIComponent(query);
        if (lat && lng && lat !== "" && lng !== "") {
            return `https://www.google.com/maps/search/${encoded}/@${lat},${lng},17z`;
        }
        return `https://www.google.com/maps/search/?api=1&query=${encoded}`;
    }

    // 2. Render Single Hospital Card HTML
    function renderHospitalCardHtml(item) {
        const distStr =
            item.currentDistance !== null &&
            item.currentDistance !== undefined &&
            item.currentDistance !== 999
                ? `${item.currentDistance} km`
                : item.distance || "2.4 km";
        let name = (
            item.hospital_name ||
            item.name ||
            "Fasilitas Kesehatan"
        ).trim();
        const address = item.hospital_address || item.address || "";
        const rating = item.hospital_rating || item.rating || null;
        const hasValidRating =
            rating && parseFloat(rating) > 0 && rating !== "Belum dirating";
        const typeSlug = item.type_slug || "hospital";
        const detailUrl =
            item.detail_url && item.detail_url !== "#"
                ? item.detail_url
                : `/faskes/${item.id}`;

        if (
            (typeSlug === "puskesmas" ||
                (item.type && item.type.toLowerCase().includes("puskesmas"))) &&
            !/^(puskesmas|pkm)\b/i.test(name)
        ) {
            name = "Puskesmas " + name;
        }

        const navUrl =
            item.link_maps && item.link_maps.startsWith("http")
                ? item.link_maps
                : buildGoogleMapsUrl(
                      name,
                      item.lat,
                      item.lng,
                      item.kota_kabupaten || "",
                  );

        let typeIcon = "bi-hospital";
        if (typeSlug === "clinic") {
            typeIcon = "bi-heart-pulse-fill";
        } else if (typeSlug === "puskesmas") {
            typeIcon = "bi-plus-circle-fill";
        }

        return `
            <div class="col-12 col-md-4 hospital-card-wrapper" 
                 id="home-card-col-${item.id}" 
                 data-id="${item.id}"
                 data-name="${name}"
                 data-type="${typeSlug}"
                 data-class="${item.hospital_class || ""}"
                 data-rating="${hasValidRating ? rating : ""}"
                 data-bpjs="${item.bpjs_support ? "1" : "0"}"
                 data-lat="${item.lat || ""}" 
                 data-lng="${item.lng || ""}">
                <div class="card hospital-card h-100">
                    <div class="hospital-card-main">
                        <div class="hospital-card-img-wrapper faskes-avatar-banner faskes-avatar-${typeSlug}">
                            <i class="bi ${typeIcon} faskes-avatar-icon text-white"></i>
                            ${
                                item.is_open
                                    ? `
                                <span class="badge-status-open d-md-none">
                                    <span class="status-dot"></span> Buka
                                </span>
                            `
                                    : ""
                            }
                        </div>

                        <div class="card-body hospital-card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="card-title fw-bold mb-0 text-dark hospital-card-title">${name}</h6>
                                ${
                                    hasValidRating
                                        ? `
                                    <div class="rating-badge d-flex align-items-center flex-shrink-0 ms-1">
                                        <i class="bi bi-star-fill text-warning me-1" style="font-size: 0.72rem;"></i>
                                        <span class="fw-bold text-dark" style="font-size: 0.78rem;">${rating}</span>
                                    </div>
                                `
                                        : `
                                    <div class="rating-badge d-flex align-items-center flex-shrink-0 ms-1 text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-star me-1 text-muted opacity-50"></i>
                                        <span class="small text-muted" style="font-size: 0.7rem;">Belum dirating</span>
                                    </div>
                                `
                                }
                            </div>
                            <p class="card-text text-muted mb-2 hospital-card-address">${address}</p>

                            <div class="d-flex gap-1 mb-2 align-items-center flex-wrap hospital-card-badges">
                                ${
                                    item.type
                                        ? `
                                    <span class="badge-custom badge-type-${typeSlug}" style="background-color: #f1f5f9; color: #475569;">
                                        ${item.type}
                                    </span>
                                `
                                        : ""
                                }
                                ${
                                    item.hospital_class
                                        ? `
                                    <span class="badge-custom badge-kelas">Kelas ${item.hospital_class}</span>
                                `
                                        : ""
                                }
                                <span class="badge-custom ${item.bpjs_support ? "badge-bpjs" : "badge-non-bpjs"}">
                                    ${item.bpjs_support ? "BPJS" : "Non BPJS"}
                                </span>
                                <span class="distance-info ms-auto" id="home-distance-${item.id}">
                                    <i class="bi bi-sign-turn-right me-1" style="font-size: 0.8rem;"></i>
                                    <span class="distance-value">${distStr}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="hospital-card-actions mt-auto d-flex gap-2">
                        <a href="${navUrl}" id="home-nav-${item.id}" class="btn btn-navigasi flex-grow-1 text-white d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none" target="_blank">
                            <i class="bi bi-sign-turn-right"></i> Navigasi
                        </a>
                        <a href="${detailUrl}" class="btn btn-detail flex-grow-1 d-flex justify-content-center align-items-center gap-1 rounded-pill text-decoration-none">
                            <i class="bi bi-info-circle"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    // Update visibility of load more button container
    function updateLoadMoreButtonState() {
        const loadMoreContainer = document.getElementById(
            "homeLoadMoreContainer",
        );
        if (loadMoreContainer) {
            if (hasMoreHomeItems) {
                loadMoreContainer.classList.remove("d-none");
            } else {
                loadMoreContainer.classList.add("d-none");
            }
        }
    }

    // 3. Apply Toggle Filters & Sort Cards
    function updateDistancesAndFilter() {
        let visibleCount = 0;

        // Calculate distances
        hospitalsData.forEach((item) => {
            if (userState.isDetected && item.lat && item.lng) {
                const distance = getDistanceKm(
                    userState.lat,
                    userState.lng,
                    item.lat,
                    item.lng,
                );
                item.currentDistance = distance !== null ? distance : 999;
            } else {
                item.currentDistance = null;
            }

            // Update UI card distance display
            const distContainer = document.getElementById(
                `home-distance-${item.id}`,
            );
            if (distContainer) {
                const distSpan = distContainer.querySelector(".distance-value");
                if (distSpan) {
                    if (
                        userState.isDetected &&
                        item.currentDistance !== null &&
                        item.currentDistance !== 999
                    ) {
                        distSpan.textContent = `${item.currentDistance} km`;
                        distContainer.classList.remove("d-none");
                    } else {
                        distSpan.textContent = item.distance || "Rating ★";
                    }
                }
            }

            // Update Navigation Link with live origin or search location
            const navBtn = document.getElementById(`home-nav-${item.id}`);
            if (navBtn) {
                let cardName = (
                    item.hospital_name ||
                    item.name ||
                    "Fasilitas Kesehatan"
                ).trim();
                if (
                    (item.type_slug === "puskesmas" ||
                        (item.type &&
                            item.type.toLowerCase().includes("puskesmas"))) &&
                    !/^(puskesmas|pkm)\b/i.test(cardName)
                ) {
                    cardName = "Puskesmas " + cardName;
                }
                navBtn.href = buildGoogleMapsUrl(
                    cardName,
                    item.lat,
                    item.lng,
                    item.kota_kabupaten || "",
                );
            }

            // Filter Evaluation
            let matchDistance = true;
            if (filterState.distance5 && userState.isDetected) {
                matchDistance =
                    item.currentDistance !== null &&
                    item.currentDistance <= 5.0;
            }

            let matchType = true;
            if (filterState.types.size > 0) {
                matchType = filterState.types.has(item.type_slug);
            }

            let matchBpjs = true;
            if (filterState.bpjsOnly) {
                matchBpjs = item.bpjs_support === true;
            }

            const shouldDisplay = matchDistance && matchType && matchBpjs;

            const cardCol = document.getElementById(`home-card-col-${item.id}`);
            if (cardCol) {
                if (shouldDisplay) {
                    cardCol.classList.remove("d-none");
                    visibleCount++;
                } else {
                    cardCol.classList.add("d-none");
                }
            }
        });

        // Sort Cards in DOM
        const cardElements = Array.from(
            gridElement.querySelectorAll(".hospital-card-wrapper"),
        );
        cardElements.sort((a, b) => {
            const idA = parseInt(a.dataset.id);
            const idB = parseInt(b.dataset.id);
            const hospA = hospitalsData.find((h) => h.id === idA);
            const hospB = hospitalsData.find((h) => h.id === idB);

            if (userState.isDetected) {
                const distA =
                    hospA && hospA.currentDistance !== null
                        ? hospA.currentDistance
                        : 999;
                const distB =
                    hospB && hospB.currentDistance !== null
                        ? hospB.currentDistance
                        : 999;
                return distA - distB;
            } else {
                const ratingA = hospA
                    ? parseFloat(hospA.hospital_rating || hospA.rating || 0)
                    : 0;
                const ratingB = hospB
                    ? parseFloat(hospB.hospital_rating || hospB.rating || 0)
                    : 0;
                return ratingB - ratingA;
            }
        });

        cardElements.forEach((card) => gridElement.appendChild(card));

        // Hide Loading State & Show Grid
        if (loadingState) loadingState.classList.add("d-none");
        gridElement.classList.remove("d-none");

        // Update Total Counter
        const totalBadge = document.getElementById("homeTotalCountBadge");
        if (totalBadge) {
            totalBadge.textContent = `${visibleCount} Faskes`;
            totalBadge.classList.remove("d-none");
        }

        // Toggle Empty State
        const emptyState = document.getElementById("homeEmptyState");
        if (visibleCount === 0) {
            emptyState?.classList.remove("d-none");
        } else {
            emptyState?.classList.add("d-none");
        }

        updateLoadMoreButtonState();
    }

    // 4. Live Fetch Nearest Faskes around GPS Coordinates (Initial batch of 12)
    async function fetchHomeNearbyFaskes(lat, lng) {
        try {
            const response = await fetch(
                `${backendApiUrl}/api/faskes?lat=${lat}&lng=${lng}&page=1&limit=12`,
            );
            if (response.ok) {
                const json = await response.json();
                const items = json.data?.data || json.data || [];
                const total = json.data?.total || items.length;
                currentHomeLoadedPage = 1;
                hasMoreHomeItems = 1 * 12 < total;

                if (Array.isArray(items) && items.length > 0) {
                    hospitalsData = items.map((item) => {
                        const itemLat = parseFloat(item.latitude || item.lat);
                        const itemLng = parseFloat(item.longitude || item.lng);
                        // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                        const typeSlug =
                            item.type_slug ||
                            (() => {
                                const j = (
                                    item.jenis_faskes ||
                                    item.tipe ||
                                    ""
                                ).toLowerCase();
                                if (
                                    j.includes("rumah sakit") ||
                                    j.includes("rs")
                                )
                                    return "hospital";
                                if (j.includes("puskesmas")) return "puskesmas";
                                if (
                                    j.includes("klinik") ||
                                    j.includes("dokter") ||
                                    j.includes("apotek")
                                )
                                    return "clinic";
                                return "hospital";
                            })();
                        const rating =
                            item.avg_rating && parseFloat(item.avg_rating) > 0
                                ? parseFloat(item.avg_rating).toFixed(1)
                                : null;
                        const distance = getDistanceKm(
                            lat,
                            lng,
                            itemLat,
                            itemLng,
                        );

                        let fName = (
                            item.nama_faskes ||
                            item.name ||
                            "Fasilitas Kesehatan"
                        ).trim();
                        // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                        const jenisFaskes =
                            item.jenis_faskes || item.tipe || "";
                        if (
                            (typeSlug === "puskesmas" ||
                                (jenisFaskes &&
                                    jenisFaskes
                                        .toLowerCase()
                                        .includes("puskesmas"))) &&
                            !/^(puskesmas|pkm)\b/i.test(fName)
                        ) {
                            fName = "Puskesmas " + fName;
                        }

                        return {
                            id: item.id_faskes ?? item.id,
                            hospital_name: fName,
                            type: jenisFaskes || "Fasilitas Kesehatan",
                            type_slug: typeSlug,
                            // UPDATE: BE menggunakan alamat_faskes, bukan alamat
                            hospital_address:
                                (item.alamat_faskes || item.alamat || "") +
                                (item.kota_kabupaten
                                    ? ", " + item.kota_kabupaten
                                    : ""),
                            hospital_rating: rating,
                            hospital_class: item.kelas || null,
                            // UPDATE: BE menggunakan _support_bpjs, bukan is_support_bpjs
                            bpjs_support: !!(
                                item._support_bpjs ?? item.is_support_bpjs
                            ),
                            distance:
                                distance !== null ? `${distance} km` : "2.4 km",
                            currentDistance: distance,
                            is_open: true,
                            lat: itemLat,
                            lng: itemLng,
                            navigasi_url:
                                item.link_maps &&
                                item.link_maps !== "-" &&
                                item.link_maps.startsWith("http")
                                    ? item.link_maps
                                    : buildGoogleMapsUrl(
                                          fName,
                                          itemLat,
                                          itemLng,
                                          item.kota_kabupaten || "",
                                      ),
                            detail_url: `/faskes/${item.id_faskes ?? item.id}`,
                        };
                    });

                    // Re-render all cards in grid
                    gridElement.innerHTML = hospitalsData
                        .map((h) => renderHospitalCardHtml(h))
                        .join("");
                }
            }
        } catch (err) {
            console.warn("Failed to live fetch home nearby faskes:", err);
        } finally {
            updateDistancesAndFilter();
        }
    }

    // 5. Load More Faskes
    async function loadMoreHomeFaskes() {
        if (isLoadingMore || !hasMoreHomeItems) return;
        isLoadingMore = true;

        const btn = document.getElementById("btnHomeLoadMore");
        const spinner = document.getElementById("homeLoadMoreSpinner");
        const icon = document.getElementById("homeLoadMoreIcon");
        const text = document.getElementById("homeLoadMoreText");

        if (spinner) spinner.classList.remove("d-none");
        if (icon) icon.classList.add("d-none");
        if (text) text.textContent = "Memuat...";
        if (btn) btn.disabled = true;

        const nextPage = currentHomeLoadedPage + 1;

        try {
            const url = `${backendApiUrl}/api/faskes?lat=${userState.lat}&lng=${userState.lng}&page=${nextPage}&limit=12`;
            const res = await fetch(url);
            if (res.ok) {
                const json = await res.json();
                const items = json.data?.data || json.data || [];
                const total = json.data?.total || items.length;

                currentHomeLoadedPage = nextPage;
                hasMoreHomeItems = nextPage * 12 < total;

                if (Array.isArray(items) && items.length > 0) {
                    const newFormattedItems = items.map((item) => {
                        const itemLat = parseFloat(item.latitude || item.lat);
                        const itemLng = parseFloat(item.longitude || item.lng);
                        // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                        const typeSlug =
                            item.type_slug ||
                            (() => {
                                const j = (
                                    item.jenis_faskes ||
                                    item.tipe ||
                                    ""
                                ).toLowerCase();
                                if (
                                    j.includes("rumah sakit") ||
                                    j.includes("rs")
                                )
                                    return "hospital";
                                if (j.includes("puskesmas")) return "puskesmas";
                                if (
                                    j.includes("klinik") ||
                                    j.includes("dokter") ||
                                    j.includes("apotek")
                                )
                                    return "clinic";
                                return "hospital";
                            })();
                        const rating =
                            item.avg_rating && parseFloat(item.avg_rating) > 0
                                ? parseFloat(item.avg_rating).toFixed(1)
                                : null;
                        const distance = userState.isDetected
                            ? getDistanceKm(
                                  userState.lat,
                                  userState.lng,
                                  itemLat,
                                  itemLng,
                              )
                            : null;

                        let fName = (
                            item.nama_faskes ||
                            item.name ||
                            "Fasilitas Kesehatan"
                        ).trim();
                        // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                        const jenisFaskes =
                            item.jenis_faskes || item.tipe || "";
                        if (
                            (typeSlug === "puskesmas" ||
                                (jenisFaskes &&
                                    jenisFaskes
                                        .toLowerCase()
                                        .includes("puskesmas"))) &&
                            !/^(puskesmas|pkm)\b/i.test(fName)
                        ) {
                            fName = "Puskesmas " + fName;
                        }

                        return {
                            id: item.id_faskes ?? item.id,
                            hospital_name: fName,
                            type: jenisFaskes || "Fasilitas Kesehatan",
                            type_slug: typeSlug,
                            // UPDATE: BE menggunakan alamat_faskes, bukan alamat
                            hospital_address:
                                (item.alamat_faskes || item.alamat || "") +
                                (item.kota_kabupaten
                                    ? ", " + item.kota_kabupaten
                                    : ""),
                            hospital_rating: rating,
                            hospital_class: item.kelas || null,
                            // UPDATE: BE menggunakan _support_bpjs, bukan is_support_bpjs
                            bpjs_support: !!(
                                item._support_bpjs ?? item.is_support_bpjs
                            ),
                            distance:
                                distance !== null ? `${distance} km` : "2.4 km",
                            currentDistance: distance,
                            is_open: true,
                            lat: itemLat,
                            lng: itemLng,
                            navigasi_url:
                                item.link_maps &&
                                item.link_maps !== "-" &&
                                item.link_maps.startsWith("http")
                                    ? item.link_maps
                                    : buildGoogleMapsUrl(
                                          fName,
                                          itemLat,
                                          itemLng,
                                          item.kota_kabupaten || "",
                                      ),
                            detail_url: `/faskes/${item.id_faskes ?? item.id}`,
                        };
                    });

                    // Append only unique items
                    newFormattedItems.forEach((newItem) => {
                        if (!hospitalsData.some((h) => h.id === newItem.id)) {
                            hospitalsData.push(newItem);
                            const cardWrapper = document.createElement("div");
                            cardWrapper.innerHTML =
                                renderHospitalCardHtml(newItem);
                            if (cardWrapper.firstElementChild) {
                                gridElement.appendChild(
                                    cardWrapper.firstElementChild,
                                );
                            }
                        }
                    });
                }
            }
        } catch (err) {
            console.error("Error loading more faskes on Beranda:", err);
        } finally {
            isLoadingMore = false;
            if (spinner) spinner.classList.add("d-none");
            if (icon) icon.classList.remove("d-none");
            if (text) text.textContent = "Muat Lebih Banyak";
            if (btn) btn.disabled = false;
            updateDistancesAndFilter();
        }
    }

    // 6. Geolocation Detection with Fallback to Rating Sort
    function detectHomeLocation(isAuto = false) {
        const btnDetect = document.getElementById("btnHomeDetectLocation");
        const btnText = document.getElementById("btnHomeDetectText");
        const btnSpinner = document.getElementById("btnHomeSpinner");
        const btnIcon = document.getElementById("btnHomeIcon");
        const locText = document.getElementById("homeCurrentLocationText");
        const lockedBadge = document.getElementById("homeGpsLockedBadge");
        const ratingSortBadge = document.getElementById("homeRatingSortBadge");
        const sectionHeading = document.getElementById("homeSectionHeading");
        const distanceChip = document.getElementById("homeChipDistance");

        function fallbackToRatingMode(reasonMsg = "Lokasi tidak diizinkan") {
            btnDetect?.classList.remove("disabled");
            btnSpinner?.classList.add("d-none");
            btnIcon?.classList.remove("d-none");

            userState.isDetected = false;
            userState.permissionDenied = true;

            filterState.distance5 = false;
            if (distanceChip) {
                distanceChip.classList.remove("active");
            }

            if (btnText) btnText.textContent = "Aktifkan GPS";
            if (locText) locText.textContent = `${reasonMsg}`;
            if (lockedBadge) lockedBadge.classList.add("d-none");
            if (ratingSortBadge) ratingSortBadge.classList.remove("d-none");
            if (sectionHeading)
                sectionHeading.textContent = "Rekomendasi Faskes Terbaik";

            updateDistancesAndFilter();
        }

        if (!navigator.geolocation) {
            fallbackToRatingMode("Browser tidak mendukung GPS");
            return;
        }

        btnDetect?.classList.add("disabled");
        btnSpinner?.classList.remove("d-none");
        btnIcon?.classList.add("d-none");
        if (btnText) btnText.textContent = "Mencari...";
        if (locText) locText.textContent = "Mencari sinyal GPS...";
        if (lockedBadge) lockedBadge.classList.add("d-none");
        if (ratingSortBadge) ratingSortBadge.classList.add("d-none");

        const totalBadge = document.getElementById("homeTotalCountBadge");
        if (totalBadge) totalBadge.classList.add("d-none");

        if (loadingState) loadingState.classList.remove("d-none");
        gridElement.classList.add("d-none");

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                btnDetect?.classList.remove("disabled");
                btnSpinner?.classList.add("d-none");
                btnIcon?.classList.remove("d-none");

                userState.lat = pos.coords.latitude;
                userState.lng = pos.coords.longitude;
                userState.isDetected = true;
                userState.permissionDenied = false;

                if (btnText) btnText.textContent = "Perbarui";
                if (locText)
                    locText.textContent = `Lat: ${userState.lat.toFixed(4)}, Lng: ${userState.lng.toFixed(4)}`;
                if (lockedBadge) lockedBadge.classList.remove("d-none");
                if (ratingSortBadge) ratingSortBadge.classList.add("d-none");
                if (sectionHeading)
                    sectionHeading.textContent = "Rumah Sakit Terdekat";

                if (distanceChip) {
                    distanceChip.classList.toggle(
                        "active",
                        filterState.distance5,
                    );
                }

                // Fetch real nearest facilities from DB
                fetchHomeNearbyFaskes(userState.lat, userState.lng);
            },
            (err) => {
                console.warn("Geolocation notice on Home:", err);
                fallbackToRatingMode("Lokasi default (Jabodetabek)");
            },
            { enableHighAccuracy: true, timeout: 4000, maximumAge: 10000 },
        );
    }

    // 7. Bind Independent Toggle Filter Chips
    const distanceChip = document.getElementById("homeChipDistance");
    distanceChip?.addEventListener("click", function () {
        if (!userState.isDetected) {
            detectHomeLocation(false);
            return;
        }

        filterState.distance5 = !filterState.distance5;
        this.classList.toggle("active", filterState.distance5);
        this.title = filterState.distance5
            ? "Filter radius 5km aktif (klik untuk nonaktifkan)"
            : "Klik untuk memfilter radius 5km";

        updateDistancesAndFilter();
    });

    document
        .querySelectorAll('#homeFilterChips .chip-filter[data-toggle="type"]')
        .forEach((chip) => {
            chip.addEventListener("click", function () {
                const type = this.dataset.type;
                if (filterState.types.has(type)) {
                    filterState.types.delete(type);
                    this.classList.remove("active");
                } else {
                    filterState.types.add(type);
                    this.classList.add("active");
                }
                updateDistancesAndFilter();
            });
        });

    const bpjsChip = document.getElementById("homeChipBpjs");
    bpjsChip?.addEventListener("click", function () {
        filterState.bpjsOnly = !filterState.bpjsOnly;
        this.classList.toggle("active", filterState.bpjsOnly);
        updateDistancesAndFilter();
    });

    document
        .getElementById("btnHomeResetFilter")
        ?.addEventListener("click", function () {
            filterState.distance5 = false;
            filterState.types.clear();
            filterState.bpjsOnly = false;
            document
                .querySelectorAll("#homeFilterChips .chip-filter")
                .forEach((c) => c.classList.remove("active"));
            updateDistancesAndFilter();
        });

    document
        .getElementById("btnHomeDetectLocation")
        ?.addEventListener("click", () => detectHomeLocation(false));

    document
        .getElementById("btnHomeLoadMore")
        ?.addEventListener("click", loadMoreHomeFaskes);

    // Auto-detect location on load
    detectHomeLocation(true);
});
