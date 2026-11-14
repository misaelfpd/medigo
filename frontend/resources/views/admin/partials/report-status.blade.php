{{-- Badge status laporan. Param: $status (pending|resolved|dismissed), $labels (opsional) --}}
@php
    $statusMap = [
        'pending' => ['Perlu Tindakan', 'admin-status admin-status--pending'],
        'resolved' => ['Sudah Ditindak', 'admin-status admin-status--resolved'],
        'dismissed' => ['Diabaikan', 'admin-status admin-status--dismissed'],
    ];
    foreach (($labels ?? []) as $labelKey => $labelText) {
        if (isset($statusMap[$labelKey])) $statusMap[$labelKey][0] = $labelText;
    }
    [$statusLabel, $statusClass] = $statusMap[$status ?? 'pending'] ?? $statusMap['pending'];
@endphp
<span class="{{ $statusClass }}">{{ $statusLabel }}</span>
