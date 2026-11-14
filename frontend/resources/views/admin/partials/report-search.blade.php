{{-- Pencarian laporan. Param: $route, $statusFilter, $placeholder --}}
<form action="{{ route($route) }}" method="GET" class="admin-report-search">
    @if(!empty($statusFilter))
        <input type="hidden" name="status" value="{{ $statusFilter }}">
    @endif
    <i class="bi bi-search"></i>
    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ $placeholder ?? 'Cari laporan...' }}">
</form>
