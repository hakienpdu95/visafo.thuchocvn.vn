<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thống kê - Báo cáo</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $subtitle }}</p>
    </div>
</div>

<div role="tablist" class="tabs tabs-border mb-4">
    <a role="tab" href="{{ route('backend.reports.volume') }}" class="tab {{ request()->routeIs('backend.reports.volume') ? 'tab-active' : '' }}">Sản lượng</a>
    <a role="tab" href="{{ route('backend.reports.picking') }}" class="tab {{ request()->routeIs('backend.reports.picking') ? 'tab-active' : '' }}">Tổng hợp nhặt hàng</a>
</div>
