@props(['suffix' => 'desktop'])

{{-- Mention 1 faskes di diskusi: cari dari database faskes (via backend), hasilnya disimpan di input hidden name="faskes_id" --}}
<div class="mg-mention-picker position-relative" data-mention-picker>
    <div class="mg-mention-input-wrap">
        <i class="bi bi-hospital"></i>
        <input type="text"
               class="form-control shadow-none mg-mention-input"
               id="mentionSearch-{{ $suffix }}"
               placeholder="Tuliskan rumah sakit yang ingin dibahas (opsional)"
               autocomplete="off"
               value="{{ old('faskes_name') }}"
               aria-label="Cari faskes yang ingin dibahas">
        <button type="button" class="mg-mention-clear {{ old('faskes_id') ? '' : 'd-none' }}" aria-label="Hapus pilihan faskes">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="mg-mention-results d-none" role="listbox"></div>
    <div class="form-text small">Pilih 1 faskes dari daftar agar diskusi tampil di halaman faskes tersebut.</div>
</div>
