document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chatBox');
    const userInput = document.getElementById('userInput');
    const sendBtn = document.getElementById('sendBtn');
    
    // Inisialisasi Modal Bootstrap CTA
    const loginModalEl = document.getElementById('loginCtaModal');
    let loginModal = null;
    if (loginModalEl) {
        loginModal = new bootstrap.Modal(loginModalEl);
    }

    let userLat = null;
    let userLng = null;

    function getCurrentTime() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} WIB`;
    }

    const timeNowEl = document.querySelector('.time-now');
    if(timeNowEl) timeNowEl.textContent = getCurrentTime();

    try {
        const sLat = sessionStorage.getItem("medigo_user_lat");
        const sLng = sessionStorage.getItem("medigo_user_lng");
        if (sLat && sLng) {
            userLat = parseFloat(sLat);
            userLng = parseFloat(sLng);
        }
    } catch (_) {}

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                try {
                    sessionStorage.setItem("medigo_user_lat", userLat);
                    sessionStorage.setItem("medigo_user_lng", userLng);
                } catch (_) {}
            },
            (error) => { console.warn("Lokasi dibatasi pengguna:", error.message); }
        );
    }

    let isSending = false;

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    function removeTypingIndicators() {
        document.querySelectorAll('.msg-row.typing-row, .typing-indicator').forEach(el => {
            const row = el.closest('.msg-row');
            if (row) row.remove();
            else el.remove();
        });
    }

    function formatChatMessage(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

        // Bold: **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Markdown Links: [text](url) -> Tombol navigasi / maps rapi
        escaped = escaped.replace(/\[(.*?)\]\((https?:\/\/[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill my-1 text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.8rem;"><i class="bi bi-geo-alt-fill text-danger"></i> $1</a>');

        // Newlines
        return escaped.replace(/\n/g, '<br>');
    }

    async function sendMessage() {
        if (isSending) return;
        const text = userInput.value.trim();
        if (!text) return;

        isSending = true;
        userInput.disabled = true;
        sendBtn.disabled = true;

        appendMessage(text, 'user');
        userInput.value = '';
        scrollToBottom();

        const loadingHtml = `<div class="typing-indicator"><span></span><span></span><span></span></div>`;
        const loadingId = appendMessage(loadingHtml, 'bot', true);
        scrollToBottom();

        try {
            const response = await fetch(window.MedigoConfig.chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.MedigoConfig.csrfToken 
                },
                body: JSON.stringify({ message: text, lat: userLat, lng: userLng })
            });

            const result = await response.json();
            removeTypingIndicators();

            if (response.ok) {
                const replyText = result.data || result.message || "Ini respons dari MediBot.";
                const msgId = appendMessage(replyText, 'bot');
                
                if (result.faskes && Array.isArray(result.faskes) && result.faskes.length > 0) {
                    const bubbleWrap = document.querySelector(`#${msgId} .bubble-wrap`);
                    if (bubbleWrap) {
                        const cardsContainer = document.createElement('div');
                        cardsContainer.className = 'faskes-cards-container';
                        
                        result.faskes.forEach(faskes => {
                            cardsContainer.appendChild(renderFaskesCardHTML(faskes));
                        });
                        
                        const textBubble = bubbleWrap.querySelector('.bubble');
                        if (textBubble) {
                            textBubble.after(cardsContainer);
                        } else {
                            bubbleWrap.appendChild(cardsContainer);
                        }
                    }
                }
            } else {
                // TANGKAP ERROR 403 JIKA LIMIT GUEST TERCAPAI
                if (response.status === 403 && result.status === 'limit_reached') {
                    if (loginModal) loginModal.show();
                } else {
                    appendMessage(result.message || "Maaf, terjadi kendala pada sistem.", 'bot');
                }
            }
        } catch (error) {
            removeTypingIndicators();
            appendMessage("Koneksi terputus. Pastikan internet Anda stabil.", 'bot');
        } finally {
            isSending = false;
            userInput.disabled = false;
            sendBtn.disabled = false;
            removeTypingIndicators();
            userInput.focus();
            scrollToBottom();
        }
    }

    let msgCounter = 0;
    function appendMessage(text, sender, isRawHtml = false) {
        const rowId = 'msg-' + Date.now() + '-' + (++msgCounter);
        const rowDiv = document.createElement('div');
        rowDiv.className = isRawHtml ? `msg-row ${sender} typing-row` : `msg-row ${sender}`;
        rowDiv.id = rowId;

        let formattedText = text;
        if (!isRawHtml) {
            formattedText = formatChatMessage(text);
        }

        const timeString = getCurrentTime();
        
        if (sender === 'user') {
            const avatarHtml = window.MedigoConfig?.currentUserAvatar 
                ? `<div class="avatar-user overflow-hidden p-0 border" style="width: 36px; height: 36px; border-radius: 50%;"><img src="${window.MedigoConfig.currentUserAvatar}" class="w-100 h-100 object-fit-cover rounded-circle"></div>`
                : `<div class="avatar-user"><i class="bi bi-person-fill"></i></div>`;
            rowDiv.innerHTML = `
                ${avatarHtml}
                <div class="bubble-wrap">
                    <div class="bubble">${formattedText}</div>
                    <div class="timestamp">Anda • ${timeString}</div>
                </div>
            `;
        } else {
            rowDiv.innerHTML = `
                <div class="avatar-bot"><i class="bi bi-stars"></i></div>
                <div class="bubble-wrap">
                    <div class="bubble">${formattedText}</div>
                    ${isRawHtml ? '' : `<div class="timestamp">MediBot AI • ${timeString}</div>`}
                </div>
            `;
        }
        
        chatBox.appendChild(rowDiv);
        return rowId;
    }

    function renderFaskesCardHTML(faskes) {
        const faskesId = faskes.id || faskes.id_faskes || 0;
        const faskesName = (faskes.name || faskes.nama_faskes || 'Fasilitas Kesehatan').trim();
        let rawAddress = (faskes.address || faskes.alamat_faskes || faskes.alamat || '').trim();
        rawAddress = rawAddress.replace(/^(?:jl|jln),\s*/i, 'Jl. ');
        const cleanAddress = rawAddress;

        let resolvedType = (faskes.type || faskes.jenis_faskes || '').trim();
        let typeSlug = 'hospital';
        const nameLower = faskesName.toLowerCase();
        const low = resolvedType.toLowerCase();

        if (low.includes('puskesmas') || nameLower.includes('puskesmas') || nameLower.includes('pkm')) {
            resolvedType = 'Puskesmas'; typeSlug = 'puskesmas';
        } else if (low.includes('klinik') || nameLower.includes('klinik') || nameLower.includes('clinic')) {
            resolvedType = 'Klinik'; typeSlug = 'clinic';
        } else if (low.includes('apotik') || low.includes('apotek') || nameLower.includes('apotik')) {
            resolvedType = 'Apotek'; typeSlug = 'apotek';
        } else {
            resolvedType = 'Rumah Sakit'; typeSlug = 'hospital';
        }

        let typeIcon = 'bi-hospital';
        if (typeSlug === 'clinic') typeIcon = 'bi-heart-pulse-fill';
        if (typeSlug === 'puskesmas') typeIcon = 'bi-plus-circle-fill';

        // GENERASI URL MAPS SEARCH DINAMIS (LAT & LNG)
        let mapUrl = faskes.link_maps && faskes.link_maps !== '#' ? faskes.link_maps : '';
        if (!mapUrl) {
            const lat = faskes.latitude || '';
            const lng = faskes.longitude || '';
            const queryNameAndAddress = encodeURIComponent(`${faskesName}, ${cleanAddress}`);
            
            if (lat && lng) {
                mapUrl = `https://www.google.com/maps/search/${queryNameAndAddress}/@${lat},${lng},17z`;
            } else {
                mapUrl = `https://www.google.com/maps/search/${queryNameAndAddress}`;
            }
        }

        let detailUrl = (faskes.detail_url && faskes.detail_url !== '#') ? faskes.detail_url : `/faskes/${faskesId}`;
        if (userLat && userLng && !detailUrl.includes('lat=')) {
            const sep = detailUrl.includes('?') ? '&' : '?';
            const distParam = faskes.distance_km ? `&distance=${encodeURIComponent(parseFloat(faskes.distance_km).toFixed(1) + ' km')}` : '';
            detailUrl += `${sep}lat=${userLat}&lng=${userLng}${distParam}`;
        }
        const distanceStr = faskes.distance_km ? `${parseFloat(faskes.distance_km).toFixed(1)} km` : '-';
        const bpjsStatus = faskes._support_bpjs ? 'BPJS' : 'Non - BPJS';
        const bpjsClass = faskes._support_bpjs ? 'badge-bpjs' : 'badge-non-bpjs';
        const classBadge = faskes.kelas ? `<span class="badge-custom badge-kelas">Kelas ${faskes.kelas}</span>` : '';

        const card = document.createElement('div');
        card.className = 'facility-item-card card'; 
        card.id = `card-faskes-${faskesId}`;
        card.setAttribute('data-id', faskesId);
        
        card.innerHTML = `
            <div class="d-flex gap-3 align-items-start">
                <div class="facility-thumb-wrap flex-shrink-0 faskes-avatar-map faskes-avatar-${typeSlug}">
                    <i class="bi ${typeIcon} text-white fs-4"></i>
                </div>
                <div class="facility-info-wrap flex-grow-1 min-w-0">
                    <h6 class="facility-name fw-bold mb-1 text-dark text-truncate" title="${faskesName}">${faskesName}</h6>
                    <p class="facility-address text-muted mb-2 text-truncate" title="${cleanAddress}">${cleanAddress}</p>
                    <div class="d-flex align-items-center gap-1 mb-2 flex-wrap">
                        <span class="badge-custom badge-type-${typeSlug}" style="background-color: #f1f5f9; color: #475569;">${resolvedType}</span>
                        ${classBadge}
                        <span class="badge-custom ${bpjsClass}">${bpjsStatus}</span>
                        <span class="facility-distance ms-auto text-dark fw-bold">
                            <i class="bi bi-arrow-down-up me-1"></i>${distanceStr}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-navigasi-pill flex-grow-1 text-white text-decoration-none d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-sign-turn-right"></i> Navigasi
                        </a>
                        <a href="${detailUrl}" class="btn btn-sm btn-detail-pill flex-grow-1 text-decoration-none d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-info-circle"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        `;
        return card;
    }

    function scrollToBottom() {
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    function initSavedFaskesCards() {
        document.querySelectorAll('.faskes-cards-container[data-faskes]').forEach(container => {
            try {
                const raw = container.getAttribute('data-faskes');
                if (raw) {
                    const list = JSON.parse(raw);
                    if (Array.isArray(list)) {
                        list.forEach(item => {
                            container.appendChild(renderFaskesCardHTML(item));
                        });
                    }
                }
            } catch (err) {
                console.warn("Gagal memuat kartu faskes tersimpan:", err);
            }
            container.removeAttribute('data-faskes');
        });
    }

    // Inisialisasi kartu faskes yang tersimpan dari database pada saat halaman dimuat
    initSavedFaskesCards();

    // Scroll otomatis ke percakapan terakhir jika ada riwayat chat sebelumnya
    scrollToBottom();
});