@props(['suffix' => 'desktop'])

{{-- Mention 1 faskes di diskusi: cari dari database faskes (via backend), hasilnya disimpan di input hidden name="faskes_id" --}}
<div class="mg-mention-picker" data-mention-picker>
    <div class="mg-mention-input-wrap position-relative">
        <i class="bi bi-search"></i>
        <input type="text"
               class="form-control shadow-none mg-mention-input"
               id="mentionSearch-{{ $suffix }}"
               placeholder="Cari faskes yang dibahas (opsional)..."
               autocomplete="off"
               value="{{ old('faskes_name') }}"
               aria-label="Cari faskes yang ingin dibahas">
        <button type="button" class="mg-mention-clear {{ old('faskes_id') ? '' : 'd-none' }}" aria-label="Hapus pilihan faskes">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="mg-mention-results d-none" role="listbox"></div>
    </div>
    <div class="form-text small text-muted mt-1" style="font-size: 0.76rem;">Pilih 1 faskes dari daftar agar diskusi tampil di halaman faskes tersebut.</div>
</div>
