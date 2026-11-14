{{-- Tab filter status laporan. Param: $route (nama rute index), $statusFilter (null|pending|resolved|dismissed), $tabs (opsional, label per status) --}}
@php
    $reportTabs = $tabs ?? [
        '' => 'Semua',
        'pending' => 'Perlu Tindakan',
        'resolved' => 'Sudah Ditindak',
        'dismissed' => 'Diabaikan',
    ];
@endphp
<div class="admin-report-tabs" role="tablist">
    @foreach($reportTabs as $tabValue => $tabLabel)
        <a href="{{ route($route, array_filter(['status' => $tabValue ?: null, 'search' => request('search')])) }}"
           class="admin-report-tab {{ ($statusFilter ?? null) === ($tabValue ?: null) ? 'active' : '' }}"
           role="tab">{{ $tabLabel }}</a>
    @endforeach
</div>
