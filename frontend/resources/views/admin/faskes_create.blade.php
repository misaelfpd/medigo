@extends('layouts.admin')

@php
    $isEdit = !empty($faskes);
    $f = $faskes ?? [];
    $val = fn ($key, $default = '') => old($key, $f[$key] ?? $default);
    $currentJenis = old('tipe', $f['jenis_faskes'] ?? 'Rumah Sakit') ?: 'Rumah Sakit';
    $currentBpjs = old('is_support_bpjs', array_key_exists('_support_bpjs', $f) ? ($f['_support_bpjs'] === null ? '' : (string) (int) $f['_support_bpjs']) : '1');
    $currentProvinsi = old('provinsi', $f['provinsi'] ?? 'Jawa Barat');
    $pageTitle = $isEdit ? 'Ubah Data Faskes' : 'Tambah Faskes Baru';
@endphp

@section('title', $pageTitle . ' - Admin MediGO')
@section('page_title', $pageTitle)

@section('content')
<div class="container-fluid px-0">

    {{-- Header Section --}}
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <a href="{{ route('admin.faskes') }}" class="text-decoration-none small fw-semibold text-medigo-primary d-inline-flex align-items-center gap-1 mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Direktori
            </a>
            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.6rem; letter-spacing: -0.02em;">{{ $isEdit ? ($f['nama_faskes'] ?? 'Ubah Data Faskes') : 'Tambah Faskes Baru' }}</h3>
            <p class="text-secondary small mb-0">
                {{ $isEdit ? 'Perbarui informasi, fasilitas, dan spesialisasi faskes ini.' : 'Lengkapi informasi di bawah ini untuk mendaftarkan fasilitas kesehatan ke dalam sistem MediGO.' }}
            </p>
        </div>
        @if($isEdit)
            <a href="{{ route('faskes.detail', $f['id_faskes']) }}" target="_blank" rel="noopener" class="btn btn-light border rounded-3 px-3 fw-semibold small">
                <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Halaman Faskes
            </a>
        @endif
    </div>

    {{-- Segmented Tab Navigation --}}
    <div class="d-inline-flex p-1 bg-white border rounded-3 mb-4 shadow-2xs custom-tabs-nav" style="border-color: #e2e8f0 !important;">
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold active" data-tab="tab-info">
            Informasi Faskes
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold" data-tab="tab-fasilitas">
            Fasilitas
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold" data-tab="tab-spesialisasi">
            Spesialisasi
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-3 border-0 fw-semibold" data-tab="tab-foto">
            Foto
        </button>
    </div>

    {{-- Unified Form --}}
    <form id="formCreateFaskes"
          action="{{ $isEdit ? route('admin.faskes.update', $f['id_faskes']) : route('admin.faskes.store') }}"
          method="POST" enctype="multipart/form-data"
          data-fasilitas='@json($allFasilitas ?? [])'
          data-spesialisasi='@json($allSpesialisasi ?? [])'
          data-selected-fasilitas='@json(old('fasilitas', $selectedFasilitas ?? []))'
          data-selected-spesialisasi='@json(old('spesialisasi', $selectedSpesialisasi ?? []))'>
        @csrf
        @if($isEdit)
            @method('PUT')
            {{-- Koordinat lama tetap dikirim agar tidak hilang saat disimpan --}}
            <input type="hidden" name="latitude" value="{{ $val('latitude') }}">
            <input type="hidden" name="longitude" value="{{ $val('longitude') }}">
        @endif
        <input type="hidden" name="is_support_bpjs" id="inputSupportBpjs" value="{{ $currentBpjs }}">
        <div id="hiddenInputsContainer"></div>

        {{-- =====================================================================
             TAB 1: INFORMASI FASKES
             ===================================================================== --}}
        <div class="tab-pane-content" id="tab-info">
            {{-- Card 1: Informasi Dasar --}}
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-4" style="font-size: 1.15rem;">Informasi Dasar</h5>

                {{-- Nama Faskes --}}
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark mb-1">
                        Nama Faskes <span class="text-danger">*</span>
                    </label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                            <i class="bi bi-hospital fs-5"></i>
                        </span>
                        <input type="text" name="nama_faskes" id="inputNamaFaskes" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: RSUD Dr. Soetomo" value="{{ $val('nama_faskes') }}" maxlength="255" required>
                    </div>
                </div>

                {{-- Row: Jenis Faskes & Kelas RS --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1" for="selectJenisFaskes">
                            Jenis Faskes <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-hospital fs-5"></i>
                            </span>
                            <select name="tipe" id="selectJenisFaskes" class="form-select border-start-0 py-2.5 rounded-end-3" required>
                                    <option value="Rumah Sakit" @selected($currentJenis === 'Rumah Sakit')>Rumah Sakit</option>
                                    <option value="Puskesmas" @selected($currentJenis === 'Puskesmas')>Puskesmas</option>
                                    <option value="Klinik Utama" @selected($currentJenis === 'Klinik Utama')>Klinik Utama</option>
                                    <option value="Klinik Pratama" @selected($currentJenis === 'Klinik Pratama')>Klinik Pratama</option>
                                    <option value="Laboratorium" @selected($currentJenis === 'Laboratorium')>Laboratorium</option>
                                    <option value="Apotek" @selected($currentJenis === 'Apotek')>Apotek</option>
                                    <option value="Optik" @selected($currentJenis === 'Optik')>Optik</option>
                                    <option value="Dokter Umum" @selected($currentJenis === 'Dokter Umum')>Dokter Umum</option>
                                    <option value="Dokter Gigi" @selected($currentJenis === 'Dokter Gigi')>Dokter Gigi</option>
                                @unless(in_array($currentJenis, ['Rumah Sakit', 'Puskesmas', 'Klinik Utama', 'Klinik Pratama', 'Laboratorium', 'Apotek', 'Optik', 'Dokter Umum', 'Dokter Gigi'], true))
                                    <option value="{{ $currentJenis }}" selected>{{ $currentJenis }}</option>
                                @endunless
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6" id="kelasWrapper">
                        <label class="form-label small fw-semibold text-dark mb-1" for="selectKelas">
                            Kelas Rumah Sakit <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-buildings fs-5"></i>
                            </span>
                            <select name="kelas" id="selectKelas" class="form-select border-start-0 py-2.5 rounded-end-3">
                                <option value="" @selected($val('kelas') === '' || $val('kelas') === null)>Pilih Kelas</option>
                                @foreach(['A', 'B', 'C', 'D'] as $kelasOpt)
                                    <option value="{{ $kelasOpt }}" @selected($val('kelas') === $kelasOpt)>Kelas {{ $kelasOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Row: Status BPJS & Nomor Telepon --}}
                <div class="row g-4 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            Mendukung BPJS?
                        </label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold {{ $currentBpjs === '1' ? 'active' : '' }}" data-val="1">
                                Menerima BPJS
                            </button>
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold {{ $currentBpjs === '0' ? 'active' : '' }}" data-val="0">
                                Tidak Menerima BPJS
                            </button>
                            <button type="button" class="btn btn-bpjs-toggle flex-fill py-2.5 fw-semibold {{ $currentBpjs === '' ? 'active' : '' }}" data-val="">
                                Tidak diketahui
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1" for="inputTelepon">
                            Nomor Telepon
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-telephone fs-5"></i>
                            </span>
                            <input type="text" name="telepon" id="inputTelepon" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="(0251) XXXXXXX" value="{{ old('telepon', $f['telepon'] ?? '') }}" maxlength="50">
                        </div>
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
                    <textarea name="alamat" id="inputAlamat" class="form-control py-2.5 rounded-3" rows="3" placeholder="Masukkan nama jalan, nomor, RT/RW..." required>{{ old('alamat', $f['alamat_faskes'] ?? '') }}</textarea>
                </div>

                {{-- Row: Kota/Kabupaten & Provinsi --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1" for="inputKota">
                            Kota / Kabupaten <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-buildings fs-5"></i>
                            </span>
                            <input type="text" name="kota_kabupaten" id="inputKota" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: Bogor" value="{{ $val('kota_kabupaten') }}" maxlength="100" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1" for="selectProvinsi">
                            Provinsi <span class="text-danger">*</span>
                        </label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                                <i class="bi bi-map fs-5"></i>
                            </span>
                            <select name="provinsi" id="selectProvinsi" class="form-select border-start-0 py-2.5 rounded-end-3" required>
                                @foreach($provincesList as $prov)
                                    <option value="{{ $prov }}" @selected($currentProvinsi === $prov)>{{ $prov }}</option>
                                @endforeach
                                @if($currentProvinsi && !in_array($currentProvinsi, $provincesList, true))
                                    <option value="{{ $currentProvinsi }}" selected>{{ $currentProvinsi }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Link Google Maps (koordinat diambil otomatis dari link) --}}
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark mb-1" for="inputLinkMaps">
                        Link Google Maps
                    </label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2 rounded-start-3">
                            <i class="bi bi-link-45deg fs-5"></i>
                        </span>
                        <input type="url" name="link_maps" id="inputLinkMaps" class="form-control border-start-0 py-2.5 rounded-end-3" placeholder="Contoh: https://maps.app.goo.gl/..." value="{{ $val('link_maps') }}">
                    </div>
                    <div class="form-text" style="font-size: .74rem;">Titik lokasi di peta diambil dari link ini.</div>
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
                        <label class="form-label small fw-semibold text-dark mb-1">Cari Fasilitas</label>
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
                            <label class="form-label small fw-semibold text-dark mb-0">Fasilitas Terpilih</label>
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
                        <label class="form-label small fw-semibold text-dark mb-1">Cari Spesialisasi</label>
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
                            <label class="form-label small fw-semibold text-dark mb-0">Spesialisasi Terpilih</label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" id="selectedSpesialisasiCountBadge">0 dipilih</span>
                        </div>
                        <div class="text-muted small mb-3" style="font-size: 0.78rem;">Daftar spesialisasi yang dimasukkan ke dalam profil faskes:</div>

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
             TAB 4: FOTO (banner, thumbnail, galeri) - dikonversi ke WebP oleh backend
             ===================================================================== --}}
        <div class="tab-pane-content d-none" id="tab-foto">
            <div class="card border rounded-4 p-4 mb-4 bg-white shadow-2xs" style="border-color: #e2e8f0 !important;">
                <h5 class="fw-bold text-dark mb-3" style="font-size: 1.15rem;">Foto Faskes</h5>
                <hr class="mt-0 mb-3 text-muted opacity-25">
                @if($isEdit)
                <div class="text-center py-4">
                    <i class="bi bi-images fs-1 text-secondary opacity-50 d-block mb-2"></i>
                    <p class="text-secondary small mb-3">Banner, thumbnail, dan galeri faskes dikelola di halaman foto agar bisa diganti satu per satu.</p>
                    <a href="{{ route('admin.faskes.photos', $f['id_faskes']) }}" class="btn mg-btn-primary rounded-3 px-4 fw-semibold">
                        <i class="bi bi-images me-1"></i> Kelola Foto Faskes
                    </a>
                </div>
                @else
                @foreach([
                    ['name' => 'banner', 'label' => 'BANNER FASKES', 'hint' => 'Foto panjang di bagian atas halaman detail (1 foto).', 'multiple' => false],
                    ['name' => 'thumbnail', 'label' => 'THUMBNAIL FASKES', 'hint' => 'Foto kecil di kartu beranda & peta (1 foto).', 'multiple' => false],
                    ['name' => 'galeri', 'label' => 'GALERI FASKES', 'hint' => 'Boleh lebih dari 1 foto (maks. 20).', 'multiple' => true],
                ] as $slot)
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary mb-2" style="font-size: .78rem; letter-spacing: .04em;" for="inputFoto-{{ $slot['name'] }}">{{ $slot['label'] }}</label>
                        <label class="mg-dropzone" for="inputFoto-{{ $slot['name'] }}" data-dropzone="{{ $slot['name'] }}">
                            <input type="file"
                                   id="inputFoto-{{ $slot['name'] }}"
                                   name="{{ $slot['multiple'] ? $slot['name'] . '[]' : $slot['name'] }}"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="visually-hidden mg-dropzone-input"
                                   {{ $slot['multiple'] ? 'multiple' : '' }}>
                            <i class="bi bi-cloud-arrow-up"></i>
                            <span>Tarik &amp; lepas foto di sini, atau <span class="mg-link">klik untuk mengunggah</span></span>
                            <small>Maksimal ukuran file 5MB (JPG, PNG, WEBP) &middot; {{ $slot['hint'] }}</small>
                        </label>
                        <div class="mg-dropzone-previews" data-previews="{{ $slot['name'] }}"></div>
                    </div>
                @endforeach
                <p class="text-muted small mb-0">Foto otomatis diubah ke format WebP agar ringan. Foto bisa diubah lagi lewat tombol <i class="bi bi-images"></i> di daftar faskes.</p>
                @endif
            </div>
        </div>

        {{-- =====================================================================
             BOTTOM ACTION BUTTONS (PERSISTENT ACROSS TABS)
             ===================================================================== --}}
        <div class="d-flex justify-content-between align-items-center gap-3 pt-2 pb-5">
            <a href="{{ route('admin.faskes') }}" class="btn mg-btn-outline rounded-3 px-5 py-2.5 fw-semibold" style="min-width: 200px;">
                Batal
            </a>
            <button type="button" id="btnSubmitForm" class="btn mg-btn-primary rounded-3 px-5 py-2.5 fw-semibold shadow-xs" style="min-width: 200px;">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
    @vite(['resources/css/pages/admin-faskes-form.css', 'resources/js/admin/faskes-form.js'])
@endpush
