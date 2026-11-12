/**
 * MediGO - Interactive Leaflet Map Module (Production Clean & Real-time Nearby GPS)
 * Clean modular architecture for hospital locator, GPS radius & two-way sync
 */

document.addEventListener("DOMContentLoaded", function () {
    const mapElement = document.getElementById("facilityLeafletMap");
    if (!mapElement) return;

    const rawBeUrl = window.MEDIGO_BE_URL || "http://127.0.0.1:8005";
    const backendApiUrl = rawBeUrl
        .replace("localhost", "127.0.0.1")
        .replace(/\/+$/, "");

    // Backend initial data passed via window
    let faskesData = window.faskesInitialData || [];

    // State
    const userState = {
        lat: -6.2088, // Default Jakarta Pusat
        lng: 106.822,
        radiusKm: 5.0,
        filterWithinRadius: false,
        isDetected: false,
        searchQuery: "",
    };

    // Independent Multi-select Filter State
    const filterState = {
        types: new Set(),
        bpjsOnly: false,
    };

    let activeFaskesId = faskesData.length > 0 ? faskesData[0].id : null;
    let markers = {};
    let isFetchingNearby = false;
    let fetchDebounceTimer = null;

    // 1. Initialize Leaflet Map Instance
    const map = L.map("facilityLeafletMap", {
        center: [userState.lat, userState.lng],
        zoom: 13,
        zoomControl: false,
        attributionControl: false,
    });

    // 2. OpenStreetMap Standard Clean Tile Layer (No API Key required)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    // 3. User Geolocation Marker (Blue Pulsing Radar Pin)
    const userIcon = L.divIcon({
        className: "user-location-marker-icon",
        html: `
            <div class="user-location-marker" title="Lokasi Anda">
                <div class="user-location-pulse"></div>
                <div class="user-location-dot"></div>
            </div>
        `,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
    });

    const userMarker = L.marker([userState.lat, userState.lng], {
        icon: userIcon,
        zIndexOffset: 2000,
        draggable: true,
    }).addTo(map);

    userMarker.bindTooltip("Lokasi Anda (Bisa Digeser)", {
        direction: "top",
        offset: [0, -10],
        permanent: false,
    });

    // 4. Dynamic Radius Circle (Primary Brand Color #002b5e)
    const userCircle = L.circle([userState.lat, userState.lng], {
        radius: userState.radiusKm * 1000,
        color: "#002b5e",
        fillColor: "#0b5ed7",
        fillOpacity: 0.08,
        weight: 1.5,
        dashArray: "6, 6",
    }).addTo(map);

    // Drag User Marker Handler
    userMarker.on("dragend", function (e) {
        const newPos = e.target.getLatLng();
        userState.lat = newPos.lat;
        userState.lng = newPos.lng;
        userCircle.setLatLng(newPos);

        const coordsText = document.getElementById("userCoordinatesText");
        if (coordsText)
            coordsText.textContent = `Lat: ${userState.lat.toFixed(4)}, Lng: ${userState.lng.toFixed(4)}`;

        const accText = document.getElementById("userAccuracyText");
        if (accText) accText.textContent = "(Lokasi Manual)";

        // Fetch live nearest faskes around new location
        fetchNearbyFaskes(true);
    });

    // 5. Haversine Fallback & OSRM Road Distance Cache (Distance in Km)
    const osrmRoadCache = new Map();

    function getRoadCacheKey(lat1, lon1, lat2, lon2) {
        return `${Number(lat1).toFixed(4)}_${Number(lon1).toFixed(4)}_${Number(lat2).toFixed(5)}_${Number(lon2).toFixed(5)}`;
    }

    function getDistanceKm(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return null;
        const cached = osrmRoadCache.get(getRoadCacheKey(lat1, lon1, lat2, lon2));
        if (cached !== undefined) return cached;

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

    async function syncOsrmRoadDistances(origLat, origLng, items) {
        if (!origLat || !origLng || !Array.isArray(items) || items.length === 0) return;

        const uncached = items.filter((it) => {
            if (!it.lat || !it.lng) return false;
            return !osrmRoadCache.has(getRoadCacheKey(origLat, origLng, it.lat, it.lng));
        });

        if (uncached.length === 0) return;

        try {
            const batch = uncached.slice(0, 60);
            const coords = [
                `${origLng},${origLat}`,
                ...batch.map((it) => `${it.lng},${it.lat}`),
            ].join(";");
            const res = await fetch(
                `https://router.project-osrm.org/table/v1/driving/${coords}?sources=0&annotations=distance`,
            );
            if (!res.ok) return;
            const data = await res.json();
            const row = data?.distances?.[0];
            if (!Array.isArray(row)) return;

            let updated = false;
            batch.forEach((it, idx) => {
                const meters = row[idx + 1];
                if (typeof meters === "number" && meters > 0) {
                    const km = Math.round((meters / 1000) * 10) / 10;
                    osrmRoadCache.set(
                        getRoadCacheKey(origLat, origLng, it.lat, it.lng),
                        km,
                    );
                    updated = true;
                }
            });

            if (updated) {
                updateFaskesDistancesAndFilter(false, true);
            }
        } catch (e) {
            console.warn("OSRM road distance fallback on map:", e);
        }
    }

    // Helper to generate Google Maps Search URL combining Name, Address, and Coordinates
    function buildGoogleMapsUrl(name, lat, lng, city = "", address = "") {
        const parts = [name, address, city].filter((p) => p && p.trim());
        const query = parts.join(", ");
        const encoded = encodeURIComponent(query);
        if (lat && lng && lat !== "" && lng !== "") {
            return `https://www.google.com/maps/search/${encoded}/@${lat},${lng},17z`;
        }
        return `https://www.google.com/maps/search/?api=1&query=${encoded}`;
    }

    // Helper to resolve specific facility type (never generic 'Fasilitas Kesehatan')
    function resolveFaskesType(item, typeSlug = "hospital", name = "") {
        let resolved = (item.type || item.jenis_faskes || item.tipe || "").trim();
        if (!resolved || resolved.toLowerCase() === "fasilitas kesehatan" || resolved.toLowerCase() === "faskes") {
            const nameLower = (name || item.name || item.nama_faskes || "").toLowerCase();
            if (typeSlug === "puskesmas" || nameLower.includes("puskesmas") || nameLower.includes("pkm")) {
                return "Puskesmas";
            }
            if (typeSlug === "clinic" || nameLower.includes("klinik") || nameLower.includes("clinic")) {
                return "Klinik";
            }
            if (nameLower.includes("apotik") || nameLower.includes("apotek") || nameLower.includes("farma")) {
                return "Apotek";
            }
            if (nameLower.includes("laboratorium") || nameLower.includes("lab")) {
                return "Laboratorium";
            }
            return "Rumah Sakit";
        }
        const low = resolved.toLowerCase();
        if (low.includes("rumah sakit") || low === "rs") return "Rumah Sakit";
        if (low.includes("puskesmas")) return "Puskesmas";
        if (low.includes("klinik")) return "Klinik";
        if (low.includes("apotik") || low.includes("apotek")) return "Apotek";
        return resolved.charAt(0).toUpperCase() + resolved.slice(1);
    }

    // Helper to clean faskes address (remove leading comma, fix JL, ensure road formatting)
    function cleanFaskesAddress(rawAlamat, kota = "", kecamatan = "") {
        let addr = (rawAlamat || "").trim();
        addr = addr.replace(/^(?:jl|jln),\s*/i, "Jl. ");
        addr = addr.replace(/^[,\-\s]+/, "");

        if (!addr && kecamatan) {
            addr = "Kec. " + kecamatan.trim();
        }

        if (addr && !/^(?:jl|jalan|gang|gg|komp|komplek|gedung|kav|kavling|dusun|desa|kel|kec)\b/i.test(addr)) {
            if (/(?:raya|barat|timur|selatan|utara|no\b|nomor|km\b|blok)/i.test(addr)) {
                addr = "Jl. " + addr;
            }
        }

        const city = (kota || "").trim();
        if (addr && city) {
            if (addr.toLowerCase().endsWith(city.toLowerCase())) {
                return addr;
            }
            return `${addr}, ${city}`;
        } else if (addr) {
            return addr;
        } else if (city) {
            return city;
        }
        return "";
    }

    // 6. Custom Pin Marker Creator
    function createCustomPin(item, isActive = false) {
        let pinClass = "pin-hospital";
        let iconHtml = '<i class="bi bi-hospital"></i>';

        const typeSlug = item.type_slug || "";
        const tipeLower = (item.type || "").toLowerCase();

        if (
            typeSlug === "clinic" ||
            tipeLower.includes("klinik") ||
            tipeLower.includes("dokter") ||
            tipeLower.includes("apotek")
        ) {
            pinClass = "pin-clinic";
            iconHtml = '<i class="bi bi-heart-pulse-fill"></i>';
        } else if (
            typeSlug === "puskesmas" ||
            tipeLower.includes("puskesmas")
        ) {
            pinClass = "pin-puskesmas";
            iconHtml = '<i class="bi bi-plus-circle-fill"></i>';
        }

        const activeClass = isActive ? "is-active-pin" : "";

        return L.divIcon({
            className: "custom-leaflet-divicon",
            html: `
                <div class="custom-map-pin ${pinClass} ${activeClass}" id="pin-${item.id}" data-id="${item.id}">
                    <div class="pin-icon-wrap">${iconHtml}</div>
                    <div class="pin-pulse"></div>
                </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
        });
    }

    // 7. Leaflet Popup HTML
    function createPopupHtml(item, distance) {
        const classBadge = item.hospital_class
            ? `
            <span class="badge-custom badge-kelas">Kelas ${item.hospital_class}</span>
        `
            : "";

        const bpjsBadge = item.bpjs_support
            ? `
            <span class="badge-custom badge-bpjs">BPJS</span>
        `
            : `
            <span class="badge-custom badge-non-bpjs">Non-BPJS</span>
        `;

        const typeSlug = item.type_slug || "hospital";
        const ratingVal =
            item.avg_rating && parseFloat(item.avg_rating) > 0
                ? parseFloat(item.avg_rating).toFixed(1)
                : item.rating && parseFloat(item.rating) > 0
                  ? parseFloat(item.rating).toFixed(1)
                  : null;
        const ratingHtml = ratingVal
            ? `<i class="bi bi-star-fill text-warning me-1"></i><span class="fw-bold">${ratingVal}</span>`
            : `<i class="bi bi-star text-muted me-1"></i><span class="text-muted" style="font-size:0.75rem;">Belum dirating</span>`;
        const detailUrl =
            item.detail_url && item.detail_url !== "#"
                ? item.detail_url
                : `/faskes/${item.id}`;

        let displayName = (
            item.name ||
            item.nama_faskes ||
            "Fasilitas Kesehatan"
        ).trim();
        if (
            (typeSlug === "puskesmas" ||
                (item.type && item.type.toLowerCase().includes("puskesmas"))) &&
            !/^(puskesmas|pkm)\b/i.test(displayName)
        ) {
            displayName = "Puskesmas " + displayName;
        }

        const navUrl =
            item.link_maps &&
            item.link_maps !== "-" &&
            item.link_maps.startsWith("http")
                ? item.link_maps
                : buildGoogleMapsUrl(
                      displayName,
                      item.lat,
                      item.lng,
                      item.kota_kabupaten || "",
                      item.address || "",
                  );

        let typeIcon = "bi-hospital";
        if (typeSlug === "clinic") {
            typeIcon = "bi-heart-pulse-fill";
        } else if (typeSlug === "puskesmas") {
            typeIcon = "bi-plus-circle-fill";
        }

        return `
            <div class="leaflet-faskes-popup-card">
                <div class="popup-card-header faskes-avatar-banner faskes-avatar-${typeSlug}">
                    <i class="bi ${typeIcon} faskes-avatar-icon text-white"></i>
                    <div class="popup-header-badges">
                        <span class="badge bg-white text-dark rounded-pill px-2 py-1 shadow-xs" style="font-size: 0.68rem;">
                            ${item.type || "Fasilitas Medis"}
                        </span>
                        <div class="rating-badge">
                            ${ratingHtml}
                        </div>
                    </div>
                </div>

                <div class="popup-card-body">
                    <h6 class="popup-card-title fw-bold text-dark mb-1 text-truncate">${displayName}</h6>
                    <p class="popup-card-address text-muted mb-2">${item.address}</p>
                    
                    <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                        ${classBadge}
                        ${bpjsBadge}
                        <span class="popup-card-distance ms-auto">
                            <i class="bi bi-sign-turn-right text-primary me-1"></i>${distance ? distance + " km" : "-"}
                        </span>
                    </div>

                    <div class="d-flex gap-2 mt-3 pt-2 border-top">
                        <a href="${navUrl}" target="_blank" class="btn btn-medigo-navigasi flex-fill text-center text-decoration-none">
                            <i class="bi bi-sign-turn-right me-1"></i> Navigasi
                        </a>
                        <a href="${detailUrl}" class="btn btn-medigo-detail flex-fill text-center text-decoration-none">
                            <i class="bi bi-info-circle me-1"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    // 8. Render Single Card for Desktop Sidebar
    function renderDesktopCardHtml(item, isFirst = false) {
        const distanceStr =
            item.currentDistance !== null
                ? `${item.currentDistance} km`
                : item.distance || "";
        const typeSlug = item.type_slug || "hospital";
        const detailUrl =
            item.detail_url && item.detail_url !== "#"
                ? item.detail_url
                : `/faskes/${item.id}`;

        let displayName = (
            item.name ||
            item.nama_faskes ||
            "Fasilitas Kesehatan"
        ).trim();
        if (
            (typeSlug === "puskesmas" ||
                (item.type && item.type.toLowerCase().includes("puskesmas"))) &&
            !/^(puskesmas|pkm)\b/i.test(displayName)
        ) {
            displayName = "Puskesmas " + displayName;
        }

        const resolvedType = resolveFaskesType(item, typeSlug, displayName);
        const cleanAddress = cleanFaskesAddress(
            item.address || item.alamat_faskes || item.alamat || "",
            item.kota_kabupaten || item.kota || "",
            item.kecamatan || ""
        );

        const navUrl =
            item.link_maps &&
            item.link_maps !== "-" &&
            item.link_maps.startsWith("http")
                ? item.link_maps
                : buildGoogleMapsUrl(
                      displayName,
                      item.lat,
                      item.lng,
                      item.kota_kabupaten || "",
                      cleanAddress,
                  );

        let typeIcon = "bi-hospital";
        if (typeSlug === "clinic") {
            typeIcon = "bi-heart-pulse-fill";
        } else if (typeSlug === "puskesmas") {
            typeIcon = "bi-plus-circle-fill";
        }

        return `
            <div class="facility-item-card card ${isFirst ? "is-selected" : ""}" 
                 id="card-faskes-${item.id}" 
                 data-id="${item.id}"
                 data-name="${displayName}"
                 data-address="${cleanAddress}"
                 data-lat="${item.lat}"
                 data-lng="${item.lng}">
                <div class="d-flex gap-3 align-items-start">
                    <div class="facility-thumb-wrap flex-shrink-0 faskes-avatar-map faskes-avatar-${typeSlug}">
                        <i class="bi ${typeIcon} text-white fs-4"></i>
                    </div>
                    <div class="facility-info-wrap flex-grow-1 min-w-0">
                        <h6 class="facility-name fw-bold mb-1 text-dark text-truncate" title="${displayName}">
                            ${displayName}
                        </h6>
                        <p class="facility-address text-muted mb-2 text-truncate" title="${cleanAddress}">
                            ${cleanAddress}
                        </p>
                        <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                            ${resolvedType ? `<span class="badge-custom badge-type-pill" style="background-color: #f1f5f9; color: #475569; font-size: 0.72rem; padding: 2px 8px; border-radius: 9999px;">${resolvedType}</span>` : ""}
                            ${item.hospital_class ? `<span class="badge-custom badge-kelas">Kelas ${item.hospital_class}</span>` : ""}
                            <span class="badge-custom ${item.bpjs_support ? "badge-bpjs" : "badge-non-bpjs"}">
                                ${item.bpjs_support ? "BPJS" : "Non - BPJS"}
                            </span>
                            <span class="facility-distance ms-auto text-dark fw-bold" id="dist-faskes-${item.id}">
                                <i class="bi bi-arrow-down-up me-1"></i>${distanceStr}
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="${navUrl}" target="_blank" class="btn btn-sm btn-navigasi-pill flex-grow-1 text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-sign-turn-right"></i> Navigasi
                            </a>
                            <a href="${detailUrl}" class="btn btn-sm btn-detail-pill flex-grow-1 text-decoration-none d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-info-circle"></i> Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // 9. Render Single Card for Mobile Bottom Sheet
    function renderMobileCardHtml(item, isFirst = false) {
        const distanceStr =
            item.currentDistance !== null
                ? `${item.currentDistance} km`
                : item.distance || "Rating ★";
        const typeSlug = item.type_slug || "hospital";
        const detailUrl =
            item.detail_url && item.detail_url !== "#"
                ? item.detail_url
                : `/faskes/${item.id}`;

        let displayName = (
            item.name ||
            item.nama_faskes ||
            "Fasilitas Kesehatan"
        ).trim();
        if (
            (typeSlug === "puskesmas" ||
                (item.type && item.type.toLowerCase().includes("puskesmas"))) &&
            !/^(puskesmas|pkm)\b/i.test(displayName)
        ) {
            displayName = "Puskesmas " + displayName;
        }

        const resolvedType = resolveFaskesType(item, typeSlug, displayName);
        const cleanAddress = cleanFaskesAddress(
            item.address || item.alamat_faskes || item.alamat || "",
            item.kota_kabupaten || item.kota || "",
            item.kecamatan || ""
        );

        const navUrl =
            item.link_maps &&
            item.link_maps !== "-" &&
            item.link_maps.startsWith("http")
                ? item.link_maps
                : buildGoogleMapsUrl(
                      displayName,
                      item.lat,
                      item.lng,
                      item.kota_kabupaten || "",
                      cleanAddress,
                  );

        let typeIcon = "bi-hospital";
        if (typeSlug === "clinic") {
            typeIcon = "bi-heart-pulse-fill";
        } else if (typeSlug === "puskesmas") {
            typeIcon = "bi-plus-circle-fill";
        }

        return `
            <div class="mobile-facility-sheet-card ${isFirst ? "is-active" : ""}" 
                 id="mobile-card-${item.id}" 
                 data-id="${item.id}"
                 data-type="${typeSlug}"
                 data-bpjs="${item.bpjs_support ? "1" : "0"}"
                 data-lat="${item.lat}"
                 data-lng="${item.lng}">
                <div class="d-flex gap-3 align-items-center mb-2">
                    <div class="mobile-sheet-thumb-wrap position-relative flex-shrink-0 faskes-avatar-map faskes-avatar-${typeSlug}">
                        <i class="bi ${typeIcon} text-white fs-4"></i>
                    </div>
                    <div class="mobile-sheet-info flex-grow-1 min-w-0">
                        <h6 class="mobile-sheet-title fw-bold text-dark text-truncate mb-1" title="${displayName}">
                            ${displayName}
                        </h6>
                        <p class="mobile-sheet-address text-muted text-truncate mb-1" title="${cleanAddress}">
                            ${cleanAddress}
                        </p>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            ${resolvedType ? `<span class="badge-custom badge-type-pill" style="background-color: #f1f5f9; color: #475569; font-size: 0.65rem;">${resolvedType}</span>` : ""}
                            ${item.hospital_class ? `<span class="badge-custom badge-kelas">Kelas ${item.hospital_class}</span>` : ""}
                            <span class="badge-custom ${item.bpjs_support ? "badge-bpjs" : "badge-non-bpjs"}">
                                ${item.bpjs_support ? "BPJS" : "Non-BPJS"}
                            </span>
                            <span class="mobile-sheet-distance ms-auto text-dark fw-bold" id="mobile-dist-${item.id}">
                                <i class="bi bi-sign-turn-right text-primary me-1"></i>${distanceStr}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="${navUrl}" id="mobile-nav-${item.id}" target="_blank" class="btn btn-medigo-navigasi flex-fill text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-sign-turn-right"></i> Navigasi
                    </a>
                    <a href="${detailUrl}" class="btn btn-medigo-detail flex-fill text-decoration-none d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-info-circle"></i> Detail
                    </a>
                </div>
            </div>
        `;
    }

    // 10. Bind Card Click Events
    function bindCardEventListeners() {
        document.querySelectorAll(".facility-item-card").forEach((card) => {
            const id = parseInt(card.dataset.id);
            card.addEventListener("mouseenter", () =>
                highlightFacility(id, true),
            );
            card.addEventListener("mouseleave", () => {
                document
                    .querySelectorAll(".facility-item-card")
                    .forEach((c) => c.classList.remove("is-hover-highlight"));
                const pinEl = document.getElementById(`pin-${id}`);
                if (pinEl) pinEl.classList.remove("is-hovered");
            });
            card.addEventListener("click", (e) => {
                if (e.target.closest("a")) return;
                selectFacility(id, true);
            });
        });

        document
            .querySelectorAll(".mobile-facility-sheet-card")
            .forEach((card) => {
                const id = parseInt(card.dataset.id);
                card.addEventListener("click", (e) => {
                    if (e.target.closest("a")) return;
                    selectFacility(id, true);
                });
            });
    }

    // 11. Re-render Leaflet Marker Pins
    function renderAllPins() {
        // Remove existing markers
        Object.values(markers).forEach((m) => map.removeLayer(m));
        markers = {};

        faskesData.forEach((item) => {
            if (!item.lat || !item.lng) return;

            const isActive = item.id === activeFaskesId;
            const icon = createCustomPin(item, isActive);
            const distance =
                item.currentDistance !== null
                    ? item.currentDistance
                    : getDistanceKm(
                          userState.lat,
                          userState.lng,
                          item.lat,
                          item.lng,
                      );

            const marker = L.marker([item.lat, item.lng], {
                icon: icon,
                zIndexOffset: isActive ? 1000 : 10,
            });

            const ratingVal =
                item.rating && parseFloat(item.rating) > 0
                    ? parseFloat(item.rating).toFixed(1)
                    : null;
            const ratingDisplay = ratingVal
                ? `${ratingVal}/5`
                : "Belum dirating";
            const ratingColor = ratingVal ? "#ffc107" : "#cbd5e1";
            const starIcon = ratingVal
                ? '<i class="bi bi-star-fill text-warning me-1"></i>'
                : '<i class="bi bi-star me-1" style="color: #cbd5e1;"></i>';

            marker.bindTooltip(
                `
            <div class="pin-mini-tooltip">
                <div class="tooltip-name fw-bold">${item.name}</div>
                <div class="tooltip-meta">
                    <span style="color: ${ratingColor};">${starIcon}${ratingDisplay}</span> • 
                    <span class="${item.bpjs_support ? "text-success fw-semibold" : "text-danger"}">${item.bpjs_support ? "BPJS" : "Non BPJS"}</span> • 
                    <span style="color: #e2e8f0;">${distance !== null ? distance + " km" : ""}</span>
                </div>
            </div>
        `,
                {
                    direction: "top",
                    offset: [0, -40],
                    opacity: 1,
                    className: "custom-pin-tooltip",
                },
            );

            marker.bindPopup(createPopupHtml(item, distance || 0), {
                maxWidth: 320,
                minWidth: 280,
                className: "custom-leaflet-popup",
                autoPan: true,
                autoPanPadding: [20, 20],
            });

            marker.on("mouseover", function () {
                const pinEl = document.getElementById(`pin-${item.id}`);
                if (pinEl) pinEl.classList.add("is-hovered");
                highlightFacility(item.id, false);
            });

            marker.on("mouseout", function () {
                const pinEl = document.getElementById(`pin-${item.id}`);
                if (pinEl) pinEl.classList.remove("is-hovered");
                document
                    .querySelectorAll(".facility-item-card")
                    .forEach((c) => c.classList.remove("is-hover-highlight"));
            });

            marker.on("click", function () {
                selectFacility(item.id, true);
            });

            markers[item.id] = marker;
            marker.addTo(map);
        });
    }

    // 12. Select & Highlight Facilities
    function selectFacility(id, shouldFly = false) {
        activeFaskesId = id;
        const item = faskesData.find((f) => f.id === id);
        if (!item) return;

        Object.keys(markers).forEach((k) => {
            const pinEl = document.getElementById(`pin-${k}`);
            if (pinEl) {
                pinEl.classList.toggle("is-active-pin", parseInt(k) === id);
            }
        });

        document.querySelectorAll(".facility-item-card").forEach((card) => {
            card.classList.toggle(
                "is-selected",
                parseInt(card.dataset.id) === id,
            );
        });

        const selectedCard = document.getElementById(`card-faskes-${id}`);
        if (selectedCard) {
            selectedCard.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
            });
        }

        document
            .querySelectorAll(".mobile-facility-sheet-card")
            .forEach((c) => {
                c.classList.toggle("is-active", parseInt(c.dataset.id) === id);
            });

        const selectedMobileCard = document.getElementById(`mobile-card-${id}`);
        if (selectedMobileCard) {
            selectedMobileCard.scrollIntoView({
                behavior: "smooth",
                inline: "center",
                block: "nearest",
            });
        }

        const marker = markers[id];
        if (marker) {
            if (shouldFly) {
                map.flyTo([item.lat, item.lng], 14, {
                    animate: true,
                    duration: 0.6,
                });
            }
            marker.openPopup();
        }
    }

    function highlightFacility(id, fromCard = false) {
        document.querySelectorAll(".facility-item-card").forEach((card) => {
            card.classList.toggle(
                "is-hover-highlight",
                parseInt(card.dataset.id) === id,
            );
        });

        const pinEl = document.getElementById(`pin-${id}`);
        if (pinEl && fromCard) {
            pinEl.classList.add("is-hovered");
        }
    }

    // 13. Dynamic Live Fetch of Nearest Facilities from Backend API
    async function fetchNearbyFaskes(shouldFit = false) {
        if (isFetchingNearby) return;
        isFetchingNearby = true;

        const mapOverlay = document.getElementById("mapLoadingOverlay");
        const mapCardsLoadingState = document.getElementById(
            "mapCardsLoadingState",
        );
        const mobileCardsLoadingState = document.getElementById(
            "mobileCardsLoadingState",
        );

        if (mapCardsLoadingState)
            mapCardsLoadingState.classList.remove("d-none");
        if (mobileCardsLoadingState)
            mobileCardsLoadingState.classList.remove("d-none");

        try {
            const params = new URLSearchParams({
                with_coords: "1",
                lat: userState.lat.toString(),
                lng: userState.lng.toString(),
                limit: "150",
            });

            if (userState.searchQuery) {
                params.append("search", userState.searchQuery);
            }

            if (filterState.bpjsOnly) {
                params.append("bpjs", "1");
            }

            const response = await fetch(
                `${backendApiUrl}/api/faskes?${params.toString()}`,
            );
            if (response.ok) {
                const resData = await response.json();
                const rawItems = resData.data?.data || resData.data || [];

                if (Array.isArray(rawItems) && rawItems.length > 0) {
                    faskesData = rawItems.map((item) => {
                        const lat = parseFloat(item.latitude || item.lat);
                        const lng = parseFloat(item.longitude || item.lng);
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

                        if (item.distance_km !== undefined && item.distance_km !== null && !isNaN(parseFloat(item.distance_km))) {
                            osrmRoadCache.set(
                                getRoadCacheKey(userState.lat, userState.lng, lat, lng),
                                Math.round(parseFloat(item.distance_km) * 10) / 10,
                            );
                        }

                        const distance = getDistanceKm(
                            userState.lat,
                            userState.lng,
                            lat,
                            lng,
                        );
                        const photo =
                            typeSlug === "hospital"
                                ? item.id % 2 === 0
                                    ? "images/hospital_waterfront.jpg"
                                    : "images/hospital.jpg"
                                : "images/clinic.jpg";

                        const rawName =
                            item.nama_faskes ||
                            item.nama ||
                            item.name ||
                            "Fasilitas Kesehatan";
                        let fName = rawName.trim();
                        // UPDATE: BE menggunakan jenis_faskes, bukan tipe
                        const jenisFaskes =
                            item.jenis_faskes || item.tipe || item.type || "";
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

                        const resolvedType = resolveFaskesType(item, typeSlug, fName);
                        const rawAlamat =
                            item.alamat_faskes ||
                            item.alamat ||
                            item.alamat_lengkap ||
                            item.address ||
                            item.jalan ||
                            item.lokasi ||
                            "";
                        const kota = item.kota_kabupaten || item.kota || item.city || "";
                        const cleanAddress = cleanFaskesAddress(rawAlamat, kota, item.kecamatan || "");

                        return {
                            id: item.id_faskes ?? item.id,
                            name: fName,
                            type: resolvedType,
                            type_slug: typeSlug,
                            address: cleanAddress,
                            rating: rating,
                            total_reviews: item.total_reviews || 0,
                            hospital_class:
                                item.kelas || item.hospital_class || null,
                            // UPDATE: BE menggunakan _support_bpjs, bukan is_support_bpjs
                            bpjs_support:
                                !!(
                                    item._support_bpjs ?? item.is_support_bpjs
                                ) || !!item.bpjs_support,
                            distance:
                                distance !== null ? `${distance} km` : "-",
                            currentDistance: distance,
                            phone: item.kontak || item.telp_faskes || null,
                            lat: lat,
                            lng: lng,
                            foto: photo,
                            navigasi_url:
                                item.link_maps &&
                                item.link_maps !== "-" &&
                                item.link_maps.startsWith("http")
                                    ? item.link_maps
                                    : buildGoogleMapsUrl(
                                          fName,
                                          lat,
                                          lng,
                                          item.kota_kabupaten || "",
                                      ),
                            detail_url: `/faskes/${item.id_faskes ?? item.id}`,
                        };
                    });
                }
            }
        } catch (err) {
            console.warn(
                "Live nearby faskes fetch error, using local fallback:",
                err,
            );
        } finally {
            isFetchingNearby = false;
            if (mapOverlay) mapOverlay.classList.add("d-none");
            if (mapCardsLoadingState)
                mapCardsLoadingState.classList.add("d-none");
            if (mobileCardsLoadingState)
                mobileCardsLoadingState.classList.add("d-none");

            renderAllPins();
            updateFaskesDistancesAndFilter(shouldFit);
        }
    }

    // 14. Distance Recalculation, Multi-Filter & UI List Update
    function updateFaskesDistancesAndFilter(shouldFit = false, skipOsrmSync = false) {
        let insideRadiusCount = 0;
        let visibleCount = 0;
        const visibleBounds = [];

        // 1. Calculate distances
        faskesData.forEach((item) => {
            const distance = getDistanceKm(
                userState.lat,
                userState.lng,
                item.lat,
                item.lng,
            );
            item.currentDistance = distance;
            item.distance = distance !== null ? `${distance} km` : "-";
        });

        // 2. Sort by distance ascending
        const sortedData = [...faskesData].sort(
            (a, b) => (a.currentDistance || 0) - (b.currentDistance || 0),
        );

        // 3. Filter items
        const visibleItems = [];
        sortedData.forEach((item) => {
            const query = (userState.searchQuery || "").toLowerCase().trim();
            const matchSearch =
                !query ||
                item.name.toLowerCase().includes(query) ||
                item.address.toLowerCase().includes(query);

            const itemType = (item.type_slug || "").toLowerCase();
            const matchType =
                filterState.types.size === 0 || filterState.types.has(itemType);
            const matchBpjs =
                !filterState.bpjsOnly || item.bpjs_support === true;

            const distance = item.currentDistance || 0;
            const isInsideRadius = distance <= userState.radiusKm;
            if (isInsideRadius) insideRadiusCount++;
            const matchRadius = !userState.filterWithinRadius || isInsideRadius;

            const shouldDisplay =
                matchSearch && matchType && matchBpjs && matchRadius;

            const marker = markers[item.id];
            if (shouldDisplay) {
                visibleCount++;
                visibleItems.push(item);
                if (marker) {
                    if (!map.hasLayer(marker)) map.addLayer(marker);
                    marker.setPopupContent(createPopupHtml(item, distance));
                }
                if (item.lat && item.lng) {
                    visibleBounds.push([item.lat, item.lng]);
                }
            } else {
                if (marker && map.hasLayer(marker)) {
                    map.removeLayer(marker);
                }
            }
        });

        // 4. Render Desktop Cards
        const facilityCardsList = document.getElementById("facilityCardsList");
        const emptyState = document.getElementById("facilityEmptyState");

        if (facilityCardsList) {
            let desktopCardsHtml = visibleItems
                .map((item, idx) => renderDesktopCardHtml(item, idx === 0))
                .join("");
            if (emptyState) {
                desktopCardsHtml += emptyState.outerHTML;
            }
            facilityCardsList.innerHTML = desktopCardsHtml;
            facilityCardsList.classList.remove("d-none");
        }

        // 5. Render Mobile Cards
        const mobileCardsContainer = document.getElementById(
            "mobileCardsContainer",
        );
        if (mobileCardsContainer) {
            mobileCardsContainer.innerHTML = visibleItems
                .map((item, idx) => renderMobileCardHtml(item, idx === 0))
                .join("");
            mobileCardsContainer.classList.remove("d-none");
        }

        // Rebind click events to new DOM nodes
        bindCardEventListeners();

        // Hide loading states immediately
        const mapCardsLoadingState = document.getElementById(
            "mapCardsLoadingState",
        );
        if (mapCardsLoadingState) mapCardsLoadingState.classList.add("d-none");
        const mapOverlay = document.getElementById("mapLoadingOverlay");
        if (mapOverlay) mapOverlay.classList.add("d-none");
        const mobileCardsLoadingState = document.getElementById(
            "mobileCardsLoadingState",
        );
        if (mobileCardsLoadingState)
            mobileCardsLoadingState.classList.add("d-none");

        // Update UI Counters
        const countInsideRadiusEl =
            document.getElementById("countInsideRadius");
        if (countInsideRadiusEl)
            countInsideRadiusEl.textContent = insideRadiusCount;

        const totalFaskesBadgeEl = document.getElementById("totalFaskesBadge");
        if (totalFaskesBadgeEl) {
            totalFaskesBadgeEl.textContent = `${visibleCount} Faskes`;
            totalFaskesBadgeEl.classList.remove("d-none");
        }

        const tabFaskesCountBadge = document.getElementById(
            "tabFaskesCountBadge",
        );
        if (tabFaskesCountBadge) {
            tabFaskesCountBadge.textContent = `${visibleCount}`;
            tabFaskesCountBadge.classList.remove("d-none");
        }

        const mobileSheetCountBadge = document.getElementById(
            "mobileSheetCountBadge",
        );
        if (mobileSheetCountBadge) {
            mobileSheetCountBadge.textContent = `${visibleCount}`;
            mobileSheetCountBadge.classList.remove("d-none");
        }

        // Empty state visibility
        const currentEmptyState = document.getElementById("facilityEmptyState");
        const mobileEmptyState = document.getElementById(
            "mobileFacilityEmptyState",
        );

        if (visibleCount === 0) {
            currentEmptyState?.classList.remove("d-none");
            mobileEmptyState?.classList.remove("d-none");
        } else {
            currentEmptyState?.classList.add("d-none");
            mobileEmptyState?.classList.add("d-none");
        }

        // Active facility
        if (visibleItems.length > 0) {
            if (
                !activeFaskesId ||
                !visibleItems.some((v) => v.id === activeFaskesId)
            ) {
                selectFacility(visibleItems[0].id, false);
            }
        }

        if (shouldFit && visibleBounds.length > 0) {
            map.fitBounds(userCircle.getBounds(), {
                padding: [25, 25],
                maxZoom: 14,
            });
        }

        if (!skipOsrmSync && userState.lat && userState.lng) {
            syncOsrmRoadDistances(userState.lat, userState.lng, faskesData);
        }
    }

    // 15. UI Sync for Multi-Select Filter Chips
    function syncFilterChipsUI() {
        ["hospital", "clinic", "puskesmas"].forEach((type) => {
            document
                .querySelectorAll(`[data-filter="${type}"]`)
                .forEach((btn) => {
                    btn.classList.toggle("active", filterState.types.has(type));
                });
        });

        document.querySelectorAll('[data-filter="bpjs"]').forEach((btn) => {
            btn.classList.toggle("active", filterState.bpjsOnly);
        });
    }

    // 16. Radius Slider Controls
    const radiusSlider = document.getElementById("radiusSlider");
    const radiusDisplayBadge = document.getElementById("radiusDisplayBadge");
    const tabRadiusBadge = document.getElementById("tabRadiusBadge");

    const mobileRadiusPanel = document.getElementById("mobileRadiusPanel");
    const mobileChipRadius = document.getElementById("mobileChipRadius");
    const btnMobileFilterToggle = document.getElementById(
        "btnMobileFilterToggle",
    );
    const btnCloseMobileRadiusPanel = document.getElementById(
        "btnCloseMobileRadiusPanel",
    );
    const mobileRadiusSlider = document.getElementById("mobileRadiusSlider");
    const mobileRadiusDisplayBadge = document.getElementById(
        "mobileRadiusDisplayBadge",
    );
    const mobileChipRadiusVal = document.getElementById("mobileChipRadiusVal");
    const mobileFilterRadiusSwitch = document.getElementById(
        "mobileFilterRadiusSwitch",
    );

    function syncRadiusValue(newRadius) {
        userState.radiusKm = newRadius;
        if (radiusDisplayBadge)
            radiusDisplayBadge.textContent = `${newRadius.toFixed(1)} Km`;
        if (tabRadiusBadge)
            tabRadiusBadge.textContent = `${newRadius.toFixed(1)} Km`;
        if (mobileRadiusDisplayBadge)
            mobileRadiusDisplayBadge.textContent = `${newRadius.toFixed(1)} Km`;
        if (mobileChipRadiusVal)
            mobileChipRadiusVal.textContent = newRadius.toFixed(1);
        if (radiusSlider) radiusSlider.value = newRadius;
        if (mobileRadiusSlider) mobileRadiusSlider.value = newRadius;

        userCircle.setRadius(newRadius * 1000);
        updateFaskesDistancesAndFilter(false);
    }

    radiusSlider?.addEventListener("input", (e) =>
        syncRadiusValue(parseFloat(e.target.value)),
    );
    mobileRadiusSlider?.addEventListener("input", (e) =>
        syncRadiusValue(parseFloat(e.target.value)),
    );

    mobileChipRadius?.addEventListener("click", () =>
        mobileRadiusPanel?.classList.toggle("d-none"),
    );
    btnMobileFilterToggle?.addEventListener("click", () =>
        mobileRadiusPanel?.classList.toggle("d-none"),
    );
    btnCloseMobileRadiusPanel?.addEventListener("click", () =>
        mobileRadiusPanel?.classList.add("d-none"),
    );

    document
        .getElementById("facilityFilterBtn")
        ?.addEventListener("click", function () {
            document.getElementById("tab-radius-btn")?.click();
        });

    const filterRadiusSwitch = document.getElementById("filterRadiusSwitch");
    filterRadiusSwitch?.addEventListener("change", function (e) {
        userState.filterWithinRadius = e.target.checked;
        if (mobileFilterRadiusSwitch)
            mobileFilterRadiusSwitch.checked = e.target.checked;
        updateFaskesDistancesAndFilter(false);
    });

    mobileFilterRadiusSwitch?.addEventListener("change", function (e) {
        userState.filterWithinRadius = e.target.checked;
        if (filterRadiusSwitch) filterRadiusSwitch.checked = e.target.checked;
        updateFaskesDistancesAndFilter(false);
    });

    document
        .getElementById("btnExpandRadius")
        ?.addEventListener("click", () => syncRadiusValue(10));
    document
        .getElementById("btnMobileExpandRadius")
        ?.addEventListener("click", () => syncRadiusValue(10));
    document
        .getElementById("btnViewFilteredFaskes")
        ?.addEventListener("click", () =>
            document.getElementById("tab-faskes-btn")?.click(),
        );

    // 17. Live Search Event Listeners (Debounced)
    const searchInput = document.getElementById("facilitySearchInput");
    const mobileSearchInput = document.getElementById(
        "mobileFacilitySearchInput",
    );

    function handleSearchInput(value) {
        userState.searchQuery = value;
        if (searchInput && searchInput.value !== value)
            searchInput.value = value;
        if (mobileSearchInput && mobileSearchInput.value !== value)
            mobileSearchInput.value = value;

        clearTimeout(fetchDebounceTimer);
        fetchDebounceTimer = setTimeout(() => {
            fetchNearbyFaskes(false);
        }, 300);
    }

    searchInput?.addEventListener("input", (e) =>
        handleSearchInput(e.target.value),
    );
    mobileSearchInput?.addEventListener("input", (e) =>
        handleSearchInput(e.target.value),
    );

    // 18. Filter Chips Event Listeners
    document.querySelectorAll("[data-filter]").forEach((chip) => {
        chip.addEventListener("click", function () {
            const filterType = this.dataset.filter;
            if (filterType === "bpjs") {
                filterState.bpjsOnly = !filterState.bpjsOnly;
            } else if (
                ["hospital", "clinic", "puskesmas"].includes(filterType)
            ) {
                if (filterState.types.has(filterType)) {
                    filterState.types.delete(filterType);
                } else {
                    filterState.types.add(filterType);
                }
            }
            syncFilterChipsUI();
            updateFaskesDistancesAndFilter(false);
        });
    });

    // 19. Geolocation Detection Function
    function detectUserLocation(isAuto = false) {
        const btnDetect = document.getElementById("btnDetectLocation");
        const btnDetectText = document.getElementById("btnDetectText");
        const btnSpinner = document.getElementById("gpsBtnSpinner");
        const btnIcon = document.getElementById("gpsBtnIcon");
        const btnMapGeo = document.getElementById("btnMapGeolocation");
        const gpsBadge = document.getElementById("gpsStatusBadge");
        const badgeSpinner = document.getElementById("gpsBadgeSpinner");
        const badgeText = document.getElementById("gpsStatusBadgeText");
        const mapOverlay = document.getElementById("mapLoadingOverlay");

        function cleanupLoadingSpinners() {
            btnDetect?.classList.remove("disabled");
            btnSpinner?.classList.add("d-none");
            btnIcon?.classList.remove("d-none");
            btnMapGeo?.classList.remove("is-loading");
            badgeSpinner?.classList.add("d-none");
            mapOverlay?.classList.add("d-none");
        }

        if (!navigator.geolocation) {
            cleanupLoadingSpinners();
            if (badgeText) badgeText.textContent = "Tidak Didukung";
            return;
        }

        btnDetect?.classList.add("disabled");
        btnSpinner?.classList.remove("d-none");
        btnIcon?.classList.add("d-none");
        btnMapGeo?.classList.add("is-loading");
        badgeSpinner?.classList.remove("d-none");
        if (btnDetectText) btnDetectText.textContent = "Mencari GPS...";
        if (badgeText) badgeText.textContent = "Mencari...";

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                cleanupLoadingSpinners();
                if (btnDetectText) btnDetectText.textContent = "Perbarui GPS";

                userState.lat = pos.coords.latitude;
                userState.lng = pos.coords.longitude;
                userState.isDetected = true;

                const acc = Math.round(pos.coords.accuracy || 15);
                const coordsText = document.getElementById(
                    "userCoordinatesText",
                );
                if (coordsText)
                    coordsText.textContent = `Lat: ${userState.lat.toFixed(4)}, Lng: ${userState.lng.toFixed(4)}`;

                const accText = document.getElementById("userAccuracyText");
                if (accText) accText.textContent = `(±${acc}m • Terkunci)`;

                if (badgeText) badgeText.textContent = "GPS Terkunci";
                if (gpsBadge) {
                    gpsBadge.className =
                        "badge bg-success-subtle text-success d-flex align-items-center px-2";
                }

                userMarker.setLatLng([userState.lat, userState.lng]);
                userCircle.setLatLng([userState.lat, userState.lng]);
                map.flyTo([userState.lat, userState.lng], 13, {
                    animate: true,
                });

                // Fetch real nearest faskes around user's GPS
                fetchNearbyFaskes(true);
            },
            (err) => {
                cleanupLoadingSpinners();
                console.warn("Geolocation unavailable/denied:", err.message);
                if (btnDetectText)
                    btnDetectText.textContent = "Deteksi GPS Saya";
                if (badgeText) badgeText.textContent = "Lokasi Manual";
                if (gpsBadge) {
                    gpsBadge.className =
                        "badge bg-secondary-subtle text-secondary d-flex align-items-center px-2";
                }
                // Fetch faskes for default location
                fetchNearbyFaskes(false);
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 10000 },
        );
    }

    document
        .getElementById("btnDetectLocation")
        ?.addEventListener("click", () => detectUserLocation(false));
    document
        .getElementById("btnMapGeolocation")
        ?.addEventListener("click", () => detectUserLocation(false));

    // 20. Mobile Bottom Sheet Gestures
    const bottomSheet = document.getElementById("mobileBottomSheet");
    const dragArea = document.getElementById("sheetDragArea");
    const btnCloseBottomSheet = document.getElementById("btnCloseBottomSheet");

    function toggleBottomSheet(expand) {
        if (!bottomSheet) return;
        if (expand === undefined) {
            expand = !bottomSheet.classList.contains("is-expanded");
        }
        bottomSheet.classList.toggle("is-expanded", expand);
        document
            .querySelector(".mobile-map-top-bar")
            ?.classList.toggle("is-hidden-by-sheet", expand);
    }

    dragArea?.addEventListener("click", (e) => {
        if (e.target.closest("a") || e.target.closest("#btnCloseBottomSheet"))
            return;
        toggleBottomSheet();
    });

    btnCloseBottomSheet?.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleBottomSheet(false);
    });

    // 21. Custom Zoom Controls
    document
        .getElementById("btnZoomIn")
        ?.addEventListener("click", () => map.zoomIn());
    document
        .getElementById("btnZoomOut")
        ?.addEventListener("click", () => map.zoomOut());

    // 22. Invalidate Map Size on Load & Window Resizing
    window.addEventListener("resize", () => map.invalidateSize());
    setTimeout(() => map.invalidateSize(), 200);
    setTimeout(() => map.invalidateSize(), 600);

    // Initial load: render pins and detect user location
    renderAllPins();
    updateFaskesDistancesAndFilter(false);
    detectUserLocation(true);
});
