/**
 * Halaman buat diskusi baru: unggah foto (maks. 3, 5MB) dan mention 1 faskes.
 */
const notifyError = (message) => {
    if (typeof window.showGlobalToast === 'function') window.showGlobalToast(message, 'error');
    else window.alert(message);
};

let fileTransfer = new DataTransfer();

// 1. Sinkronisasi Kategori Topik
function selectCategory(cat) {
    document.getElementById('form_category').value = cat;

    // Update Desktop cards
    document.querySelectorAll('.category-card').forEach(card => {
        if (card.getAttribute('data-value') === cat) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // Update Mobile pills
    document.querySelectorAll('.mobile-category-pill').forEach(pill => {
        if (pill.getAttribute('data-value') === cat) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });
}

// 2. Sinkronisasi Antara Input Desktop & Form Asli
function syncMirrorInputs(el, targetName) {
    const formInput = document.querySelector(`form#discussionForm [name="${targetName}"]`);
    if (formInput) {
        formInput.value = el.value;
    }
}

// Inisialisasi saat load
document.addEventListener('DOMContentLoaded', function() {
    const initialCategory = document.getElementById('form_category').value || 'BPJS';
    selectCategory(initialCategory);

    // Jika user mengetik di form mobile, sinkronkan ke desktop
    const mainTitle = document.querySelector('form#discussionForm [name="title"]');
    const desktopTitle = document.getElementById('desktopTitleMirror');
    if (mainTitle && desktopTitle) {
        mainTitle.addEventListener('input', function() {
            desktopTitle.value = this.value;
        });
        // Initial sync
        if (desktopTitle.value) mainTitle.value = desktopTitle.value;
        else if (mainTitle.value) desktopTitle.value = mainTitle.value;
    }

    const mainContent = document.querySelector('form#discussionForm [name="content"]');
    const desktopContent = document.getElementById('desktopContentMirror');
    if (mainContent && desktopContent) {
        mainContent.addEventListener('input', function() {
            desktopContent.value = this.value;
        });
        if (desktopContent.value) mainContent.value = desktopContent.value;
        else if (mainContent.value) desktopContent.value = mainContent.value;
    }
});

// 3. Trigger File Chooser
function triggerFileInput() {
    document.getElementById('sharedImageInput').click();
}

// 4. Tangani File
function handleFileSelect(event) {
    const newFiles = Array.from(event.target.files);
    addFiles(newFiles);
}

function addFiles(files) {
    const maxFiles = 3;
    const maxSize = 5 * 1024 * 1024; // 5MB

    for (let file of files) {
        if (fileTransfer.items.length >= maxFiles) {
            if (window.showGlobalToast) {
                showGlobalToast('Maksimal 3 foto yang dapat dilampirkan.', 'warning');
            } else {
                notifyError('Maksimal 3 foto yang dapat dilampirkan.');
            }
            break;
        }

        if (file.size > maxSize) {
            if (window.showGlobalToast) {
                showGlobalToast(`Ukuran file "${file.name}" melebihi batas 5MB.`, 'warning');
            } else {
                notifyError(`Ukuran file "${file.name}" melebihi batas 5MB.`);
            }
            continue;
        }

        if (!file.type.match('image.*')) {
            if (window.showGlobalToast) {
                showGlobalToast(`File "${file.name}" bukan format gambar yang didukung.`, 'warning');
            } else {
                notifyError(`File "${file.name}" bukan format gambar yang didukung.`);
            }
            continue;
        }

        fileTransfer.items.add(file);
    }

    document.getElementById('sharedImageInput').files = fileTransfer.files;
    renderImagePreviews();
}

// 5. Hapus File dari Galeri
function removeFile(index) {
    const dt = new DataTransfer();
    const { files } = fileTransfer;

    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }

    fileTransfer = dt;
    document.getElementById('sharedImageInput').files = fileTransfer.files;
    renderImagePreviews();
}

// 6. Render Previews
function renderImagePreviews() {
    const files = Array.from(fileTransfer.files);
    const mobileContainer = document.getElementById('mobileImagePreviews');
    const desktopContainer = document.getElementById('desktopImagePreviews');

    if (files.length === 0) {
        if (mobileContainer) {
            mobileContainer.innerHTML = '';
            mobileContainer.classList.add('d-none');
        }
        if (desktopContainer) {
            desktopContainer.innerHTML = '';
            desktopContainer.classList.add('d-none');
        }
        return;
    }

    if (mobileContainer) {
        mobileContainer.innerHTML = '';
        mobileContainer.classList.remove('d-none');
    }
    if (desktopContainer) {
        desktopContainer.innerHTML = '';
        desktopContainer.classList.remove('d-none');
    }

    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewHtml = `
                <div class="image-preview-item">
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="image-preview-remove" onclick="removeFile(${index})" title="Hapus foto">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            if (mobileContainer) mobileContainer.insertAdjacentHTML('beforeend', previewHtml);
            if (desktopContainer) desktopContainer.insertAdjacentHTML('beforeend', previewHtml);
        };
        reader.readAsDataURL(file);
    });
}

// 7. Drag & Drop Desktop
const dropzone = document.getElementById('desktopDropzone');
if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        addFiles(Array.from(files));
    }, false);
}

// Mention 1 faskes: pencarian ke backend lewat /forum/mention-search
document.addEventListener('DOMContentLoaded', function () {
    const hiddenId = document.getElementById('form_faskes_id');
    const hiddenName = document.getElementById('form_faskes_name');
    const pickers = document.querySelectorAll('[data-mention-picker]');
    const mentionSearchUrl = document.getElementById('discussionForm')?.dataset.mentionSearchUrl || '/forum/mention-search';
    if (!hiddenId || !pickers.length) return;

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    function syncAll(name) {
        pickers.forEach((p) => {
            p.querySelector('.mg-mention-input').value = name || '';
            p.querySelector('.mg-mention-clear').classList.toggle('d-none', !hiddenId.value);
        });
    }

    pickers.forEach((picker) => {
        const input = picker.querySelector('.mg-mention-input');
        const results = picker.querySelector('.mg-mention-results');
        const clearBtn = picker.querySelector('.mg-mention-clear');
        let timer = null;
        let controller = null;

        function close() { results.classList.add('d-none'); results.innerHTML = ''; }

        function search(q) {
            if (controller) controller.abort();
            controller = new AbortController();
            results.classList.remove('d-none');
            results.innerHTML = '<div class="mg-mention-empty">Mencari...</div>';
            fetch(`${mentionSearchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }, signal: controller.signal })
                .then((r) => r.json())
                .then((res) => {
                    const items = Array.isArray(res.data) ? res.data : [];
                    if (!items.length) {
                        results.innerHTML = '<div class="mg-mention-empty">Faskes tidak ditemukan.</div>';
                        return;
                    }
                    results.innerHTML = items.map((f) => `
                        <button type="button" class="mg-mention-option" data-id="${esc(f.id_faskes)}" data-name="${esc(f.nama_faskes)}">
                            <span class="fw-semibold">${esc(f.nama_faskes)}</span>
                            <small>${esc(f.jenis_faskes || '')}${f.kota_kabupaten ? ' • ' + esc(f.kota_kabupaten) : ''}</small>
                        </button>`).join('');
                })
                .catch((e) => {
                    if (e.name !== 'AbortError') results.innerHTML = '<div class="mg-mention-empty">Gagal memuat daftar faskes.</div>';
                });
        }

        input.addEventListener('input', function () {
            // Mengetik ulang = pilihan lama dibatalkan sampai memilih dari daftar
            hiddenId.value = '';
            hiddenName.value = '';
            clearBtn.classList.toggle('d-none', true);
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) { close(); return; }
            timer = setTimeout(() => search(q), 300);
        });

        results.addEventListener('click', function (e) {
            const opt = e.target.closest('.mg-mention-option');
            if (!opt) return;
            hiddenId.value = opt.dataset.id;
            hiddenName.value = opt.dataset.name;
            syncAll(opt.dataset.name);
            close();
        });

        clearBtn.addEventListener('click', function () {
            hiddenId.value = '';
            hiddenName.value = '';
            syncAll('');
            input.focus();
        });

        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) close();
        });
    });

    // Teks yang diketik tapi tidak dipilih dari daftar tidak ikut dikirim
    document.getElementById('discussionForm').addEventListener('submit', function () {
        if (!hiddenId.value) hiddenName.value = '';
    });
});

// Dipanggil lewat atribut onclick/oninput/onchange di Blade dan HTML pratinjau
Object.assign(window, { selectCategory, syncMirrorInputs, triggerFileInput, handleFileSelect, removeFile });
