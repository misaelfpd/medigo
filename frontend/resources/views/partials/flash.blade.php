{{-- Pesan flash dari server ditampilkan sebagai toast oleh resources/js/core/toast.js --}}
@php
    $mgFlash = [];
    foreach (['success' => 'success', 'global_success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $type) {
        if (session()->has($key) && is_string(session($key))) {
            $mgFlash[] = ['type' => $type, 'message' => session($key)];
        }
    }
    if (isset($errors) && $errors->any()) {
        $mgFlash[] = ['type' => 'error', 'message' => $errors->first()];
    }
@endphp
<div id="mgFlashMessages" data-messages="{{ json_encode($mgFlash) }}" hidden></div>
