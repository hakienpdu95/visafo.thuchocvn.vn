<aside class="sidebar" id="sidebar">

    <div class="brand">
        <div class="brand-logo">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="brand-logo-img">
        </div>
        {{-- brand-name ẩn: tên đã có trong logo image --}}
    </div>

    <nav class="nav-wrap">
        <div class="nav-group">
            <a href="{{ route('backend.dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>

            @can('compliance.view')
            <a href="{{ route('backend.internal-compliance.index') }}"
               class="nav-link {{ request()->routeIs('backend.internal-compliance.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
                <span class="nav-label">Hồ sơ doanh nghiệp</span>
            </a>
            @endcan

            @can('vendor.view')
            <a href="{{ route('backend.vendors.index') }}"
               class="nav-link {{ request()->routeIs('backend.vendors.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h18v4H3V3zm2 4h14v14H5V7zm3 4h8m-8 4h5"/></svg>
                <span class="nav-label">Quản lý Nhà cung cấp</span>
            </a>
            @endcan

            @can('product.view')
            <a href="{{ route('backend.partner-products.index') }}"
               class="nav-link {{ request()->routeIs('backend.partner-products.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                <span class="nav-label">Hàng hóa Nhà cung cấp</span>
            </a>
            @endcan

            @can('customer.view')
            <a href="{{ route('backend.customers.index') }}"
               class="nav-link {{ request()->routeIs('backend.customers.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="nav-label">Quản lý Khách hàng</span>
            </a>
            @endcan

            @can('contract.view')
            <a href="{{ route('backend.contracts.index') }}"
               class="nav-link {{ request()->routeIs('backend.contracts.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m3-6h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5"/></svg>
                <span class="nav-label">Quản lý Hợp đồng</span>
            </a>
            @endcan

            @can('compliance.view')
            <a href="{{ route('backend.readiness-check.index') }}"
               class="nav-link {{ request()->routeIs('backend.readiness-check.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-label">Readiness</span>
            </a>
            @endcan

            @can('customer.view')
            <a href="{{ route('backend.sales-packages.index') }}"
               class="nav-link {{ request()->routeIs('backend.sales-packages.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                <span class="nav-label">Gói chào hàng</span>
            </a>
            @endcan

            @can('goods_receipt.view')
            <a href="{{ route('backend.goods-receipts.index') }}"
               class="nav-link {{ request()->routeIs('backend.goods-receipts.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 6l1.5 12.75A2 2 0 007.49 21h9.02a2 2 0 001.99-2.25L20 6M4 6l1.2-3.6A1 1 0 016.15 2h11.7a1 1 0 01.95.4L20 6M9 10.5h6"/></svg>
                <span class="nav-label">Quản lý Mua hàng</span>
            </a>
            @endcan

            @can('sales_order.view')
            <a href="{{ route('backend.sales-orders.index') }}"
               class="nav-link {{ request()->routeIs('backend.sales-orders.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                <span class="nav-label">Quản lý Bán hàng</span>
            </a>
            @endcan

            <a href="{{ route('backend.notifications.index') }}"
               class="nav-link {{ request()->routeIs('backend.notifications.*') ? 'active' : '' }}" style="display:none;">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="nav-label">Thông báo</span>
            </a>
        </div>

        <div class="nav-group">

            @can('employee.view')
            <details {{ request()->routeIs('backend.departments.*', 'backend.employees.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.departments.*', 'backend.employees.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="nav-label">Quản lý Nhân viên</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.employees.index') }}" class="sub-link {{ request()->routeIs('backend.employees.*') ? 'active' : '' }}">Danh sách nhân viên</a>
                    <a href="{{ route('backend.departments.index') }}" class="sub-link {{ request()->routeIs('backend.departments.*') ? 'active' : '' }}">Phòng ban / Bộ phận</a>
                </div>
            </details>
            @endcan

            @can('users.view')
            <details {{ request()->routeIs('backend.users.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Tài khoản & Phân quyền</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.users.index') }}" class="sub-link {{ request()->routeIs('backend.users.index') ? 'active' : '' }}">Danh sách tài khoản</a>
                    @can('users.manage')
                    <a href="{{ route('backend.users.create') }}" class="sub-link {{ request()->routeIs('backend.users.create') ? 'active' : '' }}">Thêm tài khoản</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('activitylog.view')
            <details {{ request()->routeIs('activitylog.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('activitylog.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="nav-label">Nhật ký hoạt động</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('activitylog.index') }}"
                       class="sub-link {{ request()->routeIs('activitylog.index') ? 'active' : '' }}">
                       Danh sách log
                    </a>
                </div>
            </details>
            @endcan

        </div>
        
        <div class="nav-group">
            @can('product.view')
            <details {{ request()->routeIs('backend.products.*', 'backend.categories.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.products.*', 'backend.categories.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    <span class="nav-label">Sản phẩm & Truy xuất</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.products.index') }}" class="sub-link {{ request()->routeIs('backend.products.*') ? 'active' : '' }}">Danh mục Sản phẩm</a>
                    <a href="{{ route('backend.categories.index') }}" class="sub-link {{ request()->routeIs('backend.categories.*') ? 'active' : '' }}">Danh mục Nhóm hàng</a>
                </div>
            </details>
            @endcan
        </div>

        <div class="nav-group">
            @canany(['compliance.view', 'product.manage', 'traceability.view'])
            <details {{ request()->routeIs('backend.document-repository.*', 'backend.document-master-types.*', 'backend.traceability.*', 'backend.master-data.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.document-repository.*', 'backend.document-master-types.*', 'backend.traceability.*', 'backend.master-data.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    <span class="nav-label">Quản trị Tuân thủ</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    @can('compliance.view')
                    <a href="{{ route('backend.document-repository.index') }}" class="sub-link {{ request()->routeIs('backend.document-repository.*') ? 'active' : '' }}">Kho tài liệu & Minh chứng</a>
                    @endcan
                    @can('product.manage')
                    <a href="{{ route('backend.document-master-types.index') }}" class="sub-link {{ request()->routeIs('backend.document-master-types.*') ? 'active' : '' }}">Từ điển giấy tờ pháp lý</a>
                    @endcan
                    @can('compliance.view')
                    <a href="{{ route('backend.master-data.pesticides.index') }}" class="sub-link {{ request()->routeIs('backend.master-data.pesticides.*') ? 'active' : '' }}">Từ điển Nông nghiệp — Thuốc BVTV</a>
                    <a href="{{ route('backend.master-data.fertilizers.index') }}" class="sub-link {{ request()->routeIs('backend.master-data.fertilizers.*') ? 'active' : '' }}">Từ điển Nông nghiệp — Phân bón</a>
                    <a href="{{ route('backend.master-data.seeds.index') }}" class="sub-link {{ request()->routeIs('backend.master-data.seeds.*') ? 'active' : '' }}">Từ điển Nông nghiệp — Giống cây trồng</a>
                    @endcan
                    @can('traceability.view')
                    <a href="{{ route('backend.traceability.index') }}" class="sub-link {{ request()->routeIs('backend.traceability.*') ? 'active' : '' }}">Báo cáo Truy vết liên thông</a>
                    @endcan
                </div>
            </details>
            @endcanany
        </div>

        @can('compliance.view')
        <div class="nav-group">
            <details {{ request()->routeIs('backend.farming-sources.*', 'backend.farming-batches.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.farming-sources.*', 'backend.farming-batches.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c-4 3-6 6-6 10a6 6 0 0012 0c0-4-2-7-6-10z"/></svg>
                    <span class="nav-label">Nhật ký Sản xuất Nông hộ</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.farming-sources.index') }}" class="sub-link {{ request()->routeIs('backend.farming-sources.*') ? 'active' : '' }}">Quản lý Vùng trồng</a>
                    <a href="{{ route('backend.farming-batches.index') }}" class="sub-link {{ request()->routeIs('backend.farming-batches.*') ? 'active' : '' }}">Quản lý Vụ / Lô sản xuất</a>
                </div>
            </details>
        </div>
        @endcan

    </nav>
</aside>
