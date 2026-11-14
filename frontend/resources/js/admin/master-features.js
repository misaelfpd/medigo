/**
 * Handler modal edit master fasilitas dan spesialisasi pada Admin Dashboard
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-edit-feature').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.getAttribute('data-name');
            const action = btn.getAttribute('data-action');

            const formFasilitas = document.getElementById('formEditFasilitas');
            if (formFasilitas && action) {
                formFasilitas.setAttribute('action', action);
                const input = document.getElementById('edit_nama_fasilitas');
                if (input) input.value = name || '';
                const modalEl = document.getElementById('modalEditFasilitas');
                if (modalEl && window.bootstrap) {
                    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }

            const formSpesialisasi = document.getElementById('formEditSpesialisasi');
            if (formSpesialisasi && action) {
                formSpesialisasi.setAttribute('action', action);
                const input = document.getElementById('edit_nama_spesialisasi');
                if (input) input.value = name || '';
                const modalEl = document.getElementById('modalEditSpesialisasi');
                if (modalEl && window.bootstrap) {
                    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }
        });
    });
});
